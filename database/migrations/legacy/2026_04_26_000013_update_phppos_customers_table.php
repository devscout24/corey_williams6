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
        Schema::create('phppos_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
            
            $table->index('deleted');
        });

        Schema::table('phppos_customers', function (Blueprint $table) {
            $table->boolean('always_sms_receipt')->default(false)->after('balance');
            $table->boolean('auto_email_receipt')->default(false)->after('always_sms_receipt');
            $table->text('customer_info_popup')->nullable()->after('auto_email_receipt');
            $table->boolean('override_default_tax')->default(false)->after('customer_info_popup');
            $table->decimal('credit_limit', 23, 10)->nullable()->after('balance');
            $table->integer('points')->default(0)->after('credit_limit');
            $table->boolean('disable_loyalty')->default(false)->after('points');
            $table->decimal('current_spend_for_points', 23, 10)->default(0)->after('disable_loyalty');
            $table->decimal('current_sales_for_discount', 23, 10)->default(0)->after('current_spend_for_points');
            $table->boolean('taxable')->default(true)->after('override_default_tax');
            $table->string('tax_certificate', 255)->nullable()->after('taxable');
            $table->string('cc_token', 255)->nullable()->after('tax_certificate');
            $table->string('cc_ref_no', 255)->nullable()->after('cc_token');
            $table->string('cc_preview', 255)->nullable()->after('cc_ref_no');
            $table->string('card_issuer', 255)->nullable()->after('cc_preview');
            $table->unsignedBigInteger('tier_id')->nullable()->after('account_number');
            $table->unsignedBigInteger('tax_class_id')->nullable()->after('override_default_tax');
            $table->unsignedBigInteger('location_id')->nullable()->after('person_id');
            $table->text('internal_notes')->nullable()->after('customer_info_popup');

            for ($i = 1; $i <= 10; $i++) {
                $table->text("custom_field_{$i}_value")->nullable();
            }

            $table->foreign('tier_id')->references('id')->on('phppos_price_tiers');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
        });

        Schema::create('phppos_customers_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('person_id')->on('phppos_customers')->cascadeOnDelete();
        });

        Schema::create('phppos_store_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('transaction_amount', 23, 10)->default(0);
            $table->decimal('balance', 23, 10)->default(0);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();

            $table->foreign('customer_id')->references('person_id')->on('phppos_customers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_store_accounts');
        Schema::dropIfExists('phppos_customers_taxes');
        
        Schema::table('phppos_customers', function (Blueprint $table) {
            $table->dropForeign(['tier_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn([
                'always_sms_receipt',
                'auto_email_receipt',
                'customer_info_popup',
                'override_default_tax',
                'credit_limit',
                'points',
                'disable_loyalty',
                'current_spend_for_points',
                'current_sales_for_discount',
                'taxable',
                'tax_certificate',
                'cc_token',
                'cc_ref_no',
                'cc_preview',
                'card_issuer',
                'tier_id',
                'tax_class_id',
                'location_id',
                'internal_notes',
                'custom_field_1_value',
                'custom_field_2_value',
                'custom_field_3_value',
                'custom_field_4_value',
                'custom_field_5_value',
                'custom_field_6_value',
                'custom_field_7_value',
                'custom_field_8_value',
                'custom_field_9_value',
                'custom_field_10_value',
            ]);
        });

        Schema::dropIfExists('phppos_price_tiers');
    }
};
