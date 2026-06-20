<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\ApprovalAction;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApprovalController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = ApprovalRequest::with(['workflow', 'requester', 'actions.approver'])
            ->where('company_id', $request->user()->company_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $approvals = $query->latest()->paginate(20);

        return response()->json($approvals);
    }

    public function myPendingApprovals(Request $request): JsonResponse
    {
        $pendingActions = ApprovalAction::whereNull('action')
            ->where('approver_id', $request->user()->id)
            ->with(['approvalRequest.workflow', 'approvalRequest.requester', 'approvalLevel'])
            ->whereHas('approvalRequest', function ($query) {
                $query->where('status', 'pending');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($pendingActions);
    }

    public function show(int $id): JsonResponse
    {
        $approval = ApprovalRequest::with([
            'workflow.levels',
            'actions.approver',
            'actions.approvalLevel',
            'requester'
        ])->findOrFail($id);

        return response()->json($approval);
    }

    public function processAction(Request $request, int $actionId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approved,rejected,delegated',
            'comments' => 'nullable|string',
            'delegate_to' => 'required_if:action,delegated|exists:users,id',
        ]);

        try {
            if ($request->action === 'delegated') {
                $action = ApprovalAction::findOrFail($actionId);
                $action->update([
                    'delegated_to' => $request->delegate_to,
                    'delegated_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Approval delegated successfully',
                    'action' => $action
                ]);
            }

            $this->approvalService->processApproval(
                $actionId,
                $request->action,
                $request->comments
            );

            return response()->json([
                'message' => 'Approval processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing approval',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $approval = ApprovalRequest::findOrFail($id);

        if ($approval->requested_by !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($approval->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending approvals can be cancelled'
            ], 400);
        }

        $approval->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Approval request cancelled successfully'
        ]);
    }
}
