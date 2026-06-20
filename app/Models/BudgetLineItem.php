<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'gl_account_id',
        'asset_category_id',
        'line_item_name',
        'description',
        'budgeted_amount',
        'allocated_amount',
        'committed_amount',
        'actual_amount',
        'variance',
        'variance_percentage',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'committed_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function glAccount()
    {
        return $this->belongsTo(GeneralLedger::class, 'gl_account_id');
    }

    public function assetCategory()
    {
        return $this->belongsTo(AssetCategory::class);
    }
}
