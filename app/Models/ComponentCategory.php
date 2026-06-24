<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentCategory extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'icon',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'category_id');
    }
}