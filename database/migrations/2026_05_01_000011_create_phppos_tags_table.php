<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_tags', function (Blueprint $table) {
            $table->id();
            $table->string('ecommerce_tag_id', 255)->nullable();
            $table->timestamp('last_modified')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('deleted')->default(false);
            $table->string('name', 255)->unique();
            $table->timestamps();

            $table->index('deleted');
            $table->index('name');
            $table->unique(['name', 'deleted'], 'tag_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_tags');
    }
};
