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
        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->tinyInteger('suspended')->default(0)->after('deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->dropColumn('suspended');
        });
    }
};
