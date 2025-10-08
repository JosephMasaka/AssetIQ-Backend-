<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $fillable = [
        'name', 
        'code', 
        'description', 
        'parent_id',
        'company_id',
        'created_by',
    ];

    public function parent() {
        return $this->belongsTo(AssetCategory::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(AssetCategory::class, 'parent_id');
    }

    public function assets() {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
