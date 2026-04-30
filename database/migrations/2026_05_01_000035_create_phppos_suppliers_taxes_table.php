<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_suppliers_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('supplier_id', 'suppliers_taxes_supplier_fk')
                ->references('person_id')
                ->on('phppos_suppliers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_suppliers_taxes');
    }
};
