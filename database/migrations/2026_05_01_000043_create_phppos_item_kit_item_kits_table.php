<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_kit_item_kits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_kit_id');
            $table->unsignedBigInteger('item_kit_item_kit');
            $table->decimal('quantity', 23, 10)->default(1);
            $table->timestamps();

            $table->foreign('item_kit_id', 'kit_kit_parent_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
            $table->foreign('item_kit_item_kit', 'kit_kit_child_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_kit_item_kits');
    }
};
