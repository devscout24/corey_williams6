<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules_price_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->decimal('item_qty_to_buy', 23, 10)->nullable();
            $table->decimal('discount_per_unit_fixed', 23, 10)->nullable();
            $table->decimal('discount_per_unit_percent', 23, 10)->nullable();

            $table->index('rule_id');
            $table->foreign('rule_id', 'price_rules_breaks_rule_fk')
                ->references('id')
                ->on('phppos_price_rules')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules_price_breaks');
    }
};
