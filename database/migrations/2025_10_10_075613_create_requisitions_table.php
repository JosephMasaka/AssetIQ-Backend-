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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('req_number')->unique(); // Like PR Number
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('requisition_type_id');
            $table->date('request_date');
            $table->string('status')->default('Pending'); // Pending, Approved, ConvertedToPO
            $table->text('justification')->nullable();
            $table->unsignedBigInteger('capex_request_id')->nullable(); // CAPEX linkage
            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('capex_request_id')->references('id')->on('capex_requests')->nullOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->unsignedBigInteger('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
