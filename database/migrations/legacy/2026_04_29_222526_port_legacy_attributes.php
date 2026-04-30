<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET foreign_key_checks=0;');

        Schema::dropIfExists('phppos_attributes');
        Schema::dropIfExists('phppos_attribute_values');
        Schema::dropIfExists('phppos_item_attributes');
        Schema::dropIfExists('phppos_item_attribute_values');
        Schema::dropIfExists('phppos_item_variations');
        Schema::dropIfExists('phppos_item_variation_attribute_values');
        Schema::dropIfExists('phppos_location_item_variations');

        DB::unprepared("
        CREATE TABLE `phppos_attributes` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `item_id` bigint unsigned NULL DEFAULT NULL,
          `name`  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `deleted` tinyint(1) NOT NULL DEFAULT '0',
          `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ecommerce_attribute_id` varchar(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`item_id`,`name`),
          CONSTRAINT `phppos_attributes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_attribute_values` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `attribute_id` bigint unsigned NOT NULL,
          `name`  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `deleted` tinyint(1) NOT NULL DEFAULT '0',
          `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ecommerce_attribute_term_id` varchar(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
          UNIQUE KEY `name_attribute_id` (`name`,`attribute_id`),
          CONSTRAINT `phppos_attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `phppos_attributes` (`id`) ON DELETE CASCADE,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_item_attributes` (
          `attribute_id` bigint unsigned NOT NULL,
          `item_id` bigint unsigned NOT NULL,
          CONSTRAINT `phppos_item_attributes_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`),
          CONSTRAINT `phppos_item_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `phppos_attributes` (`id`) ON DELETE CASCADE,
          PRIMARY KEY (`attribute_id`,`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_item_attribute_values` (
          `item_id` bigint unsigned NOT NULL,
          `attribute_value_id` bigint unsigned NOT NULL,
          CONSTRAINT `phppos_item_attribute_values_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`),
          CONSTRAINT `phppos_item_attribute_values_ibfk_2` FOREIGN KEY (`attribute_value_id`) REFERENCES `phppos_attribute_values` (`id`) ON DELETE CASCADE,
          PRIMARY KEY (`attribute_value_id`,`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_item_variations` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `deleted` tinyint(1) NOT NULL DEFAULT '0',
          `item_id` bigint unsigned NOT NULL,
          `reorder_level` decimal(23,10) DEFAULT NULL,
          `replenish_level` decimal(23,10) DEFAULT NULL,
          `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
          `item_number` varchar(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
          `unit_price` decimal(23,10) DEFAULT NULL,
          `cost_price` decimal(23,10) DEFAULT NULL,
          `promo_price` decimal(23,10) DEFAULT NULL,
          `start_date` date DEFAULT NULL,
          `end_date` date DEFAULT NULL,
          `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          CONSTRAINT `phppos_item_variations_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`),
          UNIQUE KEY `item_number` (`item_number`),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_item_variation_attribute_values` (
         `attribute_value_id` bigint unsigned NOT NULL,
         `item_variation_id` bigint unsigned NOT NULL,
         CONSTRAINT `phppos_item_variation_attribute_values_ibfk_1` FOREIGN KEY (`attribute_value_id`) REFERENCES `phppos_attribute_values` (`id`) ON DELETE CASCADE,
         CONSTRAINT `phppos_item_variation_attribute_values_ibfk_2` FOREIGN KEY (`item_variation_id`) REFERENCES `phppos_item_variations` (`id`) ON DELETE CASCADE,
         PRIMARY KEY (`attribute_value_id`,`item_variation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_location_item_variations` (
          `item_variation_id` bigint unsigned NOT NULL,
          `location_id` bigint unsigned NOT NULL,
          `quantity` int(11) NULL DEFAULT NULL,
          `reorder_level` decimal(23,10) DEFAULT NULL,
          `replenish_level` decimal(23,10) DEFAULT NULL,
          CONSTRAINT `phppos_item_attribute_location_values_ibfk_1` FOREIGN KEY (`item_variation_id`) REFERENCES `phppos_item_variations` (`id`),
          CONSTRAINT `phppos_item_attribute_location_values_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `phppos_locations` (`location_id`),
          PRIMARY KEY (`item_variation_id`,`location_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        if (Schema::hasTable('phppos_sales_items') && !Schema::hasColumn('phppos_sales_items', 'item_variation_id')) {
            Schema::table('phppos_sales_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('item_id');
            });
        }
        if (Schema::hasTable('phppos_receivings_items') && !Schema::hasColumn('phppos_receivings_items', 'item_variation_id')) {
            Schema::table('phppos_receivings_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('item_id');
            });
        }
        if (Schema::hasTable('phppos_inventory') && !Schema::hasColumn('phppos_inventory', 'item_variation_id')) {
            Schema::table('phppos_inventory', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('trans_items');
            });
        }
        if (Schema::hasTable('phppos_inventory_counts_items') && !Schema::hasColumn('phppos_inventory_counts_items', 'item_variation_id')) {
            Schema::table('phppos_inventory_counts_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('item_id');
            });
        }
        if (Schema::hasTable('phppos_item_kit_items') && !Schema::hasColumn('phppos_item_kit_items', 'item_variation_id')) {
            if (!Schema::hasColumn('phppos_item_kit_items', 'id')) {
                DB::unprepared("
                    ALTER TABLE `phppos_item_kit_items` DROP FOREIGN KEY `phppos_item_kit_items_ibfk_1`;
                    ALTER TABLE `phppos_item_kit_items` DROP FOREIGN KEY `phppos_item_kit_items_ibfk_2`;
                    ALTER TABLE `phppos_item_kit_items` DROP PRIMARY KEY;
                    ALTER TABLE `phppos_item_kit_items` ADD COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);
                    ALTER TABLE `phppos_item_kit_items` ADD CONSTRAINT `phppos_item_kit_items_ibfk_1` FOREIGN KEY (`item_kit_id`) REFERENCES `phppos_item_kits` (`item_kit_id`) ON DELETE CASCADE;
                    ALTER TABLE `phppos_item_kit_items` ADD CONSTRAINT `phppos_item_kit_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`) ON DELETE CASCADE;
                ");
            }
            Schema::table('phppos_item_kit_items', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('item_id');
            });
        }
        if (Schema::hasTable('phppos_item_images') && !Schema::hasColumn('phppos_item_images', 'item_variation_id')) {
            Schema::table('phppos_item_images', function (Blueprint $table) {
                $table->unsignedBigInteger('item_variation_id')->nullable()->after('item_id');
            });
        }

        DB::statement('SET foreign_key_checks=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
