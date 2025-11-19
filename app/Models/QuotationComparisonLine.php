<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationComparisonLine extends Model
{
    protected $fillable = [
        'comparison_id',
        'quotation_item_id',
        'vendor_id',
        'unit_price',
        'total_price',
        'rank',
        'updated_by',
        'created_by',
        'company_id'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function quotationItem()
    {
        return $this->belongsTo(QuotationItem::class);
    }
}
