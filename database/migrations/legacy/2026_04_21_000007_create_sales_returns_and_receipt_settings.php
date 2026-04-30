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
        Schema::create('phppos_sales_item_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity_returned', 15, 3);
            $table->unsignedBigInteger('employee_id');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('phppos_sales');
            $table->foreign('sale_item_id')->references('id')->on('phppos_sales_items');
            $table->foreign('item_id')->references('item_id')->on('phppos_items');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
                $table->index(['sale_id', 'sale_item_id']);
        });

        Schema::create('phppos_receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->default('Store Receipt');
            $table->string('footer', 255)->default('Thank you');
            $table->string('paper_size', 20)->default('80mm');
            $table->boolean('show_cashier')->default(true);
            $table->boolean('show_customer')->default(true);
            $table->timestamps();
        });

        DB::table('phppos_receipt_settings')->insert([
            'id' => 1,
            'title' => 'Store Receipt',
            'footer' => 'Thank you',
            'paper_size' => '80mm',
            'show_cashier' => 1,
            'show_customer' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_item_returns');
        Schema::dropIfExists('phppos_receipt_settings');
    }
};
