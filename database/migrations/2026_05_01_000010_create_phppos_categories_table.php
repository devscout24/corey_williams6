<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('deleted')->default(false);
            $table->boolean('hide_from_grid')->default(false);
            $table->timestamps();

            $table->foreign('parent_id', 'cat_parent_fk')->references('id')->on('phppos_categories');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_categories');
    }
};
