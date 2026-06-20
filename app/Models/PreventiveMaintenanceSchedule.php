<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreventiveMaintenanceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_name',
        'asset_id',
        'asset_category_id',
        'frequency',
        'frequency_value',
        'start_date',
        'end_date',
        'last_performed_date',
        'next_due_date',
        'tasks',
        'instructions',
        'assigned_to',
        'vendor_id',
        'estimated_cost',
        'estimated_duration',
        'is_active',
        'auto_generate_wo',
        'lead_time_days',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_performed_date' => 'date',
        'next_due_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'auto_generate_wo' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

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
