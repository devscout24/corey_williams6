<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->ulid('ulid')->nullable()->unique();
            $table->string('name');
            $table->string('address_1')->default('');
            $table->string('address_2')->default('');
            $table->string('city')->default('');
            $table->string('state')->default('');
            $table->string('zip')->default('');
            $table->string('country')->default('');
            $table->string('phone')->default('');
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_locations');
    }
};
