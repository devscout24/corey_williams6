<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_store_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('transaction_amount', 23, 10)->default(0);
            $table->decimal('balance', 23, 10)->default(0);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();

            $table->foreign('customer_id', 'store_acct_customer_fk')
                ->references('person_id')
                ->on('phppos_customers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_store_accounts');
    }
};
