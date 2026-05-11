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
        Schema::table('phppos_sales_items', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->string('serialnumber')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_sales_items', function (Blueprint $table) {
            $table->dropColumn(['description', 'serialnumber']);
        });
    }
};
