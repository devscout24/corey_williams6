<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_sales_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('payment_type', 60)->default('Cash');
            $table->decimal('payment_amount', 23, 10);
            $table->timestamps();

            $table->foreign('sale_id', 'sales_payments_sale_fk')->references('sale_id')->on('phppos_sales');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_sales_payments');
    }
};
