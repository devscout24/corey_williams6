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
        Schema::create('phppos_inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->timestamp('count_date')->useCurrent();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('location_id');
            $table->string('status', 255);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
        });

        Schema::create('phppos_inventory_counts_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_counts_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->decimal('count', 23, 10)->default(0);
            $table->decimal('actual_quantity', 23, 10)->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('inventory_counts_id', 'inv_counts_items_counts_fk')->references('id')->on('phppos_inventory_counts')->cascadeOnDelete();
            $table->foreign('item_id', 'inv_counts_items_items_fk')->references('item_id')->on('phppos_items');
            $table->foreign('item_variation_id', 'inv_counts_items_vars_fk')->references('id')->on('phppos_item_variations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_inventory_counts_items');
        Schema::dropIfExists('phppos_inventory_counts');
    }
};
