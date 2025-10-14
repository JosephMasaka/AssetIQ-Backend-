<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitOfMeasure extends Model
{
    use HasFactory;

    protected $table = 'units_of_measure';
    protected $primaryKey = 'uom_id';

    protected $fillable = [
        'uom_code',
        'uom_name',
        'uom_category',
        'sap_uom_reference',
        'is_active',
        'created_by',
        'updated_by',
        'company_id'
    ];

    /**
     * Relationship: UoM → RequisitionItems (one-to-many)
     */
    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class, 'uom_id', 'uom_id');
    }
}
