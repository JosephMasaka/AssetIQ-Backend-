<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanModule extends Model
{
    protected $table = "module_plan";

    protected $fillable = [
        'plan_id',
        'module_id',
    ];

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    public function module()
    {
        return $this->belongsTo(\App\Models\Module::class);
    }
}
