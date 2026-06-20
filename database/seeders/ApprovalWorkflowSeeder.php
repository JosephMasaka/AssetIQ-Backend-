<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->createRequisitionWorkflow($company->id);
            $this->createPurchaseOrderWorkflow($company->id);
            $this->createCapexWorkflow($company->id);
            $this->createAssetDisposalWorkflow($company->id);
        }
    }

    protected function createRequisitionWorkflow(int $companyId): void
    {
        $workflow = ApprovalWorkflow::create([
            'name' => 'Standard Requisition Approval',
            'code' => 'REQ_STANDARD',
            'entity_type' => 'requisition',
            'description' => 'Standard approval workflow for purchase requisitions',
            'is_active' => true,
            'requires_sequential' => true,
            'company_id' => $companyId,
            'created_by' => 1,
        ]);

        // Level 1: Department Manager (up to $5,000)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 1,
            'level_name' => 'Department Manager',
            'approver_type' => 'dynamic',
            'dynamic_rule' => 'requester_manager',
            'amount_threshold_min' => 0,
            'amount_threshold_max' => 5000,
            'can_delegate' => true,
            'auto_approve_days' => 7,
            'required' => true,
        ]);

        // Level 2: Finance Manager ($5,000 - $50,000)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 2,
            'level_name' => 'Finance Manager',
            'approver_type' => 'role',
            'approver_role_id' => 3, // Finance Manager role
            'amount_threshold_min' => 5000,
            'amount_threshold_max' => 50000,
            'can_delegate' => true,
            'auto_approve_days' => 5,
            'required' => true,
        ]);

        // Level 3: Finance Director ($50,000+)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 3,
            'level_name' => 'Finance Director',
            'approver_type' => 'role',
            'approver_role_id' => 2, // Finance Director role
            'amount_threshold_min' => 50000,
            'amount_threshold_max' => null,
            'can_delegate' => false,
            'auto_approve_days' => null,
            'required' => true,
        ]);
    }

    protected function createPurchaseOrderWorkflow(int $companyId): void
    {
        $workflow = ApprovalWorkflow::create([
            'name' => 'Purchase Order Approval',
            'code' => 'PO_STANDARD',
            'entity_type' => 'purchase_order',
            'description' => 'Approval workflow for purchase orders',
            'is_active' => true,
            'requires_sequential' => true,
            'company_id' => $companyId,
            'created_by' => 1,
        ]);

        // Level 1: Procurement Manager (all POs)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 1,
            'level_name' => 'Procurement Manager',
            'approver_type' => 'role',
            'approver_role_id' => 4, // Procurement Manager role
            'amount_threshold_min' => 0,
            'amount_threshold_max' => null,
            'can_delegate' => true,
            'auto_approve_days' => 5,
            'required' => true,
        ]);

        // Level 2: Finance Director ($25,000+)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 2,
            'level_name' => 'Finance Director',
            'approver_type' => 'role',
            'approver_role_id' => 2,
            'amount_threshold_min' => 25000,
            'amount_threshold_max' => null,
            'can_delegate' => false,
            'auto_approve_days' => null,
            'required' => true,
        ]);

        // Level 3: CEO ($100,000+)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 3,
            'level_name' => 'CEO',
            'approver_type' => 'role',
            'approver_role_id' => 1, // CEO role
            'amount_threshold_min' => 100000,
            'amount_threshold_max' => null,
            'can_delegate' => false,
            'auto_approve_days' => null,
            'required' => true,
        ]);
    }

    protected function createCapexWorkflow(int $companyId): void
    {
        $workflow = ApprovalWorkflow::create([
            'name' => 'Capital Expenditure Approval',
            'code' => 'CAPEX_STANDARD',
            'entity_type' => 'capex_request',
            'description' => 'Approval workflow for capital expenditure requests',
            'is_active' => true,
            'requires_sequential' => true,
            'company_id' => $companyId,
            'created_by' => 1,
        ]);

        // Level 1: Department Manager
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 1,
            'level_name' => 'Department Manager',
            'approver_type' => 'dynamic',
            'dynamic_rule' => 'requester_manager',
            'can_delegate' => true,
            'auto_approve_days' => 7,
            'required' => true,
        ]);

        // Level 2: Finance Director
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 2,
            'level_name' => 'Finance Director',
            'approver_type' => 'role',
            'approver_role_id' => 2,
            'can_delegate' => false,
            'required' => true,
        ]);

        // Level 3: CEO
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 3,
            'level_name' => 'CEO',
            'approver_type' => 'role',
            'approver_role_id' => 1,
            'can_delegate' => false,
            'required' => true,
        ]);
    }

    protected function createAssetDisposalWorkflow(int $companyId): void
    {
        $workflow = ApprovalWorkflow::create([
            'name' => 'Asset Disposal Approval',
            'code' => 'DISPOSAL_STANDARD',
            'entity_type' => 'asset_disposal',
            'description' => 'Approval workflow for asset disposals',
            'is_active' => true,
            'requires_sequential' => true,
            'company_id' => $companyId,
            'created_by' => 1,
        ]);

        // Level 1: Asset Manager
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 1,
            'level_name' => 'Asset Manager',
            'approver_type' => 'role',
            'approver_role_id' => 5, // Asset Manager role
            'can_delegate' => true,
            'auto_approve_days' => 5,
            'required' => true,
        ]);

        // Level 2: Finance Manager
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 2,
            'level_name' => 'Finance Manager',
            'approver_type' => 'role',
            'approver_role_id' => 3,
            'can_delegate' => false,
            'required' => true,
        ]);

        // Level 3: CEO (high-value assets)
        ApprovalLevel::create([
            'workflow_id' => $workflow->id,
            'level_order' => 3,
            'level_name' => 'CEO',
            'approver_type' => 'role',
            'approver_role_id' => 1,
            'amount_threshold_min' => 50000,
            'amount_threshold_max' => null,
            'can_delegate' => false,
            'required' => true,
        ]);
    }
}
