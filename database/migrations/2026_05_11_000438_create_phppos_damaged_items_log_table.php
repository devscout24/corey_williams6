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
        Schema::create('phppos_damaged_items_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('damaged_date')->useCurrent();
            $table->decimal('damaged_qty', 23, 10)->default(0);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->string('damaged_reason', 255)->nullable();
            $table->string('damaged_reason_comment', 255)->nullable();
            $table->timestamps();

            $table->foreign('item_id', 'damaged_items_items_fk')->references('item_id')->on('phppos_items');
            $table->foreign('item_variation_id', 'damaged_items_vars_fk')->references('id')->on('phppos_item_variations');
            $table->foreign('sale_id', 'damaged_items_sales_fk')->references('sale_id')->on('phppos_sales');
            $table->foreign('location_id', 'damaged_items_locs_fk')->references('location_id')->on('phppos_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_damaged_items_log');
    }
};
