<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Component extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'component_tag',
        'name',
        'manufacturer',
        'model_number',
        'serial_number',
        'category_id',
        'is_rotable',
        'quantity_on_hand',
        'reorder_point',
        'reorder_quantity',
        'unit_cost',
        'currency',
        'lifecycle_status',
        'current_asset_id',
        'install_position',
        'warranty_expiry',
        'last_serviced_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'is_rotable' => 'boolean',
        'unit_cost' => 'decimal:2',
        'warranty_expiry' => 'date',
        'last_serviced_at' => 'date',
    ];

    const STATUSES = ['in_stock', 'installed', 'in_repair', 'retired'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(ComponentCategory::class, 'category_id');
    }

    public function currentAsset()
    {
        return $this->belongsTo(Asset::class, 'current_asset_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installations()
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    public function activeInstallation()
    {
        return $this->hasOne(ComponentInstallation::class)->whereNull('removed_at');
    }

    /**
     * IMPORTANT: scope by company_id, never created_by.
     * This mirrors the multi-tenancy bug fix already applied in
     * AssetAIContextService — created_by identifies who logged the record,
     * not which tenant it belongs to. Using it here would leak/hide
     * components across companies the same way the dashboard counts bug did.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeInStock($query)
    {
        return $query->where('lifecycle_status', 'in_stock');
    }

    public function scopeInstalled($query)
    {
        return $query->where('lifecycle_status', 'installed');
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('reorder_point')
            ->whereColumn('quantity_on_hand', '<=', 'reorder_point');
    }
}