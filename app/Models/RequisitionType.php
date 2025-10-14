<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_code',
        'name',
        'category',
        'requires_approval',
        'is_active',
        'sap_reference',
        'description',
        'created_by',
        'updated_by',
        'company_id'
    ];

    // Relationships
    public function requisitions()
    {
        return $this->hasMany(Requisition::class, 'requisition_type_id');
    }
}
