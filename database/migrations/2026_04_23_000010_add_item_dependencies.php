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
            $table->unsignedBigInteger('supplier_id')->nullable()->after('category_id');
            $table->string('product_id', 255)->nullable()->after('item_number');
            $table->text('description')->nullable()->after('product_id');
            $table->boolean('tax_included')->default(false)->after('description');
            $table->boolean('is_service')->default(false)->after('tax_included');

            $table->index('supplier_id', 'items_supplier_idx');
            $table->index('product_id', 'items_product_idx');
            $table->foreign('supplier_id', 'items_supplier_fk')
                ->references('person_id')
                ->on('phppos_suppliers');
        });

        Schema::table('phppos_item_kits', function (Blueprint $table) {
            $table->string('item_kit_number', 255)->nullable()->after('id');
            $table->string('product_id', 255)->nullable()->after('item_kit_number');
            $table->unsignedBigInteger('category_id')->nullable()->after('product_id');
            $table->text('description')->nullable()->after('category_id');
            $table->boolean('tax_included')->default(false)->after('description');
            $table->decimal('cost_price', 23, 10)->default(0)->after('unit_price');

            $table->index('category_id', 'item_kits_category_idx');
            $table->index('item_kit_number', 'item_kits_number_idx');
            $table->index('product_id', 'item_kits_product_idx');
            $table->foreign('category_id', 'item_kits_category_fk')
                ->references('id')
                ->on('phppos_categories');
        });

        Schema::table('phppos_categories', function (Blueprint $table) {
            $table->boolean('hide_from_grid')->default(false)->after('deleted');
        });

        Schema::create('phppos_item_kit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 23, 10)->default(1);

            $table->index('item_kit_id', 'kit_items_kit_idx');
            $table->index('item_id', 'kit_items_item_idx');
            $table->foreign('item_kit_id', 'kit_items_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('item_id', 'kit_items_item_fk')
                ->references('item_id')
                ->on('phppos_items')
                ->cascadeOnDelete();
        });

        Schema::create('phppos_items_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);

            $table->unique(['item_id', 'name', 'percent'], 'items_taxes_unique');
            $table->foreign('item_id', 'items_taxes_item_fk')
                ->references('item_id')
                ->on('phppos_items')
                ->cascadeOnDelete();
        });

        Schema::create('phppos_item_kits_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);

            $table->foreign('item_kit_id', 'kit_taxes_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
        });

        Schema::create('phppos_location_item_kits', function (Blueprint $table) {
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('unit_price', 23, 10)->nullable();
            $table->decimal('cost_price', 23, 10)->nullable();
            $table->boolean('override_default_tax')->default(false);

            $table->primary(['item_kit_id', 'location_id']);
            $table->foreign('item_kit_id', 'loc_item_kits_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('location_id', 'loc_item_kits_loc_fk')
                ->references('location_id')
                ->on('phppos_locations')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_location_item_kits');
        Schema::dropIfExists('phppos_item_kits_taxes');
        Schema::dropIfExists('phppos_items_taxes');
        Schema::dropIfExists('phppos_item_kit_items');

        Schema::table('phppos_categories', function (Blueprint $table) {
            $table->dropColumn('hide_from_grid');
        });

        Schema::table('phppos_item_kits', function (Blueprint $table) {
            $table->dropForeign('item_kits_category_fk');
            $table->dropIndex('item_kits_category_idx');
            $table->dropIndex('item_kits_number_idx');
            $table->dropIndex('item_kits_product_idx');
            $table->dropColumn([
                'item_kit_number',
                'product_id',
                'category_id',
                'description',
                'tax_included',
                'cost_price',
            ]);
        });

        Schema::table('phppos_items', function (Blueprint $table) {
            $table->dropForeign('items_supplier_fk');
            $table->dropIndex('items_supplier_idx');
            $table->dropIndex('items_product_idx');
            $table->dropColumn([
                'supplier_id',
                'product_id',
                'description',
                'tax_included',
                'is_service',
            ]);
        });
    }
};
