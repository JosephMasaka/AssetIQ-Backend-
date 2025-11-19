<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'po_item_id',
        'gr_item_id',
        'line_number',
        'description',
        'quantity',
        'uom',
        'unit_price',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Invoice header
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // Purchase Order Item (EKPO)
    public function poItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }

    // Goods Receipt Item (MSEG)
    public function grItem()
    {
        return $this->belongsTo(GrItem::class, 'gr_item_id');
    }
}
