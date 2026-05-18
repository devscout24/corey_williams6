<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_location_items', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();

            $table->primary(['location_id', 'item_id']);
            $table->foreign('location_id', 'loc_items_loc_fk')
                ->references('location_id')
                ->on('phppos_locations');
            $table->foreign('item_id', 'loc_items_item_fk')
                ->references('item_id')
                ->on('phppos_items');
            $table->foreign('tax_class_id', 'loc_items_tax_class_fk')
                ->references('id')
                ->on('phppos_tax_classes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_location_items');
    }
};
