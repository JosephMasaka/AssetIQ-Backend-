<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComponentCategory;
use App\Models\User;

class ComponentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('role', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');
            return;
        }

        $categories = [
            ['name' => 'Filters', 'icon' => 'bi-funnel'],
            ['name' => 'Belts', 'icon' => 'bi-arrow-repeat'],
            ['name' => 'Engine Parts', 'icon' => 'bi-gear'],
            ['name' => 'Electrical', 'icon' => 'bi-lightning'],
            ['name' => 'Fluids', 'icon' => 'bi-droplet'],
            ['name' => 'Brakes', 'icon' => 'bi-disc'],
            ['name' => 'Sensors', 'icon' => 'bi-thermometer'],
            ['name' => 'Bearings', 'icon' => 'bi-circle'],
            ['name' => 'Gaskets & Seals', 'icon' => 'bi-border-all'],
            ['name' => 'Hoses & Clamps', 'icon' => 'bi-bezier2'],
        ];

        foreach ($companies as $company) {
            foreach ($categories as $category) {
                ComponentCategory::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $category['name']
                    ],
                    [
                        'icon' => $category['icon']
                    ]
                );
            }
        }

        $this->command->info('Component categories seeded successfully!');
    }
}
