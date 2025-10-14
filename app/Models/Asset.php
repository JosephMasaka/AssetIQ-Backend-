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
        'company_id',
        'created_by',
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
        return $this->hasMany(License::class, 'asset_id');
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
}

