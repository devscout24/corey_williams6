<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_location_item_kits', function (Blueprint $table) {
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->decimal('cost_price', 23, 10)->nullable();
            $table->boolean('override_default_tax')->default(false);
            $table->unsignedBigInteger('tax_class_id')->nullable();

            $table->primary(['item_kit_id', 'location_id']);
            $table->foreign('item_kit_id', 'loc_item_kits_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('location_id', 'loc_item_kits_loc_fk')
                ->references('location_id')
                ->on('phppos_locations')
                ->cascadeOnDelete();
            $table->foreign('tax_class_id', 'loc_item_kits_tax_class_fk')
                ->references('id')
                ->on('phppos_tax_classes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_location_item_kits');
    }
};
