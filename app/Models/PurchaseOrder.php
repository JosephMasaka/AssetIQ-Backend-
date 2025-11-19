<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'requisition_id',
        'order_date',
        'status',
        'currency',
        'created_by',
        'updated_by',
        'company_id',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
