<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_additional_item_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('item_number', 255)->unique();
            $table->timestamps();

            $table->foreign('item_id', 'add_item_numbers_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_additional_item_numbers');
    }
};
