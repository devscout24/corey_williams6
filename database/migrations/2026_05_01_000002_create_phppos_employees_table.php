<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_employees', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->unique();
            $table->string('password');
            $table->boolean('force_password_change')->default(false);
            $table->boolean('always_require_password')->default(false);
            $table->unsignedBigInteger('person_id')->unique();
            $table->string('language')->nullable();
            $table->decimal('commission_percent', 23, 10)->default(0);
            $table->string('commission_percent_type')->default('');
            $table->decimal('hourly_pay_rate', 23, 10)->default(0);
            $table->boolean('not_required_to_clock_in')->default(false);
            $table->boolean('inactive')->default(false);
            $table->text('reason_inactive')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('employee_number')->nullable()->unique();
            $table->date('birthday')->nullable();
            $table->date('termination_date')->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('custom_field_1_value')->nullable();
            $table->string('custom_field_2_value')->nullable();
            $table->string('custom_field_3_value')->nullable();
            $table->string('custom_field_4_value')->nullable();
            $table->string('custom_field_5_value')->nullable();
            $table->string('custom_field_6_value')->nullable();
            $table->string('custom_field_7_value')->nullable();
            $table->string('custom_field_8_value')->nullable();
            $table->string('custom_field_9_value')->nullable();
            $table->string('custom_field_10_value')->nullable();
            $table->decimal('max_discount_percent', 15, 3)->nullable();
            $table->time('login_start_time')->nullable();
            $table->time('login_end_time')->nullable();
            $table->boolean('dark_mode')->default(false);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->boolean('override_price_adjustments')->default(false);
            $table->text('allowed_ip_address')->nullable();
            $table->string('secret_key_2fa')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('person_id', 'emp_person_fk')->references('person_id')->on('phppos_people');
            $table->index('deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_employees');
    }
};
