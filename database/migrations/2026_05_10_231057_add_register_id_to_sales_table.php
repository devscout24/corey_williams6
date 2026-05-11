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
            if (!Schema::hasColumn('phppos_sales', 'register_id')) {
                $table->unsignedBigInteger('register_id')->nullable()->after('employee_id');
                $table->foreign('register_id', 'sales_register_fk')->references('register_id')->on('phppos_registers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->dropForeign('sales_register_fk');
            $table->dropColumn('register_id');
        });
    }
};
