<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'level_order',
        'level_name',
        'approver_type',
        'approver_role_id',
        'approver_user_id',
        'dynamic_rule',
        'amount_threshold_min',
        'amount_threshold_max',
        'can_delegate',
        'auto_approve_days',
        'required',
    ];

    protected $casts = [
        'amount_threshold_min' => 'decimal:2',
        'amount_threshold_max' => 'decimal:2',
        'can_delegate' => 'boolean',
        'required' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function approverRole()
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function approverUser()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
