<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    use HasFactory;

    protected $table = 'maintenance';

    protected $fillable = [
        'asset_id',
        'maintenance_date',
        'type',
        'description',
        'cost',
        'performed_by',
        'company_id',
        'created_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
