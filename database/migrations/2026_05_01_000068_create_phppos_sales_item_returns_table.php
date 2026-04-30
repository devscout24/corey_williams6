<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_sales_item_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity_returned', 15, 3);
            $table->unsignedBigInteger('employee_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'sale_item_id']);
            $table->foreign('sale_id', 'sale_returns_sale_fk')->references('sale_id')->on('phppos_sales');
            $table->foreign('sale_item_id', 'sale_returns_sale_item_fk')->references('id')->on('phppos_sales_items');
            $table->foreign('item_id', 'sale_returns_item_fk')->references('item_id')->on('phppos_items');
            $table->foreign('location_id', 'sale_returns_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('employee_id', 'sale_returns_emp_fk')->references('person_id')->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_item_returns');
    }
};
