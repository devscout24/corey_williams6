<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees_time_clock');
    }
};
