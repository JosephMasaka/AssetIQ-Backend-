<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequisitionVendor extends Model
{
    use HasFactory;

    protected $table = 'requisition_vendors';

    protected $fillable = [
        'requisition_id',
        'vendor_id',
        'contact_person',
        'email',
        'phone',
        'rfq_number',
        'rfq_date',
        'response_deadline',
        'quoted_amount',
        'remarks',
        'status',
        'public_token',
        'public_token_expires_at',
        'created_by',
        'updated_by',
        'company_id'
    ];

    protected $casts = [
        'rfq_date' => 'date',
        'response_deadline' => 'date',
        'public_token_expires_at' => 'datetime',
        'quoted_amount' => 'decimal:2'
    ];

    /**
     * Automatically generate a secure public token if missing.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->public_token)) {
                $model->public_token = Str::random(40);  // 40-char cryptographically secure token
            }
        });
    }

    /**
     * Relationship: RequisitionVendor belongs to a Requisition.
     */
    public function requisition()
    {
        return $this->belongsTo(Requisition::class, 'requisition_id');
    }

    /**
     * Relationship: RequisitionVendor belongs to a Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Accessor for a human-readable status label.
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'invited' => 'Invited',
            'responded' => 'Responded',
            'shortlisted' => 'Shortlisted',
            'awarded' => 'Awarded',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Generate the public RFQ link for vendors (no login required).
     */
    public function getPublicRfqUrlAttribute()
    {
        return url("/public/rfq/{$this->public_token}");
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by requisition.
     */
    public function scopeForRequisition($query, int $requisitionId)
    {
        return $query->where('requisition_id', $requisitionId);
    }

    /**
     * Scope: filter by vendor.
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
