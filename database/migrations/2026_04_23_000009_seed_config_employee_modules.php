<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('phppos_modules')->upsert([
            [
                'module_id' => 'config',
                'name_lang_key' => 'module_config',
                'desc_lang_key' => 'module_config_desc',
                'sort' => 100,
                'icon' => 'icon ti-settings',
            ],
            [
                'module_id' => 'employees',
                'name_lang_key' => 'module_employees',
                'desc_lang_key' => 'module_employees_desc',
                'sort' => 80,
                'icon' => 'icon ti-id-badge',
            ],
        ], ['module_id'], ['name_lang_key', 'desc_lang_key', 'sort', 'icon']);

        DB::table('phppos_modules_actions')->upsert([
            ['action_id' => 'add_update', 'module_id' => 'employees', 'action_name_key' => 'module_action_add_update', 'sort' => 130],
            ['action_id' => 'delete', 'module_id' => 'employees', 'action_name_key' => 'module_action_delete', 'sort' => 140],
            ['action_id' => 'search', 'module_id' => 'employees', 'action_name_key' => 'module_action_search_employees', 'sort' => 150],
            ['action_id' => 'edit_profile', 'module_id' => 'employees', 'action_name_key' => 'common_edit_profile', 'sort' => 155],
            ['action_id' => 'assign_all_locations', 'module_id' => 'employees', 'action_name_key' => 'module_action_assign_all_locations', 'sort' => 151],
            ['action_id' => 'excel_export', 'module_id' => 'employees', 'action_name_key' => 'common_excel_export', 'sort' => 160],
        ], ['action_id', 'module_id'], ['action_name_key', 'sort']);

        DB::table('phppos_permissions')->upsert([
            ['module_id' => 'config', 'person_id' => 1],
            ['module_id' => 'employees', 'person_id' => 1],
        ], ['module_id', 'person_id'], []);

        DB::table('phppos_permissions_actions')->upsert([
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'add_update'],
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'delete'],
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'search'],
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'edit_profile'],
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'assign_all_locations'],
            ['module_id' => 'employees', 'person_id' => 1, 'action_id' => 'excel_export'],
        ], ['module_id', 'person_id', 'action_id'], []);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('phppos_permissions_actions')
            ->where('module_id', 'employees')
            ->where('person_id', 1)
            ->whereIn('action_id', ['add_update', 'delete', 'search', 'edit_profile', 'assign_all_locations', 'excel_export'])
            ->delete();

        DB::table('phppos_permissions')
            ->where('person_id', 1)
            ->whereIn('module_id', ['config', 'employees'])
            ->delete();

        DB::table('phppos_modules_actions')
            ->where('module_id', 'employees')
            ->whereIn('action_id', ['add_update', 'delete', 'search', 'edit_profile', 'assign_all_locations', 'excel_export'])
            ->delete();

        DB::table('phppos_modules')
            ->whereIn('module_id', ['config', 'employees'])
            ->delete();
    }
};
