<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'entity_type',
        'description',
        'is_active',
        'requires_sequential',
        'conditions',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_sequential' => 'boolean',
        'conditions' => 'array',
    ];

    public function levels()
    {
        return $this->hasMany(ApprovalLevel::class, 'workflow_id')->orderBy('level_order');
    }

    public function requests()
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
