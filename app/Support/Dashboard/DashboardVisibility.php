<?php

namespace App\Support\Dashboard;

use App\Models\User;

class DashboardVisibility
{
    public static function tabs(User $user): array
    {
        $tabs = ['overview'];

        $permissions = collect();

        if ($user->permissions) {
            $permissions = $permissions->merge($user->permissions->pluck('name'));
        }

        foreach ($user->roles as $role) {
            if ($role->permissions) {
                $permissions = $permissions->merge($role->permissions->pluck('name'));
            }
        }

        $permissions = $permissions->unique()->values();

        // Helper closure instead of $user->can()
        $can = fn(string $perm) => $permissions->contains($perm);

        if ($can('procurement:manage')) $tabs[] = 'procurement';
        if ($can('asset:manage'))       $tabs[] = 'assets';
        if ($can('finance:manage'))     $tabs[] = 'finance';
        if ($can('maintenance and work orders:manage'))     $tabs[] = 'maintenance';
        if ($can('report:manage'))     $tabs[] = 'reports';

        return $tabs;
    }

    public static function quickActions(User $user): array
    {
        $all = [
            // Superadmin
            ['permission' => 'reseller:create',        'label' => 'New Reseller',      'icon' => 'bi-person-plus',       'route' => '/admin/reseller/create'],
            ['permission' => 'company:create',          'label' => 'New Company',       'icon' => 'bi-building-add',      'route' => '/admin/company/create'],
            ['permission' => 'plan request:manage',     'label' => 'Plan Requests',     'icon' => 'bi-file-earmark-check','route' => '/admin/plan-requests'],
            ['permission' => 'plans:manage',            'label' => 'Manage Plans',      'icon' => 'bi-diagram-3',         'route' => '/admin/plans'],
            // Company
            ['permission' => 'asset:create',            'label' => 'New Asset',         'icon' => 'bi-plus-circle',       'route' => '/admin/assetmaster/create'],
            ['permission' => 'requisition:create',      'label' => 'New PR',            'icon' => 'bi-file-earmark-plus', 'route' => '/admin/requisitions/create'],
            ['permission' => 'maintenance:create',      'label' => 'Log Maintenance',   'icon' => 'bi-tools',             'route' => '/admin/maintenance/create'],
            ['permission' => 'vendor:manage',           'label' => 'Vendors',           'icon' => 'bi-shop',              'route' => '/admin/vendors'],
            ['permission' => 'finance:manage',          'label' => 'Depreciation Run',  'icon' => 'bi-play-btn',          'route' => '/admin/depreciation-run'],
            ['permission' => 'purchase order:manage',   'label' => 'Invoices',          'icon' => 'bi-receipt-cutoff',    'route' => '/admin/invoices'],
        ];

        return array_values(
            array_filter($all, fn($a) => $user->can($a['permission']))
        );
    }

    public static function heroTiles(User $user): array
    {
        $tiles = [];

        // Superadmin & reseller get platform tiles
        if (in_array($user->role, ['superadmin', 'reseller'])) {
            $tiles[] = 'platformGrowth';
        }
        if ($user->can('asset:manage'))  $tiles[] = 'assetHealth';
        if ($user->can('vendor:manage')) $tiles[] = 'activeVendors';
        if ($user->can('finance:manage')) {
            $tiles[] = 'compliance';
            $tiles[] = 'depreciation';
        }

        return $tiles;
    }

    public static function insightStrip(User $user): array
    {
        $all = [
            ['key' => 'operationalEfficiency', 'permission' => 'asset:manage'],
            ['key' => 'assetsNearEOL',          'permission' => 'asset:manage'],
            ['key' => 'maintenanceCost',         'permission' => 'maintenance:manage'],
            ['key' => 'overdueCheckouts',        'permission' => 'asset:manage'],
        ];

        return array_values(
            array_column(
                array_filter($all, fn($i) => $user->can($i['permission'])),
                'key'
            )
        );
    }
}