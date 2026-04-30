<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_location_item_variations', function (Blueprint $table) {
            $table->unsignedBigInteger('item_variation_id');
            $table->unsignedBigInteger('location_id');
            $table->integer('quantity')->nullable();
            $table->decimal('reorder_level', 23, 10)->nullable();
            $table->decimal('replenish_level', 23, 10)->nullable();

            $table->primary(['item_variation_id', 'location_id']);
            $table->foreign('item_variation_id', 'loc_item_var_var_fk')
                ->references('id')
                ->on('phppos_item_variations');
            $table->foreign('location_id', 'loc_item_var_loc_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_location_item_variations');
    }
};
