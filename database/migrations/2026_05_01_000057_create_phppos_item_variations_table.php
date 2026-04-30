<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_variations', function (Blueprint $table) {
            $table->id();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('item_id');
            $table->decimal('reorder_level', 23, 10)->nullable();
            $table->decimal('replenish_level', 23, 10)->nullable();
            $table->string('name', 255)->default('');
            $table->string('item_number', 255)->nullable();
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->decimal('cost_price', 23, 10)->nullable();
            $table->decimal('promo_price', 23, 10)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('last_modified')->useCurrent();

            $table->unique('item_number');
            $table->foreign('item_id', 'item_variations_item_fk')->references('item_id')->on('phppos_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_variations');
    }
};
