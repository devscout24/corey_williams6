<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kits_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);

            $table->foreign('item_kit_id', 'kit_taxes_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kits_taxes');
    }
};
