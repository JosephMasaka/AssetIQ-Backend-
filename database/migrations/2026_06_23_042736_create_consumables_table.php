<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();

            // --- Multi-tenancy ---
            $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();

            // --- Core identity ---
            $table->string('name');
            $table->string('item_no')->nullable()->comment('Internal SKU / material number (SAP MATNR-style)');
            $table->string('category')->nullable();
            $table->string('model_no')->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();

            // --- Vendor / manufacturer reference ---
            $table->string('manufacturer')->nullable();
            $table->string('manufacturer_part_no')->nullable();
            $table->foreignId('primary_vendor_id')->nullable();

            // --- Unit of measure & purchasing ---
            $table->string('unit_of_measure')->default('each');
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->decimal('moving_average_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('KES');

            // --- Stock quantities (SAP MRP-style) ---
            $table->integer('qty')->default(0);
            $table->integer('qty_reserved')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('lot_size')->nullable();
            $table->integer('lead_time_days')->nullable();

            // --- Classification (SAP ABC/XYZ; Maximo criticality) ---
            $table->enum('abc_classification', ['A', 'B', 'C'])->nullable();
            $table->enum('xyz_classification', ['X', 'Y', 'Z'])->nullable();
            $table->enum('criticality', ['high', 'medium', 'low'])->default('medium');

            // --- Condition / lifecycle ---
            $table->string('condition_code')->nullable();
            $table->enum('lifecycle_status', ['active', 'discontinued', 'retired'])->default('active');

            // --- Batch / lot tracking ---
            $table->boolean('is_batch_tracked')->default(false);
            $table->string('batch_no')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();

            // --- Location ---
            $table->foreignId('location_id')->nullable();

            // --- Audit ---
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'lifecycle_status']);
            $table->index(['company_id', 'category']);
            $table->unique(['company_id', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};