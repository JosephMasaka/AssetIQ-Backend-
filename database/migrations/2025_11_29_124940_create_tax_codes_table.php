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
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();      // SAP Style e.g. V0, A1, ZR
            $table->string('description');
            $table->enum('tax_type', [
                'INPUT_VAT',      // For procurement (SAP: Input Tax)
                'OUTPUT_VAT',     // For sales (SAP: Output Tax)
                'WITHHOLDING',    // Supplier tax
                'EXEMPT',         // Zero-rated
                'NONE'
            ])->default('INPUT_VAT');

            $table->decimal('rate', 5, 2)->default(0.00); // VAT % e.g. 16.00

            // Mapping to GL accounts (SAP: Automatic Account Determination)
            $table->unsignedBigInteger('gl_tax_account_id')->nullable();
            $table->unsignedBigInteger('gl_offset_account_id')->nullable();

            // Country / jurisdiction
            $table->string('country')->default('KE'); // Kenya default
            $table->boolean('active')->default(true);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('gl_tax_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
            $table->foreign('gl_offset_account_id')->references('id')->on('gl_accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};
