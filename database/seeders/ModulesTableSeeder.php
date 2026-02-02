<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulesTableSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            // ['key' => 'dashboard', 'name' => 'Dashboard'],
            ['key' => 'assets', 'name' => 'Asset Management'],
            // ['key' => 'inventory', 'name' => 'Inventory'],
            ['key' => 'procurement', 'name' => 'Procurement'],
            ['key' => 'finance', 'name' => 'Finance'],
            ['key' => 'maintenance', 'name' => 'Maintenance'],
            ['key' => 'reports', 'name' => 'Reports'],
            ['key' => 'users', 'name' => 'User Management'],
            // ['key' => 'settings', 'name' => 'Settings'],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['key' => $module['key']],
                [
                    'name' => $module['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
