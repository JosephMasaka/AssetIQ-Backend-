<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalService
{
    public function initiateApproval($entity, string $entityType, float $amount = null): ApprovalRequest
    {
        $workflow = $this->findApplicableWorkflow($entityType, $amount, $entity->company_id);

        if (!$workflow) {
            throw new Exception("No approval workflow found for {$entityType}");
        }

        DB::beginTransaction();
        try {
            $approvalRequest = ApprovalRequest::create([
                'approval_number' => $this->generateApprovalNumber(),
                'workflow_id' => $workflow->id,
                'entity_type' => $entityType,
                'entity_id' => $entity->id,
                'amount' => $amount,
                'status' => 'pending',
                'requested_by' => auth()->id() ?? $entity->created_by,
                'requested_date' => now(),
                'current_level' => 1,
                'company_id' => $entity->company_id,
            ]);

            $this->createApprovalActions($approvalRequest, $workflow, $entity);

            if ($workflow->requires_sequential) {
                $this->notifyCurrentLevelApprovers($approvalRequest);
            } else {
                $this->notifyAllApprovers($approvalRequest);
            }

            DB::commit();
            return $approvalRequest;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function findApplicableWorkflow(string $entityType, ?float $amount, int $companyId): ?ApprovalWorkflow
    {
        $query = ApprovalWorkflow::where('entity_type', $entityType)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with('levels');

        if ($amount !== null) {
            $query->where(function ($q) use ($amount) {
                $q->whereNull('conditions')
                    ->orWhereRaw("JSON_EXTRACT(conditions, '$.amount_min') IS NULL")
                    ->orWhereRaw("JSON_EXTRACT(conditions, '$.amount_min') <= ?", [$amount]);
            })->where(function ($q) use ($amount) {
                $q->whereNull('conditions')
                    ->orWhereRaw("JSON_EXTRACT(conditions, '$.amount_max') IS NULL")
                    ->orWhereRaw("JSON_EXTRACT(conditions, '$.amount_max') >= ?", [$amount]);
            });
        }

        return $query->first();
    }

    protected function createApprovalActions(ApprovalRequest $approvalRequest, ApprovalWorkflow $workflow, $entity): void
    {
        foreach ($workflow->levels as $level) {
            if ($approvalRequest->amount) {
                if ($level->amount_threshold_min && $approvalRequest->amount < $level->amount_threshold_min) {
                    continue;
                }
                if ($level->amount_threshold_max && $approvalRequest->amount > $level->amount_threshold_max) {
                    continue;
                }
            }

            $approverId = $this->determineApprover($level, $entity);

            if ($approverId) {
                ApprovalAction::create([
                    'approval_request_id' => $approvalRequest->id,
                    'approval_level_id' => $level->id,
                    'level_order' => $level->level_order,
                    'approver_id' => $approverId,
                ]);
            }
        }
    }

    protected function determineApprover($level, $entity): ?int
    {
        if ($level->approver_type === 'user' && $level->approver_user_id) {
            return $level->approver_user_id;
        }

        if ($level->approver_type === 'role' && $level->approver_role_id) {
            $user = User::whereHas('roles', function ($query) use ($level) {
                $query->where('roles.id', $level->approver_role_id);
            })->where('company_id', $entity->company_id)->first();

            return $user?->id;
        }

        if ($level->approver_type === 'dynamic') {
            return $this->resolveDynamicApprover($level->dynamic_rule, $entity);
        }

        return null;
    }

    protected function resolveDynamicApprover(string $rule, $entity): ?int
    {
        switch ($rule) {
            case 'requester_manager':
                return $entity->requestedBy?->manager_id ?? null;
            case 'department_head':
                return $entity->department?->head_user_id ?? null;
            case 'asset_custodian':
                return $entity->responsible_person ?? null;
            default:
                return null;
        }
    }

    public function processApproval(int $actionId, string $action, ?string $comments = null): bool
    {
        DB::beginTransaction();
        try {
            $approvalAction = ApprovalAction::with(['approvalRequest', 'approvalLevel'])->findOrFail($actionId);
            $approvalRequest = $approvalAction->approvalRequest;

            if ($approvalAction->action) {
                throw new Exception('This approval action has already been processed');
            }

            $approvalAction->update([
                'action' => $action,
                'actioned_by' => auth()->id(),
                'action_date' => now(),
                'comments' => $comments,
            ]);

            if ($action === 'rejected') {
                $approvalRequest->update([
                    'status' => 'rejected',
                    'completed_date' => now(),
                ]);
                $this->updateEntityStatus($approvalRequest, 'rejected');
            } elseif ($action === 'approved') {
                $this->handleApprovalGranted($approvalRequest);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function handleApprovalGranted(ApprovalRequest $approvalRequest): void
    {
        $workflow = $approvalRequest->workflow;
        $pendingActions = $approvalRequest->pendingActions()
            ->where('level_order', '>', $approvalRequest->current_level)
            ->orderBy('level_order')
            ->get();

        if ($workflow->requires_sequential) {
            $nextAction = $pendingActions->first();

            if ($nextAction) {
                $approvalRequest->update(['current_level' => $nextAction->level_order]);
                $this->notifyApprover($nextAction);
            } else {
                $this->completeApproval($approvalRequest);
            }
        } else {
            if ($pendingActions->isEmpty()) {
                $this->completeApproval($approvalRequest);
            }
        }
    }

    protected function completeApproval(ApprovalRequest $approvalRequest): void
    {
        $approvalRequest->update([
            'status' => 'approved',
            'completed_date' => now(),
        ]);

        $this->updateEntityStatus($approvalRequest, 'approved');
    }

    protected function updateEntityStatus(ApprovalRequest $approvalRequest, string $status): void
    {
        $entityType = $approvalRequest->entity_type;
        $entityClass = 'App\\Models\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $entityType)));

        if (class_exists($entityClass)) {
            $entity = $entityClass::find($approvalRequest->entity_id);
            if ($entity) {
                $entity->update(['approval_status' => $status]);
            }
        }
    }

    protected function notifyCurrentLevelApprovers(ApprovalRequest $approvalRequest): void
    {
        $actions = $approvalRequest->actions()
            ->where('level_order', $approvalRequest->current_level)
            ->whereNull('action')
            ->get();

        foreach ($actions as $action) {
            $this->notifyApprover($action);
        }
    }

    protected function notifyAllApprovers(ApprovalRequest $approvalRequest): void
    {
        $actions = $approvalRequest->actions()->whereNull('action')->get();

        foreach ($actions as $action) {
            $this->notifyApprover($action);
        }
    }

    protected function notifyApprover(ApprovalAction $action): void
    {
        $action->update(['notification_sent_at' => now()]);
        // TODO: Send email/notification to approver
    }

    protected function generateApprovalNumber(): string
    {
        $prefix = 'APR';
        $date = now()->format('Ymd');
        $sequence = str_pad(ApprovalRequest::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequence}";
    }
}
