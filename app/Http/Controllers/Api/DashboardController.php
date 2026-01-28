<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'stats' => $this->stats($user),
            'assets' => $this->assets($user),
            'recentActivity' => $this->recentActivity($user),
            // 'charts' => [
            //     'purchaseOrders' => $this->purchaseOrders($request),
            // ],
        ]);
    }

    private function assets($user)
    {
        return Asset::with('category')
            ->where('company_id', $user->getCompany())
            ->latest()
            ->take(5)
            ->get();
    }

    public function purchaseOrders(Request $request)
    {
        $user = $request->user();
        $year = $request->query('year', date('Y')); // default to current year

        $companyId = $user->getCompany();

        $rows = DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.po_id')
            ->where('po.company_id', $companyId)
            ->whereYear('po.created_at', $year)
            ->selectRaw('
                po.currency,
                MONTH(po.created_at) as month,
                SUM(poi.total_price) as total
            ')
            ->groupBy('po.currency', 'month')
            ->orderBy('month')
            ->get();

        $chart = [];

        foreach ($rows as $row) {
            $chart[$row->currency][] = [
                'month' => date('M', mktime(0, 0, 0, $row->month, 1)),
                'total' => (float) $row->total,
            ];
        }

        return response()->json($chart);
    }



    /* ===================== STATS ===================== */

    private function stats(User $user): array
    {
        return match ($user->role) {
            'superadmin' => $this->superAdminStats(),
            'reseller'   => $this->resellerStats($user),
            'company'    => $this->companyStats($user),
            'employee'   => $this->employeeStats($user),
            default      => [],
        };
    }

    private function superAdminStats(): array
    {
        return [
            // $this->card('Total Assets', Asset::count(), '+8%', 82, 'bi-box-seam'),
            $this->card('Resellers', User::where('role', 'reseller')->count(), '+3%', 60, 'bi-buildings'),
            // $this->card('Purchase Orders', PurchaseOrder::count(), '+5%', 70, 'bi-receipt'),
            $this->card('System Growth', 'Stable', '+2%', 55, 'bi-graph-up-arrow'),
        ];
    }

    private function resellerStats(User $user): array
    {
        $companies = User::where('role', 'company')
            ->where('created_by', $user->id)
            ->pluck('id');

        return [
            $this->card('Managed Companies', $companies->count(), '+1', 65, 'bi-buildings'),
            // $this->card('Total Assets', Asset::whereIn('company_id', $companies)->count(), '+6%', 75, 'bi-box-seam'),
            // $this->card('Purchase Orders', PurchaseOrder::whereIn('company_id', $companies)->count(), '+4%', 68, 'bi-receipt'),
            $this->card('Account Health', 'Good', '+3%', 72, 'bi-shield-check'),
        ];
    }

    private function companyStats(User $user): array
    {
        $companyId = $user->getCompany();

        /* ---------- ASSETS ---------- */
        $assetsNow = Asset::where('company_id', $companyId)->count();
        $assetsLastMonth = Asset::where('company_id', $companyId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        /* ---------- PURCHASE ORDERS ---------- */
        $poNow = PurchaseOrder::where('company_id', $companyId)->count();
        $poLastMonth = PurchaseOrder::where('company_id', $companyId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        /* ---------- USERS ---------- */
        $usersNow = User::where('created_by', $companyId)->count();
        $usersLastMonth = User::where('created_by', $companyId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        /* ---------- ASSET HEALTH ---------- */
        $healthyAssets = Asset::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        return [
            $this->card(
                'Company Assets',
                $assetsNow,
                $this->percentChange($assetsNow, $assetsLastMonth),
                $this->progress($assetsNow, max($assetsLastMonth, 1)),
                'bi-box-seam'
            ),

            $this->card(
                'Purchase Orders',
                $poNow,
                $this->percentChange($poNow, $poLastMonth),
                $this->progress($poNow, max($poLastMonth, 1)),
                'bi-receipt'
            ),

            $this->card(
                'Active Users',
                $usersNow,
                $this->percentChange($usersNow, $usersLastMonth),
                $this->progress($usersNow, max($usersLastMonth, 1)),
                'bi-people-fill'
            ),

            $this->card(
                'Asset Health',
                "{$healthyAssets}/{$assetsNow}",
                $assetsNow > 0 ? '+' . round(($healthyAssets / $assetsNow) * 100, 1) . '%' : '0%',
                $this->progress($healthyAssets, $assetsNow),
                'bi-activity'
            ),
        ];
    }


    private function employeeStats(User $user): array
    {
        return [
            $this->card('Assigned Assets', Asset::where('responsible_person', $user->id)->count(), '0%', 50, 'bi-laptop'),
            $this->card('Pending Checkouts', 0, '0%', 30, 'bi-arrow-left-right'),
            $this->card('Open Requests', 0, '0%', 40, 'bi-inbox'),
            $this->card('Profile Status', 'Active', '+0%', 90, 'bi-person-check'),
        ];
    }

    private function card($title, $value, $change, $percent, $icon): array
    {
        return compact('title', 'value', 'change', 'percent', 'icon');
    }

    /* ===================== ACTIVITY ===================== */

    private function recentActivity(User $user): array
    {
        return match ($user->role) {
            'superadmin' => $this->systemActivity(),
            'reseller'   => $this->resellerActivity($user),
            'company'    => $this->companyActivity($user),
            'employee'   => $this->employeeActivity($user),
            default      => [],
        };
    }

    private function systemActivity(): array
    {
        return Asset::latest()
            ->take(5)
            ->get()
            ->map(fn ($a) => [
                'user' => 'System',
                'action' => "Asset {$a->name} added",
                'time' => $a->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function resellerActivity(User $user): array
    {
        return User::where('created_by', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($u) => [
                'user' => $u->username,
                'action' => 'Company account created',
                'time' => $u->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function companyActivity(User $user): array
    {
        $companyId = $user->getCompany();

        return Asset::where('company_id', $companyId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($a) => [
                'user' => 'System',
                'action' => "Asset {$a->name} registered",
                'time' => $a->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function employeeActivity(User $user): array
    {
        return [
            [
                'user' => $user->username,
                'action' => 'Logged in',
                'time' => now()->diffForHumans(),
            ],
        ];
    }

    private function percentChange($current, $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    private function progress($value, $max): int
    {
        if ($max == 0) return 0;
        return min(100, round(($value / $max) * 100));
    }

}


