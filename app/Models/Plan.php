<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Module;

class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'price',
        'duration',
        'max_users',
        'max_assets',
        'description',
        'image',
    ];

    // return $this->belongsToMany(Module::class);

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'module_plan', 'plan_id', 'module_id');
    }
}
