<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules_manufacturers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->integer('manufacturer_id');

            $table->index('rule_id');
            $table->index('manufacturer_id');
            $table->foreign('rule_id', 'price_rules_manu_rule_fk')
                ->references('id')
                ->on('phppos_price_rules')
                ->cascadeOnDelete();
            $table->foreign('manufacturer_id', 'price_rules_manu_fk')
                ->references('id')
                ->on('phppos_manufacturers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules_manufacturers');
    }
};
