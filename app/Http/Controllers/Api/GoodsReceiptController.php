<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Traits\ApiResponser;

class GoodsReceiptController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $gr = GoodsReceipt::with(['items.poItem', 'po.vendor'])->get();
        return $this->successResponse($gr);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'po_id' => 'required|exists:purchase_orders,id',
            'receipt_date' => 'required|date',
            'items' => 'required|array',
            'items.*.po_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:1',
        ]);

        $gr = GoodsReceipt::create([
            'po_id' => $data['po_id'],
            'receipt_date' => $data['receipt_date'],
            'status' => 'Pending',
            'created_by' => auth()->id(),
            'company_id' => auth('api')->user()->getCompany(),
        ]);

        foreach($data['items'] as $item){
            GoodsReceiptItem::create([
                'gr_id' => $gr->id,
                'po_item_id' => $item['po_item_id'],
                'quantity_received' => $item['quantity_received'],
            ]);
        }

        return $this->successResponse($gr, 'Goods Receipt created successfully');
    }

    public function show($id)
    {
        $gr = GoodsReceipt::with(['po.vendor', 'items.poItem'])->findOrFail($id);
        return $this->successResponse($gr);
    }
}
