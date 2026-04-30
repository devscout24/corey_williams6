<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kits_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->primary(['item_kit_id', 'tag_id']);
            $table->foreign('item_kit_id', 'kit_tags_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('tag_id', 'kit_tags_tag_fk')
                ->references('id')
                ->on('phppos_tags')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_tags');
    }
};
