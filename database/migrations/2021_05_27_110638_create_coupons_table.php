<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            // Coupon details
            $table->string('code')->unique();
            $table->string('name');

            // fixed | percentage
            $table->enum('type', [
                'fixed',
                'percentage',
            ])->default('percentage');

            // Discount amount
            $table->decimal('value', 12, 2);

            // Optional limits
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);

            // Minimum purchase amount
            $table->decimal('minimum_amount', 12, 2)
                ->nullable();

            // Optional max discount for percentage coupons
            $table->decimal('maximum_discount', 12, 2)
                ->nullable();

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Restrict to plans if needed
            $table->json('plan_ids')->nullable();

            // Multi-tenant support
            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Status
            $table->boolean('is_active')
                ->default(true);

            $table->text('description')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};