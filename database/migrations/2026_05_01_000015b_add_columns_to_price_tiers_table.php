<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_price_tiers', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_price_tiers', 'default_percent_off')) {
                $table->decimal('default_percent_off', 15, 4)->nullable()->after('name');
            }
            if (!Schema::hasColumn('phppos_price_tiers', 'default_cost_plus_percent')) {
                $table->decimal('default_cost_plus_percent', 15, 4)->nullable()->after('default_percent_off');
            }
            if (!Schema::hasColumn('phppos_price_tiers', 'default_cost_plus_fixed_amount')) {
                $table->decimal('default_cost_plus_fixed_amount', 15, 4)->nullable()->after('default_cost_plus_percent');
            }
            if (!Schema::hasColumn('phppos_price_tiers', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('default_cost_plus_fixed_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('phppos_price_tiers', function (Blueprint $table) {
            $table->dropColumn(['default_percent_off', 'default_cost_plus_percent', 'default_cost_plus_fixed_amount', 'sort_order']);
        });
    }
};
