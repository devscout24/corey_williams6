<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_modules_actions', function (Blueprint $table) {
            $table->string('action_id', 100);
            $table->string('module_id', 100);
            $table->string('action_name_key', 100);
            $table->integer('sort');
            $table->timestamps();

            $table->primary(['action_id', 'module_id']);
            $table->foreign('module_id', 'mod_act_mod_fk')->references('module_id')->on('phppos_modules');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_modules_actions');
    }
};
