<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'approval_level_id',
        'level_order',
        'approver_id',
        'action',
        'actioned_by',
        'action_date',
        'comments',
        'attachments',
        'delegated_to',
        'delegated_at',
        'notification_sent_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'delegated_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function approvalLevel()
    {
        return $this->belongsTo(ApprovalLevel::class, 'approval_level_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function delegatedTo()
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }
}
