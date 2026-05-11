<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phppos_expenses_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('phppos_expenses_categories');
        });

        Schema::create('phppos_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('expense_type');
            $table->text('expense_description')->nullable();
            $table->string('expense_reason')->nullable();
            $table->timestamp('expense_date')->useCurrent();
            $table->decimal('expense_amount', 23, 10);
            $table->decimal('expense_tax', 23, 10)->default(0);
            $table->text('expense_note')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('approved_employee_id')->nullable();
            $table->string('expense_payment_type')->nullable();
            $table->unsignedBigInteger('expense_image_id')->nullable();
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('category_id')->references('id')->on('phppos_expenses_categories');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('approved_employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('expense_image_id')->references('file_id')->on('phppos_app_files');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_expenses');
        Schema::dropIfExists('phppos_expenses_categories');
    }
};
