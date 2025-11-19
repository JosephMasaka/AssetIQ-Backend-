<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();

            // Header reference
            $table->unsignedBigInteger('quotation_id');

            // Original requisition item reference
            $table->unsignedBigInteger('requisition_item_id')->nullable();

            // Item details
            $table->string('item_name')->nullable();
            $table->text('description')->nullable();

            $table->decimal('quantity', 15, 2)->default(1);
            $table->unsignedBigInteger('uom_id')->nullable(); // from unit_of_measure

            // Vendor-provided pricing
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->timestamps();

            // FK
            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('requisition_item_id')->references('id')->on('requisition_items')->onDelete('set null');
            $table->foreign('uom_id')->references('uom_id')->on('units_of_measure')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
