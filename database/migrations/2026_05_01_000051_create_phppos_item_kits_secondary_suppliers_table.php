<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kits_secondary_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_kit_id')
                ->constrained('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreignId('supplier_id')
                ->constrained('phppos_suppliers', 'person_id')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_secondary_suppliers');
    }
};
