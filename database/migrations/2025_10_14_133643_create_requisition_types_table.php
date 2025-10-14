<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_types', function (Blueprint $table) {
            $table->id();

            // SAP-style Type Code (e.g., NB - Standard PR, FO - Framework Order)
            $table->string('type_code', 10)->unique()->comment('SAP-aligned short code for requisition type');

            // Descriptive Name
            $table->string('name')->comment('Requisition type name, e.g., Material Request, Service Request');

            // Purpose/Category (Material, Service, Maintenance, etc.)
            $table->string('category')->nullable()->comment('General category of requisition type');

            // Flags for specific handling logic
            $table->boolean('requires_approval')->default(true)->comment('If approval workflow is required');
            $table->boolean('is_active')->default(true);

            // Additional fields for integration and references
            $table->string('sap_reference')->nullable()->comment('SAP type reference if integrated');
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_types');
    }
};
