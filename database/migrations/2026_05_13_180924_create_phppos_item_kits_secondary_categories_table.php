<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phppos_item_kits_secondary_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('item_kit_id', 'ik_sec_cat_kit_fk')->references('id')->on('phppos_item_kits')->cascadeOnDelete();
            $table->foreign('category_id', 'ik_sec_cat_cat_fk')->references('id')->on('phppos_categories')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_secondary_categories');
    }
};
