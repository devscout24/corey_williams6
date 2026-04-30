<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_supplier_store_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('receiving_id')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('transaction_amount', 23, 10)->default(0);
            $table->decimal('balance', 23, 10)->default(0);
            $table->timestamp('date')->useCurrent();
            $table->timestamps();

            $table->foreign('supplier_id', 'supplier_store_supplier_fk')
                ->references('person_id')
                ->on('phppos_suppliers')
                ->cascadeOnDelete();
            $table->foreign('receiving_id', 'supplier_store_receiving_fk')
                ->references('receiving_id')
                ->on('phppos_receivings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_supplier_store_accounts');
    }
};
