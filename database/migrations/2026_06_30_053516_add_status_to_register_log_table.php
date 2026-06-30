<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_register_log', function (Blueprint $table) {
            $table->string('status', 50)->default('open')->after('notes');
        });

        DB::table('phppos_register_log')
            ->whereNotNull('shift_end')
            ->update(['status' => 'closed']);

        DB::table('phppos_register_log')
            ->whereNull('shift_end')
            ->update(['status' => 'open']);
    }

    public function down(): void
    {
        Schema::table('phppos_register_log', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
