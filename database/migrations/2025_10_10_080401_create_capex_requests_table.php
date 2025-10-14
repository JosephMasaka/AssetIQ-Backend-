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
        Schema::create('capex_requests', function (Blueprint $table) {
            $table->id('capex_request_id');
            $table->string('capex_number')->unique();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->decimal('budget_amount', 18, 2);
            $table->decimal('committed_amount', 18, 2)->default(0);
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Closed

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
        Schema::dropIfExists('capex_requests');
    }
};
