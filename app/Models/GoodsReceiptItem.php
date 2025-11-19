<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = ['gr_id','po_item_id','quantity_received'];

    public function poItem() {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }
}

