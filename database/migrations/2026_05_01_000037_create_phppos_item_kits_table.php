<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('barcode_name', 255)->nullable();
            $table->string('item_kit_number', 255)->nullable();
            $table->string('product_id', 255)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('description')->nullable();
            $table->text('info_popup')->nullable();
            $table->boolean('tax_included')->default(false);
            $table->boolean('override_default_tax')->default(false);
            $table->unsignedBigInteger('tax_class_id')->nullable();
            $table->decimal('unit_price', 23, 10)->default(0);
            $table->decimal('cost_price', 23, 10)->default(0);
            $table->integer('manufacturer_id')->nullable();
            $table->boolean('is_ebt_item')->default(false);
            $table->decimal('commission_percent', 15, 3)->nullable();
            $table->string('commission_percent_type', 20)->default('profit');
            $table->decimal('commission_fixed', 23, 10)->nullable();
            $table->boolean('change_cost_price')->default(false);
            $table->boolean('disable_loyalty')->default(false);
            $table->decimal('max_discount_percent', 15, 3)->nullable();
            $table->decimal('max_edit_price', 23, 10)->nullable();
            $table->decimal('min_edit_price', 23, 10)->nullable();
            $table->integer('required_age')->nullable();
            $table->boolean('verify_age')->default(false);
            $table->boolean('allow_price_override_regardless_of_permissions')->default(false);
            $table->boolean('only_integer')->default(false);
            $table->boolean('is_barcoded')->default(true);
            $table->boolean('item_kit_inactive')->default(false);
            $table->decimal('default_quantity', 23, 10)->nullable();
            $table->decimal('reorder_level', 23, 10)->nullable();
            $table->boolean('dynamic_pricing')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->decimal('loyalty_multiplier', 15, 3)->nullable();
            $table->unsignedBigInteger('main_image_id')->nullable();
            $table->boolean('disable_from_price_rules')->default(false);
            $table->boolean('deleted')->default(false);
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

            $table->index('category_id', 'item_kits_category_idx');
            $table->index('supplier_id', 'item_kits_supplier_idx');
            $table->index('item_kit_number', 'item_kits_number_idx');
            $table->index('product_id', 'item_kits_product_idx');
            $table->index('manufacturer_id', 'item_kits_manufacturer_idx');
            $table->foreign('category_id', 'item_kits_category_fk')->references('id')->on('phppos_categories');
            $table->foreign('supplier_id', 'item_kits_supplier_fk')->references('person_id')->on('phppos_suppliers');
            $table->foreign('manufacturer_id', 'item_kits_manufacturer_fk')->references('id')->on('phppos_manufacturers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits');
    }
};
