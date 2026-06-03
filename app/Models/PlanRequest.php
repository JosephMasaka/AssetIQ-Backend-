<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reseller_id',
        'current_plan_id',
        'requested_plan_id',
        'status',
        'coupon_id',
        'reviewed_by',
        'reviewed_at',
        'reason',
        'rejection_note',
        'billing_cycle',
        'quoted_price',
        'currency',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'quoted_price' => 'decimal:2',
    ];

    /* ── Status constants ── */
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /* ── Relationships ── */

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function requestedPlan()
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* ── Scopes ── */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForReseller($query, int $resellerId)
    {
        return $query->where('reseller_id', $resellerId);
    }

    /* ── Helpers ── */

    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isApproved(): bool  { return $this->status === self::STATUS_APPROVED; }
    public function isRejected(): bool  { return $this->status === self::STATUS_REJECTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
}