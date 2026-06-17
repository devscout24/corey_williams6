<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_transfers', function (Blueprint $table) {
            $table->string('internal_code', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('phppos_transfers', function (Blueprint $table) {
            $table->dropColumn('internal_code');
        });
    }
};
