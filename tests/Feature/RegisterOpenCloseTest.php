<?php

namespace Tests\Feature;

use App\Models\PhpposEmployee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegisterOpenCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @touch(storage_path('app/install.lock'));
    }

    public function test_register_status_flow(): void
    {
        $now = now();

        // 1. Create employee, location, employee_location, and register
        $employeeId = DB::table('phppos_people')->insertGetId([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'full_name' => 'John Doe',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_employees')->insert([
            'person_id' => $employeeId,
            'username' => 'admin',
            'password' => 'secret',
            'inactive' => 0,
            'deleted' => 0,
        ]);
        DB::table('phppos_locations')->insert([
            'location_id' => 1,
            'name' => 'Main Store',
            'deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_employees_locations')->insert([
            'employee_id' => $employeeId,
            'location_id' => 1,
        ]);
        DB::table('phppos_registers')->insert([
            'register_id' => 1,
            'location_id' => 1,
            'name' => 'Main Register',
            'deleted' => 0,
        ]);
        DB::table('phppos_registers')->insert([
            'register_id' => 2,
            'location_id' => 1,
            'name' => 'Second Register',
            'deleted' => 0,
        ]);

        // Grant the employee access to the sales module (modules FK must exist)
        DB::table('phppos_modules')->insertOrIgnore([
            'module_id' => 'sales',
            'name_lang_key' => 'module_sales',
            'desc_lang_key' => 'module_sales_desc',
            'sort' => 50,
            'icon' => 'ti-shopping-cart',
        ]);
        DB::table('phppos_permissions')->insert([
            'module_id' => 'sales',
            'person_id' => $employeeId,
        ]);

        $employee = PhpposEmployee::findOrFail($employeeId);
        Auth::guard('employee')->login($employee);

        // 2. Accessing /sales while register is closed -> should redirect to /sales/register/open
        $response = $this->actingAs($employee, 'employee')
            ->get('/sales');

        $response->assertRedirect(route('sales.register.open'));

        // 3. Accessing /sales/register/open should return the open form view
        $response = $this->actingAs($employee, 'employee')
            ->get('/sales/register/open');

        $response->assertStatus(200);
        $response->assertSee('Open Register');
        $response->assertSee('Main Register');

        // 4. Open the register with $150.00 cash float
        $response = $this->actingAs($employee, 'employee')
            ->post('/sales/register/open', [
                'opening_amount' => 150.00,
                'notes' => 'Shift starting now',
            ]);

        $response->assertRedirect(route('sales.index'));
        
        // Assert register_log and register_log_payments records were created
        $this->assertDatabaseHas('phppos_register_log', [
            'register_id' => 1,
            'employee_id_open' => $employeeId,
            'shift_end' => null,
        ]);
        $this->assertDatabaseHas('phppos_register_log_payments', [
            'payment_type' => 'Cash',
            'open_amount' => 150.00,
        ]);

        // 5. Accessing /sales now should load the index successfully
        $response = $this->actingAs($employee, 'employee')
            ->get('/sales');
        $response->assertStatus(200);
        $response->assertSee('Register: Main Register');
        $response->assertSee('Close Register');

        // 6. Switch registers to register 2 (which is closed)
        $response = $this->actingAs($employee, 'employee')
            ->post('/sales/register/change', [
                'register_id' => 2,
            ]);

        // Accessing /sales should now redirect to /sales/register/open for register 2
        $response = $this->actingAs($employee, 'employee')
            ->get('/sales');
        $response->assertRedirect(route('sales.register.open'));

        // 7. Open register 2
        $response = $this->actingAs($employee, 'employee')
            ->post('/sales/register/open', [
                'opening_amount' => 100.00,
                'notes' => 'Register 2 opening',
            ]);
        $response->assertRedirect(route('sales.index'));

        // 8. View Close register page
        $response = $this->actingAs($employee, 'employee')
            ->get('/sales/register/close');
        $response->assertStatus(200);
        $response->assertSee('Close Register');
        $response->assertSee('100.00'); // Expected cash

        // 9. Close the register
        $response = $this->actingAs($employee, 'employee')
            ->post('/sales/register/close', [
                'closed_payments' => [
                    'Cash' => ['actual' => 100.00],
                ],
                'notes' => 'Ending shift',
            ]);

        $response->assertRedirect(route('modules.index'));

        // Verify shift ended in database
        $this->assertDatabaseMissing('phppos_register_log', [
            'register_id' => 2,
            'shift_end' => null,
        ]);
    }
}
