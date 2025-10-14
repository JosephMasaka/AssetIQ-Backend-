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
            'reseller:edit',
            'reseller:update',
            'reseller:delete',

            // Tenant
            'tenant:manage',
            'tenant:create',
            'tenant:edit',
            'tenant:update',
            'tenant:delete',

            //Company
            'company:manage',
            'company:create',
            'company:edit',
            'company:update',
            'company:delete',

            // User
            'user:manage',
            'user:create',
            'user:edit',
            'user:update',
            'user:delete',

            // Asset Category
            'asset category:manage',
            'asset category:create',
            'asset category:edit',
            'asset category:update',
            'asset category:delete',

            // Asset
            'asset:manage',
            'asset:create',
            'asset:edit',
            'asset:update',
            'asset:delete',

            // Asset Code/RFID/Barcode
            'asset code:manage',
            'asset code:create',
            'asset code:edit',
            'asset code:update',
            'asset code:delete',

            // Asset Valuation
            'asset valuation:manage',
            'asset valuation:create',
            'asset valuation:edit',
            'asset valuation:update',
            'asset valuation:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:edit',
            'asset attribute:update',
            'asset attribute:delete',

            // Asset History
            'asset history:manage',
            'asset history:create',
            'asset history:edit',
            'asset history:update',
            'asset history:delete',

            // File
            'file:manage',
            'file:create',
            'file:edit',
            'file:update',
            'file:delete',

            // License
            'license:manage',
            'license:create',
            'license:edit',
            'license:update',
            'license:delete',

            // Maintenance
            'maintenance:manage',
            'maintenance:create',
            'maintenance:edit',
            'maintenance:update',
            'maintenance:delete',

            // Components
            'components:manage',
            'components:create',
            'components:edit',
            'components:update',
            'components:delete',

            // consumables
            'consumables:manage',
            'consumables:create',
            'consumables:edit',
            'consumables:update',
            'consumables:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:edit',
            'asset attribute:update',
            'asset attribute:delete',



            // Vendor
            'vendor:manage',
            'vendor:create',
            'vendor:edit',
            'vendor:update',
            'vendor:delete',

            // Asset Vendor
            'asset vendor:manage',
            'asset vendor:create',
            'asset vendor vendor vendor:edit',
            'asset vendor vendor:update',
            'asset vendor:delete',

            // Requisition
            'requisition:manage',
            'requisition:create',
            'requisition:edit',
            'requisition:update',
            'requisition:delete',

            // Purchase Order
            'purchase order:manage',
            'purchase order:create',
            'purchase order:edit',
            'purchase order:update',
            'purchase order:delete',

            // Procurement Module
            'procurement:manage',
            
            // Reports
            'report:view',

            //Plans
            'plans:manage',
            'plans:create',
            'plans:edit',
            'plans:update',
            'plans:delete',

            //Plans Request
            'plan request:manage',
            'plan request:create',
            'plan request Request Request:edit',
            'plan request Request:update',
            'plan request:delete',

            //Coupon
            'coupon:manage',
            'coupon:create',
            'coupon:edit',
            'coupon:update',
            'coupon:delete',

            //Order
            'order:manage',
            'order:create',
            'order:edit',
            'order:update',
            'order:delete',

            //Order
            'tenant:manage',
            'tenant:create',
            'tenant:edit',
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
            'reseller:edit',
            'reseller:update',
            'reseller:delete',

            // Tenant
            'tenant:manage',
            'tenant:create',
            'tenant:edit',
            'tenant:update',
            'tenant:delete',

            //Plans
            'plans:manage',
            'plans:create',
            'plans:edit',
            'plans:update',
            'plans:delete',

            //Plans Request
            'plan request:manage',
            'plan request:create',
            'plan request Request Request:edit',
            'plan request Request:update',
            'plan request:delete',

            //Coupon
            'coupon:manage',
            'coupon:create',
            'coupon:edit',
            'coupon:update',
            'coupon:delete',

            //Order
            'order:manage',
            'order:create',
            'order:edit',
            'order:update',
            'order:delete',

            //Tenant
            'tenant:manage',
            'tenant:create',
            'tenant:edit',
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
            'company:edit',
            'company:update',
            // 'company:delete',

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
            'user:edit',
            'user:update',
            'user:delete',

            // Asset Category
            'asset category:manage',
            'asset category:create',
            'asset category:edit',
            'asset category:update',
            'asset category:delete',

            // Asset
            'asset:manage',
            'asset:create',
            'asset:edit',
            'asset:update',
            'asset:delete',

            // Asset Code/RFID/Barcode
            'asset code:manage',
            'asset code:create',
            'asset code:edit',
            'asset code:update',
            'asset code:delete',

            // Asset Valuation
            'asset valuation:manage',
            'asset valuation:create',
            'asset valuation:edit',
            'asset valuation:update',
            'asset valuation:delete',

            // Asset Valuation
            'asset attribute:manage',
            'asset attribute:create',
            'asset attribute:edit',
            'asset attribute:update',
            'asset attribute:delete',

            // Vendor
            'vendor:manage',
            'vendor:create',
            'vendor:edit',
            'vendor:update',
            'vendor:delete',

            // Asset Vendor
            'asset vendor:manage',
            'asset vendor:create',
            'asset vendor vendor vendor:edit',
            'asset vendor vendor:update',
            'asset vendor:delete',

            // Asset History
            'asset history:manage',
            'asset history:create',
            'asset history:edit',
            'asset history:update',
            'asset history:delete',

            // File
            'file:manage',
            'file:create',
            'file:edit',
            'file:update',
            'file:delete',

            // License
            'license:manage',
            'license:create',
            'license:edit',
            'license:update',
            'license:delete',

            // Maintenance
            'maintenance:manage',
            'maintenance:create',
            'maintenance:edit',
            'maintenance:update',
            'maintenance:delete',

            // Components
            'components:manage',
            'components:create',
            'components:edit',
            'components:update',
            'components:delete',

            // consumables
            'consumables:manage',
            'consumables:create',
            'consumables:edit',
            'consumables:update',
            'consumables:delete',

            // Procurement Module
            'procurement:manage',

            // Requisition
            'requisition:manage',
            'requisition:create',
            'requisition:edit',
            'requisition:update',
            'requisition:delete',

            // Purchase Order
            'purchase order:manage',
            'purchase order:create',
            'purchase order:edit',
            'purchase order:update',
            'purchase order:delete',

            //Reports
            'report:view',

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
