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

            // ── Ownership ────────────────────────────────────────────────────
            $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // ── Identity ─────────────────────────────────────────────────────
            $table->string('name');                         // e.g. "Laserjet Toner (black)"
            $table->string('item_number')->nullable();      // internal item no.
            $table->string('model_number')->nullable();     // manufacturer model
            $table->string('category')->nullable();         // e.g. "Printer Ink"

            // ── Procurement ──────────────────────────────────────────────────
            $table->string('order_number')->nullable();
            $table->string('supplier')->nullable();         // vendor/supplier name
            $table->date('purchase_date')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('currency', 10)->default('KES');
            $table->string('location')->nullable();

            // ── Quantity tracking ────────────────────────────────────────────
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('remaining_quantity')->default(0);
            $table->unsignedInteger('min_quantity')->default(1); // low-stock threshold

            // ── Status & notes ───────────────────────────────────────────────
            $table->enum('status', ['available', 'low_stock', 'out_of_stock', 'discontinued'])
                  ->default('available');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};