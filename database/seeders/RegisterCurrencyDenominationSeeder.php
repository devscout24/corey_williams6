<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegisterCurrencyDenominationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $denominations = [
            ['id' => 1, 'name' => "100's", 'value' => 100.00, 'deleted' => 0],
            ['id' => 2, 'name' => "50's", 'value' => 50.00, 'deleted' => 0],
            ['id' => 3, 'name' => "20's", 'value' => 20.00, 'deleted' => 0],
            ['id' => 4, 'name' => "10's", 'value' => 10.00, 'deleted' => 0],
            ['id' => 5, 'name' => "5's", 'value' => 5.00, 'deleted' => 0],
            ['id' => 6, 'name' => "2's", 'value' => 2.00, 'deleted' => 0],
            ['id' => 7, 'name' => "1's", 'value' => 1.00, 'deleted' => 0],
            ['id' => 8, 'name' => 'Half Dollars', 'value' => 0.50, 'deleted' => 0],
            ['id' => 9, 'name' => 'Quarters', 'value' => 0.25, 'deleted' => 0],
            ['id' => 10, 'name' => 'Dimes', 'value' => 0.10, 'deleted' => 0],
            ['id' => 11, 'name' => 'Nickels', 'value' => 0.05, 'deleted' => 0],
            ['id' => 12, 'name' => 'Pennies', 'value' => 0.01, 'deleted' => 0],
        ];

        foreach ($denominations as $denom) {
            DB::table('phppos_register_currency_denominations')->updateOrInsert(
                ['id' => $denom['id']],
                [
                    'name' => $denom['name'],
                    'value' => $denom['value'],
                    'deleted' => $denom['deleted'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
