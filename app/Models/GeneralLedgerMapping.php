<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralLedgerMapping extends Model
{
    protected $fillable = [
        'company_id',
        'asset_class_id',
        'transaction_type',
        'debit_gl_id',
        'credit_gl_id',
        'created_by',
    ];

    /* -----------------------------
     | Relationships
     |------------------------------
     */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assetClass()
    {
        return $this->belongsTo(AssetClass::class, 'asset_class_id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(GlAccount::class, 'debit_gl_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(GlAccount::class, 'credit_gl_id');
    }
}
