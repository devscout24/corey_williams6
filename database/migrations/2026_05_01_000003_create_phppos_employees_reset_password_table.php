<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_employees_reset_password', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('expire')->useCurrent();

            $table->foreign('employee_id', 'emp_reset_emp_fk')->references('person_id')->on('phppos_employees');
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees_reset_password');
    }
};
