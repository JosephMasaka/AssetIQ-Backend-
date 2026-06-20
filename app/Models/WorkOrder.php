<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_number',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'asset_id',
        'location',
        'requested_by',
        'assigned_to',
        'assigned_team_id',
        'requested_date',
        'scheduled_date',
        'due_date',
        'started_date',
        'completed_date',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'vendor_id',
        'work_performed',
        'parts_used',
        'notes',
        'attachments',
        'parent_work_order_id',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'scheduled_date' => 'date',
        'due_date' => 'date',
        'started_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }

    public function partsUsed()
    {
        return $this->hasMany(PartsUsage::class);
    }

    public function parentWorkOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'parent_work_order_id');
    }

    public function childWorkOrders()
    {
        return $this->hasMany(WorkOrder::class, 'parent_work_order_id');
    }

    public function downtimeLogs()
    {
        return $this->hasMany(AssetDowntimeLog::class);
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
