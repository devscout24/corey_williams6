<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_kit_id')->nullable();
            $table->integer('line');
            $table->text('description')->nullable();
            $table->decimal('quantity_purchased', 23, 10)->default(0);
            $table->decimal('quantity_received', 23, 10)->default(0);
            $table->decimal('item_cost_price', 23, 10)->default(0);
            $table->decimal('item_unit_price', 23, 10)->default(0);
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('phppos_orders')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('phppos_items')->nullOnDelete();
            $table->foreign('item_kit_id')->references('id')->on('phppos_item_kits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_order_items');
    }
};
