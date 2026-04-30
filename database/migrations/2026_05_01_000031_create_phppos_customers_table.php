<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->primary();
            $table->string('company_name')->default('');
            $table->string('account_number')->nullable()->unique();
            $table->decimal('balance', 23, 10)->default(0);
            $table->boolean('deleted')->default(false);
            $table->boolean('always_sms_receipt')->default(false);
            $table->boolean('auto_email_receipt')->default(false);
            $table->text('customer_info_popup')->nullable();
            $table->boolean('override_default_tax')->default(false);
            $table->decimal('credit_limit', 23, 10)->nullable();
            $table->integer('points')->default(0);
            $table->boolean('disable_loyalty')->default(false);
            $table->decimal('current_spend_for_points', 23, 10)->default(0);
            $table->decimal('current_sales_for_discount', 23, 10)->default(0);
            $table->boolean('taxable')->default(true);
            $table->string('tax_certificate', 255)->nullable();
            $table->string('cc_token', 255)->nullable();
            $table->string('cc_ref_no', 255)->nullable();
            $table->string('cc_preview', 255)->nullable();
            $table->string('card_issuer', 255)->nullable();
            $table->unsignedBigInteger('tier_id')->nullable();
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
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

            $table->foreign('person_id', 'customers_person_fk')->references('person_id')->on('phppos_people');
            $table->foreign('tier_id', 'customers_tier_fk')->references('id')->on('phppos_price_tiers');
            $table->foreign('location_id', 'customers_loc_fk')->references('location_id')->on('phppos_locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_customers');
    }
};
