<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comprehensive Audit Log
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // created, updated, deleted, viewed, exported, approved, rejected
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->string('module')->nullable(); // assets, procurement, finance, maintenance
            $table->unsignedBigInteger('company_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['company_id', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Document Management
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('document_type'); // contract, invoice, certificate, policy, manual, report
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('documentable_type')->nullable();
            $table->unsignedBigInteger('documentable_id')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('active'); // active, expired, archived, deleted
            $table->boolean('is_confidential')->default(false);
            $table->json('access_permissions')->nullable();
            $table->string('version')->default('1.0');
            $table->foreignId('parent_document_id')->nullable()->constrained('documents');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            });
        }

        // Compliance Requirements
        if (!Schema::hasTable('compliance_requirements')) {
            Schema::create('compliance_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('regulation_type'); // ISO, SOX, IFRS, GDPR, local, industry
            $table->string('category'); // financial, environmental, safety, data_privacy, quality
            $table->string('frequency'); // one_time, monthly, quarterly, annual
            $table->date('start_date');
            $table->date('next_due_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users');
            $table->string('status')->default('active'); // active, completed, waived, not_applicable
            $table->text('evidence_required')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Compliance Submissions
        if (!Schema::hasTable('compliance_submissions')) {
            Schema::create('compliance_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_requirement_id')->constrained('compliance_requirements');
            $table->string('submission_number')->unique();
            $table->date('submission_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected, under_review
            $table->text('findings')->nullable();
            $table->text('actions_taken')->nullable();
            $table->json('evidence_documents')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
            });
        }

        // Asset Audits
        if (!Schema::hasTable('asset_audits')) {
            Schema::create('asset_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number')->unique();
            $table->string('audit_type'); // physical_verification, financial_audit, compliance_audit, cycle_count
            $table->date('audit_date');
            $table->date('scheduled_date');
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->foreignId('auditor_id')->constrained('users');
            $table->string('scope')->nullable(); // location, category, all_assets
            $table->json('scope_filters')->nullable();
            $table->integer('assets_expected')->default(0);
            $table->integer('assets_found')->default(0);
            $table->integer('assets_missing')->default(0);
            $table->integer('discrepancies')->default(0);
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('attachments')->nullable();
            $table->date('completed_date')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            });
        }

        // Asset Audit Items
        if (!Schema::hasTable('asset_audit_items')) {
            Schema::create('asset_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_audit_id')->constrained('asset_audits')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets');
            $table->string('expected_location')->nullable();
            $table->string('actual_location')->nullable();
            $table->string('expected_custodian')->nullable();
            $table->string('actual_custodian')->nullable();
            $table->string('condition')->nullable(); // excellent, good, fair, poor, damaged
            $table->string('verification_status'); // found, missing, damaged, surplus
            $table->boolean('has_discrepancy')->default(false);
            $table->text('discrepancy_notes')->nullable();
            $table->json('photos')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            });
        }

        // User Activity Sessions
        if (!Schema::hasTable('user_sessions')) {
            Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('session_id')->unique();
            $table->string('ip_address', 45);
            $table->string('user_agent');
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->timestamp('login_at')->useCurrent();
            $table->timestamp('logout_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->integer('duration_minutes')->nullable();
            $table->integer('actions_count')->default(0);
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->index(['user_id', 'login_at']);
            });
        }

        // Data Export Logs (for GDPR compliance)
        if (!Schema::hasTable('data_export_logs')) {
            Schema::create('data_export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('export_type'); // report, data_extract, user_request
            $table->string('entity_type')->nullable();
            $table->json('filters')->nullable();
            $table->integer('records_exported');
            $table->string('file_format'); // csv, excel, pdf, json
            $table->string('file_path')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->string('ip_address', 45);
            $table->unsignedBigInteger('company_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['requested_by', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('data_export_logs');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('asset_audit_items');
        Schema::dropIfExists('asset_audits');
        Schema::dropIfExists('compliance_submissions');
        Schema::dropIfExists('compliance_requirements');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('audit_logs');
    }
};
