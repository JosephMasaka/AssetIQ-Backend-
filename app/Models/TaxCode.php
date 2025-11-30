<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'tax_type',
        'rate',
        'gl_tax_account_id',
        'gl_offset_account_id',
        'country',
        'active',
        'created_by',
        'company_id',
        'updated_by'
    ];

    public function taxAccount()
    {
        return $this->belongsTo(GeneralLedger::class, 'gl_tax_account_id');
    }

    public function offsetAccount()
    {
        return $this->belongsTo(GeneralLedger::class, 'gl_offset_account_id');
    }
}
