<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'usage_limit',
        'used_count',
        'minimum_amount',
        'maximum_discount',
        'starts_at',
        'expires_at',
        'plan_ids',
        'created_by',
        'is_active',
        'description',
    ];

    protected $casts = [
        'plan_ids'   => 'array',
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    /* =========================================
     * RELATIONSHIPS
     * ========================================= */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function planRequests()
    {
        return $this->hasMany(PlanRequest::class);
    }

    /* =========================================
     * HELPERS
     * ========================================= */

    public function isExpired(): bool
    {
        return $this->expires_at
            ? $this->expires_at->isPast()
            : false;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at
            ? $this->starts_at->isPast()
            : true;
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if (!$this->hasStarted()) {
            return false;
        }

        if (
            $this->usage_limit !== null &&
            $this->used_count >= $this->usage_limit
        ) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'fixed') {
            return min($this->value, $amount);
        }

        $discount = ($amount * $this->value) / 100;

        if ($this->maximum_discount) {
            $discount = min(
                $discount,
                $this->maximum_discount
            );
        }

        return $discount;
    }
}