<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_receivings_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receiving_id');
            $table->string('payment_type', 255);
            $table->decimal('payment_amount', 23, 10);
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();

            $table->foreign('receiving_id', 'receivings_payments_receiving_fk')
                ->references('receiving_id')
                ->on('phppos_receivings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_receivings_payments');
    }
};
