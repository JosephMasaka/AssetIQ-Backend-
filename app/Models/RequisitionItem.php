<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'description',
        'quantity',
        'uom_id',
        'estimated_cost',
        'company_id',
        'created_by',
        'changed_by'
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }
}
