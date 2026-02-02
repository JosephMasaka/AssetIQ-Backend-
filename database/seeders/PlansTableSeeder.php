<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use App\Models\Module;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Plan',
                'price' => 0,
                'duration' => 'monthly',
                'max_users' => 2,
                'max_assets' => 50,
                'description' => 'Basic access for small teams',
                'image' => null,
                // 'modules' => ['assets', 'users'], // module keys
            ],
            [
                'name' => 'Bronze License',
                'price' => 10,
                'duration' => 'monthly',
                'max_users' => 5,
                'max_assets' => 200,
                'description' => 'Good for small businesses',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'users'],
            ],
            [
                'name' => 'Silver License',
                'price' => 25,
                'duration' => 'monthly',
                'max_users' => 15,
                'max_assets' => 1000,
                'description' => 'For growing companies',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'finance', 'maintenance', 'users', 'reports'],
            ],
            [
                'name' => 'Gold License',
                'price' => 50,
                'duration' => 'monthly',
                'max_users' => 50,
                'max_assets' => 5000,
                'description' => 'For large teams and enterprises',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'finance', 'maintenance', 'users', 'reports', 'settings'],
            ],
        ];

        foreach ($plans as $planData) {
            // Insert or update plan
            $plan = Plan::updateOrCreate(
                ['name' => $planData['name']],
                array_merge($planData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );

            // Attach modules
            if (!empty($planData['modules'])) {
                $moduleIds = Module::whereIn('key', $planData['modules'])->pluck('id')->toArray();
                $plan->modules()->sync($moduleIds);
            }
        }
    }
}
