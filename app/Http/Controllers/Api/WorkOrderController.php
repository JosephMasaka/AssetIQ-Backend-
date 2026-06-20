<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with(['asset', 'requester', 'assignedTo', 'vendor'])
            ->where('company_id', $request->user()->company_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $workOrders = $query->latest()->paginate(20);

        return response()->json($workOrders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:corrective,preventive,inspection,calibration,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'asset_id' => 'nullable|exists:assets,id',
            'location' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
            'vendor_id' => 'nullable|exists:vendors,id',
            'notes' => 'nullable|string',
        ]);

        $workOrder = WorkOrder::create([
            ...$validated,
            'work_order_number' => $this->generateWorkOrderNumber(),
            'status' => 'open',
            'requested_by' => $request->user()->id,
            'requested_date' => now(),
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Work order created successfully',
            'work_order' => $workOrder->load(['asset', 'requester', 'assignedTo'])
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $workOrder = WorkOrder::with([
            'asset',
            'requester',
            'assignedTo',
            'vendor',
            'maintenances',
            'partsUsed.sparePart',
            'downtimeLogs'
        ])->findOrFail($id);

        return response()->json($workOrder);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type' => 'sometimes|in:corrective,preventive,inspection,calibration,emergency',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:open,assigned,in_progress,on_hold,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'started_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'estimated_hours' => 'nullable|string',
            'actual_hours' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric',
            'actual_cost' => 'nullable|numeric',
            'work_performed' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $workOrder->update($validated);

        return response()->json([
            'message' => 'Work order updated successfully',
            'work_order' => $workOrder->fresh(['asset', 'requester', 'assignedTo'])
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        if (!in_array($workOrder->status, ['open', 'cancelled'])) {
            return response()->json([
                'message' => 'Only open or cancelled work orders can be deleted'
            ], 400);
        }

        $workOrder->delete();

        return response()->json([
            'message' => 'Work order deleted successfully'
        ]);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'work_performed' => 'required|string',
            'actual_hours' => 'nullable|string',
            'actual_cost' => 'nullable|numeric',
            'parts_used' => 'nullable|string',
        ]);

        $workOrder = WorkOrder::findOrFail($id);

        $workOrder->update([
            'status' => 'completed',
            'completed_date' => now(),
            'work_performed' => $request->work_performed,
            'actual_hours' => $request->actual_hours,
            'actual_cost' => $request->actual_cost,
            'parts_used' => $request->parts_used,
        ]);

        return response()->json([
            'message' => 'Work order completed successfully',
            'work_order' => $workOrder
        ]);
    }

    protected function generateWorkOrderNumber(): string
    {
        $prefix = 'WO';
        $date = now()->format('Ymd');
        $sequence = str_pad(WorkOrder::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$date}-{$sequence}";
    }
}
