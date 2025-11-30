<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\GeneralLedger;

class AccountGroup extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
        'created_by',
        'company_id',
        'updated_by'
    ];

    // Example: Link to GL accounts
    public function glAccounts()
    {
        return $this->hasMany(GeneralLedger::class, 'account_group');
    }
}
