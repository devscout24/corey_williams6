<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_item_kits', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_item_kits', 'override_default_tax')) {
                $table->boolean('override_default_tax')->default(false)->after('tax_included');
            }
        });
    }

    public function down(): void
    {
        Schema::table('phppos_item_kits', function (Blueprint $table) {
            if (Schema::hasColumn('phppos_item_kits', 'override_default_tax')) {
                $table->dropColumn('override_default_tax');
            }
        });
    }
};
