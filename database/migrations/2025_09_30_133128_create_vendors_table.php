<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorsTable extends Migration
{
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            
            // Basic Vendor Information
            $table->string('vendor_code', 20)->unique();
            $table->string('vendor_name', 255);
            $table->string('vendor_type', 50)->default('supplier');
            $table->string('company_reg_number', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            
            // Contact Information
            $table->string('contact_person', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('fax', 50)->nullable();
            
            // Address Information
            $table->string('street', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('region', 50)->nullable();
            
            // Banking Information
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_swift_code', 50)->nullable();
            $table->string('bank_iban', 100)->nullable();
            
            // SAP Fields
            $table->string('vendor_account_group', 50)->default('creditor');
            $table->string('reconciliation_account', 50)->nullable();
            $table->string('sort_key', 50)->nullable();
            $table->string('payment_terms', 50)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            
            // Status
            $table->enum('status', ['active', 'blocked', 'archived'])->default('active');
            $table->enum('block_reason', ['payment', 'delivery', 'quality', 'other'])->nullable();
            $table->boolean('is_one_time_vendor')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->date('approval_date')->nullable();
            
            // User and Company references (just plain columns, no foreign keys)
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->string('company_code', 10)->default('1000');

            // Indexes
            // $table->index(['vendor_code', 'vendor_name', 'vendor_type', 'status', 'company_code']);

            $table->timestamps();
        });

        Schema::create('vendor_company_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id'); // No foreign key constraint
            $table->string('company_code', 10);
            $table->string('purchasing_organization', 10)->nullable();
            $table->string('reconciliation_account', 50)->nullable();
            $table->string('payment_terms', 50)->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            $table->unique(['vendor_id', 'company_code']);
            // $table->index('company_code');
            // NO foreign key constraint here
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_company_codes');
        Schema::dropIfExists('vendors');
    }
}