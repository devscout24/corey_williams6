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
        Schema::create('phppos_inventory', function (Blueprint $table) {
            $table->id('trans_id');
            $table->unsignedBigInteger('trans_items');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->unsignedBigInteger('trans_user');
            $table->timestamp('trans_date')->useCurrent();
            $table->text('trans_comment');
            $table->decimal('trans_inventory', 23, 10)->default(0);
            $table->unsignedBigInteger('location_id');
            $table->decimal('trans_current_quantity', 23, 10)->nullable();
            $table->timestamps();

            $table->foreign('trans_items')->references('item_id')->on('phppos_items');
            $table->foreign('item_variation_id')->references('id')->on('phppos_item_variations');
            $table->foreign('trans_user')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            
            $table->index(['trans_items', 'location_id', 'trans_date']);
            $table->index(['item_variation_id', 'location_id', 'trans_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_inventory');
    }
};
