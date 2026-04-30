<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('category_id');

            $table->foreign('rule_id', 'price_rules_categories_rule_fk')
                ->references('id')
                ->on('phppos_price_rules')
                ->cascadeOnDelete();
            $table->foreign('category_id', 'price_rules_categories_cat_fk')
                ->references('id')
                ->on('phppos_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules_categories');
    }
};
