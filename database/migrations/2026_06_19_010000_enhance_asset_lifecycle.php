<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new lifecycle fields to assets table
        Schema::table('assets', function (Blueprint $table) {
            $table->string('lifecycle_status')->default('in_use')->after('status')
                ->comment('in_use, available, assigned, under_maintenance, retired, disposed, donated, sold, stolen, lost');
            $table->date('warranty_start_date')->nullable()->after('acquisition_date');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date');
            $table->date('disposal_date')->nullable();
            $table->string('disposal_method')->nullable()->comment('sold, donated, recycled, destroyed');
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->text('disposal_notes')->nullable();
            $table->unsignedBigInteger('disposed_by')->nullable();
            $table->date('retirement_date')->nullable();
            $table->text('retirement_reason')->nullable();
            $table->decimal('residual_value', 15, 2)->nullable();
            $table->decimal('salvage_value', 15, 2)->nullable();
            $table->integer('useful_life_years')->nullable();
            $table->date('expected_eol_date')->nullable()->comment('Expected end of life date');
        });

        // Create asset disposal table for detailed tracking
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('disposal_number')->unique();
            $table->date('disposal_date');
            $table->string('disposal_method'); // sold, donated, recycled, destroyed, traded
            $table->decimal('book_value', 15, 2)->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->decimal('gain_loss', 15, 2)->nullable();
            $table->foreignId('buyer_vendor_id')->nullable()->constrained('vendors');
            $table->string('authorization_number')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users');
            $table->date('authorization_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('certificate_of_destruction')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // Create asset transfer table
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->date('transfer_date');
            $table->string('from_location');
            $table->string('to_location');
            $table->unsignedBigInteger('from_custodian')->nullable();
            $table->unsignedBigInteger('to_custodian')->nullable();
            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->string('from_company_code')->nullable();
            $table->string('to_company_code')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, in_transit, completed, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->date('approval_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('from_custodian')->references('id')->on('users');
            $table->foreign('to_custodian')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('asset_disposals');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_status',
                'warranty_start_date',
                'warranty_end_date',
                'disposal_date',
                'disposal_method',
                'disposal_value',
                'disposal_notes',
                'disposed_by',
                'retirement_date',
                'retirement_reason',
                'residual_value',
                'salvage_value',
                'useful_life_years',
                'expected_eol_date'
            ]);
        });
    }
};
