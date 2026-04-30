<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('added_on')->nullable()->useCurrent();
            $table->boolean('active')->default(true);
            $table->boolean('deleted')->default(false);
            $table->string('type', 255);
            $table->decimal('items_to_buy', 23, 10)->nullable();
            $table->decimal('items_to_get', 23, 10)->nullable();
            $table->decimal('percent_off', 23, 10)->nullable();
            $table->decimal('fixed_off', 23, 10)->nullable();
            $table->decimal('spend_amount', 23, 10)->nullable();
            $table->integer('num_times_to_apply');
            $table->string('coupon_code', 255)->nullable();
            $table->decimal('coupon_spend_amount', 23, 10)->nullable();
            $table->boolean('mix_and_match')->default(false);
            $table->boolean('disable_loyalty_for_rule')->default(false);
            $table->boolean('show_on_receipt')->default(false);
            $table->text('description')->nullable();

            $table->index('name');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_price_rules');
    }
};
