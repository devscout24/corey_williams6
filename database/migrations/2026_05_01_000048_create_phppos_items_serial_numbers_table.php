<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_items_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('serial_number', 255)->unique();
            $table->decimal('cost_price', 23, 10)->nullable();
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->timestamps();

            $table->foreign('item_id', 'serial_numbers_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_items_serial_numbers');
    }
};
