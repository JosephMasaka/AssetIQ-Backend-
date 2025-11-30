<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'alpha2',
        'name',
        'phone_code',
        'currency',
        'currency_symbol',
        'active'
    ];
}
