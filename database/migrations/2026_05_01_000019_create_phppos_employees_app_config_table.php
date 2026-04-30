<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_employees_app_config', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->string('key', 255);
            $table->text('value');

            $table->primary(['employee_id', 'key']);
            $table->foreign('employee_id', 'emp_app_cfg_emp_fk')->references('person_id')->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees_app_config');
    }
};
