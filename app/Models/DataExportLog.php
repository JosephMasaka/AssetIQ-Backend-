<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExportLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'export_type',
        'entity_type',
        'filters',
        'records_exported',
        'file_format',
        'file_path',
        'reason',
        'requested_by',
        'ip_address',
        'company_id',
    ];

    protected $casts = [
        'filters' => 'array',
        'created_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
