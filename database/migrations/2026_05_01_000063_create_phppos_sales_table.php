<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->decimal('rule_discount', 23, 10)->nullable();
            $table->string('sale_type', 30)->default('sale');
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->decimal('amount_tendered', 23, 10)->default(0);
            $table->decimal('change_due', 23, 10)->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('location_id', 'sales_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('employee_id', 'sales_emp_fk')->references('person_id')->on('phppos_employees');
            $table->foreign('customer_id', 'sales_cust_fk')->references('person_id')->on('phppos_customers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_sales');
    }
};
