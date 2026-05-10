<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_receivings', function (Blueprint $table) {
            $table->id('receiving_id');
            $table->timestamp('receiving_time')->useCurrent();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->text('comment')->nullable();
            $table->string('payment_type', 255)->nullable();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->integer('suspended')->default(0);
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('transfer_to_location_id')->nullable();
            $table->decimal('exchange_rate', 23, 10)->default(1);
            $table->string('exchange_name', 255)->default('');
            $table->decimal('total_quantity_purchased', 23, 10)->default(0);
            $table->decimal('total_quantity_received', 23, 10)->default(0);
            $table->string('mode', 20)->default('receive');
            $table->string('type', 20)->default('receive')->comment('receive|return|transfer — business document type');
            $table->string('internal_code', 40)->nullable()->unique()->comment('RCV-xxxxxxxx or RTV-xxxxxxxx');
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->decimal('tax', 23, 10)->default(0);
            $table->decimal('profit', 23, 10)->default(0);
            $table->decimal('shipping_cost', 23, 10)->nullable();
            $table->boolean('is_po')->default(false);
            $table->boolean('store_account_payment')->default(false);
            $table->text('deleted_taxes')->nullable();
            $table->text('override_taxes')->nullable();
            $table->timestamp('last_modified')->nullable();
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

            $table->foreign('supplier_id', 'receivings_supplier_fk')->references('person_id')->on('phppos_suppliers');
            $table->foreign('employee_id', 'receivings_emp_fk')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id', 'receivings_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('transfer_to_location_id', 'receivings_transfer_loc_fk')->references('location_id')->on('phppos_locations');
            $table->index('receiving_time');
            $table->index('deleted');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_receivings');
    }
};
