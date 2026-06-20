<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_code',
        'title',
        'description',
        'regulation_type',
        'category',
        'frequency',
        'start_date',
        'next_due_date',
        'responsible_user_id',
        'status',
        'evidence_required',
        'is_critical',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_due_date' => 'date',
        'is_critical' => 'boolean',
    ];

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function submissions()
    {
        return $this->hasMany(ComplianceSubmission::class);
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
