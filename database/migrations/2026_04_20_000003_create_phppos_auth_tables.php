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
        Schema::create('phppos_people', function (Blueprint $table) {
            $table->id('person_id');
            $table->string('first_name')->default('');
            $table->string('last_name')->default('');
            $table->string('full_name')->nullable();
            $table->string('phone_number')->default('');
            $table->string('email')->default('')->index();
            $table->string('address_1')->default('');
            $table->string('address_2')->default('');
            $table->string('city')->default('');
            $table->string('state')->default('');
            $table->string('zip')->default('');
            $table->string('country')->default('');
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamp('create_date')->nullable();
            $table->timestamp('last_modified')->nullable();
            $table->string('title')->nullable();

            $table->index('first_name');
            $table->index('last_name');
            $table->index('phone_number');
        });

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

            $table->foreign('person_id')->references('person_id')->on('phppos_people');
            $table->index('deleted');
        });

        Schema::create('phppos_employees_reset_password', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('expire')->useCurrent();

            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->index('key');
        });

        Schema::create('phppos_modules', function (Blueprint $table) {
            $table->string('module_id', 100)->primary();
            $table->string('name_lang_key');
            $table->string('desc_lang_key');
            $table->integer('sort');
            $table->string('icon');
        });

        Schema::create('phppos_modules_actions', function (Blueprint $table) {
            $table->string('action_id', 100);
            $table->string('module_id', 100);
            $table->string('action_name_key', 100);
            $table->integer('sort');

            $table->primary(['action_id', 'module_id']);
            $table->foreign('module_id')->references('module_id')->on('phppos_modules');
        });

        Schema::create('phppos_permissions', function (Blueprint $table) {
            $table->string('module_id', 100);
            $table->unsignedBigInteger('person_id');

            $table->primary(['module_id', 'person_id']);
            $table->foreign('module_id')->references('module_id')->on('phppos_modules');
            $table->foreign('person_id')->references('person_id')->on('phppos_employees');
        });

        Schema::create('phppos_permissions_actions', function (Blueprint $table) {
            $table->string('module_id', 100);
            $table->unsignedBigInteger('person_id');
            $table->string('action_id', 100);

            $table->primary(['module_id', 'person_id', 'action_id']);
            $table->foreign('module_id')->references('module_id')->on('phppos_modules');
            $table->foreign('person_id')->references('person_id')->on('phppos_employees');
            $table->foreign(['action_id', 'module_id'])
                ->references(['action_id', 'module_id'])
                ->on('phppos_modules_actions');
        });

        // Seed a baseline admin user compatible with the legacy CI3 data model.
        DB::table('phppos_people')->insert([
            'person_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'full_name' => 'John Doe',
            'phone_number' => '555-555-5555',
            'email' => 'no-reply@example.com',
            'address_1' => 'Address 1',
        ]);

        DB::table('phppos_employees')->insert([
            'id' => 1,
            'person_id' => 1,
            'username' => 'admin',
            'password' => md5('12345678'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_actions');
        Schema::dropIfExists('phppos_permissions');
        Schema::dropIfExists('phppos_modules_actions');
        Schema::dropIfExists('phppos_modules');
        Schema::dropIfExists('phppos_employees_reset_password');
        Schema::dropIfExists('phppos_employees');
        Schema::dropIfExists('phppos_people');
    }
};
