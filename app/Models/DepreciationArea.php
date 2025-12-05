<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationArea extends Model
{
    protected $table = 'depreciation_areas';
    
    protected $fillable =
    [
        'name',
        'description',
        'is_posting_to_gl',
        'company_id',
        'created_by',
        'updated_by',
    ];
}
