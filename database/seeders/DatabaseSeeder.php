<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PosCoreSeeder::class);

        if ((bool) env('POS_SEED_DEMO', false)) {
            $this->call(PosDemoSeeder::class);
        }
        $this->call(RegisterCurrencyDenominationSeeder::class);
    }
}
