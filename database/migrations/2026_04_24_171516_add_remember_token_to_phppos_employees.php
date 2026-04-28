<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phppos_employees', function (Blueprint $table) {
            $table->rememberToken()->after('secret_key_2fa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_employees', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
