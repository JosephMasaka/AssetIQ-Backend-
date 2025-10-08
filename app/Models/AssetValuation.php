<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetValuation extends Model
{
     protected $fillable = [
        'asset_id', 
        'acquisition_value', 
        'book_value',
        'residual_value', 
        'currency', 
        'useful_life_years',
        'depreciation_rate', 
        'depreciation_key',
        'company_id',
        'created_by',
    ];

    public function asset() {
        return $this->belongsTo(Asset::class);
    }
}
