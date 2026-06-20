<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'contract_name',
        'vendor_id',
        'contract_type',
        'start_date',
        'end_date',
        'contract_value',
        'payment_terms',
        'scope_of_work',
        'sla_terms',
        'response_time_hours',
        'resolution_time_hours',
        'coverage',
        'covered_assets',
        'covered_categories',
        'covered_locations',
        'includes_parts',
        'includes_labor',
        'visits_per_year',
        'visits_completed',
        'status',
        'renewal_notice_date',
        'auto_renew',
        'attachments',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_notice_date' => 'date',
        'contract_value' => 'decimal:2',
        'includes_parts' => 'boolean',
        'includes_labor' => 'boolean',
        'auto_renew' => 'boolean',
        'covered_assets' => 'array',
        'covered_categories' => 'array',
        'covered_locations' => 'array',
        'attachments' => 'array',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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
