<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_invoice_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->integer('days_due')->default(0);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_invoice_terms');
    }
};
