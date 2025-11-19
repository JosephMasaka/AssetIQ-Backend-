<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;

class PurchaseOrderController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        try {
            $search = $request->get('search');

            $query = PurchaseOrder::with(['vendor', 'createdBy'])
                ->when($search, function ($q) use ($search) {
                    $q->where('po_number', 'like', "%$search%")
                      ->orWhereHas('vendor', function ($sub) use ($search) {
                          $sub->where('name', 'like', "%$search%");
                      });
                })
                ->orderBy('created_at', 'desc');

            $purchaseOrders = $query->paginate(10);

            return $this->successResponse($purchaseOrders, 'Purchase Orders fetched successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to load purchase orders', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        $po = PurchaseOrder::with(['vendor', 'items.unitOfMeasure'])->findOrFail($id);

        // Compute subtotal
        $subtotal = $po->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        // Apply tax rate (optional)
        $taxRate = $po->tax_rate ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        // Append computed fields
        $po->subtotal = $subtotal;
        $po->tax_rate = $taxRate;
        $po->tax_amount = $taxAmount;
        $po->total_amount = $totalAmount;

        return response()->json([
            'success' => true,
            'data' => $po
        ]);
    }


}
