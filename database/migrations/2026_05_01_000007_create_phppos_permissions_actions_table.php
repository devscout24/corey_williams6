<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_permissions_actions', function (Blueprint $table) {
            $table->string('module_id', 100);
            $table->unsignedBigInteger('person_id');
            $table->string('action_id', 100);

            $table->primary(['module_id', 'person_id', 'action_id']);
            $table->foreign('module_id', 'perm_act_mod_fk')->references('module_id')->on('phppos_modules');
            $table->foreign('person_id', 'perm_act_person_fk')->references('person_id')->on('phppos_employees');
            $table->foreign(['action_id', 'module_id'], 'perm_act_action_fk')
                ->references(['action_id', 'module_id'])
                ->on('phppos_modules_actions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_actions');
    }
};
