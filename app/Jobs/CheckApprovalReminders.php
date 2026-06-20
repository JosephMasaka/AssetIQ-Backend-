<?php

namespace App\Jobs;

use App\Models\ApprovalAction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckApprovalReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $pendingActions = ApprovalAction::whereNull('action')
            ->whereHas('approvalRequest', function ($query) {
                $query->where('status', 'pending');
            })
            ->with(['approvalRequest', 'approver', 'approvalLevel'])
            ->get();

        foreach ($pendingActions as $action) {
            $daysPending = now()->diffInDays($action->created_at);

            // Send reminder at 3 days, 7 days, and every 7 days after
            if ($daysPending == 3 || $daysPending == 7 || ($daysPending > 7 && $daysPending % 7 == 0)) {
                $this->sendReminder($action);
            }

            // Auto-approve if configured
            if ($action->approvalLevel->auto_approve_days) {
                if ($daysPending >= $action->approvalLevel->auto_approve_days) {
                    $this->autoApprove($action);
                }
            }
        }
    }

    protected function sendReminder(ApprovalAction $action): void
    {
        $daysPending = now()->diffInDays($action->created_at);

        Log::info("Approval reminder sent", [
            'approval_request_id' => $action->approval_request_id,
            'approval_number' => $action->approvalRequest->approval_number,
            'approver_id' => $action->approver_id,
            'approver_name' => $action->approver->name,
            'days_pending' => $daysPending,
        ]);

        $action->update(['reminder_sent_at' => now()]);

        // TODO: Send email reminder to approver
        // TODO: Send in-app notification
    }

    protected function autoApprove(ApprovalAction $action): void
    {
        Log::warning("Approval auto-approved due to timeout", [
            'approval_request_id' => $action->approval_request_id,
            'approval_number' => $action->approvalRequest->approval_number,
            'approver_id' => $action->approver_id,
            'auto_approve_days' => $action->approvalLevel->auto_approve_days,
        ]);

        $action->update([
            'action' => 'approved',
            'actioned_by' => 1, // System
            'action_date' => now(),
            'comments' => 'Auto-approved due to no action within configured timeframe',
        ]);

        // TODO: Trigger approval workflow progression
    }
}
