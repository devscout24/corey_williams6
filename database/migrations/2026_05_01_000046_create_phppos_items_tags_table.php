<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_items_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['item_id', 'tag_id']);
            $table->foreign('item_id', 'items_tags_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('tag_id', 'items_tags_tag_fk')->references('id')->on('phppos_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_items_tags');
    }
};
