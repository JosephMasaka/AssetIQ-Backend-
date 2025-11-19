<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTax extends Model
{
    protected $table = 'invoice_taxes';
    
    protected $fillable = [
        'invoice_item_id',
        'tax_code',
        'tax_amount',
        'tax_rate'
    ];
}
