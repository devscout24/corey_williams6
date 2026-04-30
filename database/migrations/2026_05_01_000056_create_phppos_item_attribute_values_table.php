<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('attribute_value_id');

            $table->primary(['attribute_value_id', 'item_id']);
            $table->foreign('item_id', 'item_attr_values_item_fk')->references('item_id')->on('phppos_items');
            $table->foreign('attribute_value_id', 'item_attr_values_value_fk')
                ->references('id')
                ->on('phppos_attribute_values')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_attribute_values');
    }
};
