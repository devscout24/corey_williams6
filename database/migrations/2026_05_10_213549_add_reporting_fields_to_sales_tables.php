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
        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->decimal('tax', 23, 10)->default(0)->after('total');
            $table->decimal('profit', 23, 10)->default(0)->after('tax');
            $table->string('payment_type')->nullable()->after('customer_name');
            $table->tinyInteger('deleted')->default(0)->after('comment');
            $table->decimal('tip', 23, 10)->default(0)->after('deleted');
            $table->string('ecommerce_order_id')->nullable()->after('tip');
            $table->string('cc_ref_no')->nullable()->after('ecommerce_order_id');
        });

        Schema::table('phppos_sales_items', function (Blueprint $table) {
            $table->decimal('tax', 23, 10)->default(0)->after('line_total');
            $table->decimal('profit', 23, 10)->default(0)->after('tax');
            $table->decimal('discount_percent', 15, 3)->default(0)->after('profit');
            $table->decimal('subtotal', 23, 10)->default(0)->after('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phppos_sales', function (Blueprint $table) {
            $table->dropColumn(['tax', 'profit', 'payment_type', 'deleted', 'tip', 'ecommerce_order_id', 'cc_ref_no']);
        });

        Schema::table('phppos_sales_items', function (Blueprint $table) {
            $table->dropColumn(['tax', 'profit', 'discount_percent', 'subtotal']);
        });
    }
};
