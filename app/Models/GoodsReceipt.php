<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = ['po_id','receipt_date','status','created_by','company_id','updated_by'];

    public function po() {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function items() {
        return $this->hasMany(GoodsReceiptItem::class, 'gr_id');
    }
}
