<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->primary();
            $table->string('company_name')->default('');
            $table->string('account_number')->nullable()->unique();
            $table->boolean('deleted')->default(false);
            $table->boolean('override_default_tax')->default(false);
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->decimal('balance', 23, 10)->default(0);
            $table->unsignedBigInteger('default_term_id')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('custom_field_1_value')->nullable();
            $table->text('custom_field_2_value')->nullable();
            $table->text('custom_field_3_value')->nullable();
            $table->text('custom_field_4_value')->nullable();
            $table->text('custom_field_5_value')->nullable();
            $table->text('custom_field_6_value')->nullable();
            $table->text('custom_field_7_value')->nullable();
            $table->text('custom_field_8_value')->nullable();
            $table->text('custom_field_9_value')->nullable();
            $table->text('custom_field_10_value')->nullable();
            $table->timestamps();

            $table->foreign('person_id', 'suppliers_person_fk')->references('person_id')->on('phppos_people');
            $table->foreign('tax_class_id', 'suppliers_tax_class_fk')->references('id')->on('phppos_tax_classes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_suppliers');
    }
};
