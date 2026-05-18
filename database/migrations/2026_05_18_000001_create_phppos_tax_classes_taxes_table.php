<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_tax_classes_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedBigInteger('tax_class_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);

            $table->unique(['tax_class_id', 'name', 'percent'], 'tax_class_taxes_unique');
            $table->foreign('tax_class_id', 'tax_class_taxes_class_fk')
                ->references('id')
                ->on('phppos_tax_classes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_tax_classes_taxes');
    }
};
