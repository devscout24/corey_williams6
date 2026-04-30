<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_permissions_template_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id');
            $table->string('module_id', 100);
            $table->unsignedBigInteger('location_id');

            $table->primary(['template_id', 'module_id', 'location_id']);
            $table->index('location_id');
            $table->index('template_id');
            $table->index('module_id');
            $table->foreign('module_id', 'perm_tpl_loc_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('location_id', 'perm_tpl_loc_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
            $table->foreign('template_id', 'perm_tpl_loc_template_fk')
                ->references('id')
                ->on('phppos_permissions_templates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_template_locations');
    }
};
