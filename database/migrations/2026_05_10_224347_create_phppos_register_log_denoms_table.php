<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_register_log_denoms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('register_log_id');
            $table->unsignedBigInteger('register_currency_denominations_id');
            $table->integer('count');
            $table->string('type');
            $table->timestamps();

            $table->foreign('register_log_id')->references('register_log_id')->on('phppos_register_log');
            $table->foreign('register_currency_denominations_id', 'reg_curr_den_fk')->references('id')->on('phppos_register_currency_denominations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_register_log_denoms');
    }
};
