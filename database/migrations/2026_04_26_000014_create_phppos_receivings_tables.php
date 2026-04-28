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
            
            for ($i = 1; $i <= 10; $i++) {
                $table->text("custom_field_{$i}_value")->nullable();
            }

            $table->timestamps();

            $table->foreign('supplier_id')->references('person_id')->on('phppos_suppliers');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('transfer_to_location_id')->references('location_id')->on('phppos_locations');
            
            $table->index('receiving_time');
            $table->index('deleted');
        });

        Schema::create('phppos_receivings_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('item_variation_id')->nullable();
            $table->integer('line');
            $table->text('description')->nullable();
            $table->string('serialnumber', 255)->nullable();
            $table->decimal('quantity_purchased', 23, 10)->default(0);
            $table->decimal('quantity_received', 23, 10)->default(0);
            $table->decimal('discount_percent', 15, 3)->default(0);
            $table->decimal('item_cost_price', 23, 10)->default(0);
            $table->decimal('item_unit_price', 23, 10)->default(0);
            $table->date('expire_date')->nullable();
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->decimal('tax', 23, 10)->default(0);
            $table->decimal('profit', 23, 10)->default(0);
            $table->text('override_taxes')->nullable();
            $table->decimal('unit_quantity', 23, 10)->nullable();
            $table->unsignedBigInteger('items_quantity_units_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();

            $table->foreign('receiving_id')->references('receiving_id')->on('phppos_receivings')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
            $table->foreign('supplier_id')->references('person_id')->on('phppos_suppliers');
        });

        Schema::create('phppos_receivings_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->string('payment_type', 255);
            $table->decimal('payment_amount', 23, 10);
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();

            $table->foreign('receiving_id')->references('receiving_id')->on('phppos_receivings')->cascadeOnDelete();
        });

        Schema::create('phppos_receivings_items_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('line');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('receiving_id')->references('receiving_id')->on('phppos_receivings')->cascadeOnDelete();
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
        });

        Schema::create('phppos_supplier_store_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('receiving_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('transaction_amount', 23, 10)->default(0);
            $table->decimal('balance', 23, 10)->default(0);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();

            $table->foreign('supplier_id')->references('person_id')->on('phppos_suppliers')->cascadeOnDelete();
            $table->foreign('receiving_id')->references('receiving_id')->on('phppos_receivings')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_supplier_store_accounts');
        Schema::dropIfExists('phppos_receivings_items_taxes');
        Schema::dropIfExists('phppos_receivings_payments');
        Schema::dropIfExists('phppos_receivings_items');
        Schema::dropIfExists('phppos_receivings');
    }
};
