<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number',
        'part_name',
        'description',
        'asset_category_id',
        'manufacturer',
        'model',
        'quantity_on_hand',
        'minimum_quantity',
        'reorder_quantity',
        'location',
        'unit_cost',
        'preferred_vendor_id',
        'status',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function preferredVendor()
    {
        return $this->belongsTo(Vendor::class, 'preferred_vendor_id');
    }

    public function usages()
    {
        return $this->hasMany(PartsUsage::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
