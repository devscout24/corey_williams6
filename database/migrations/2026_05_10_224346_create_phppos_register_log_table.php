<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_register_log', function (Blueprint $table) {
            $table->id('register_log_id');
            $table->unsignedBigInteger('employee_id_open');
            $table->unsignedBigInteger('employee_id_close')->nullable();
            $table->unsignedBigInteger('register_id')->nullable();
            $table->timestamp('shift_start')->useCurrent();
            $table->timestamp('shift_end')->nullable();
            $table->text('notes')->nullable();
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('employee_id_open')->references('person_id')->on('phppos_employees');
            $table->foreign('employee_id_close')->references('person_id')->on('phppos_employees');
            $table->foreign('register_id')->references('register_id')->on('phppos_registers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_register_log');
    }
};
