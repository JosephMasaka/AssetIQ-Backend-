<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationComparison extends Model
{
    protected $fillable = [
        'requisition_id',
        'title',
        'compared_on',
        'created_by',
        'updated_by',
        'company_id'
    ];

    public function lines()
    {
        return $this->hasMany(QuotationComparisonLine::class, 'comparison_id');
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }
}
