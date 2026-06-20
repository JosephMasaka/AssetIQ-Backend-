<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\RequisitionVendor;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequisitionController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the requisitions.
     */
    public function index()
    {
        $user = auth()->user();

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
        $user = auth()->user();

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
        $user = auth()->user();

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
            'justification' => 'required|string',
            'type' => 'required',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.uom' => 'required|string|max:20',
            'items.*.estimated_unit_cost' => 'nullable|numeric|min:0',
            'items.*.name' => 'required|string|max:100',
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
                    'name' => $item['name'],
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

    public function addVendors(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $validated = $request->validate([
            'requisition_id' => 'required|exists:requisitions,id',
            'vendor_id' => 'required', // can be 'all' or a specific ID
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'rfq_number' => 'nullable|string|max:50',
            'rfq_date' => 'nullable|date',
            'response_deadline' => 'nullable|date',
            'quoted_amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $requisition = Requisition::findOrFail($validated['requisition_id']);
            $vendorsToAttach = [];

            if ($validated['vendor_id'] === 'all') {
                // Get all vendors from vendors table
                $allVendors = \App\Models\Vendor::all();

                foreach ($allVendors as $vendor) {
                    $exists = \App\Models\RequisitionVendor::where('requisition_id', $requisition->id)
                        ->where('vendor_id', $vendor->id)
                        ->exists();

                    if (!$exists) {
                        $vendorsToAttach[] = [
                            'requisition_id' => $requisition->id,
                            'vendor_id' => $vendor->id,
                            'contact_person' => $vendor->contact_person ?? null,
                            'email' => $vendor->email ?? null,
                            'phone' => $vendor->phone ?? null,
                            // 'rfq_number' => 'RFQ-' . now()->format('YmdHis') . '-' . $vendor->id,
                            // 'rfq_date' => now(),
                            // 'response_deadline' => now()->addDays(7),
                            'quoted_amount' => 0,
                            'remarks' => 'Auto-added from bulk selection',
                            // 'status' => 'invited',
                            'created_by' => $user->id,
                            'company_id' => $user->getCompany(),
                            'created_at' => now(),
                            // 'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($vendorsToAttach)) {
                    RequisitionVendor::insert($vendorsToAttach);
                }

            } else {
                // Add single vendor
                $vendorId = (int) $validated['vendor_id'];

                $exists = \App\Models\RequisitionVendor::where('requisition_id', $requisition->id)
                    ->where('vendor_id', $vendorId)
                    ->exists();

                if ($exists) {
                    return $this->errorResponse('Vendor already added to this requisition.', 409);
                }

                \App\Models\RequisitionVendor::create([
                    'requisition_id'   => $requisition->id,
                    'vendor_id'        => $vendorId,
                    'contact_person'   => $validated['contact_person'] ?? null,
                    'email'            => $validated['email'] ?? null,
                    'phone'            => $validated['phone'] ?? null,
                    // 'rfq_number'       => $validated['rfq_number'] ?? 'RFQ-' . now()->format('YmdHis'),
                    // 'rfq_date'         => $validated['rfq_date'] ?? now(),
                    // 'response_deadline'=> $validated['response_deadline'] ?? now()->addDays(7),
                    'quoted_amount'    => $validated['quoted_amount'] ?? 0,
                    'remarks'          => $validated['remarks'] ?? null,
                    'status'           => $validated['status'] ?? 'not yet invited',
                    'created_by' => $user->id,
                    'company_id' => $user->getCompany(),
                ]);
            }

            DB::commit();

            // Reload requisition with vendors
            $requisition->load('vendors');

            return $this->successResponse(
                $requisition,
                $validated['vendor_id'] === 'all'
                    ? 'All vendors added successfully'
                    : 'Vendor added successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to add vendor(s): ' . $e->getMessage(), 500);
        }
    }

    public function getVendors($id)
    {
        try {
            $requisition = Requisition::with(['vendors' => function ($query) {
                $query->select(
                    'vendors.id',
                    'vendors.vendor_name',
                    'vendors.email',
                    'vendors.phone',
                    'vendors.street',
                    'vendors.city',
                    'vendors.country',
                );
            }])->findOrFail($id);

            return $this->successResponse(
                $requisition->vendors,
                'Vendors fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to load vendors: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getVendor($id, $vendorId)
    {
        try {
            $requisition = Requisition::where('id', $id)->first();
            $rfq = RequisitionVendor::where('requisition_id', $id)->where('vendor_id', $vendorId)->first();
            $vendorDetails = Vendor::where('id', $vendorId)->first();
            $items = RequisitionItem::with('unitOfMeasure')->where('requisition_id', $id)->get();

            return $this->successResponse(
            [
                'requisition' => $requisition,
                'vendor' => $vendorDetails,
                'rfq' => $rfq,
                'items' => $items
            ],
                'Requisition and Vendor fetched successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to load vendor: ' . $e->getMessage(),
                500
            );
        }
    }

    public function getRequisitionItems($id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Fetch requisition items with their related Unit of Measure
        $items = RequisitionItem::with('unitOfMeasure')
            ->where('requisition_id', $id)
            ->get();

        if ($items->isEmpty()) {
            return $this->errorResponse('No items found for this requisition', 404);
        }

        // Transform items to include uom_name directly in the response
        $items = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'requisition_id' => $item->requisition_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'estimated_cost' => $item->estimated_cost,
                'uom_id' => $item->uom_id,
                'uom_name' => $item->unitOfMeasure?->uom_name, // Include UOM name
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return $this->successResponse($items, 'Requisition items fetched successfully');
    }

    public function generateRFQLinks($id, $vendorId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Find vendor entry for this requisition
        $vendorLink = RequisitionVendor::where('requisition_id', $id)
            ->where('vendor_id', $vendorId)
            ->first();

        if (!$vendorLink) {
            return $this->errorResponse('Vendor not linked to this requisition', 404);
        }

        // Generate RFQ Number (e.g. RFQ-2025-0001)
        $rfqNumber = 'RFQ-' . now()->format('Y') . '-' . str_pad($vendorLink->id, 4, '0', STR_PAD_LEFT);

        $vendorLink->update([
            'rfq_number' => $rfqNumber,
            'rfq_date' => now(),
            'status' => 'invited',
            'response_deadline'=> $validated['response_deadline'] ?? now()->addDays(7),
            'public_token' => Str::random(40),
            'updated_by' => $user->id
        ]);

        return $this->successResponse([
            'rfq_number' => $rfqNumber,
            'vendorName' => $vendorLink->vendor->vendor_name,
            'vendorId' => $vendorId,
            'requisitionId' => $id
        ], 'RFQ generated successfully');
    }

    public function getRequisitionRFQs($requisitionId)
    {
        $rfqs = RequisitionVendor::with('vendor')
            ->where('requisition_id', $requisitionId)
            ->whereNotNull('rfq_number')
            ->get();

        return $this->successResponse($rfqs, 'RFQs fetched');
    }

}
