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
        Schema::create('requisition_vendors', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('requisition_id');
            $table->unsignedBigInteger('vendor_id');

            // Vendor contact info at the time of invitation (snapshot)
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // RFQ-related fields
            $table->string('rfq_number')->nullable()->index();
            $table->date('rfq_date')->nullable();
            $table->date('response_deadline')->nullable();

            // Quotation details
            $table->decimal('quoted_amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['invited', 'responded', 'shortlisted', 'awarded', 'rejected', 'not yet invited'])->default('not yet invited');

            $table->string('public_token')->nullable()->unique();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('requisition_id')
                  ->references('id')->on('requisitions')
                  ->onDelete('cascade');

            $table->foreign('vendor_id')
                  ->references('id')->on('vendors')
                  ->onDelete('cascade');

            // Prevent duplicate vendor invitations for the same requisition
            $table->unique(['requisition_id', 'vendor_id'], 'unique_requisition_vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_vendors');
    }
};
