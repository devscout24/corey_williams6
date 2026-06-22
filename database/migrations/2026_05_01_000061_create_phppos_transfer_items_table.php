<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('item_kit_id')->nullable();
            $table->string('item_kit_name')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->foreign('transfer_id', 'transfer_items_transfer_fk')->references('id')->on('phppos_transfers');
            $table->foreign('item_id', 'transfer_items_item_fk')->references('item_id')->on('phppos_items')->nullOnDelete();
            $table->foreign('item_kit_id', 'transfer_items_kit_fk')->references('id')->on('phppos_item_kits')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_transfer_items');
    }
};
