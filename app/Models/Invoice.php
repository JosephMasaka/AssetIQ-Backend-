<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'vendor_id',
        'po_id',
        'invoice_number',
        'invoice_date',
        'posting_date',
        'total_amount',
        'tax_amount',
        'discount_amount',
        'status',
        'reference',
        'currency',
        'created_by',
        'approved_by'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'posting_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Vendor (RBKP-LIFNR)
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Purchase Order
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    // Items (RSEG)
    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    // Payments (FI Document - BSEG equivalent)
    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    // Creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Approver
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
