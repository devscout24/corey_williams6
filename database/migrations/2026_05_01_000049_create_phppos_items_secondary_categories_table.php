<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_items_secondary_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('category_id');
            $table->foreign('item_id', 'item_sec_cat_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('category_id', 'item_sec_cat_cat_fk')->references('id')->on('phppos_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_items_secondary_categories');
    }
};
