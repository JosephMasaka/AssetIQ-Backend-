<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_requirement_id',
        'submission_number',
        'submission_date',
        'period_start',
        'period_end',
        'status',
        'findings',
        'actions_taken',
        'evidence_documents',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'review_comments',
        'company_id',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
        'evidence_documents' => 'array',
    ];

    public function requirement()
    {
        return $this->belongsTo(ComplianceRequirement::class, 'compliance_requirement_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
