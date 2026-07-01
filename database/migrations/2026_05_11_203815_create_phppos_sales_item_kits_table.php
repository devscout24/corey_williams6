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
        Schema::create('phppos_sales_item_kits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('item_kit_id');
            $table->decimal('quantity_purchased', 15, 3)->default(0);
            $table->decimal('item_kit_unit_price', 23, 10)->default(0);
            $table->decimal('discount_percent', 15, 3)->default(0);
            $table->decimal('line_total', 23, 10)->default(0);
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->decimal('tax', 23, 10)->default(0);
            $table->decimal('profit', 23, 10)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_item_kits');
    }
};
