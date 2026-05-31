<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_receivings_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->unsignedBigInteger('item_id')->nullable();        // null when line is a kit
            $table->unsignedBigInteger('item_kit_id')->nullable();    // set when line is a kit
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
            $table->decimal('vat', 23, 10)->default(0)->comment('VAT = total_with_tax * rate / (1 + rate)');
            $table->decimal('profit', 23, 10)->default(0);
            $table->text('override_taxes')->nullable();
            $table->decimal('unit_quantity', 23, 10)->nullable();
            $table->unsignedBigInteger('items_quantity_units_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->timestamps();

            $table->foreign('receiving_id', 'receivings_items_receiving_fk')
                ->references('receiving_id')
                ->on('phppos_receivings')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'receivings_items_item_fk')->references('item_id')->on('phppos_items')->nullOnDelete();
            $table->foreign('item_kit_id', 'receivings_items_kit_fk')->references('id')->on('phppos_item_kits')->nullOnDelete();
            $table->foreign('supplier_id', 'receivings_items_supplier_fk')->references('person_id')->on('phppos_suppliers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_receivings_items');
    }
};
