<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->enum('status', ['pending', 'delivered', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_queue');
    }
};
