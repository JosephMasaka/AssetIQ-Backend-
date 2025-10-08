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
        Schema::create('asset_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->decimal('acquisition_value', 15, 2)->nullable();
            $table->decimal('book_value', 15, 2)->nullable();
            $table->decimal('residual_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('useful_life_years')->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable(); // %
            $table->string('depreciation_key')->nullable(); // SAP depreciation key
            $table->integer('company_id');
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_valuations');
    }
};
