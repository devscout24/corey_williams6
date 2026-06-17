<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_locations', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('name');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('name');
            $table->string('last_poke_id', 36)->nullable()->after('phppos_location_id');
            $table->timestamp('last_poke_sent_at')->nullable()->after('last_poke_id');
            $table->timestamp('last_poke_received_at')->nullable()->after('last_poke_sent_at');
            $table->timestamp('last_poke_ack_at')->nullable()->after('last_poke_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('phppos_locations', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['slug', 'last_poke_id', 'last_poke_sent_at', 'last_poke_received_at', 'last_poke_ack_at']);
        });
    }
};
