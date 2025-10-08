<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $table = 'components';

    protected $fillable = [
        'asset_id',
        'component_name',
        'component_code',
        'quantity',
        'serial_number',
        'status',
        'company_id',
        'created_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
