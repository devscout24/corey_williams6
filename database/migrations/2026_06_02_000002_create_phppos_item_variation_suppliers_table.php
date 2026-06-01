<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_variation_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_variation_id');
            $table->unsignedBigInteger('supplier_id');
            $table->timestamps();

            $table->index('item_variation_id');
            $table->index('supplier_id');
            $table->foreign('item_variation_id', 'var_sup_var_fk')
                ->references('id')
                ->on('phppos_item_variations')
                ->cascadeOnDelete();
            $table->foreign('supplier_id', 'var_sup_sup_fk')
                ->references('person_id')
                ->on('phppos_suppliers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_variation_suppliers');
    }
};
