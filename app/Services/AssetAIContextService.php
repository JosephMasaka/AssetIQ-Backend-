<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetAssignment;
use App\Models\Vendor;
use Carbon\Carbon;

class AssetAIContextService
{
    public function build(): array
    {
        $company = auth()->user()->getCompany();

        $totalAssets = Asset::where(
            'company_id',
            $company
        )->count();

        $activeAssets = Asset::where(
            'company_id',
            $company
        )
        ->where('status', 'active')
        ->count();

        $maintenanceAssets = Asset::where(
            'company_id',
            $company
        )
        ->where('status', 'under_maintenance')
        ->count();

        $disposedAssets = Asset::where(
            'company_id',
            $company
        )
        ->where('status', 'disposed')
        ->count();

        $assignedAssets = AssetAssignment::whereHas(
            'asset',
            fn($q) =>
                $q->where(
                    'company_id',
                    $company
                )
        )
        ->whereNull('returned_date')
        ->count();

        $vendors = Vendor::where(
            'company_id',
            $company
        )->count();

        return [

            'company_id' => $company,

            'dashboard' => [

                'total_assets' => $totalAssets,

                'active_assets' => $activeAssets,

                'maintenance_assets' => $maintenanceAssets,

                'disposed_assets' => $disposedAssets,

                'assigned_assets' => $assignedAssets,

                'vendors' => $vendors,

                'asset_health_score' =>
                    $this->calculateHealthScore(
                        $totalAssets,
                        $maintenanceAssets,
                        $disposedAssets
                    ),

                'ai_savings' =>
                    $this->calculateEstimatedSavings(
                        $maintenanceAssets
                    )
            ],

            'assets' => [

                'total' => $totalAssets,

                'active' => $activeAssets,

                'maintenance' => $maintenanceAssets,

                'disposed' => $disposedAssets

            ],

            'categories' => AssetCategory::where(
                'company_id',
                $company
            )
            ->withCount('assets')
            ->get()
            ->map(fn($item) => [

                'name' => $item->name,

                'assets' => $item->assets_count

            ]),

            'recent_assets' => Asset::where(
                'company_id',
                $company
            )
            ->with([
                'category',
                'vendors'
            ])
            ->latest()
            ->limit(100)
            ->get(),

            'assignments' => AssetAssignment::whereHas(
                'asset',
                fn($q) =>
                    $q->where(
                        'company_id',
                        $company
                    )
            )
            ->with([
                'employee',
                'asset'
            ])
            ->latest()
            ->limit(50)
            ->get(),

            'suggested_questions' => [

                'Which assets are under maintenance?',

                'Show me asset distribution by category',

                'Which vendor supplies most assets?',

                'Which assets are currently assigned?',

                'Show inactive assets',

                'What is the current asset health score?'

            ]
        ];
    }

    private function calculateHealthScore(
        int $total,
        int $maintenance,
        int $disposed
    ): int {

        if ($total === 0) {
            return 100;
        }

        $badAssets =
            $maintenance +
            $disposed;

        return max(
            0,
            round(
                (($total - $badAssets) / $total)
                * 100
            )
        );
    }

    private function calculateEstimatedSavings(
        int $maintenanceAssets
    ): float {

        return round(
            $maintenanceAssets * 250,
            2
        );
    }
}