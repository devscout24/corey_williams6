<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_items_secondary_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('supplier_id');
            $table->timestamps();

            $table->index('item_id');
            $table->index('supplier_id');
            $table->foreign('item_id', 'item_sec_sup_item_fk')->references('item_id')->on('phppos_items')->cascadeOnDelete();
            $table->foreign('supplier_id', 'item_sec_sup_sup_fk')->references('person_id')->on('phppos_suppliers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_items_secondary_suppliers');
    }
};
