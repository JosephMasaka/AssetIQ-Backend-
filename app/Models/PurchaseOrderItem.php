<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_id',
        'requisition_item_id',
        'material_code',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'uom',
        'created_by',
        'updated_by',
        'company_id',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }
}

