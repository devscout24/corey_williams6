<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules_item_kits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('item_kit_id');

            $table->foreign('rule_id', 'price_rules_kits_rule_fk')
                ->references('id')
                ->on('phppos_price_rules')
                ->cascadeOnDelete();
            $table->foreign('item_kit_id', 'price_rules_kits_kit_fk')
                ->references('id')
                ->on('phppos_item_kits')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules_item_kits');
    }
};
