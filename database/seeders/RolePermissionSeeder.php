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
            'tenant:manage',
            'tenant:create',
            'tenant:update',
            'tenant:delete',

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

            // Procurement Module
            'procurement:manage',

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
            
            // Reports
            'report:manage',

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
            'tenant:manage',
            'tenant:create',
            'tenant:update',
            'tenant:delete',

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
            'tenant:manage',
            'tenant:create',
            'tenant:update',
            'tenant:delete',

            //Administration
            'administration:manage',
            'activity logs:manage',

        ];

        $resellerPermissions = [
            // 'reseller:manage',
            // 'tenant:create',
            // 'tenant:manage',

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

            //Reports
            'report:manage',

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
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
        }

        // --- Define Roles ---
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $reseller   = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'api']);
        $company    = Role::firstOrCreate(['name' => 'company', 'guard_name' => 'api']);

        // --- Assign Permissions ---
        $superAdmin->syncPermissions($superAdminPermissions);
        $reseller->syncPermissions($resellerPermissions);
        $company->syncPermissions($companyPermissions);
    }
}
