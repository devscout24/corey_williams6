<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->timestamp('order_time')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->text('comment')->nullable();
            $table->boolean('deleted')->default(false);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('suspended')->default(false);
            $table->unsignedBigInteger('location_id');
            $table->string('internal_code', 40)->nullable()->unique();
            $table->decimal('subtotal', 23, 10)->default(0);
            $table->decimal('total', 23, 10)->default(0);
            $table->timestamps();

            $table->foreign('supplier_id')->references('person_id')->on('phppos_suppliers');
            $table->foreign('employee_id')->references('person_id')->on('phppos_employees');
            $table->foreign('location_id')->references('location_id')->on('phppos_locations');
            $table->index('order_time');
            $table->index('deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_orders');
    }
};
