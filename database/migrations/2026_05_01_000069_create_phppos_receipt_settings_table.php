<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->default('Store Receipt');
            $table->string('footer', 255)->default('Thank you');
            $table->string('paper_size', 20)->default('80mm');
            $table->boolean('show_cashier')->default(true);
            $table->boolean('show_customer')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_receipt_settings');
    }
};
