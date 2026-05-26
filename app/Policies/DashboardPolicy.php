// App/Policies/DaschboardPolicy

<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public static function tabs(User $user): array
    {
        $tabs = ['overview'];

        if ($user->can('procurement:manage')) {
            $tabs[] = 'procurement';
        }

        if ($user->can('asset:manage')) {
            $tabs[] = 'assets';
        }

        if ($user->can('finance:manage')) {
            $tabs[] = 'finance';
        }

        return $tabs;
    }

    public static function quickActions(User $user): array
    {
        return [
            [
                'permission' => 'asset:create',
                'label' => 'New Asset',
                'icon' => 'bi-plus-circle',
                'route' => '/admin/assetmaster/create'
            ],

            [
                'permission' => 'requisition:create',
                'label' => 'New PR',
                'icon' => 'bi-file-earmark-plus',
                'route' => '/admin/requisitions/create'
            ],

            [
                'permission' => 'maintenance:create',
                'label' => 'Log Maintenance',
                'icon' => 'bi-tools',
                'route' => '/admin/maintenance/create'
            ],

            [
                'permission' => 'vendor:manage',
                'label' => 'Vendors',
                'icon' => 'bi-shop',
                'route' => '/admin/vendors'
            ],
        ];
    }
}


<?php

namespace App\Support\Dashboard;

use App\Models\User;

class DashboardVisibility
{
    public static function tabs(User $user): array
    {
        $tabs = ['overview'];

        if ($user->can('procurement:manage')) {
            $tabs[] = 'procurement';
        }

        if ($user->can('asset:manage')) {
            $tabs[] = 'assets';
        }

        if ($user->can('finance:manage')) {
            $tabs[] = 'finance';
        }

        return $tabs;
    }

    public static function quickActions(User $user): array
    {
        return [
            [
                'permission' => 'asset:create',
                'label' => 'New Asset',
                'icon' => 'bi-plus-circle',
                'route' => '/admin/assetmaster/create'
            ],

            [
                'permission' => 'requisition:create',
                'label' => 'New PR',
                'icon' => 'bi-file-earmark-plus',
                'route' => '/admin/requisitions/create'
            ],

            [
                'permission' => 'maintenance:create',
                'label' => 'Log Maintenance',
                'icon' => 'bi-tools',
                'route' => '/admin/maintenance/create'
            ],

            [
                'permission' => 'vendor:manage',
                'label' => 'Vendors',
                'icon' => 'bi-shop',
                'route' => '/admin/vendors'
            ],
        ];
    }
}