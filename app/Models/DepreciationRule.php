<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationRule extends Model
{
    protected $fillable = [
        'company_id',
        'depreciation_key_id',
        'depreciation_area',
        'name',
        'method',
        'useful_life',
        'depreciation_rate',
        'salvage_value',
        'valid_from_year',
        'valid_to_year',
        'gl_account',
        'cost_center',
        'asset_class',
        'is_active',
        'period_control',
        'base_value',
        'is_active',
    ];

    protected $casts = [
        'useful_life'   => 'integer',
        'salvage_value' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function assets()
    {
        return $this->hasMany(AssetDepreciationSetting::class, 'depreciation_rule_id');
    }
}
