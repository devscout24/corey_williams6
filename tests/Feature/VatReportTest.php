<?php

namespace Tests\Feature;

use App\Models\PhpposEmployee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VatReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the install lock exists so EnsureInstalled middleware passes
        @touch(storage_path('app/install.lock'));
    }

    public function test_vat_report_calculates_and_displays_output_and_input_taxes(): void
    {
        $now = now();

        // 1. Create default employee, location, employee_location, etc.
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
            'country' => 'Saint Vincent',
            'deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_employees_locations')->insert([
            'employee_id' => $employeeId,
            'location_id' => 1,
        ]);

        // Create category and item
        DB::table('phppos_categories')->insert([
            'id' => 1,
            'name' => 'Default Category',
            'deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_items')->insert([
            'item_id' => 1,
            'name' => 'Sample Item',
            'item_number' => 'ITEM-001',
            'category_id' => 1,
            'cost_price' => 5,
            'unit_price' => 10,
            'deleted' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create Import Supplier (country = USA, which is foreign)
        $importPersonId = DB::table('phppos_people')->insertGetId([
            'first_name' => 'Import',
            'last_name' => 'Supplier',
            'country' => 'United States',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_suppliers')->insert([
            'person_id' => $importPersonId,
            'company_name' => 'US Parts supplier',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 3. Create Domestic Supplier (country = Saint Vincent, which is local)
        $domesticPersonId = DB::table('phppos_people')->insertGetId([
            'first_name' => 'Domestic',
            'last_name' => 'Supplier',
            'country' => 'Saint Vincent',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_suppliers')->insert([
            'person_id' => $domesticPersonId,
            'company_name' => 'SVG local supplier',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4. Create Sales (Output Tax): Standard Rated
        $saleId = DB::table('phppos_sales')->insertGetId([
            'created_at' => $now,
            'employee_id' => $employeeId,
            'location_id' => 1,
            'subtotal' => 200,
            'total' => 230,
            'vat' => 30,
        ]);
        DB::table('phppos_sales_items')->insert([
            'sale_id' => $saleId,
            'item_id' => 1,
            'quantity_purchased' => 2,
            'item_unit_price' => 100,
            'line_total' => 200,
            'vat' => 30,
        ]);

        // 5. Create Receivings (Input Tax) - Import
        $recImportId = DB::table('phppos_receivings')->insertGetId([
            'receiving_time' => $now,
            'supplier_id' => $importPersonId,
            'employee_id' => $employeeId,
            'location_id' => 1,
            'subtotal' => 1000,
            'total' => 1150,
            'vat' => 150,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_receivings_items')->insert([
            'receiving_id' => $recImportId,
            'item_id' => 1,
            'line' => 1,
            'quantity_purchased' => 10,
            'item_cost_price' => 100,
            'subtotal' => 1000,
            'total' => 1000,
            'vat' => 150,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 6. Create Receivings (Input Tax) - Domestic
        $recDomesticId = DB::table('phppos_receivings')->insertGetId([
            'receiving_time' => $now,
            'supplier_id' => $domesticPersonId,
            'employee_id' => $employeeId,
            'location_id' => 1,
            'subtotal' => 500,
            'total' => 575,
            'vat' => 75,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('phppos_receivings_items')->insert([
            'receiving_id' => $recDomesticId,
            'item_id' => 1,
            'line' => 1,
            'quantity_purchased' => 5,
            'item_cost_price' => 100,
            'subtotal' => 500,
            'total' => 500,
            'vat' => 75,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Get default employee and authenticate
        $employee = PhpposEmployee::findOrFail($employeeId);
        Auth::guard('employee')->login($employee);

        // Hit the output tax VAT report route
        $response = $this->actingAs($employee, 'employee')
            ->post('/reports/generate/output_tax', [
                'report_date_range_simple' => 'TODAY',
            ]);

        $response->assertStatus(200);

        // Verify Output tax numbers are present in the response
        $response->assertSee('Standard Rated Supplies (Sales)');
        $response->assertSee('230.00'); // Sales standard total (VAT incl)
        $response->assertSee('30.00');  // Sales standard VAT amount

        // Verify Input tax numbers are present in the response
        $response->assertSee('Value of Imports (Goods & Services)', false);
        $response->assertSee('1,000.00'); // Import ex-vat total
        $response->assertSee('150.00');   // Import VAT

        $response->assertSee('Value of Domestic Purchases on which VAT was paid');
        $response->assertSee('500.00');  // Domestic ex-vat total
        $response->assertSee('75.00');   // Domestic VAT

        // Verify Net VAT Status
        $response->assertSee('Net VAT Status for Period');
        // Output VAT (30) - Input VAT (150 + 75 = 225) = -195
        $response->assertSee('195.00');
        $response->assertSee('Net VAT Refundable');
    }
}
