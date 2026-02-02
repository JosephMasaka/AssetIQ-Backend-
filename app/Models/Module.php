<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    return $this->belongsToMany(Plan::class);
}
