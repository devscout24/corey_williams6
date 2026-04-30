<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_manufacturers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->boolean('deleted')->default(false);
            $table->string('name', 255);
            $table->timestamps();

            $table->index('deleted');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_manufacturers');
    }
};
