<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $table = 'quotations';

    protected $fillable = [
        'requisition_id',
        'rfq_id',
        'vendor_id',
        'quotation_number',

        // Vendor details cache
        'contact_person',
        'email',
        'phone',

        // Dates
        'quotation_date',
        'valid_until',

        // Commercial
        'currency',
        'total_amount',
        'discount_amount',
        'tax_amount',
        'net_amount',

        // Terms
        'delivery_terms',
        'payment_terms',
        'delivery_location',

        // Workflow
        'status',
        'public_token',

        // System
        'created_by',
        'updated_by',
        'company_id'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // --- Status Constants ---
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_AWARDED = 'awarded';
    public const STATUS_REJECTED = 'rejected';

    # ---------------------------------------
    # Relationships
    # ---------------------------------------

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function rfq()
    {
        return $this->belongsTo(RequisitionVendor::class, 'rfq_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    # ---------------------------------------
    # Business Logic
    # ---------------------------------------

    /**
     * Recalculate total, tax, discount and net_amount.
     */
    public function computeTotals()
    {
        $total = $this->items()->sum('total_cost');

        $this->total_amount = $total;
        $this->tax_amount = $total * 0.16; // Example VAT
        $this->net_amount = ($total - $this->discount_amount) + $this->tax_amount;

        $this->save();
    }

    /**
     * Generates quotation number like: QT-2025-00012
     */
    public static function generateNumber()
    {
        $year = now()->format('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf("QT-%s-%05d", $year, $count);
    }
}
