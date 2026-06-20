<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Requisition extends Model
{
    use HasFactory;

    protected $table = 'requisitions';

    protected $fillable = [
        'req_number',
        'requisition_type_id',
        'requested_by',
        'request_date',
        'status',
        'justification',
        'capex_request_id',
        'approval_request_id',
        'approval_status',
        'budget_id',
        'cost_center_id',
        'company_id',
        'created_by',
        'changed_by'
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function capexRequest()
    {
        return $this->belongsTo(CapexRequest::class, 'capex_request_id', 'capex_request_id');
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function requisitionType()
    {
        return $this->belongsTo(RequisitionType::class, 'requisition_type_id');
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'requisition_vendors', 'requisition_id', 'vendor_id')
            ->withPivot(['rfq_number', 'rfq_date', 'response_deadline', 'quoted_amount', 'status', 'remarks'])
            ->withTimestamps();
    }

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
