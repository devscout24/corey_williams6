<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency_symbol', 255)->default('');
            $table->string('currency_code_to', 255)->default('');
            $table->decimal('exchange_rate', 23, 10)->default(1);
            $table->string('currency_symbol_location', 255)->default('before');
            $table->string('number_of_decimals', 255)->default('');
            $table->string('thousands_separator', 255)->default('');
            $table->string('decimal_point', 255)->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_currency_exchange_rates');
    }
};
