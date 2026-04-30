<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('name', 255);
            $table->boolean('deleted')->default(false);
            $table->timestamp('last_modified')->useCurrent();
            $table->string('ecommerce_attribute_id', 255)->nullable();

            $table->unique(['item_id', 'name']);
            $table->foreign('item_id', 'attributes_item_fk')->references('item_id')->on('phppos_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_attributes');
    }
};
