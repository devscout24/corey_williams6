<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_module_submodules', function (Blueprint $table) {
            $table->id();
            $table->string('module_id', 100);
            $table->string('submodule_key', 100);
            $table->string('label', 120);
            $table->integer('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'submodule_key']);
            $table->foreign('module_id', 'submodules_module_fk')->references('module_id')->on('phppos_modules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_module_submodules');
    }
};
