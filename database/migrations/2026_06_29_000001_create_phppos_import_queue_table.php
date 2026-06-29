<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_import_queue', function (Blueprint $table) {
            $table->id();
            $table->string('import_batch', 36);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_number')->nullable();
            $table->string('product_id', 255)->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('existing_cost_price', 23, 10)->default(0);
            $table->decimal('existing_unit_price', 23, 10)->default(0);
            $table->decimal('existing_quantity', 23, 10)->default(0);
            $table->decimal('incoming_cost_price', 23, 10)->default(0);
            $table->decimal('incoming_unit_price', 23, 10)->default(0);
            $table->decimal('incoming_quantity', 23, 10)->default(0);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();

            $table->index(['import_batch', 'status']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_import_queue');
    }
};
