<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('item_id');

            $table->primary(['attribute_id', 'item_id']);
            $table->foreign('item_id', 'item_attributes_item_fk')->references('item_id')->on('phppos_items');
            $table->foreign('attribute_id', 'item_attributes_attr_fk')
                ->references('id')
                ->on('phppos_attributes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_attributes');
    }
};
