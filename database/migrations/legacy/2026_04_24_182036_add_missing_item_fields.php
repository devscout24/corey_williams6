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
        Schema::table('phppos_items', function (Blueprint $table) {
            $table->string('barcode_name', 255)->nullable()->after('name');
            $table->string('size', 255)->nullable()->after('barcode_name');
            $table->unsignedBigInteger('manufacturer_id')->nullable()->after('size');
            $table->text('long_description')->nullable()->after('description');
            $table->text('info_popup')->nullable()->after('long_description');
            $table->decimal('weight', 23, 10)->nullable()->after('info_popup');
            $table->string('weight_unit', 50)->nullable()->after('weight');
            $table->decimal('length', 23, 10)->nullable()->after('weight_unit');
            $table->decimal('width', 23, 10)->nullable()->after('length');
            $table->decimal('height', 23, 10)->nullable()->after('width');
            $table->decimal('default_quantity', 23, 10)->nullable()->after('height');
            $table->boolean('item_inactive')->default(false)->after('deleted');
            $table->boolean('is_barcoded')->default(true)->after('item_inactive');
            $table->boolean('is_favorite')->default(false)->after('is_barcoded');
            $table->boolean('is_ecommerce')->default(false)->after('is_favorite');
            $table->unsignedBigInteger('ecommerce_shipping_class_id')->nullable()->after('is_ecommerce');
            $table->boolean('is_ebt_item')->default(false)->after('ecommerce_shipping_class_id');
            $table->boolean('is_series_package')->default(false)->after('is_ebt_item');
            $table->integer('series_quantity')->nullable()->after('is_series_package');
            $table->integer('series_days_to_use_within')->nullable()->after('series_quantity');
            $table->boolean('allow_alt_description')->default(false)->after('series_days_to_use_within');
            $table->boolean('is_serialized')->default(false)->after('allow_alt_description');
            $table->boolean('disable_loyalty')->default(false)->after('is_serialized');
            $table->decimal('loyalty_multiplier', 15, 3)->nullable()->after('disable_loyalty');
            $table->boolean('verify_age')->default(false)->after('loyalty_multiplier');
            $table->integer('required_age')->nullable()->after('verify_age');
        });

        Schema::create('phppos_items_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['item_id', 'tag_id']);
            $table->foreign('item_id')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('phppos_tags')->cascadeOnDelete();
        });

        Schema::create('phppos_additional_item_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('item_number', 255)->unique();
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('phppos_items')->cascadeOnDelete();
        });

        Schema::create('phppos_items_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('serial_number', 255)->unique();
            $table->decimal('cost_price', 23, 10)->nullable();
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('phppos_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_items_serial_numbers');
        Schema::dropIfExists('phppos_additional_item_numbers');
        Schema::dropIfExists('phppos_items_tags');

        Schema::table('phppos_items', function (Blueprint $table) {
            $table->dropColumn([
                'barcode_name', 'size', 'manufacturer_id', 'long_description', 'info_popup',
                'weight', 'weight_unit', 'length', 'width', 'height', 'default_quantity',
                'item_inactive', 'is_barcoded', 'is_favorite', 'is_ecommerce', 'ecommerce_shipping_class_id',
                'is_ebt_item', 'is_series_package', 'series_quantity', 'series_days_to_use_within',
                'allow_alt_description', 'is_serialized', 'disable_loyalty', 'loyalty_multiplier',
                'verify_age', 'required_age'
            ]);
        });
    }
};
