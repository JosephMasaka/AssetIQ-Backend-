<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAuditItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_audit_id',
        'asset_id',
        'expected_location',
        'actual_location',
        'expected_custodian',
        'actual_custodian',
        'condition',
        'verification_status',
        'has_discrepancy',
        'discrepancy_notes',
        'photos',
        'verified_at',
    ];

    protected $casts = [
        'has_discrepancy' => 'boolean',
        'photos' => 'array',
        'verified_at' => 'datetime',
    ];

    public function assetAudit()
    {
        return $this->belongsTo(AssetAudit::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
