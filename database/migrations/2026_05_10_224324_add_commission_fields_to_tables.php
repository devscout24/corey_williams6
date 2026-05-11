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
            if (!Schema::hasColumn('phppos_sales', 'sold_by_employee_id')) {
                $table->unsignedBigInteger('sold_by_employee_id')->nullable()->after('employee_id');
                $table->foreign('sold_by_employee_id')->references('person_id')->on('phppos_employees');
            }
        });

        Schema::table('phppos_sales_items', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_sales_items', 'commission')) {
                $table->decimal('commission', 23, 10)->default(0)->after('line_total');
            }
        });

        Schema::table('phppos_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('phppos_employees', 'commission_fixed')) {
                $table->decimal('commission_fixed', 23, 10)->nullable()->after('commission_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_employees', function (Blueprint $table) {
            $table->dropColumn(['commission_fixed']);
        });

        Schema::table('phppos_sales_items', function (Blueprint $table) {
            $table->dropColumn('commission');
        });

        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->dropForeign(['sold_by_employee_id']);
            $table->dropColumn('sold_by_employee_id');
        });
    }
};
