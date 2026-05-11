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
        Schema::create('phppos_shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();
        });

        Schema::create('phppos_shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_provider_id');
            $table->string('name');
            $table->decimal('fee', 23, 10)->default(0);
            $table->unsignedBigInteger('fee_tax_class_id')->nullable();
            $table->integer('time_in_days')->nullable();
            $table->integer('has_tracking_number')->default(0);
            $table->integer('is_default')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('shipping_provider_id')->references('id')->on('phppos_shipping_providers');
        });

        Schema::create('phppos_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('fee', 23, 10)->default(0);
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->integer('order')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();
        });

        Schema::create('phppos_zips', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->unsignedBigInteger('shipping_zone_id')->nullable();
            $table->integer('order')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('shipping_zone_id')->references('id')->on('phppos_shipping_zones');
        });

        Schema::create('phppos_delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->text('color')->nullable();
            $table->integer('notify_by_email')->default(0)->nullable();
            $table->integer('notify_by_sms')->default(0)->nullable();
            $table->timestamps();
        });

        Schema::create('phppos_sales_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('shipping_address_person_id');
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->unsignedBigInteger('shipping_zone_id')->nullable();
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->string('status', 30);
            $table->timestamp('estimated_shipping_date')->nullable();
            $table->timestamp('actual_shipping_date')->nullable();
            $table->timestamp('estimated_delivery_or_pickup_date')->nullable();
            $table->timestamp('actual_delivery_or_pickup_date')->nullable();
            $table->integer('is_pickup')->default(0);
            $table->string('tracking_number')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('delivery_employee_person_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('phppos_sales');
            $table->foreign('shipping_address_person_id')->references('person_id')->on('phppos_people');
            $table->foreign('shipping_method_id')->references('id')->on('phppos_shipping_methods');
            $table->foreign('shipping_zone_id')->references('id')->on('phppos_shipping_zones');
            $table->foreign('delivery_employee_person_id')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_deliveries');
        Schema::dropIfExists('phppos_delivery_statuses');
        Schema::dropIfExists('phppos_zips');
        Schema::dropIfExists('phppos_shipping_zones');
        Schema::dropIfExists('phppos_shipping_methods');
        Schema::dropIfExists('phppos_shipping_providers');
    }
};
