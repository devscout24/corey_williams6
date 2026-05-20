<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('name');
            $table->string('barcode_name', 255)->nullable();
            $table->string('size', 255)->nullable();
            $table->string('item_number')->nullable()->unique();
            $table->string('product_id', 255)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable();
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->text('description')->nullable();
            $table->text('long_description')->nullable();
            $table->text('info_popup')->nullable();
            $table->decimal('cost_price', 23, 10)->default(0);
            $table->decimal('markup', 23, 10)->default(0);
            $table->decimal('unit_price', 23, 10)->default(0);
            $table->decimal('weight', 23, 10)->nullable();
            $table->string('weight_unit', 50)->nullable();
            $table->decimal('length', 23, 10)->nullable();
            $table->decimal('width', 23, 10)->nullable();
            $table->decimal('height', 23, 10)->nullable();
            $table->decimal('default_quantity', 23, 10)->nullable();
            $table->decimal('reorder_level', 23, 10)->nullable();
            $table->boolean('tax_included')->default(false);
            $table->boolean('is_service')->default(false);
            $table->boolean('deleted')->default(false);
            $table->boolean('item_inactive')->default(false);
            $table->boolean('is_barcoded')->default(true);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_ecommerce')->default(false);
            $table->unsignedBigInteger('ecommerce_shipping_class_id')->nullable();
            $table->boolean('is_ebt_item')->default(false);
            $table->boolean('is_series_package')->default(false);
            $table->integer('series_quantity')->nullable();
            $table->integer('series_days_to_use_within')->nullable();
            $table->boolean('allow_alt_description')->default(false);
            $table->boolean('is_serialized')->default(false);
            $table->boolean('disable_loyalty')->default(false);
            $table->decimal('loyalty_multiplier', 15, 3)->nullable();
            $table->boolean('verify_age')->default(false);
            $table->integer('required_age')->nullable();
            $table->boolean('disable_from_price_rules')->default(false);
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

            $table->index('supplier_id', 'items_supplier_idx');
            $table->index('product_id', 'items_product_idx');
            $table->foreign('category_id', 'items_category_fk')->references('id')->on('phppos_categories');
            $table->foreign('supplier_id', 'items_supplier_fk')->references('person_id')->on('phppos_suppliers');
            $table->foreign('tax_class_id', 'items_tax_class_fk')->references('id')->on('phppos_tax_classes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_items');
    }
};
