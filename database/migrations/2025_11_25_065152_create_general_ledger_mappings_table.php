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
        Schema::create('gl_mappings', function (Blueprint $table) {
            $table->id();

            // $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('asset_class_id')->index();

            // Transaction: acquisition, depreciation, disposal, etc.
            $table->string('transaction_type');

            // GL Accounts
            $table->unsignedBigInteger('debit_gl_id')->nullable()->index();
            $table->unsignedBigInteger('credit_gl_id')->nullable()->index();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_mappings');
    }
};
