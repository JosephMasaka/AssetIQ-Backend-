<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTrackingLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'asset_id',
        'action',
        'old_values',
        'new_values',
        'user_id'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array'
    ];
}
