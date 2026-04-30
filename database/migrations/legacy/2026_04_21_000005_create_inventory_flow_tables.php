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
        Schema::create('phppos_location_items', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();

            $table->primary(['location_id', 'item_id']);
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
        });

        Schema::create('phppos_transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('transfer_type', ['out', 'in']);
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('to_location_id');
            $table->unsignedBigInteger('parent_transfer_id')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->unsignedBigInteger('created_by_person_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('from_location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('to_location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('parent_transfer_id')->references('id')->on('phppos_transfers');
            $table->foreign('created_by_person_id')->references('person_id')->on('phppos_employees');
        });

        Schema::create('phppos_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 15, 3);
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('phppos_transfers');
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
        });

        Schema::create('phppos_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->enum('movement_type', ['receiving', 'return', 'transfer_out', 'transfer_in']);
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('created_by_person_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('item_id')->references('item_id')->on('phppos_items');
            $table->foreign('from_location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('to_location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('created_by_person_id')->references('person_id')->on('phppos_employees');
            $table->index(['movement_type', 'created_at']);
        });

        // Ensure we have at least 2 locations for transfer flows.
        if (! DB::table('phppos_locations')->where('location_id', 2)->exists()) {
            DB::table('phppos_locations')->insert([
                'location_id' => 2,
                'name' => 'Store 2',
                'address_1' => 'Address 2',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('phppos_employees_locations')->where('employee_id', 1)->where('location_id', 2)->exists()) {
            DB::table('phppos_employees_locations')->insert([
                'employee_id' => 1,
                'location_id' => 2,
            ]);
        }

        // Initial inventory: all existing items start with stock at location 1.
        $itemIds = DB::table('phppos_items')->pluck('item_id');
        foreach ($itemIds as $itemId) {
            if (! DB::table('phppos_location_items')->where('location_id', 1)->where('item_id', $itemId)->exists()) {
                DB::table('phppos_location_items')->insert([
                    'location_id' => 1,
                    'item_id' => $itemId,
                    'quantity' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (! DB::table('phppos_location_items')->where('location_id', 2)->where('item_id', $itemId)->exists()) {
                DB::table('phppos_location_items')->insert([
                    'location_id' => 2,
                    'item_id' => $itemId,
                    'quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('phppos_modules')->upsert([
            ['module_id' => 'receivings', 'name_lang_key' => 'module_receivings', 'desc_lang_key' => 'module_receivings_desc', 'sort' => 40, 'icon' => 'ti-truck'],
        ], ['module_id'], ['name_lang_key', 'desc_lang_key', 'sort', 'icon']);

        DB::table('phppos_module_submodules')->upsert([
            ['module_id' => 'receivings', 'submodule_key' => 'receiving', 'label' => 'Receiving', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'receivings', 'submodule_key' => 'returns', 'label' => 'Return', 'sort' => 20, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'receivings', 'submodule_key' => 'transfer_out', 'label' => 'Transfer Out', 'sort' => 30, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'receivings', 'submodule_key' => 'transfer_in_auto', 'label' => 'Transfer In (Auto)', 'sort' => 40, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], ['module_id', 'submodule_key'], ['label', 'sort', 'enabled', 'updated_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_inventory_movements');
        Schema::dropIfExists('phppos_transfer_items');
        Schema::dropIfExists('phppos_transfers');
        Schema::dropIfExists('phppos_location_items');
    }
};
