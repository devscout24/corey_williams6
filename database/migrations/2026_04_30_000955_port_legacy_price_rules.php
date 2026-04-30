<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET foreign_key_checks=0;');

        // Drop existing tables if they exist
        $tablesToDrop = [
            'phppos_price_rules_price_breaks',
            'phppos_price_rules_items',
            'phppos_price_rules_item_kits',
            'phppos_price_rules_categories',
            'phppos_price_rules_tags',
            'phppos_price_rules_manufacturers',
            'phppos_price_rules_locations',
            'phppos_price_rules_tiers_exclude',
            'phppos_price_rules',
        ];

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        DB::unprepared("
        CREATE TABLE `phppos_price_rules` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name`  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `start_date` timestamp NULL DEFAULT NULL,
          `end_date` timestamp NULL DEFAULT NULL,
          `added_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `active`  tinyint(1) NOT NULL DEFAULT '1',
          `deleted` tinyint(1) NOT NULL DEFAULT '0',
          `type`  varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,    
          `items_to_buy`  decimal(23,10) NULL DEFAULT NULL,
          `items_to_get`  decimal(23,10) NULL DEFAULT NULL,
          `percent_off`  decimal(23,10) NULL DEFAULT NULL,
          `fixed_off`  decimal(23,10) NULL DEFAULT NULL,
          `spend_amount`  decimal(23,10) NULL DEFAULT NULL,
          `num_times_to_apply` int(11) NOT NULL,
          `coupon_code` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
          `coupon_spend_amount` decimal(23,10) DEFAULT NULL,
          `mix_and_match` tinyint(1) NOT NULL DEFAULT '0',
          `disable_loyalty_for_rule` tinyint(1) NOT NULL DEFAULT '0',
          `show_on_receipt` tinyint(1) NOT NULL DEFAULT '0',
          `description` text COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
          KEY `name` (`name`),
          KEY `start_date` (`start_date`),
          KEY `end_date` (`end_date`),
          KEY `type` (`type`),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_price_breaks` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id` bigint unsigned NOT NULL,
          `item_qty_to_buy` decimal(23,10) DEFAULT NULL,
          `discount_per_unit_fixed` decimal(23,10) DEFAULT NULL,
          `discount_per_unit_percent` decimal(23,10) DEFAULT NULL,
          KEY `phppos_price_rules_custom_ibfk_1` (`rule_id`),
          CONSTRAINT `phppos_price_rules_price_breaks_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_items` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id` bigint unsigned NOT NULL,
          `item_id` bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `phppos_price_rules_items_ibfk_1` (`rule_id`),
          KEY `phppos_price_rules_items_ibfk_2` (`item_id`),
          CONSTRAINT `phppos_price_rules_items_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `phppos_items` (`item_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_item_kits` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id`  bigint unsigned NOT NULL,
          `item_kit_id`  bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          CONSTRAINT `phppos_price_rules_item_kits_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_item_kits_ibfk_2` FOREIGN KEY (`item_kit_id`) REFERENCES `phppos_item_kits` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_categories` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id`  bigint unsigned NOT NULL,
          `category_id`  bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          CONSTRAINT `phppos_price_rules_categories_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `phppos_categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_tags` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id`  bigint unsigned NOT NULL,
          `tag_id`  bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          CONSTRAINT `phppos_price_rules_tags_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `phppos_tags` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_manufacturers` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id` bigint unsigned NOT NULL,
          `manufacturer_id` int NOT NULL,
          PRIMARY KEY (`id`),
          KEY `phppos_price_rules_manufacturers_ibfk_1` (`rule_id`),
          KEY `phppos_price_rules_manufacturers_ibfk_2` (`manufacturer_id`),
          CONSTRAINT `phppos_price_rules_manufacturers_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_manufacturers_ibfk_2` FOREIGN KEY (`manufacturer_id`) REFERENCES `phppos_manufacturers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_locations` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `rule_id` bigint unsigned NOT NULL,
          `location_id` bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          CONSTRAINT `phppos_price_rules_locations_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_locations_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `phppos_locations` (`location_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE `phppos_price_rules_tiers_exclude` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `price_rule_id` bigint unsigned NOT NULL,
          `tier_id` bigint unsigned NOT NULL,
          PRIMARY KEY (`id`),
          CONSTRAINT `phppos_price_rules_tiers_ibfk_1` FOREIGN KEY (`price_rule_id`) REFERENCES `phppos_price_rules` (`id`) ON DELETE CASCADE,
          CONSTRAINT `phppos_price_rules_tiers_ibfk_2` FOREIGN KEY (`tier_id`) REFERENCES `phppos_price_tiers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        if (Schema::hasTable('phppos_sales') && !Schema::hasColumn('phppos_sales', 'rule_id')) {
            Schema::table('phppos_sales', function (Blueprint $table) {
                $table->unsignedBigInteger('rule_id')->nullable()->after('sale_id');
                $table->decimal('rule_discount', 23, 10)->nullable()->after('rule_id');
            });
        }

        if (Schema::hasTable('phppos_sales_items') && !Schema::hasColumn('phppos_sales_items', 'rule_id')) {
            Schema::table('phppos_sales_items', function (Blueprint $table) {
                $table->unsignedBigInteger('rule_id')->nullable()->after('item_id');
                $table->decimal('rule_discount', 23, 10)->nullable()->after('rule_id');
                $table->boolean('is_bogo')->default(false)->after('rule_discount');
            });
        }

        if (Schema::hasTable('phppos_sales_item_kits') && !Schema::hasColumn('phppos_sales_item_kits', 'rule_id')) {
            Schema::table('phppos_sales_item_kits', function (Blueprint $table) {
                $table->unsignedBigInteger('rule_id')->nullable()->after('item_kit_id');
                $table->decimal('rule_discount', 23, 10)->nullable()->after('rule_id');
                $table->boolean('is_bogo')->default(false)->after('rule_discount');
                $table->decimal('regular_item_kit_unit_price_at_time_of_sale', 23, 10)->nullable();
            });
        }

        if (Schema::hasTable('phppos_items') && !Schema::hasColumn('phppos_items', 'disable_from_price_rules')) {
            Schema::table('phppos_items', function (Blueprint $table) {
                $table->boolean('disable_from_price_rules')->default(false);
            });
        }

        if (Schema::hasTable('phppos_item_kits') && !Schema::hasColumn('phppos_item_kits', 'disable_from_price_rules')) {
            Schema::table('phppos_item_kits', function (Blueprint $table) {
                $table->boolean('disable_from_price_rules')->default(false);
            });
        }

        DB::statement('SET foreign_key_checks=1;');
    }

    public function down(): void
    {
        // Add down if necessary
    }
};
