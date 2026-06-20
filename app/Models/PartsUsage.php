<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartsUsage extends Model
{
    use HasFactory;

    protected $table = 'parts_usage';

    protected $fillable = [
        'spare_part_id',
        'work_order_id',
        'maintenance_id',
        'asset_id',
        'quantity_used',
        'unit_cost',
        'total_cost',
        'usage_date',
        'used_by',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
