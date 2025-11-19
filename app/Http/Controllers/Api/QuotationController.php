<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\RequisitionVendor;
use App\Models\RequisitionItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class QuotationController extends Controller
{
    use ApiResponser;

    public function index()
    {
        try {
            $quotations = Quotation::with('vendor')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $quotations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quotations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $public_token)
    {
        // ✅ Validate RFQ link
        $rfq = RequisitionVendor::with(['vendor', 'requisition.items.unitOfMeasure'])
            ->where('public_token', $public_token)
            ->first();

        if (!$rfq) {
            return $this->errorResponse('Invalid or expired link', 404);
        }

        // ✅ Validation with ApiResponser support
        try {
            $validated = $request->validate([
                'quoted_amount' => 'required|numeric|min:1',
                'valid_until' => 'required|date',
                'currency' => 'required|string|max:5',
                'discount_amount' => 'nullable|numeric|min:0',
                'tax_amount' => 'nullable|numeric|min:0',
                'net_amount' => 'required|numeric|min:0',
                'delivery_terms' => 'nullable|string|max:255',
                'payment_terms' => 'nullable|string|max:255',
                'delivery_location' => 'nullable|string|max:255',
                'remarks' => 'nullable|string|max:2000',

                'items' => 'required|array',
                'items.*.requisition_item_id' => 'required|exists:requisition_items,id',
                'items.*.unit_cost' => 'required|numeric|min:0',

                'attachment' => 'nullable|file|mimes:pdf,docx,xlsx|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );
        }

        DB::beginTransaction();

        try {

            // ✅ Generate quotation number
            $quotationNumber = 'QT-' . date('Y') . '-' . str_pad(Quotation::count() + 1, 5, '0', STR_PAD_LEFT);

            // ✅ File upload
            $filePath = null;
            if ($request->hasFile('attachment')) {
                $filePath = $request->file('attachment')->store('quotation_docs', 'public');
            }

            // ✅ Create Quotation Header
            $quotation = Quotation::create([
                'requisition_id' => $rfq->requisition_id,
                'rfq_id' => $rfq->id,
                'vendor_id' => $rfq->vendor_id,

                'quotation_number' => $quotationNumber,
                'quotation_date' => now(),

                'valid_until' => $validated['valid_until'],
                'currency' => $validated['currency'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'net_amount' => $validated['net_amount'],

                'delivery_terms' => $validated['delivery_terms'],
                'payment_terms' => $validated['payment_terms'],
                'delivery_location' => $validated['delivery_location'],

                'total_amount' => $validated['quoted_amount'],
                'remarks' => $validated['remarks'] ?? null,

                'file_path' => $filePath,
                'company_id' => $rfq->company_id,
                'status' => 'submitted',
                'public_token' => Str::random(60)
            ]);

            // ✅ Insert Quotation Items
            foreach ($validated['items'] as $itemData) {
                $reqItem = RequisitionItem::find($itemData['requisition_item_id']);

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'requisition_item_id' => $reqItem->id,
                    'item_name' => $reqItem->name,
                    'description' => $reqItem->description,
                    'quantity' => $reqItem->quantity,
                    'uom_id' => $reqItem->uom_id,
                    'unit_price' => $itemData['unit_cost'],
                    'total_price' => $reqItem->quantity * $itemData['unit_cost'],
                ]);
            }

            // ✅ Update RFQ status
            $rfq->update(['status' => 'responded']);

            DB::commit();

            return $this->successResponse(
                [
                    'quotation' => $quotation,
                    'rfq' => $rfq,
                ],
                'Quotation submitted successfully!',
                201
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return $this->errorResponse(
                'Failed to submit quotation',
                500,
                $e->getMessage()
            );
        }
    }

    public function show($id)
    {
        try {
            $quotation = Quotation::with([
                'vendor',
                'requisition',
                'items.unitOfMeasure'
            ])->find($id);

            if (!$quotation) {
                return $this->errorResponse('Quotation not found', 404);
            }

            return $this->successResponse($quotation, 'Quotation fetched successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch quotation', 500, $e->getMessage());
        }
    }

    public function awardQuotation($id)
    {
        $quotation = Quotation::with('items.unitOfMeasure', 'vendor')->findOrFail($id);

        if ($quotation->status === 'awarded') {
            return response()->json(['message' => 'Quotation already awarded.'], 400);
        }

        DB::beginTransaction();

        try {
            // 1️⃣ Mark quotation as awarded
            $quotation->update([
                'status' => 'awarded',
                'is_awarded' => true,
                'awarded_by' => auth()->id(),
                'awarded_at' => now(),
            ]);

            // 2️⃣ Create Purchase Order
            $po = PurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'vendor_id' => $quotation->vendor_id,
                'requisition_id' => $quotation->requisition_id ?? null,
                'order_date' => now(),
                'status' => 'Draft',
                'currency' => 'KSH',
                'created_by' => auth()->id(),
                'company_id' => $quotation->company_id,
            ]);

            // 3️⃣ Create Purchase Order Items
            foreach ($quotation->items as $item) {
                // Log::info('UOM Debug', [
                //     'item_id' => $item->id,
                //     'item_name' => $item->item_name,
                //     'uom_id' => $item->uom_id ?? null,
                //     'relation_loaded' => $item->relationLoaded('unitOfMeasure'),
                //     'uom_object' => $item->unitOfMeasure,
                //     'uom_name' => $item->unitOfMeasure->uom_name ?? null,
                // ]);

                PurchaseOrderItem::create([
                    'po_id' => $po->id,
                    'requisition_item_id' => $item->requisition_item_id ?? null,
                    'material_code' => $item->material_code ?? null,
                    'description' => $item->item_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'uom' => $item->unitOfMeasure->uom_name ?? null, 
                    'created_by' => auth()->id(),
                    'company_id' => $quotation->company_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Quotation awarded successfully. Purchase Order created.',
                'po_number' => $po->po_number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to award quotation or create PO.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a unique PO number (e.g., PO-20251113-001)
     */
    private function generatePONumber()
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';
        $latestPO = PurchaseOrder::latest('id')->first();
        $nextNumber = $latestPO ? str_pad($latestPO->id + 1, 3, '0', STR_PAD_LEFT) : '001';
        return $prefix . $nextNumber;
    }

}
