<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_requests', function (Blueprint $table) {
            $table->id();

            // The company requesting the plan change
            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('users')->onDelete('cascade');

            // The reseller who manages this company (nullable — direct companies have none)
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->foreign('reseller_id')->references('id')->on('users')->onDelete('set null');

            // Current plan and requested plan
            $table->unsignedBigInteger('current_plan_id')->nullable();
            $table->foreign('current_plan_id')->references('id')->on('plans')->onDelete('set null');

            $table->unsignedBigInteger('requested_plan_id');
            $table->foreign('requested_plan_id')->references('id')->on('plans')->onDelete('cascade');

            // Status lifecycle: pending → approved | rejected → cancelled
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending');

            // Optional coupon applied at request time
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Who acted on it and when
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('reviewed_at')->nullable();

            // Free-text fields
            $table->text('reason')->nullable();       // why the company wants this plan
            $table->text('rejection_note')->nullable(); // reviewer's rejection reason

            // Billing cycle preference
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');

            // Snapshot of the plan price at request time (avoids plan price drift)
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->string('currency', 10)->default('KSH');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common dashboard queries
            $table->index(['company_id', 'status']);
            $table->index(['reseller_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_requests');
    }
};