<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_sales_items_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('item_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('sale_id', 'sales_items_taxes_sale_fk')
                ->references('sale_id')
                ->on('phppos_sales')
                ->cascadeOnDelete();
            $table->foreign('sale_item_id', 'sales_items_taxes_item_fk')
                ->references('id')
                ->on('phppos_sales_items')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'sales_items_taxes_item_id_fk')
                ->references('item_id')
                ->on('phppos_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_items_taxes');
    }
};
