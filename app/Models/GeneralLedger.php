<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    protected $table = "gl_accounts";

    protected $fillable = [
        'company_id',
        'created_by',
        'gl_code',
        'name',
        'type',
        'asset_category',
        'long_text',
        'account_group',
        'reconciliation_type',
        'currency',
        'sort_key',
        'balance_sheet_account',
        'is_active',
    ];

    protected $casts = [
        'balance_sheet_account' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    public function group()
    {
        return $this->belongsTo(AccountGroup::class, 'account_group');
    }

}

