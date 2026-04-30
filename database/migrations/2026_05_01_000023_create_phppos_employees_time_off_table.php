<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees_time_off');
    }
};
