<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->string('subject', 255);
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sender_id', 'messages_sender_fk')->references('person_id')->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_messages');
    }
};
