<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_code',
        'name',
        'budget_type',
        'cost_center_id',
        'fiscal_year',
        'period_type',
        'start_date',
        'end_date',
        'total_budget',
        'allocated_amount',
        'committed_amount',
        'actual_amount',
        'available_amount',
        'status',
        'notes',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_budget' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
    ];

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function lineItems()
    {
        return $this->hasMany(BudgetLineItem::class);
    }

    public function requisitions()
    {
        return $this->hasMany(Requisition::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
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
