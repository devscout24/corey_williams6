<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_modules', function (Blueprint $table) {
            $table->string('module_id', 100)->primary();
            $table->string('name_lang_key');
            $table->string('desc_lang_key');
            $table->integer('sort');
            $table->string('icon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_modules');
    }
};
