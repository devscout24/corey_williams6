<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_people', function (Blueprint $table) {
            $table->id('person_id');
            $table->string('first_name')->default('');
            $table->string('last_name')->default('');
            $table->string('full_name')->nullable();
            $table->string('phone_number')->default('');
            $table->string('email')->default('');
            $table->string('address_1')->default('');
            $table->string('address_2')->default('');
            $table->string('city')->default('');
            $table->string('state')->default('');
            $table->string('zip')->default('');
            $table->string('country')->default('');
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamp('create_date')->nullable();
            $table->timestamp('last_modified')->nullable();
            $table->string('title')->nullable();

            $table->index('first_name');
            $table->index('last_name');
            $table->index('phone_number');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_people');
    }
};
