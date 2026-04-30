<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_variation_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('attribute_value_id');
            $table->unsignedBigInteger('item_variation_id');

            $table->primary(['attribute_value_id', 'item_variation_id']);
            $table->foreign('attribute_value_id', 'item_var_attr_val_fk')
                ->references('id')
                ->on('phppos_attribute_values')
                ->cascadeOnDelete();
            $table->foreign('item_variation_id', 'item_var_attr_var_fk')
                ->references('id')
                ->on('phppos_item_variations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_variation_attribute_values');
    }
};
