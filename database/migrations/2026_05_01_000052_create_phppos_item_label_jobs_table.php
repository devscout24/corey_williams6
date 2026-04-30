<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phppos_item_label_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->unsignedBigInteger('employee_person_id')->nullable();
            $table->decimal('logo_width_mm', 8, 2)->nullable();
            $table->decimal('logo_height_mm', 8, 2)->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->foreign('employee_person_id', 'label_jobs_emp_fk')
                ->references('person_id')
                ->on('phppos_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phppos_item_label_jobs');
    }
};
