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
        Schema::create('depreciation_keys', function (Blueprint $table) {
            $table->id();

            // Multi-tenant support
            // $table->unsignedBigInteger('tenant_id');

            // Depreciation Key (SAP-like)
            $table->string('code', 20);      // e.g. LINR, DB25
            $table->string('name');          // Straight Line, Declining Balance

            // Control flags
            $table->boolean('is_multi_level')->default(false); // multiple rules allowed
            $table->boolean('allow_change')->default(false);   // allow manual change

            // Status
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_keys');
    }
};
