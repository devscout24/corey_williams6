<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_people_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->unsignedBigInteger('person_id');

            $table->foreign('file_id', 'people_files_file_fk')->references('file_id')->on('phppos_app_files');
            $table->index('file_id');
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_people_files');
    }
};
