<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_employees_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('location_id');
            $table->timestamps();

            $table->primary(['employee_id', 'location_id']);
            $table->foreign('employee_id', 'emp_loc_emp_fk')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id', 'emp_loc_loc_fk')->references('location_id')->on('phppos_locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees_locations');
    }
};
