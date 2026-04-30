<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phppos_app_config', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->text('value');
        });

        Schema::create('phppos_app_files', function (Blueprint $table) {
            $table->id('file_id');
            $table->string('file_name', 255);
            $table->binary('file_data');
            $table->timestamp('timestamp')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('expires')->nullable();

            $table->index('expires');
            $table->index('file_name');
            $table->index('timestamp');
            $table->index(['file_name', 'timestamp'], 'filename_timestamp');
        });

        Schema::create('phppos_people_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->unsignedBigInteger('person_id');

            $table->foreign('file_id', 'people_files_file_fk')
                ->references('file_id')
                ->on('phppos_app_files');
            $table->index('file_id');
            $table->index('person_id');
        });

        Schema::create('phppos_employees_app_config', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->string('key', 255);
            $table->text('value');

            $table->primary(['employee_id', 'key']);
            $table->foreign('employee_id', 'emp_app_cfg_emp_fk')
                ->references('person_id')
                ->on('phppos_employees');
        });

        Schema::create('phppos_registers', function (Blueprint $table) {
            $table->id('register_id');
            $table->unsignedBigInteger('location_id');
            $table->string('name', 255);
            $table->string('iptran_device_id', 255)->nullable();
            $table->string('emv_terminal_id', 255)->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('card_connect_hsn', 255)->nullable();
            $table->string('emv_pinpad_ip', 255)->nullable();
            $table->string('emv_pinpad_port', 255)->nullable();
            $table->boolean('enable_tips')->default(false);

            $table->index('deleted');
            $table->index('location_id');
            $table->foreign('location_id', 'registers_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });

        Schema::create('phppos_employee_registers', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('register_id');

            $table->primary(['employee_id', 'register_id']);
            $table->index('register_id');
            $table->foreign('employee_id', 'emp_registers_emp_fk')
                ->references('person_id')
                ->on('phppos_employees');
            $table->foreign('register_id', 'emp_registers_reg_fk')
                ->references('register_id')
                ->on('phppos_registers');
        });

        Schema::create('phppos_employees_time_clock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('location_id');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->text('clock_in_comment');
            $table->text('clock_out_comment');
            $table->decimal('hourly_pay_rate', 23, 10)->default(0);
            $table->string('ip_address_clock_in', 255);
            $table->string('ip_address_clock_out', 255);

            $table->index('employee_id');
            $table->index('location_id');
            $table->foreign('employee_id', 'emp_time_clock_emp_fk')
                ->references('person_id')
                ->on('phppos_employees');
            $table->foreign('location_id', 'emp_time_clock_loc_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });

        Schema::create('phppos_employees_time_off', function (Blueprint $table) {
            $table->id();
            $table->boolean('approved')->default(false);
            $table->date('start_day')->nullable();
            $table->date('end_day')->nullable();
            $table->decimal('hours_requested', 23, 10)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->string('reason', 255)->nullable();
            $table->unsignedBigInteger('employee_requested_person_id')->nullable();
            $table->unsignedBigInteger('employee_requested_location_id')->nullable();
            $table->unsignedBigInteger('employee_approved_person_id')->nullable();
            $table->boolean('deleted')->default(false);

            $table->index('employee_requested_person_id');
            $table->index('employee_approved_person_id');
            $table->index('employee_requested_location_id');
            $table->foreign('employee_requested_person_id', 'emp_timeoff_req_fk')
                ->references('person_id')
                ->on('phppos_people');
            $table->foreign('employee_approved_person_id', 'emp_timeoff_appr_fk')
                ->references('person_id')
                ->on('phppos_people');
            $table->foreign('employee_requested_location_id', 'emp_timeoff_loc_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });

        Schema::create('phppos_permissions_locations', function (Blueprint $table) {
            $table->string('module_id', 100);
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('location_id');

            $table->primary(['module_id', 'person_id', 'location_id']);
            $table->index('person_id');
            $table->index('location_id');
            $table->foreign('module_id', 'perm_loc_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('person_id', 'perm_loc_person_fk')
                ->references('person_id')
                ->on('phppos_employees');
            $table->foreign('location_id', 'perm_loc_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });

        Schema::create('phppos_permissions_actions_locations', function (Blueprint $table) {
            $table->string('module_id', 100);
            $table->unsignedBigInteger('person_id');
            $table->string('action_id', 100);
            $table->unsignedBigInteger('location_id');

            $table->primary(['module_id', 'person_id', 'action_id', 'location_id']);
            $table->index('person_id');
            $table->index('action_id');
            $table->index('location_id');
            $table->foreign('module_id', 'perm_act_loc_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('person_id', 'perm_act_loc_person_fk')
                ->references('person_id')
                ->on('phppos_employees');
            $table->foreign('location_id', 'perm_act_loc_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
            $table->foreign(['action_id', 'module_id'], 'perm_act_loc_action_fk')
                ->references(['action_id', 'module_id'])
                ->on('phppos_modules_actions');
        });

        Schema::create('phppos_permissions_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->boolean('deleted')->default(false);

            $table->index('deleted');
            $table->index('name');
            $table->index(['name', 'deleted'], 'name_deleted');
        });

        Schema::create('phppos_permissions_template', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id');
            $table->string('module_id', 100);

            $table->primary(['template_id', 'module_id']);
            $table->index('module_id');
            $table->foreign('module_id', 'perm_tpl_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('template_id', 'perm_tpl_template_fk')
                ->references('id')
                ->on('phppos_permissions_templates');
        });

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

        Schema::create('phppos_permissions_template_actions_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id');
            $table->string('module_id', 100);
            $table->string('action_id', 100);
            $table->unsignedBigInteger('location_id');

            $table->primary(['template_id', 'module_id', 'action_id', 'location_id']);
            $table->index('action_id');
            $table->index('location_id');
            $table->index('template_id');
            $table->index('module_id');
            $table->foreign('module_id', 'perm_tpl_actloc_module_fk')
                ->references('module_id')
                ->on('phppos_modules');
            $table->foreign('location_id', 'perm_tpl_actloc_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
            $table->foreign('template_id', 'perm_tpl_actloc_template_fk')
                ->references('id')
                ->on('phppos_permissions_templates');
            $table->foreign(['action_id', 'module_id'], 'perm_tpl_actloc_action_fk')
                ->references(['action_id', 'module_id'])
                ->on('phppos_modules_actions');
        });

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

        DB::table('phppos_registers')->insert([
            'register_id' => 1,
            'location_id' => 1,
            'name' => 'Default',
            'deleted' => 0,
            'enable_tips' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_template_locations');
        Schema::dropIfExists('phppos_permissions_template_actions_locations');
        Schema::dropIfExists('phppos_permissions_template_actions');
        Schema::dropIfExists('phppos_permissions_template');
        Schema::dropIfExists('phppos_permissions_templates');
        Schema::dropIfExists('phppos_permissions_actions_locations');
        Schema::dropIfExists('phppos_permissions_locations');
        Schema::dropIfExists('phppos_employees_time_off');
        Schema::dropIfExists('phppos_employees_time_clock');
        Schema::dropIfExists('phppos_employee_registers');
        Schema::dropIfExists('phppos_registers');
        Schema::dropIfExists('phppos_employees_app_config');
        Schema::dropIfExists('phppos_people_files');
        Schema::dropIfExists('phppos_app_files');
        Schema::dropIfExists('phppos_app_config');
    }
};
