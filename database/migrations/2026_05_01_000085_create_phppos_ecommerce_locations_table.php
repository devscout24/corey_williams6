<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_ecommerce_locations', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained('phppos_locations', 'location_id')->cascadeOnDelete();
            
            $table->primary('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_ecommerce_locations');
    }
};
