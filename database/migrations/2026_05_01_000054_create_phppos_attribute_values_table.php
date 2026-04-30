<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->string('name', 255);
            $table->boolean('deleted')->default(false);
            $table->timestamp('last_modified')->useCurrent();
            $table->string('ecommerce_attribute_term_id', 255)->nullable();

            $table->unique(['name', 'attribute_id'], 'name_attribute_id');
            $table->foreign('attribute_id', 'attribute_values_attr_fk')
                ->references('id')
                ->on('phppos_attributes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_attribute_values');
    }
};
