<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'asset_id', 'created_by', 'updated_by',
        'name', 'item_number', 'model_number', 'category',
        'order_number', 'supplier', 'purchase_date', 'unit_cost', 'currency', 'location',
        'total_quantity', 'remaining_quantity', 'min_quantity',
        'status', 'notes',
    ];

    protected $casts = [
        'purchase_date'      => 'date',
        'unit_cost'          => 'decimal:2',
        'total_quantity'     => 'integer',
        'remaining_quantity' => 'integer',
        'min_quantity'       => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Computed ────────────────────────────────────────────────────────────

    /** Percentage of stock remaining (0-100) */
    public function getPercentRemainingAttribute(): float
    {
        if (!$this->total_quantity) return 0;
        return round(($this->remaining_quantity / $this->total_quantity) * 100, 1);
    }

    /** Total cost = unit_cost × total_quantity */
    public function getTotalCostAttribute(): float
    {
        return (float) ($this->unit_cost ?? 0) * $this->total_quantity;
    }

    /** Auto-resolve status based on quantity vs threshold */
    public function syncStatus(): void
    {
        if ($this->remaining_quantity === 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->remaining_quantity <= $this->min_quantity) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'available';
        }
        $this->save();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereIn('status', ['low_stock', 'out_of_stock']);
    }
}