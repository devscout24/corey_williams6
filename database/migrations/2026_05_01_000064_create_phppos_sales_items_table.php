<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->decimal('rule_discount', 23, 10)->nullable();
            $table->boolean('is_bogo')->default(false);
            $table->decimal('quantity_purchased', 15, 3);
            $table->decimal('item_unit_price', 23, 10);
            $table->decimal('line_total', 23, 10);
            $table->timestamps();

            $table->foreign('sale_id', 'sales_items_sale_fk')->references('sale_id')->on('phppos_sales');
            $table->foreign('item_id', 'sales_items_item_fk')->references('item_id')->on('phppos_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_items');
    }
};
