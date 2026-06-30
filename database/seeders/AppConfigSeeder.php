<?php

namespace Database\Seeders;

use App\Models\PhpposAppConfig;
use Illuminate\Database\Seeder;

class AppConfigSeeder extends Seeder
{
    public function run(): void
    {
        PhpposAppConfig::updateOrCreate(
            ['key' => 'number_of_decimals'],
            ['value' => '5'],
        );
    }
}
