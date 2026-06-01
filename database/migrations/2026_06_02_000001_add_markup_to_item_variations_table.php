<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phppos_item_variations', function (Blueprint $table) {
            $table->decimal('markup', 23, 10)->default(0)->after('cost_price');
            $table->string('markup_type', 50)->default('flat')->after('markup');
        });
    }

    public function down(): void
    {
        Schema::table('phppos_item_variations', function (Blueprint $table) {
            $table->dropColumn(['markup', 'markup_type']);
        });
    }
};
