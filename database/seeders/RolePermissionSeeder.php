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

            // User
            'user:manage',
            'user:create',
            'user:edit',
            'user:update',
            'user:delete',

            // Asset
            'asset:manage',
            'asset:create',

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

        ];

        // --- Group Permissions per Role ---
        $superAdminPermissions = $permissions; // Full set

        $resellerPermissions = [
            // 'reseller:manage',
            'tenant:create',
            'tenant:manage',
            'user:create',
            'order:manage',
            'plans:manage',
            'plan request:manage',
        ];

        $companyPermissions = [
            'user:manage',
            'user:create',
            'user:edit',
            'user:update',
            'user:delete',
            'asset:manage',
            'asset:create',
            'report:view',
            'plans:manage',
            'plan request:manage',
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
