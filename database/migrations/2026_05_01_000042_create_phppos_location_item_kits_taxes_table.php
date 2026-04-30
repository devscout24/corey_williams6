<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_location_item_kits_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('location_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->index(['item_kit_id', 'location_id'], 'loc_kit_taxes_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_location_item_kits_taxes');
    }
};
