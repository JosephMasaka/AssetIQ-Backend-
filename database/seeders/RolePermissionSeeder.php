<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // --- Define Permissions ---
        $permissions = [
            // Reseller
            'reseller:manage',
            'reseller:create',
            'reseller:update',
            'reseller:delete',

            // Tenant
            // 'tenant:manage',
            // 'tenant:create',
            // 'tenant:update',
            // 'tenant:delete',

            //Company
            'company:manage',
            'company:create',            
            'company:update',
            'company:delete',

            // User
            'user:manage',
            'user:create',
            'user:update',
            'user:delete',

            // Role
            'role:manage',
            'role:create',
            'role:update',
            'role:delete',

            // Asset Category
            'asset category:manage',
            'asset category:create',
            'asset category:update',
            'asset category:delete',

            // Asset
            'asset:manage',
            'asset:create',
            'asset:update',
            'asset:delete',

            // Asset Code/RFID/Barcode
            'asset code:manage',
            'asset code:create',
            'asset code:update',
            'asset code:delete',

            // Asset Valuation
            'asset valuation:manage',
            'asset valuation:create',
            'asset valuation:update',
            'asset valuation:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:update',
            'asset attribute:delete',

            // Asset History
            'asset history:manage',
            'asset history:create',
            'asset history:update',
            'asset history:delete',

            // File
            'file:manage',
            'file:create',
            'file:update',
            'file:delete',

            // License
            'license:manage',
            'license:create',
            'license:update',
            'license:delete',

            // Maintenance
            'maintenance:manage',
            'maintenance:create',
            'maintenance:update',
            'maintenance:delete',

            // Components
            'components:manage',
            'components:create',
            'components:update',
            'components:delete',

            // consumables
            'consumables:manage',
            'consumables:create',
            'consumables:update',
            'consumables:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:update',
            'asset attribute:delete',



            // Vendor
            'vendor:manage',
            'vendor:create',
            'vendor:update',
            'vendor:delete',

            // Asset Vendor
            'asset vendor:manage',
            'asset vendor:create',
            'asset vendor:update',
            'asset vendor:delete',

            //Components
            'components:manage',
            'components:create',
            'components:update',
            'components:delete',

            //Accessories
            'accessories:manage',
            'accessories:create',
            'accessories:update',
            'accessories:delete',

            //Consumables
            'consumables:manage',
            'consumables:create',
            'consumables:update',
            'consumables:delete',

            // Asset Transfers
            'asset transfer:manage',
            'asset transfer:create',
            'asset transfer:update',
            'asset transfer:delete',

            // Asset Disposals
            'asset disposal:manage',
            'asset disposal:create',
            'asset disposal:update',
            'asset disposal:delete',

            ////////////////////////////////////////////////////////////////////////////
            //Procurement
            // Requisition
            'requisition:manage',
            'requisition:create',
            'requisition:update',
            'requisition:delete',

            // Units of Measure
            'uom:manage',
            'uom:create',
            'uom:update',
            'uom:delete',

            // Requisition Types
            'requisition type:manage',
            'requisition type:create',
            'requisition type:update',
            'requisition type:delete',

            // Request For Quotations
            'rfq:manage',
            'rfq:create',
            'rfq:update',
            'rfq:delete',

            // Quotations
            'quotation:manage',
            'quotation:create',
            'quotation:update',
            'quotation:delete',

            // Goods Receipts
            'goods receipt:manage',
            'goods receipt:create',
            'goods receipt:update',
            'goods receipt:delete',

            // Invoices
            'invoice:manage',
            'invoice:create',
            'invoice:update',
            'invoice:delete',

            // Purchase Order
            'purchase order:manage',
            'purchase order:create',
            'purchase order:update',
            'purchase order:delete',

            // Procurement Module
            'procurement:manage',

            //////////////////////////////////////////////////////////////////////////////
            //Approvals
            'approval:manage',
            'approval:create',
            'approval:update',
            'approval:delete',

            //////////////////////////////////////////////////////////////////////////////
            //Finance
            'finance:manage',

            // Cost Centers
            'cost center:manage',
            'cost center:create',
            'cost center:update',
            'cost center:delete',

            // Budgets
            'budget:manage',
            'budget:create',
            'budget:update',
            'budget:delete',

            // Chart of Accounts
            'chart of accounts:manage',
            'chart of accounts:create',
            'chart of accounts:update',
            'chart of accounts:delete',

            // Tax Codes
            'tax code:manage',
            'tax code:create',
            'tax code:update',
            'tax code:delete',

            // Asset Classes
            'asset class:manage',
            'asset class:create',
            'asset class:update',
            'asset class:delete',

            // Depreciation Run
            'depreciation run:manage',

            // Asset History Sheet
            'asset history sheet:manage',

            // GR/IR Clearing
            'grir clearing:manage',

            // Vendor Reconciliation
            'vendor reconciliation:manage',

            // AP Invoices
            'ap invoice:manage',
            'ap invoice:create',
            'ap invoice:update',
            'ap invoice:delete',

            // ERP Export
            'erp export:manage',

            'gl accounts:manage',
            'gl accounts:create',
            'gl accounts:update',
            'gl accounts:delete',

            'depreciation keys:manage',
            'depreciation keys:create',
            'depreciation keys:update',
            'depreciation keys:delete',

            'depreciation rules:manage',
            'depreciation rules:create',
            'depreciation rules:update',
            'depreciation rules:delete',

            'depreciation areas:manage',
            'depreciation areas:create',
            'depreciation areas:update',
            'depreciation areas:delete',

            'account groups:manage',
            'account groups:create',
            'account groups:update',
            'account groups:delete',

            //////////////////////////////////////////////////////////////////////////////
            //Maintenance
            // Work Orders
            'work order:manage',
            'work order:create',
            'work order:update',
            'work order:delete',

            // Maintenance Contracts
            'maintenance contract:manage',
            'maintenance contract:create',
            'maintenance contract:update',
            'maintenance contract:delete',

            // Preventive Maintenance
            'preventive maintenance:manage',
            'preventive maintenance:create',
            'preventive maintenance:update',
            'preventive maintenance:delete',

            // Spare Parts
            'spare part:manage',
            'spare part:create',
            'spare part:update',
            'spare part:delete',
            
            ///////////////////////////////////////////////////////////////////////////////////////////////
            // Reports
            'report:manage',

            'audit:manage',
            'audit:create',
            'audit:update',
            'audit:delete',

            'compliance:manage',
            'compliance:create',
            'compliance:update',
            'compliance:delete',

            ////////////////////////////////////////////////////////////////////////////////////////////
            //AI
            'ai:manage',
            'ai chat:manage',
            'ai predictions:manage',
            'ai triage:manage',
            'ai digest:manage',
            'ai reports:manage',

            ////////////////////////////////////////////////////////////////////////////////////////////
            //Plans
            'plans:manage',
            'plans:create',
            'plans:update',
            'plans:delete',

            //Plans Request
            'plan request:manage',
            'plan request:create',
            'plan request:update',
            'plan request:delete',

            //Coupon
            'coupon:manage',
            'coupon:create',
            'coupon:update',
            'coupon:delete',

            //Order
            'order:manage',
            'order:create',
            'order:update',
            'order:delete',

            //Order
            'tenant:manage',
            'tenant:create',
            'tenant:update',
            'tenant:delete',

            //Administration
            'administration:manage',
            'activity logs:manage',

        ];

        // --- Group Permissions per Role ---
        $superAdminPermissions = [
            // Reseller
            'reseller:manage',
            'reseller:create',
            'reseller:update',
            'reseller:delete',

            // Tenant
            'company:manage',
            'company:create',
            'company:update',
            'company:delete',

            //Plans
            'plans:manage',
            'plans:create',
            'plans:update',
            'plans:delete',

            //Plans Request
            'plan request:manage',
            'plan request:create',
            'plan request:update',
            'plan request:delete',

            //Coupon
            'coupon:manage',
            'coupon:create',
            'coupon:update',
            'coupon:delete',

            //Order
            'order:manage',
            'order:create',
            'order:update',
            'order:delete',

            //Tenant
            // 'tenant:manage',
            // 'tenant:create',
            // 'tenant:update',
            // 'tenant:delete',

            //Administration
            'administration:manage',
            'activity logs:manage',

        ];

        $resellerPermissions = [

            //Company
            'company:manage',
            'company:create',
            'company:update',
            'company:delete',

            //User
            'user:create',

            //Coupon
            'coupon:manage',

            //Order
            'order:manage',

            //Plans
            'plans:manage',

            //Plan request
            'plan request:manage',

            //Admin
            'administration:manage',
            'activity logs:manage',

        ];

        $companyPermissions = [
            //User
            'user:manage',
            'user:create',
            'user:update',
            'user:delete',

            // Role
            'role:manage',
            'role:create',
            'role:update',
            'role:delete',

            // Asset Category
            'asset category:manage',
            'asset category:create',
            'asset category:update',
            'asset category:delete',

            // Asset
            'asset:manage',
            'asset:create',
            'asset:update',
            'asset:delete',

            // Asset Code/RFID/Barcode
            'asset code:manage',
            'asset code:create',
            'asset code:update',
            'asset code:delete',

            // Asset Valuation
            'asset valuation:manage',
            'asset valuation:create',
            'asset valuation:update',
            'asset valuation:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:update',
            'asset attribute:delete',

            // Asset Transfers
            'asset transfer:manage',
            'asset transfer:create',
            'asset transfer:update',
            'asset transfer:delete',

            // Asset Disposals
            'asset disposal:manage',
            'asset disposal:create',
            'asset disposal:update',
            'asset disposal:delete',

            // Vendor
            'vendor:manage',
            'vendor:create',
            'vendor:update',
            'vendor:delete',

            // Asset Vendor
            'asset vendor:manage',
            'asset vendor:create',
            'asset vendor:update',
            'asset vendor:delete',

            // Asset History
            'asset history:manage',
            'asset history:create',
            'asset history:update',
            'asset history:delete',

            // File
            'file:manage',
            'file:create',
            'file:update',
            'file:delete',

            // License
            'license:manage',
            'license:create',
            'license:update',
            'license:delete',

            // Maintenance
            'maintenance:manage',
            'maintenance:create',
            'maintenance:update',
            'maintenance:delete',

            // Components
            'components:manage',
            'components:create',
            'components:update',
            'components:delete',

            // consumables
            'consumables:manage',
            'consumables:create',
            'consumables:update',
            'consumables:delete',

            // Procurement Module
            'procurement:manage',

            // Units of Measure
            'uom:manage',
            'uom:create',
            'uom:update',
            'uom:delete',

            // Requisition Types
            'requisition type:manage',
            'requisition type:create',
            'requisition type:update',
            'requisition type:delete',

            // Request For Quotations
            'rfq:manage',
            'rfq:create',
            'rfq:update',
            'rfq:delete',

            // Quotations
            'quotation:manage',
            'quotation:create',
            'quotation:update',
            'quotation:delete',

            // Goods Receipts
            'goods receipt:manage',
            'goods receipt:create',
            'goods receipt:update',
            'goods receipt:delete',

            // Invoices
            'invoice:manage',
            'invoice:create',
            'invoice:update',
            'invoice:delete',

            // Requisition
            'requisition:manage',
            'requisition:create',
            'requisition:update',
            'requisition:delete',

            // Purchase Order
            'purchase order:manage',
            'purchase order:create',
            'purchase order:update',
            'purchase order:delete',

            //Finance
            'finance:manage',

            'gl accounts:manage',
            'gl accounts:create',
            'gl accounts:update',
            'gl accounts:delete',

            'depreciation keys:manage',
            'depreciation keys:create',
            'depreciation keys:update',
            'depreciation keys:delete',

            'depreciation rules:manage',
            'depreciation rules:create',
            'depreciation rules:update',
            'depreciation rules:delete',

            'depreciation areas:manage',
            'depreciation areas:create',
            'depreciation areas:update',
            'depreciation areas:delete',

            'account groups:manage',
            'account groups:create',
            'account groups:update',
            'account groups:delete',

            //////////////////////////////////////////////////////////////////////////////
            //Approvals
            'approval:manage',
            'approval:create',
            'approval:update',
            'approval:delete',

            //////////////////////////////////////////////////////////////////////////////
            //Finance
            'finance:manage',

            // Cost Centers
            'cost center:manage',
            'cost center:create',
            'cost center:update',
            'cost center:delete',

            // Budgets
            'budget:manage',
            'budget:create',
            'budget:update',
            'budget:delete',

            // Chart of Accounts
            'chart of accounts:manage',
            'chart of accounts:create',
            'chart of accounts:update',
            'chart of accounts:delete',

            // Tax Codes
            'tax code:manage',
            'tax code:create',
            'tax code:update',
            'tax code:delete',

            // Asset Classes
            'asset class:manage',
            'asset class:create',
            'asset class:update',
            'asset class:delete',

            // Depreciation Run
            'depreciation run:manage',

            // Asset History Sheet
            'asset history sheet:manage',

            // GR/IR Clearing
            'grir clearing:manage',

            // Vendor Reconciliation
            'vendor reconciliation:manage',

            // AP Invoices
            'ap invoice:manage',
            'ap invoice:create',
            'ap invoice:update',
            'ap invoice:delete',

            // ERP Export
            'erp export:manage',


            //Reports
            'report:manage',

            
            'audit:manage',
            'audit:create',
            'audit:update',
            'audit:delete',

            'compliance:manage',
            'compliance:create',
            'compliance:update',
            'compliance:delete',

            //////////////////////////////////////////////////////////////////////////////
            //Maintenance
            // Work Orders
            'work order:manage',
            'work order:create',
            'work order:update',
            'work order:delete',

            // Maintenance Contracts
            'maintenance contract:manage',
            'maintenance contract:create',
            'maintenance contract:update',
            'maintenance contract:delete',

            // Preventive Maintenance
            'preventive maintenance:manage',
            'preventive maintenance:create',
            'preventive maintenance:update',
            'preventive maintenance:delete',

            // Spare Parts
            'spare part:manage',
            'spare part:create',
            'spare part:update',
            'spare part:delete',

            //AI
            'ai:manage',
            'ai chat:manage',
            'ai predictions:manage',
            'ai triage:manage',
            'ai digest:manage',
            'ai reports:manage',

            //Plans
            'plans:manage',

            //Plan Request
            'plan request:manage',

            //Admin
            'administration:manage',
            'activity logs:manage',
        ];

        // --- Create Permissions if Missing ---
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // --- Define Roles ---
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $reseller   = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
        $company    = Role::firstOrCreate(['name' => 'company', 'guard_name' => 'web']);

        // --- Assign Permissions ---
        $superAdmin->syncPermissions($superAdminPermissions);
        $reseller->syncPermissions($resellerPermissions);
        $company->syncPermissions($companyPermissions);
    }
}
