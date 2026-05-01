<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phppos_inventory_movements')) {
            return;
        }

        DB::statement(
            "ALTER TABLE phppos_inventory_movements "
            . "MODIFY movement_type ENUM('receiving','return','transfer_out','transfer_in','sale') NOT NULL"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('phppos_inventory_movements')) {
            return;
        }

        DB::statement(
            "ALTER TABLE phppos_inventory_movements "
            . "MODIFY movement_type ENUM('receiving','return','transfer_out','transfer_in') NOT NULL"
        );
    }
};
