<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phppos_items', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_items', 'replenish_level')) {
                $table->decimal('replenish_level', 23, 10)->nullable()->after('reorder_level');
            }
        });

        Schema::table('phppos_location_items', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_location_items', 'reorder_level')) {
                $table->decimal('reorder_level', 23, 10)->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('phppos_location_items', 'replenish_level')) {
                $table->decimal('replenish_level', 23, 10)->nullable()->after('reorder_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_items', function (Blueprint $table) {
            $table->dropColumn(['reorder_level', 'replenish_level']);
        });

        Schema::table('phppos_location_items', function (Blueprint $table) {
            $table->dropColumn(['reorder_level', 'replenish_level']);
        });
    }
};
