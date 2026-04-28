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
        // Secondary Categories for Items
        Schema::create('phppos_items_secondary_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('category_id');
            $table->foreign('item_id')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('phppos_categories')->cascadeOnDelete();
        });

        // Secondary Suppliers for Items
        Schema::create('phppos_items_secondary_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('supplier_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('supplier_id');
            $table->foreign('item_id')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('person_id')->on('phppos_suppliers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_items_secondary_suppliers');
        Schema::dropIfExists('phppos_items_secondary_categories');
    }
};
