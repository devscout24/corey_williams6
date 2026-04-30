<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_registers', function (Blueprint $table) {
            $table->id('register_id');
            $table->unsignedBigInteger('location_id');
            $table->string('name', 255);
            $table->string('iptran_device_id', 255)->nullable();
            $table->string('emv_terminal_id', 255)->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('card_connect_hsn', 255)->nullable();
            $table->string('emv_pinpad_ip', 255)->nullable();
            $table->string('emv_pinpad_port', 255)->nullable();
            $table->boolean('enable_tips')->default(false);

            $table->index('deleted');
            $table->index('location_id');
            $table->foreign('location_id', 'registers_location_fk')
                ->references('location_id')
                ->on('phppos_locations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_registers');
    }
};
