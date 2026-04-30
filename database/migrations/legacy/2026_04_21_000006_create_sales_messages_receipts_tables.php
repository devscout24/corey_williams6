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
        Schema::create('phppos_sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('sale_type', 30)->default('sale');
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->decimal('amount_tendered', 23, 10)->default(0);
            $table->decimal('change_due', 23, 10)->default(0);
            $table->string('customer_name')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
        });

        Schema::create('phppos_sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity_purchased', 15, 3);
            $table->decimal('item_unit_price', 23, 10);
            $table->decimal('line_total', 23, 10);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('phppos_sales');
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
        });

        Schema::create('phppos_sales_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('payment_type', 60)->default('Cash');
            $table->decimal('payment_amount', 23, 10);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('phppos_sales');
        });

        Schema::create('phppos_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->string('subject', 255);
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('person_id')->on('phppos_employees');
        });

        Schema::create('phppos_message_receiver', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('receiver_id');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'receiver_id']);
            $table->foreign('message_id')->references('id')->on('phppos_messages');
            $table->foreign('receiver_id')->references('person_id')->on('phppos_employees');
        });

        DB::table('phppos_modules')->upsert([
            ['module_id' => 'sales', 'name_lang_key' => 'module_sales', 'desc_lang_key' => 'module_sales_desc', 'sort' => 50, 'icon' => 'ti-shopping-cart'],
            ['module_id' => 'messages', 'name_lang_key' => 'module_messages', 'desc_lang_key' => 'module_messages_desc', 'sort' => 60, 'icon' => 'ti-email'],
        ], ['module_id'], ['name_lang_key', 'desc_lang_key', 'sort', 'icon']);

        DB::table('phppos_module_submodules')->upsert([
            ['module_id' => 'sales', 'submodule_key' => 'sales_register', 'label' => 'Sales Register', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'sales', 'submodule_key' => 'receipts', 'label' => 'Receipts', 'sort' => 20, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'messages', 'submodule_key' => 'inbox', 'label' => 'Inbox', 'sort' => 10, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['module_id' => 'messages', 'submodule_key' => 'compose', 'label' => 'Compose', 'sort' => 20, 'enabled' => 1, 'created_at' => now(), 'updated_at' => now()],
        ], ['module_id', 'submodule_key'], ['label', 'sort', 'enabled', 'updated_at']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_message_receiver');
        Schema::dropIfExists('phppos_messages');
        Schema::dropIfExists('phppos_sales_payments');
        Schema::dropIfExists('phppos_sales_items');
        Schema::dropIfExists('phppos_sales');
    }
};
