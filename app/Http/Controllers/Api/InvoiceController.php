<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoodsReceipt;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Traits\ApiResponser;

class InvoiceController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $query = Invoice::with([
            'vendor:id,vendor_name,vendor_code',
            'purchaseOrder:id,po_number',
            'items', 
            'payments'
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhereHas('vendor', function ($v) use ($search) {
                      $v->where('vendor_name', 'like', "%$search%")
                        ->orWhere('vendor_code', 'like', "%$search%");
                  });
            });
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by PO
        if ($request->filled('po_id')) {
            $query->where('po_id', $request->po_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('posting_date', [
                $request->from_date,
                $request->to_date
            ]);
        }

        $invoices = $query
            ->orderBy('posting_date', 'desc')
            ->paginate($request->get('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }


    public function generateFromGR($id)
    {
        $gr = GoodsReceipt::with(['po', 'items.poItem'])->findOrFail($id);

        //  Prevent duplicate invoice for same GR
        $existing = Invoice::where('reference', 'GR-' . $gr->id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already exists for this Goods Receipt',
                'invoice_id' => $existing->id
            ], 409);
        }

        // Generate SAP-style invoice number
        $nextNumber = 'INV-' . now()->year . '-' . str_pad(
            Invoice::max('id') + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        // Calculate totals
        $totalAmount = $gr->items->sum(function ($item) {
            return $item->quantity_received * $item->poItem->unit_price;
        });

        $taxAmount = $gr->items->sum(function ($item) {
            $taxRate = $item->poItem->tax_rate ?? 0;
            return ($item->quantity_received * $item->poItem->unit_price) * ($taxRate / 100);
        });

        // Create invoice header
        $invoice = Invoice::create([
            'vendor_id'      => $gr->po->vendor_id,
            'po_id'          => $gr->po->id,
            'invoice_number' => $nextNumber,
            'invoice_date'   => now(),
            'posting_date'   => now(),
            'total_amount'   => $totalAmount,
            'tax_amount'     => $taxAmount,
            'discount_amount'=> 0,
            'status'         => 'Draft',
            'reference'      => 'GR-' . $gr->id,
            'currency'       => $gr->po->currency ?? 'KES',
            'created_by'     => auth()->id(),
        ]);

        // Create invoice items (RSEG equivalent)
        foreach ($gr->items as $index => $item) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'po_item_id'  => $item->po_item_id,
                'gr_item_id'  => $item->id,
                'line_number' => $index + 1,
                'description' => $item->description ?? $item->poItem->description,
                'quantity'    => $item->quantity_received,
                'uom'         => $item->poItem->uom,
                'unit_price'  => $item->poItem->unit_price,
                'tax_rate'    => $item->poItem->tax_rate ?? 0,
                'line_total'  => $item->quantity_received * $item->poItem->unit_price,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully',
            'data' => $invoice->load('items', 'vendor', 'purchaseOrder')
        ], 201);
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with([
                'vendor',
                'purchaseOrder.vendor',
                'purchaseOrder.items',    // PO items
                'items.poItem',           // Invoice items referencing PO items (correct name)
                'payments',
                'creator',
                'approver'
            ])->find($id);

            if (!$invoice) {
                return $this->errorResponse('Invoice not found', 404);
            }

            return $this->successResponse($invoice, 'Invoice retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
