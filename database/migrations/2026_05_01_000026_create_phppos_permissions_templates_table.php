<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_permissions_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->boolean('deleted')->default(false);

            $table->index('deleted');
            $table->index('name');
            $table->index(['name', 'deleted'], 'name_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_permissions_templates');
    }
};
