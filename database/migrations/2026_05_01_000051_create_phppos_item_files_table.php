<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('file_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('file_id');
            $table->foreign('item_id', 'item_files_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('file_id', 'item_files_file_fk')->references('file_id')->on('phppos_app_files')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_files');
    }
};
