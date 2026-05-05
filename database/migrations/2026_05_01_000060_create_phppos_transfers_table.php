<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('transfer_type', ['out', 'in']);
            $table->unsignedBigInteger('from_location_id');
            $table->unsignedBigInteger('to_location_id');
            $table->unsignedBigInteger('parent_transfer_id')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->unsignedBigInteger('created_by_person_id')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('external_source', 100)->nullable();
            $table->string('external_transfer_id', 100)->nullable();
            $table->timestamps();

            $table->foreign('from_location_id', 'transfers_from_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('to_location_id', 'transfers_to_loc_fk')->references('location_id')->on('phppos_locations');
            $table->foreign('parent_transfer_id', 'transfers_parent_fk')->references('id')->on('phppos_transfers');
            $table->foreign('created_by_person_id', 'transfers_created_by_fk')->references('person_id')->on('phppos_employees');

            $table->unique(['transfer_type', 'external_source', 'external_transfer_id'], 'transfers_external_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_transfers');
    }
};
