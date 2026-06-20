<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_code',
        'name',
        'asset_img',
        'description',
        'category_id',
        'serial_number',
        'acquisition_date',
        'purchase_cost',
        'location',
        'responsible_person',
        'status',
        'lifecycle_status',
        'warranty_start_date',
        'warranty_end_date',
        'disposal_date',
        'disposal_method',
        'disposal_value',
        'disposal_notes',
        'disposed_by',
        'retirement_date',
        'retirement_reason',
        'residual_value',
        'salvage_value',
        'useful_life_years',
        'expected_eol_date',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'disposal_date' => 'date',
        'retirement_date' => 'date',
        'expected_eol_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'disposal_value' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'salvage_value' => 'decimal:2',
    ];

    public function category() {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function attributes() {
        return $this->hasMany(AssetAttribute::class);
    }

    public function vendors() {
        return $this->belongsToMany(Vendor::class, 'asset_vendor');
    }

    public function codes() {
        return $this->hasMany(AssetCode::class);
    }

    public function licenses()
    {
        return $this->morphToMany(
            License::class,
            'assignable',
            'license_assignments'
        );
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'asset_id');
    }

    public function files()
    {
        return $this->hasMany(AssetFile::class, 'asset_id');
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class, 'asset_id');
    }

    public function histories()
    {
        return $this->hasMany(AssetHistory::class, 'asset_id');
    }

    public function checkinsCheckouts()
    {
        return $this->hasMany(AssetCheckinCheckout::class, 'asset_id');
    }

    public function assignments()
    {
        return $this->hasMany(
            AssetAssignment::class
        );
    }

    public function currentAssignment()
    {
        return $this->hasOne(
            AssetAssignment::class
        )->where('status','assigned');
    }

    public function trackingLogs()
    {
        return $this->hasMany(
            AssetTrackingLog::class
        );
    }

    public function disposals()
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function disposal()
    {
        return $this->hasOne(AssetDisposal::class)->latestOfMany();
    }

    public function transfers()
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function valuations()
    {
        return $this->hasMany(AssetValuation::class);
    }

    public function currentValuation()
    {
        return $this->hasOne(AssetValuation::class)->latestOfMany('valuation_date');
    }
}

