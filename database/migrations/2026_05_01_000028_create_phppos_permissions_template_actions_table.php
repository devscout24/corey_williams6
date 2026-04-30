<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_permissions_template_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id');
            $table->string('module_id', 100);
            $table->string('action_id', 100);

            $table->primary(['template_id', 'module_id', 'action_id']);
            $table->index('action_id');
            $table->index('template_id');
            $table->index('module_id');
            $table->foreign('module_id', 'perm_tpl_act_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('template_id', 'perm_tpl_act_template_fk')
                ->references('id')
                ->on('phppos_permissions_templates');
            $table->foreign(['action_id', 'module_id'], 'perm_tpl_act_action_fk')
                ->references(['action_id', 'module_id'])
                ->on('phppos_modules_actions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_template_actions');
    }
};
