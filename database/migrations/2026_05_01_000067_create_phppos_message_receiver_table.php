<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_message_receiver', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('receiver_id');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'receiver_id']);
            $table->foreign('message_id', 'msg_receiver_msg_fk')->references('id')->on('phppos_messages');
            $table->foreign('receiver_id', 'msg_receiver_emp_fk')->references('person_id')->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_message_receiver');
    }
};
