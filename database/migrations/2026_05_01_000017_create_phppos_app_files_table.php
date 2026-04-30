<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_app_files', function (Blueprint $table) {
            $table->id('file_id');
            $table->string('file_name', 255);
            $table->binary('file_data');
            $table->timestamp('timestamp')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('expires')->nullable();

            $table->index('expires');
            $table->index('file_name');
            $table->index('timestamp');
            $table->index(['file_name', 'timestamp'], 'filename_timestamp');
        });

        // Ensure the legacy CI3 LONGBLOB size is honored.
        DB::statement('ALTER TABLE phppos_app_files MODIFY file_data LONGBLOB');
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_app_files');
    }
};
