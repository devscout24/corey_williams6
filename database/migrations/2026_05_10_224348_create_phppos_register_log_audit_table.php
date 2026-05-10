<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_register_log_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('register_log_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('date')->useCurrent();
            $table->decimal('amount', 23, 10)->default(0);
            $table->text('note')->nullable();
            $table->string('payment_type');
            $table->timestamps();

            $table->foreign('register_log_id')->references('register_log_id')->on('phppos_register_log');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_register_log_audit');
    }
};
