<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationRule extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'method',
        'useful_life',
        'salvage_value',
        'gl_account',
        'cost_center',
        'asset_class',
        'is_active',
        'period_control',
        'base_value'
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
