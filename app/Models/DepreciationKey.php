<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationKey extends Model
{
    protected $table = 'depreciation_keys';

    protected $fillable = [
        'code',
        'name',
        'is_multi_level',
        'allow_change',
        'is_active',
        'created_by',
        'updated_by',
        'company_id',
    ];
}
