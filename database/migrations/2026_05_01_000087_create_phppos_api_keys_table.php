<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->integer('level')->default(0);
            $table->boolean('ignore_limits')->default(0);
            $table->boolean('is_private_key')->default(0);
            $table->text('ip_addresses')->nullable();
            $table->string('key_ending', 7)->nullable();
            $table->integer('date_created')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_api_keys');
    }
};
