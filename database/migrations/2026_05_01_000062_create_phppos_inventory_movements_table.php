<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('movement_type', ['receiving', 'return', 'transfer_out', 'transfer_in']);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('created_by_person_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('item_id', 'inventory_item_fk')->references('item_id')->on('phppos_items');
            $table->foreign('from_location_id', 'inventory_from_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('to_location_id', 'inventory_to_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('created_by_person_id', 'inventory_created_by_fk')->references('person_id')->on('phppos_employees');
            $table->index(['movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_inventory_movements');
    }
};
