<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // relation to vendor
            $table->unsignedBigInteger('vendor_id');

            // relation to PO (optional — some invoices reference multiple POs)
            $table->unsignedBigInteger('po_id')->nullable();

            // SAP invoice fields
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('posting_date')->nullable(); // accounting posting date

            // invoice financial fields
            $table->decimal('total_amount', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            // document status
            $table->enum('status', [
                'draft',
                'posted',
                'partially_paid',
                'paid',
                'cancelled'
            ])->default('draft');
            
            // cross-reference (SAP RBKP-BELNR)
            $table->string('reference')->nullable();

            $table->string('currency', 10)->default('KES');

            // audit fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
