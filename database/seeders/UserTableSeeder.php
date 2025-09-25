<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // --- Super Admin ---
        $superAdmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'tenant_id'         => null, // Global user
                'name'              => 'Super Admin',
                'email'             => 'superadmin@assetiq.com',
                'email_verified_at' => $now,
                'password'          => Hash::make('SuperSecure123!'),
                'role' => 'superadmin',
                'auth_provider'     => 'local',
                'is_active'         => true,
                'phone'             => '+254700000000',
                'job_title'         => 'System Owner',
                'department'        => 'HQ',
                'created_by'        => null,
                'updated_by'        => null,
            ]
        );
        $superAdmin->assignRole('superadmin');

        // --- Reseller ---
        $reseller = User::firstOrCreate(
            ['username' => 'reseller'],
            [
                'tenant_id'         => null,
                'name'              => 'Demo Reseller',
                'email'             => 'reseller@assetiq.com',
                'email_verified_at' => $now,
                'password'          => Hash::make('Reseller123!'),
                'role' => 'reseller',
                'auth_provider'     => 'local',
                'is_active'         => true,
                'phone'             => '+254711111111',
                'job_title'         => 'Reseller Manager',
                'department'        => 'Sales',
                'created_by'        => 1,
                'updated_by'        => 1,
            ]
        );
        $reseller->assignRole('reseller');

        // --- Company Admin ---
        $companyAdmin = User::firstOrCreate(
            ['username' => 'Demo'],
            [
                'tenant_id'         => 2, // example tenant/company
                'name'              => 'Demo Company',
                'email'             => 'admin@company.com',
                'email_verified_at' => $now,
                'password'          => Hash::make('Company123!'),
                'role' => 'company',
                'auth_provider'     => 'local',
                'is_active'         => true,
                'phone'             => '+254722222222',
                'job_title'         => 'IT Admin',
                'department'        => 'IT',
                'created_by'        => 1,
                'updated_by'        => 1,
            ]
        );
        $companyAdmin->assignRole('company');
    }
}
