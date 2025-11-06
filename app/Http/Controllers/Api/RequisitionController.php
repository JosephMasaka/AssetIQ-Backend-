<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;

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
        return $this->successResponse($requisition, 'Requisition fetched successfully');
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
        DB::beginTransaction();
        $validated = $request->validate([
            'justification' => 'required|string|max:255',
            'type' => 'required',
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
                'requisition_type_id' => $validated['type'],
                'justification' => $validated['justification'],
                'company_id' => $user->getCompany(),
                'created_by' => $user->id,
            ]);

            foreach ($validated['items'] as $item) {
                $requisition->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'uom_id' => $item['uom'], // only if uom_id exists
                    'estimated_cost' => $item['estimated_unit_cost'] ?? 0,
                    'company_id' => $user->getCompany(),
                    'created_by' => $user->id,
                    // 'changed_by' => $user->id,
                ]);
            }
            DB::commit();
            return $this->successResponse($requisition->load('items'), 'Requisition created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create requisition: ' . $e->getMessage(), 500);
        }
    }
}
