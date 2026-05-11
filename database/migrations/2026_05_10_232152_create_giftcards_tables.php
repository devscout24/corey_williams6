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
        Schema::create('phppos_giftcards', function (Blueprint $table) {
            $table->id('giftcard_id');
            $table->string('giftcard_number', 255)->unique();
            $table->text('description')->nullable();
            $table->decimal('value', 23, 10);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->integer('inactive')->default(0);
            $table->integer('deleted')->default(0);
            $table->timestamps();

            $table->foreign('customer_id')->references('person_id')->on('phppos_customers');
        });

        Schema::create('phppos_giftcards_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('log_date')->useCurrent();
            $table->unsignedBigInteger('giftcard_id');
            $table->decimal('transaction_amount', 23, 10);
            $table->text('log_message');
            $table->timestamps();

            $table->foreign('giftcard_id')->references('giftcard_id')->on('phppos_giftcards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_giftcards_log');
        Schema::dropIfExists('phppos_giftcards');
    }
};
