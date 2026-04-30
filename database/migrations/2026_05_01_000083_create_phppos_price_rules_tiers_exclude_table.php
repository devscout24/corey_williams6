<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules_tiers_exclude', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_rule_id');
            $table->unsignedBigInteger('tier_id');

            $table->foreign('price_rule_id', 'price_rules_tiers_rule_fk')
                ->references('id')
                ->on('phppos_price_rules')
                ->cascadeOnDelete();
            $table->foreign('tier_id', 'price_rules_tiers_tier_fk')
                ->references('id')
                ->on('phppos_price_tiers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules_tiers_exclude');
    }
};
