<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'transfer_number',
        'transfer_date',
        'from_location',
        'to_location',
        'from_custodian',
        'to_custodian',
        'from_department_id',
        'to_department_id',
        'from_company_code',
        'to_company_code',
        'reason',
        'status',
        'approved_by',
        'approval_date',
        'notes',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approval_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromCustodian()
    {
        return $this->belongsTo(User::class, 'from_custodian');
    }

    public function toCustodian()
    {
        return $this->belongsTo(User::class, 'to_custodian');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
