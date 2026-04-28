<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phppos_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->string('name');
            $table->string('address_1')->default('');
            $table->string('address_2')->default('');
            $table->string('city')->default('');
            $table->string('state')->default('');
            $table->string('zip')->default('');
            $table->string('country')->default('');
            $table->string('phone')->default('');
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('phppos_employees_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('location_id');
            $table->primary(['employee_id', 'location_id']);

            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
        });

        Schema::create('phppos_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->primary();
            $table->string('company_name')->default('');
            $table->string('account_number')->nullable()->unique();
            $table->decimal('balance', 23, 10)->default(0);
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->foreign('person_id')->references('person_id')->on('phppos_people');
        });

        Schema::create('phppos_suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('person_id')->primary();
            $table->string('company_name')->default('');
            $table->string('account_number')->nullable()->unique();
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->foreign('person_id')->references('person_id')->on('phppos_people');
        });

        Schema::create('phppos_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('phppos_categories');
        });

        Schema::create('phppos_tags', function (Blueprint $table) {
            $table->id();

            // $table->integer('id', true);
            $table->string('ecommerce_tag_id', 255)->nullable();
            $table->timestamp('last_modified')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('deleted')->default(false);
            $table->string('name', 255)->unique();
            $table->timestamps();

            $table->index('deleted');
            $table->index('name');
            $table->unique(['name', 'deleted'], 'tag_name');
        });

        Schema::create('phppos_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('text');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('phppos_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->string('name');
            $table->string('item_number')->nullable()->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('cost_price', 23, 10)->default(0);
            $table->decimal('unit_price', 23, 10)->default(0);
            $table->boolean('deleted')->default(false);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('phppos_categories');
        });

        Schema::create('phppos_item_kits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('unit_price', 23, 10)->default(0);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('phppos_price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rule_type', 30)->default('percent');
            $table->decimal('amount', 23, 10)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('phppos_module_submodules', function (Blueprint $table) {
            $table->id();
            $table->string('module_id', 100);
            $table->string('submodule_key', 100);
            $table->string('label', 120);
            $table->integer('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['module_id', 'submodule_key']);
            $table->foreign('module_id')->references('module_id')->on('phppos_modules');
        });

        Schema::create('phppos_item_label_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->unsignedBigInteger('employee_person_id')->nullable();
            $table->decimal('logo_width_mm', 8, 2)->nullable();
            $table->decimal('logo_height_mm', 8, 2)->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->foreign('employee_person_id')->references('person_id')->on('phppos_employees');
        });

        DB::table('phppos_locations')->insert([
            'location_id' => 1,
            'name' => 'Main Store',
            'address_1' => 'Address 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('phppos_employees_locations')->insert([
            'employee_id' => 1,
            'location_id' => 1,
        ]);

        DB::table('phppos_modules')->upsert([
            ['module_id' => 'locations', 'name_lang_key' => 'module_locations', 'desc_lang_key' => 'module_locations_desc', 'sort' => 10, 'icon' => 'ti-map'],
            ['module_id' => 'contacts', 'name_lang_key' => 'module_contacts', 'desc_lang_key' => 'module_contacts_desc', 'sort' => 20, 'icon' => 'ti-id-badge'],
            ['module_id' => 'items', 'name_lang_key' => 'module_items', 'desc_lang_key' => 'module_items_desc', 'sort' => 30, 'icon' => 'ti-package'],
        ], ['module_id'], ['name_lang_key', 'desc_lang_key', 'sort', 'icon']);

        DB::table('phppos_module_submodules')->insert([
            ['module_id' => 'locations', 'submodule_key' => 'locations', 'label' => 'Locations', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'contacts', 'submodule_key' => 'customers', 'label' => 'Customers', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'contacts', 'submodule_key' => 'suppliers', 'label' => 'Suppliers', 'sort' => 20, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'items', 'label' => 'Items', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'item_kits', 'label' => 'Item Kits', 'sort' => 20, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'categories', 'label' => 'Categories', 'sort' => 30, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'tags', 'label' => 'Tags', 'sort' => 40, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'attributes', 'label' => 'Attributes', 'sort' => 50, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'price_rules', 'label' => 'Price Rules', 'sort' => 60, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'items', 'submodule_key' => 'labels', 'label' => 'Labels', 'sort' => 70, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('phppos_categories')->insert([
            'id' => 1,
            'name' => 'Default Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('phppos_items')->insert([
            'item_id' => 1,
            'name' => 'Sample Item',
            'item_number' => 'ITEM-001',
            'category_id' => 1,
            'cost_price' => 5,
            'unit_price' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_item_label_jobs');
        Schema::dropIfExists('phppos_module_submodules');
        Schema::dropIfExists('phppos_price_rules');
        Schema::dropIfExists('phppos_item_kits');
        Schema::dropIfExists('phppos_items');
        Schema::dropIfExists('phppos_attributes');
        Schema::dropIfExists('phppos_tags');
        Schema::dropIfExists('phppos_categories');
        Schema::dropIfExists('phppos_suppliers');
        Schema::dropIfExists('phppos_customers');
        Schema::dropIfExists('phppos_employees_locations');
        Schema::dropIfExists('phppos_locations');
    }
};
