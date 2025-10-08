<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $table = 'licenses';

    protected $fillable = [
        'asset_id',
        'license_key',
        'vendor',
        'issue_date',
        'expiry_date',
        'type',
        'notes',
        'company_id',
        'created_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
