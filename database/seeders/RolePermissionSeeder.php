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
        ];

        // --- Group Permissions per Role ---
        $superAdminPermissions = $permissions; // Full set

        $resellerPermissions = [
            'reseller:manage',
            'tenant:create',
            'tenant:manage',
            'user:create',
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
        ];

        // --- Create Permissions if Missing ---
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // --- Define Roles ---
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $reseller   = Role::firstOrCreate(['name' => 'Reseller']);
        $company    = Role::firstOrCreate(['name' => 'CompanyAdmin']);

        // --- Assign Permissions ---
        $superAdmin->syncPermissions($superAdminPermissions);
        $reseller->syncPermissions($resellerPermissions);
        $company->syncPermissions($companyPermissions);
    }
}
