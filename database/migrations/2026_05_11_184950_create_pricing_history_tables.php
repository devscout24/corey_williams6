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
        Schema::create('phppos_items_pricing_history', function (Blueprint $table) {
            $table->id();
            $table->timestamp('on_date')->useCurrent();
            $table->integer('employee_id');
            $table->integer('item_id');
            $table->integer('item_variation_id')->nullable();
            $table->integer('location_id')->nullable();
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->decimal('cost_price', 23, 10)->nullable();
        });

        Schema::create('phppos_item_kits_pricing_history', function (Blueprint $table) {
            $table->id();
            $table->timestamp('on_date')->useCurrent();
            $table->integer('employee_id');
            $table->integer('item_kit_id');
            $table->integer('location_id')->nullable();
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->decimal('cost_price', 23, 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_pricing_history');
        Schema::dropIfExists('phppos_items_pricing_history');
    }
};
