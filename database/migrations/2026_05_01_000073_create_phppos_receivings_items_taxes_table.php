<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_receivings_items_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('line');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('receiving_id', 'receivings_taxes_receiving_fk')
                ->references('receiving_id')
                ->on('phppos_receivings')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'receivings_taxes_item_fk')->references('item_id')->on('phppos_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_receivings_items_taxes');
    }
};
