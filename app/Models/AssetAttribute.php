<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAttribute extends Model
{
    protected $fillable = [
        'asset_id', 
        'key', 
        'value',
        'company_id',
        'created_by',
    ];

    public function asset() {
        return $this->belongsTo(Asset::class);
    }
}
