<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->decimal('quantity', 23, 10)->default(1);
            $table->timestamps();

            $table->index('item_kit_id', 'kit_items_kit_idx');
            $table->index('item_id', 'kit_items_item_idx');
            $table->foreign('item_kit_id', 'kit_items_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'kit_items_item_fk')
                ->references('item_id')
                ->on('phppos_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kit_items');
    }
};
