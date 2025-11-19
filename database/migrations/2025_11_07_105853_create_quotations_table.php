<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            // --- Foreign relations ---
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('rfq_id')->nullable();  // RequisitionVendor row (RFQ)
            $table->unsignedBigInteger('vendor_id');

            // --- SAP-like quotation identifier ---
            $table->string('quotation_number')->unique();  // Example: QT-2025-00012

            // --- Vendor details (copied in case vendor is modified later) ---
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // --- Dates ---
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();  // like SAP: QUOTATION VALIDITY

            // --- Commercial Details ---
            $table->string('currency', 10)->default('KES');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);

            // --- Delivery & Payment Terms ---
            $table->string('delivery_terms')->nullable();    // e.g. "Within 14 days"
            $table->string('payment_terms')->nullable();     // e.g. "30% deposit, 70% on delivery"
            $table->string('delivery_location')->nullable(); // e.g. "Main Warehouse"

            // --- Status workflow (SAP-style) ---
            $table->enum('status', [
                'draft',       // Saved by vendor but not submitted
                'submitted',   // Vendor submitted quotation
                'reviewed',    // Procurement team reviewed
                'shortlisted', // Shortlisted for negotiation/award
                'awarded',     // Vendor awarded
                'rejected'     // Rejected
            ])->default('draft');

            // --- Security for vendor public access ---
            $table->string('public_token')->unique()->nullable();

            // --- System info ---
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            // --- Foreign Keys ---
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('rfq_id')->references('id')->on('requisition_vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
