<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    protected $fillable = [
        'tenant_id',
        'asset_id',
        'employee_id',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'status',
        'notes',
        'created_by',
        'updated_by'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class,'employee_id');
    }
}
