<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_number',
        'workflow_id',
        'entity_type',
        'entity_id',
        'amount',
        'status',
        'requested_by',
        'requested_date',
        'current_level',
        'completed_date',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'completed_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id')->orderBy('level_order');
    }

    public function pendingActions()
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id')
            ->whereNull('action');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }
}
