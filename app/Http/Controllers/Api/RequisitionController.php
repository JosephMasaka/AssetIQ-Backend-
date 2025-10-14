<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;

class RequisitionController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the requisitions.
     */
    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'requisition:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $requisitions = Requisition::with(['requestedBy:id,name', 'capexRequest:id,capex_request_id,capex_title'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($requisitions, 'requisitions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load requisitions', 500);
        }
    }

    /**
     * Show a single requisition.
     */
    public function show($id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'requisition:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $requisition = Requisition::with(['requestedBy:id,name', 'capexRequest'])->find($id);

        if (!$requisition) {
            
            return $this->errorResponse('Requisition not found', 500);
        }

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Requisition fetched successfully',
        //     'data' => $requisition
        // ]);
        return $this->successResponse('Requisition fetched successfully', 200);
    }

     /**
     * Create a requisition.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canCreate = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'requisition:create');
        })->exists();

        if (!$canCreate) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'justification' => 'required|string|max:255',
            'type' => 'required|string|in:Maintenance,Material,Service',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.uom' => 'required|string|max:20',
            'items.*.estimated_unit_cost' => 'nullable|numeric|min:0'
        ]);

        try {
            $requisition = Requisition::create([
                'req_number' => 'PR-' . now()->format('Y') . '-' . str_pad(Requisition::count() + 1, 4, '0', STR_PAD_LEFT),
                'requested_by' => $user->id,
                'request_date' => now(),
                'status' => 'Pending',
                'justification' => $validated['justification'],
                'company_id' => $user->company_id ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                $requisition->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'uom_id' => $item['uom_id'],
                    'estimated_unit_cost' => $item['estimated_unit_cost'] ?? 0,
                    'total_cost' => $item['quantity'] * ($item['estimated_unit_cost'] ?? 0),
                ]);
            }

            return $this->successResponse($requisition->load('items'), 'Requisition created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create requisition: ' . $e->getMessage(), 500);
        }
    }
}
