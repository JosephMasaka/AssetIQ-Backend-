<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Plan;

class Module extends Model
{
    protected $table = "modules";

    protected $fillable = [
        'key',
        'name'
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'module_plan', 'module_id', 'plan_id');
    }
}
