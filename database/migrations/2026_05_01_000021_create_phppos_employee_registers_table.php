<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employee_registers');
    }
};
