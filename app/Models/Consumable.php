<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Consumable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'item_no',
        'category',
        'model_no',
        'notes',
        'image_path',
        'manufacturer',
        'manufacturer_part_no',
        'primary_vendor_id',
        'unit_of_measure',
        'purchase_cost',
        'moving_average_price',
        'currency',
        'qty',
        'qty_reserved',
        'reorder_point',
        'safety_stock',
        'minimum_order_quantity',
        'lot_size',
        'lead_time_days',
        'abc_classification',
        'xyz_classification',
        'criticality',
        'condition_code',
        'lifecycle_status',
        'is_batch_tracked',
        'batch_no',
        'manufacture_date',
        'expiry_date',
        'location_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_cost' => 'decimal:2',
        'moving_average_price' => 'decimal:2',
        'qty' => 'integer',
        'qty_reserved' => 'integer',
        'reorder_point' => 'integer',
        'safety_stock' => 'integer',
        'minimum_order_quantity' => 'integer',
        'lot_size' => 'integer',
        'lead_time_days' => 'integer',
        'is_batch_tracked' => 'boolean',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected $appends = [
        'qty_available',
        'is_below_reorder_point',
    ];

    // ----------------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------------

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // public function location()
    // {
    //     return $this->belongsTo(Location::class);
    // }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // If a checkout/checkin ledger exists (recommended next step), e.g.:
    // public function checkouts()
    // {
    //     return $this->hasMany(ConsumableCheckout::class);
    // }

    // ----------------------------------------------------------------------
    // Accessors
    // ----------------------------------------------------------------------

    /**
     * Quantity actually available to issue (on hand minus reserved).
     */
    public function getQtyAvailableAttribute(): int
    {
        return max(0, $this->qty - $this->qty_reserved);
    }

    /**
     * SAP-style reorder signal: available stock has fallen below the
     * reorder point, so a replenishment proposal should be raised.
     */
    public function getIsBelowReorderPointAttribute(): bool
    {
        return $this->qty_available <= $this->reorder_point;
    }

    // ----------------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------------

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('lifecycle_status', 'active');
    }

    public function scopeRetired(Builder $query): Builder
    {
        return $query->where('lifecycle_status', 'retired');
    }

    /**
     * Items at or below their SAP-style reorder point — candidates for
     * a replenishment / purchase requisition.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn(
            'qty',
            '<=',
            'reorder_point'
        );
    }

    /**
     * Items that have dipped into safety stock territory — more urgent
     * than a plain low-stock flag.
     */
    public function scopeBelowSafetyStock(Builder $query): Builder
    {
        return $query->whereColumn('qty', '<=', 'safety_stock');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('criticality', 'high');
    }

    /**
     * Batch-tracked items expiring within $days (default 30) — useful
     * for shelf-life consumables like lubricants, chemicals, PPE.
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('is_batch_tracked', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days));
    }

    public function scopeAbcClass(Builder $query, string $class): Builder
    {
        return $query->where('abc_classification', $class);
    }
}