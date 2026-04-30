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
        Schema::table('phppos_item_kits', function (Blueprint $table) {
            $table->string('barcode_name', 255)->nullable()->after('name');
            $table->text('info_popup')->nullable()->after('description');
            $table->integer('manufacturer_id')->nullable()->after('category_id');
            $table->boolean('is_ebt_item')->default(false)->after('tax_included');
            $table->decimal('commission_percent', 15, 3)->nullable()->after('is_ebt_item');
            $table->string('commission_percent_type', 20)->default('profit')->after('commission_percent');
            $table->decimal('commission_fixed', 23, 10)->nullable()->after('commission_percent_type');
            $table->boolean('change_cost_price')->default(false)->after('commission_fixed');
            $table->boolean('disable_loyalty')->default(false)->after('change_cost_price');
            $table->unsignedBigInteger('tax_class_id')->nullable()->after('tax_included');
            $table->decimal('max_discount_percent', 15, 3)->nullable()->after('disable_loyalty');
            $table->decimal('max_edit_price', 23, 10)->nullable()->after('max_discount_percent');
            $table->decimal('min_edit_price', 23, 10)->nullable()->after('max_edit_price');
            
            for ($i = 1; $i <= 10; $i++) {
                $table->text("custom_field_{$i}_value")->nullable();
            }

            $table->integer('required_age')->nullable()->after('min_edit_price');
            $table->boolean('verify_age')->default(false)->after('required_age');
            $table->boolean('allow_price_override_regardless_of_permissions')->default(false)->after('verify_age');
            $table->boolean('only_integer')->default(false)->after('allow_price_override_regardless_of_permissions');
            $table->boolean('is_barcoded')->default(true)->after('only_integer');
            $table->boolean('item_kit_inactive')->default(false)->after('is_barcoded');
            $table->decimal('default_quantity', 23, 10)->nullable()->after('item_kit_inactive');
            $table->boolean('dynamic_pricing')->default(false)->after('default_quantity');
            $table->boolean('is_favorite')->default(false)->after('dynamic_pricing');
            $table->decimal('loyalty_multiplier', 15, 3)->nullable()->after('is_favorite');
            $table->unsignedBigInteger('main_image_id')->nullable()->after('loyalty_multiplier');

            $table->index('manufacturer_id', 'item_kits_manufacturer_idx');
            $table->foreign('manufacturer_id', 'item_kits_manufacturer_fk')
                ->references('id')
                ->on('phppos_manufacturers');
        });

        Schema::create('phppos_location_item_kits_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('location_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->index(['item_kit_id', 'location_id'], 'loc_kit_taxes_idx');
        });

        Schema::create('phppos_item_kit_item_kits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('item_kit_item_kit'); // Nested item kit ID
            $table->decimal('quantity', 23, 10)->default(1);
            $table->timestamps();

            $table->foreign('item_kit_id', 'kit_kit_parent_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('item_kit_item_kit', 'kit_kit_child_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
        });

        Schema::create('phppos_item_kits_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->primary(['item_kit_id', 'tag_id']);
            $table->foreign('item_kit_id', 'kit_tags_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('tag_id', 'kit_tags_tag_fk')
                ->references('id')
                ->on('phppos_tags')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_tags');
        Schema::dropIfExists('phppos_item_kit_item_kits');
        Schema::dropIfExists('phppos_location_item_kits_taxes');
        
        Schema::table('phppos_item_kits', function (Blueprint $table) {
            $table->dropForeign('item_kits_manufacturer_fk');
            $table->dropIndex('item_kits_manufacturer_idx');
            $table->dropColumn([
                'barcode_name',
                'info_popup',
                'manufacturer_id',
                'is_ebt_item',
                'commission_percent',
                'commission_percent_type',
                'commission_fixed',
                'change_cost_price',
                'disable_loyalty',
                'tax_class_id',
                'max_discount_percent',
                'max_edit_price',
                'min_edit_price',
                'required_age',
                'verify_age',
                'allow_price_override_regardless_of_permissions',
                'only_integer',
                'is_barcoded',
                'item_kit_inactive',
                'default_quantity',
                'dynamic_pricing',
                'is_favorite',
                'loyalty_multiplier',
                'main_image_id',
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
    }
};
