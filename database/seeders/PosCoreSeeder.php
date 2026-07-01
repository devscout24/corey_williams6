<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PosCoreSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        if (Schema::hasTable('phppos_people')) {
            DB::table('phppos_people')->updateOrInsert(
                ['person_id' => 1],
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'full_name' => 'John Doe',
                    'phone_number' => '555-555-5555',
                    'email' => 'no-reply@example.com',
                    'address_1' => 'Address 1',
                ]
            );
        }

        if (Schema::hasTable('phppos_employees')) {
            DB::table('phppos_employees')->updateOrInsert(
                ['id' => 1],
                [
                    'person_id' => 1,
                    'username' => 'admin',
                    // Legacy-compatible default. On first successful login it auto-upgrades to bcrypt.
                    'password' => md5('12345678'),
                    'inactive' => 0,
                    'deleted' => 0,
                ]
            );
        }

        if (Schema::hasTable('phppos_locations')) {
            DB::table('phppos_locations')->updateOrInsert(
                ['location_id' => 1],
                [
                    'name' => 'Main Store',
                    'address_1' => 'Address 1',
                    'deleted' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // DB::table('phppos_locations')->updateOrInsert(
            //     ['location_id' => 2],
            //     [
            //         'name' => 'Store 2',
            //         'address_1' => 'Address 2',
            //         'deleted' => 0,
            //         'created_at' => $now,
            //         'updated_at' => $now,
            //     ]
            // );
        }

        if (Schema::hasTable('phppos_vat_rates')) {
            $vatRates = [
                ['name' => 'VAT 15%', 'percent' => 15],
                ['name' => 'VAT 20%', 'percent' => 20],
                ['name' => 'Exempt', 'percent' => 0],
                ['name' => 'Zero', 'percent' => 0],
            ];

            foreach ($vatRates as $vat) {
                DB::table('phppos_vat_rates')->updateOrInsert(
                    ['name' => $vat['name']],
                    [
                        'percent' => $vat['percent'],
                        'deleted' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('phppos_employees_locations')) {
            DB::table('phppos_employees_locations')->insertOrIgnore([
                ['employee_id' => 1, 'location_id' => 1],
                // ['employee_id' => 1, 'location_id' => 2],
            ]);
        }

        if (Schema::hasTable('phppos_modules')) {
            DB::table('phppos_modules')->upsert(
                [
                    ['module_id' => 'locations', 'name_lang_key' => 'module_locations', 'desc_lang_key' => 'module_locations_desc', 'sort' => 10, 'icon' => 'ti-map'],
                    ['module_id' => 'contacts', 'name_lang_key' => 'module_contacts', 'desc_lang_key' => 'module_contacts_desc', 'sort' => 20, 'icon' => 'ti-id-badge'],
                    ['module_id' => 'items', 'name_lang_key' => 'module_items', 'desc_lang_key' => 'module_items_desc', 'sort' => 30, 'icon' => 'ti-package'],
                    ['module_id' => 'receivings', 'name_lang_key' => 'module_receivings', 'desc_lang_key' => 'module_receivings_desc', 'sort' => 40, 'icon' => 'ti-truck'],
                    ['module_id' => 'sales', 'name_lang_key' => 'module_sales', 'desc_lang_key' => 'module_sales_desc', 'sort' => 50, 'icon' => 'ti-shopping-cart'],
                    ['module_id' => 'reports', 'name_lang_key' => 'module_reports', 'desc_lang_key' => 'module_reports_desc', 'sort' => 60, 'icon' => 'ti-bar-chart-alt'],
                    ['module_id' => 'reconciliation', 'name_lang_key' => 'module_reconciliation', 'desc_lang_key' => 'module_reconciliation_desc', 'sort' => 70, 'icon' => 'ti-calculator'],
                    ['module_id' => 'messages', 'name_lang_key' => 'module_messages', 'desc_lang_key' => 'module_messages_desc', 'sort' => 80, 'icon' => 'ti-email'],
                    ['module_id' => 'config', 'name_lang_key' => 'module_config', 'desc_lang_key' => 'module_config_desc', 'sort' => 90, 'icon' => 'icon ti-settings'],
                    ['module_id' => 'employees', 'name_lang_key' => 'module_employees', 'desc_lang_key' => 'module_employees_desc', 'sort' => 100, 'icon' => 'icon ti-id-badge'],
                ],
                ['module_id'],
                ['name_lang_key', 'desc_lang_key', 'sort', 'icon']
            );
        }

        if (Schema::hasTable('phppos_module_submodules')) {
            $submodules = [
                ['module_id' => 'locations', 'submodule_key' => 'locations', 'label' => 'Locations', 'sort' => 10],
                ['module_id' => 'contacts', 'submodule_key' => 'customers', 'label' => 'Customers', 'sort' => 10],
                ['module_id' => 'contacts', 'submodule_key' => 'suppliers', 'label' => 'Suppliers', 'sort' => 20],
                ['module_id' => 'items', 'submodule_key' => 'items', 'label' => 'Items', 'sort' => 10],
                ['module_id' => 'items', 'submodule_key' => 'item_kits', 'label' => 'Item Kits', 'sort' => 20],
                ['module_id' => 'items', 'submodule_key' => 'categories', 'label' => 'Categories', 'sort' => 30],
                ['module_id' => 'items', 'submodule_key' => 'tags', 'label' => 'Tags', 'sort' => 40],
                ['module_id' => 'items', 'submodule_key' => 'attributes', 'label' => 'Attributes', 'sort' => 50],
                ['module_id' => 'items', 'submodule_key' => 'price_rules', 'label' => 'Price Rules', 'sort' => 60],
                ['module_id' => 'items', 'submodule_key' => 'labels', 'label' => 'Labels', 'sort' => 70],

                ['module_id' => 'receivings', 'submodule_key' => 'receiving', 'label' => 'Purchases', 'sort' => 10],
                ['module_id' => 'receivings', 'submodule_key' => 'returns', 'label' => 'Return', 'sort' => 20],
                ['module_id' => 'receivings', 'submodule_key' => 'transfer_out', 'label' => 'Transfer Out', 'sort' => 30],
                ['module_id' => 'receivings', 'submodule_key' => 'transfer_in_auto', 'label' => 'Transfer In (Auto)', 'sort' => 40],

                ['module_id' => 'sales', 'submodule_key' => 'sales_register', 'label' => 'Sales Register', 'sort' => 10],
                ['module_id' => 'sales', 'submodule_key' => 'receipts', 'label' => 'Receipts', 'sort' => 20],

                ['module_id' => 'messages', 'submodule_key' => 'inbox', 'label' => 'Inbox', 'sort' => 10],
                ['module_id' => 'messages', 'submodule_key' => 'compose', 'label' => 'Compose', 'sort' => 20],
            ];

            $rows = array_map(static function (array $s) use ($now): array {
                return [
                    'module_id' => $s['module_id'],
                    'submodule_key' => $s['submodule_key'],
                    'label' => $s['label'],
                    'sort' => $s['sort'],
                    'enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $submodules);

            DB::table('phppos_module_submodules')->upsert(
                $rows,
                ['module_id', 'submodule_key'],
                ['label', 'sort', 'enabled', 'updated_at']
            );
        }

        if (Schema::hasTable('phppos_modules_actions')) {
            DB::table('phppos_modules_actions')->upsert(
                [
                    ['action_id' => 'add_update', 'module_id' => 'employees', 'action_name_key' => 'module_action_add_update', 'sort' => 130],
                    ['action_id' => 'delete', 'module_id' => 'employees', 'action_name_key' => 'module_action_delete', 'sort' => 140],
                    ['action_id' => 'search', 'module_id' => 'employees', 'action_name_key' => 'module_action_search_employees', 'sort' => 150],
                    ['action_id' => 'edit_profile', 'module_id' => 'employees', 'action_name_key' => 'common_edit_profile', 'sort' => 155],
                    ['action_id' => 'assign_all_locations', 'module_id' => 'employees', 'action_name_key' => 'module_action_assign_all_locations', 'sort' => 151],
                    ['action_id' => 'excel_export', 'module_id' => 'employees', 'action_name_key' => 'common_excel_export', 'sort' => 160],
                ],
                ['action_id', 'module_id'],
                ['action_name_key', 'sort']
            );
        }

        if (Schema::hasTable('phppos_permissions')) {
            $modules = ['locations', 'contacts', 'items', 'receivings', 'sales', 'reports', 'reconciliation', 'messages', 'employees', 'config'];

            foreach ($modules as $moduleId) {
                DB::table('phppos_permissions')->updateOrInsert(
                    [
                        'module_id' => $moduleId,
                        'person_id' => 1,
                    ],
                    []
                );
            }
        }

        if (Schema::hasTable('phppos_permissions_actions')) {
            $actions = [
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'add_update'],
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'delete'],
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'search'],
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'edit_profile'],
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'assign_all_locations'],
                ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'excel_export'],
            ];

            foreach ($actions as $action) {
                DB::table('phppos_permissions_actions')->updateOrInsert(
                    [
                        'module_id' => $action['module_id'],
                        'person_id' => $action['person_id'],
                        'action_id' => $action['action_id'],
                    ],
                    []
                );
            }
        }

        if (Schema::hasTable('phppos_categories')) {
            DB::table('phppos_categories')->updateOrInsert(
                ['id' => 1],
                [
                    'name' => 'Default Category',
                    'slug' => 'default-category',
                    'deleted' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('phppos_items')) {
            DB::table('phppos_items')->updateOrInsert(
                ['item_id' => 1],
                [
                    'name' => 'Sample Item',
                    'item_number' => 'ITEM-001',
                    'category_id' => 1,
                    'cost_price' => 5,
                    'unit_price' => 10,
                    'deleted' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('phppos_location_items')) {
            DB::table('phppos_location_items')->updateOrInsert(
                ['location_id' => 1, 'item_id' => 1],
                [
                    'quantity' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // DB::table('phppos_location_items')->updateOrInsert(
            //     ['location_id' => 2, 'item_id' => 1],
            //     [
            //         'quantity' => 0,
            //         'created_at' => $now,
            //         'updated_at' => $now,
            //     ]
            // );
        }

        if (Schema::hasTable('phppos_registers')) {
            DB::table('phppos_registers')->updateOrInsert(
                ['register_id' => 1],
                [
                    'location_id' => 1,
                    'name' => 'Default',
                    'deleted' => 0,
                    'enable_tips' => 0,
                ]
            );
        }

        if (Schema::hasTable('phppos_app_config')) {
            DB::table('phppos_app_config')->updateOrInsert(
                ['key' => 'hide_item_image_upload'],
                ['value' => '1']
            );
        }

        if (Schema::hasTable('phppos_receipt_settings')) {
            DB::table('phppos_receipt_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'title' => 'Store Receipt',
                    'footer' => 'Thank you',
                    'paper_size' => '80mm',
                    'show_cashier' => 1,
                    'show_customer' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
