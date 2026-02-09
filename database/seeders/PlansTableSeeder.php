<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Module;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial License',
                'price' => 0, // Ksh per month
                'duration' => 'monthly',
                'max_users' => 2,
                'max_assets' => 10,
                'description' => 'Free Trial.',
                'image' => null,
                // 'modules' => ['assets', 'users'],
            ],
            [
                'name' => 'Bronze License',
                'price' => 2500, // Ksh per month
                'duration' => 'monthly',
                'max_users' => 5,
                'max_assets' => 200,
                'description' => 'Starter plan for small businesses managing basic assets and users.',
                'image' => null,
                // 'modules' => ['assets', 'users'],
            ],
            [
                'name' => 'Silver License',
                'price' => 6500, // Ksh per month
                'duration' => 'monthly',
                'max_users' => 15,
                'max_assets' => 1000,
                'description' => 'Ideal for growing SMEs with procurement, reports and asset tracking.',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'users', 'reports'],
            ],
            [
                'name' => 'Gold License',
                'price' => 15000, // Ksh per month
                'duration' => 'monthly',
                'max_users' => 50,
                'max_assets' => 5000,
                'description' => 'For established companies needing finance, maintenance and full reporting.',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'finance', 'maintenance', 'users', 'reports'],
            ],
            [
                'name' => 'Platinum License',
                'price' => 35000, // Ksh per month
                'duration' => 'monthly',
                'max_users' => 200,
                'max_assets' => 20000,
                'description' => 'Enterprise-grade plan with all modules, high limits and priority support.',
                'image' => null,
                // 'modules' => ['assets', 'procurement', 'finance', 'maintenance', 'users', 'reports', 'settings'],
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(
                ['name' => $planData['name']],
                [
                    'price' => $planData['price'],
                    'duration' => $planData['duration'],
                    'max_users' => $planData['max_users'],
                    'max_assets' => $planData['max_assets'],
                    'description' => $planData['description'],
                    'image' => $planData['image'],
                ]
            );

            if (!empty($planData['modules'])) {
                $moduleIds = Module::whereIn('key', $planData['modules'])->pluck('id')->toArray();
                $plan->modules()->sync($moduleIds);
            }
        }
    }
}
