<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Approval workflow templates
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('entity_type'); // requisition, purchase_order, capex_request, asset_disposal, invoice
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_sequential')->default(true); // sequential vs parallel approval
            $table->json('conditions')->nullable(); // amount thresholds, category conditions
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // Approval levels/steps
        Schema::create('approval_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->integer('level_order'); // 1, 2, 3...
            $table->string('level_name'); // e.g., "Department Manager", "Finance Director", "CEO"
            $table->string('approver_type'); // role, user, dynamic (based on department)
            $table->unsignedBigInteger('approver_role_id')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('dynamic_rule')->nullable(); // requester_manager, department_head, etc.
            $table->decimal('amount_threshold_min', 15, 2)->nullable();
            $table->decimal('amount_threshold_max', 15, 2)->nullable();
            $table->boolean('can_delegate')->default(false);
            $table->integer('auto_approve_days')->nullable(); // auto-approve if no action
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->foreign('approver_role_id')->references('id')->on('roles');
            $table->foreign('approver_user_id')->references('id')->on('users');
        });

        // Actual approval instances
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('approval_number')->unique();
            $table->foreignId('workflow_id')->constrained('approval_workflows');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled, expired
            $table->unsignedBigInteger('requested_by');
            $table->date('requested_date');
            $table->integer('current_level')->default(1);
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('users');
            $table->index(['entity_type', 'entity_id']);
        });

        // Individual approval actions
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approval_level_id')->constrained('approval_levels');
            $table->integer('level_order');
            $table->unsignedBigInteger('approver_id'); // assigned approver
            $table->string('action')->nullable(); // approved, rejected, delegated, returned
            $table->unsignedBigInteger('actioned_by')->nullable(); // who actually took action (if delegated)
            $table->timestamp('action_date')->nullable();
            $table->text('comments')->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedBigInteger('delegated_to')->nullable();
            $table->timestamp('delegated_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('approver_id')->references('id')->on('users');
            $table->foreign('actioned_by')->references('id')->on('users');
            $table->foreign('delegated_to')->references('id')->on('users');
        });

        // Add approval tracking to requisitions
        Schema::table('requisitions', function (Blueprint $table) {
            $table->foreignId('approval_request_id')->nullable()->after('status')->constrained('approval_requests');
            $table->string('approval_status')->nullable()->after('approval_request_id'); // pending, approved, rejected
        });

        // Add approval tracking to purchase orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('approval_request_id')->nullable()->after('status')->constrained('approval_requests');
            $table->string('approval_status')->nullable()->after('approval_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['approval_request_id']);
            $table->dropColumn(['approval_request_id', 'approval_status']);
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['approval_request_id']);
            $table->dropColumn(['approval_request_id', 'approval_status']);
        });

        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_levels');
        Schema::dropIfExists('approval_workflows');
    }
};
