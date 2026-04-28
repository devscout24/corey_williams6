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
        Schema::table('phppos_suppliers', function (Blueprint $table) {
            $table->boolean('override_default_tax')->default(false)->after('account_number');
            $table->unsignedBigInteger('tax_class_id')->nullable()->after('override_default_tax');
            $table->decimal('balance', 23, 10)->default(0)->after('tax_class_id');
            $table->unsignedBigInteger('default_term_id')->nullable()->after('balance');
            $table->unsignedBigInteger('image_id')->nullable()->after('default_term_id');
            $table->text('internal_notes')->nullable()->after('image_id');
            
            for ($i = 1; $i <= 10; $i++) {
                $table->text("custom_field_{$i}_value")->nullable();
            }
        });

        Schema::create('phppos_suppliers_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('name', 255);
            $table->decimal('percent', 15, 3);
            $table->boolean('cumulative')->default(false);
            $table->timestamps();

            $table->foreign('supplier_id', 'suppliers_taxes_supplier_fk')
                ->references('person_id')
                ->on('phppos_suppliers')
                ->cascadeOnDelete();
        });
        
        // Create tax_classes if not exists (checked via search previously, assuming it might be needed)
        if (!Schema::hasTable('phppos_tax_classes')) {
            Schema::create('phppos_tax_classes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->boolean('deleted')->default(false);
                $table->timestamps();
            });
        }

        // Create invoice_terms if not exists
        if (!Schema::hasTable('phppos_invoice_terms')) {
            Schema::create('phppos_invoice_terms', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->integer('days_due')->default(0);
                $table->boolean('deleted')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phppos_suppliers_taxes');
        
        Schema::table('phppos_suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'override_default_tax',
                'tax_class_id',
                'balance',
                'default_term_id',
                'image_id',
                'internal_notes',
                'custom_field_1_value',
                'custom_field_2_value',
                'custom_field_3_value',
                'custom_field_4_value',
                'custom_field_5_value',
                'custom_field_6_value',
                'custom_field_7_value',
                'custom_field_8_value',
                'custom_field_9_value',
                'custom_field_10_value',
            ]);
        });
    }
};
