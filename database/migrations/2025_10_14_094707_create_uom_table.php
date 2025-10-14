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
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id('uom_id');

            // SAP-oriented fields
            $table->string('uom_code', 10)->unique();   // e.g. "EA", "KG", "LTR"
            $table->string('uom_name', 100);            // e.g. "Each", "Kilogram", "Litre"
            $table->string('uom_category', 50)->nullable(); // e.g. "Weight", "Volume", "Count"

            // Optional SAP reference
            $table->string('sap_uom_reference', 20)->nullable(); // for mapping with SAP UoM codes

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
