<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_number',
        'audit_type',
        'audit_date',
        'scheduled_date',
        'status',
        'auditor_id',
        'scope',
        'scope_filters',
        'assets_expected',
        'assets_found',
        'assets_missing',
        'discrepancies',
        'findings',
        'recommendations',
        'attachments',
        'completed_date',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'scope_filters' => 'array',
        'attachments' => 'array',
    ];

    public function auditor()
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function items()
    {
        return $this->hasMany(AssetAuditItem::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
