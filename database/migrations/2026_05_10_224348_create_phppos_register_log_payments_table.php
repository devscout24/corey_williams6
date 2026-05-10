<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_register_log_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('register_log_id');
            $table->string('payment_type');
            $table->decimal('open_amount', 23, 10)->default(0);
            $table->decimal('close_amount', 23, 10)->default(0);
            $table->decimal('payment_sales_amount', 23, 10)->default(0);
            $table->decimal('total_payment_additions', 23, 10)->default(0);
            $table->decimal('total_payment_subtractions', 23, 10)->default(0);
            $table->timestamps();

            $table->foreign('register_log_id')->references('register_log_id')->on('phppos_register_log');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_register_log_payments');
    }
};
