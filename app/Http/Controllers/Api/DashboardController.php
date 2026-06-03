<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Plan;
use App\Models\PlanRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\Dashboard\DashboardVisibility;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ✅ Safe permission check — same approach as me()
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

        return response()->json([
            'stats'          => $this->stats($user),
            'assets'         => $this->assets($user),
            'recentActivity' => $this->recentActivity($user),
            'growthChart'    => $this->growthChart($user),
            'visibility'     => [
                'tabs'                    => DashboardVisibility::tabs($user),
                'quickActions'            => DashboardVisibility::quickActions($user),
                'heroTiles'               => DashboardVisibility::heroTiles($user),
                'insightStrip'            => DashboardVisibility::insightStrip($user),
                'canViewProcurementChart' => $can('procurement:manage'),
                'canViewAssetTable'       => $can('asset:manage'),
                'canViewFinanceDonut'     => $can('finance:manage'),
                'canCreateReseller'       => $can('reseller:create'),
                'canCreateAsset'          => $can('asset:create'),
                'canCreateCompany'        => $can('company:create'),
                'canViewGrowthChart'      => in_array($user->role, ['superadmin', 'reseller']),
                // 'canViewGrowthChart' => $permissions->isNotEmpty() || 
                //         $user->roles->pluck('name')->intersect(['superadmin', 'reseller'])->isNotEmpty(),
            ],
        ]);
    }

    // ─── Growth Chart ────────────────────────────────────────────────────────
    // Returns last-12-months monthly counts for the relevant entities per role.

    private function growthChart(User $user): array
    {
        $months = collect(range(11, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));

        if ($user->role === 'superadmin') {
            $resellers = $this->monthlyCount(
                User::where('role', 'reseller'),
                $months
            );
            $companies = $this->monthlyCount(
                User::where('role', 'company'),
                $months
            );
            $assets = $this->monthlyCount(
                Asset::query(),
                $months
            );
            $planRequests = $this->monthlyCount(
                \App\Models\PlanRequest::query(),   // adjust model name if different
                $months
            );

            return [
                'labels'   => $months->map(fn($m) => $m->format('M y'))->values()->toArray(),
                'datasets' => [
                    ['label' => 'Resellers',     'data' => $resellers],
                    ['label' => 'Companies',     'data' => $companies],
                    ['label' => 'Assets',        'data' => $assets],
                    ['label' => 'Plan Requests', 'data' => $planRequests],
                ],
            ];
        }

        if ($user->role === 'reseller') {
            $companyIds = User::where('role', 'company')
                ->where('created_by', $user->id)
                ->pluck('id');

            $companies = $this->monthlyCount(
                User::where('role', 'company')->where('created_by', $user->id),
                $months
            );
            $assets = $this->monthlyCount(
                Asset::whereIn('company_id', $companyIds),
                $months
            );
            $planRequests = $this->monthlyCount(
                \App\Models\PlanRequest::whereIn('company_id', $companyIds),
                $months
            );

            return [
                'labels'   => $months->map(fn($m) => $m->format('M y'))->values()->toArray(),
                'datasets' => [
                    ['label' => 'Companies',     'data' => $companies],
                    ['label' => 'Assets',        'data' => $assets],
                    ['label' => 'Plan Requests', 'data' => $planRequests],
                ],
            ];
        }

        return [];
    }

    /**
     * Given a base query and a collection of month start dates,
     * returns a cumulative monthly count array.
     */
    private function monthlyCount($query, $months): array
    {
        // Pull raw monthly counts from DB in one query
        $rows = (clone $query)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->where('created_at', '>=', $months->first())
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $months->map(fn($m) => (int) ($rows[$m->format('Y-m')] ?? 0))
            ->values()
            ->toArray();
    }

    // ─── Stats ───────────────────────────────────────────────────────────────

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
        $now   = now();
        $prev  = now()->subMonth();

        // Resellers
        $resellersNow  = User::where('role', 'reseller')->count();
        $resellersPrev = User::where('role', 'reseller')
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // Companies
        $companiesNow  = User::where('role', 'company')->count();
        $companiesPrev = User::where('role', 'company')
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // Assets
        $assetsNow  = Asset::count();
        $assetsPrev = Asset::whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // Plan requests (pending)
        $pendingPlanRequests = \App\Models\PlanRequest::where('status', 'pending')->count();
        $planReqPrev         = \App\Models\PlanRequest::where('status', 'pending')
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // System growth: % change in total companies month-over-month
        $growthPct = $this->percentChange($companiesNow, max($companiesNow - $companiesPrev, 0));

        return [
            $this->card(
                'Resellers',
                $resellersNow,
                $this->percentChange($resellersNow, $resellersPrev),
                $this->progress($resellersNow, max($resellersNow, 1)),
                'bi-buildings'
            ),
            $this->card(
                'Companies',
                $companiesNow,
                $this->percentChange($companiesNow, $companiesPrev),
                $this->progress($companiesNow, max($companiesNow, 1)),
                'bi-building'
            ),
            $this->card(
                'Total Assets',
                $assetsNow,
                $this->percentChange($assetsNow, $assetsPrev),
                $this->progress($assetsNow, max($assetsNow, 1)),
                'bi-box-seam'
            ),
            $this->card(
                'Pending Plan Requests',
                $pendingPlanRequests,
                $this->percentChange($pendingPlanRequests, $planReqPrev),
                $this->progress($pendingPlanRequests, max($pendingPlanRequests + 10, 1)),
                'bi-file-earmark-check',
                ['urgent' => $pendingPlanRequests > 0]
            ),
        ];
    }

    private function resellerStats(User $user): array
    {
        $prev = now()->subMonth();

        $companyIds = User::where('role', 'company')
            ->where('created_by', $user->id)
            ->pluck('id');

        // Companies managed
        $companiesNow  = $companyIds->count();
        $companiesPrev = User::where('role', 'company')
            ->where('created_by', $user->id)
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // Assets across their companies
        $assetsNow  = Asset::whereIn('company_id', $companyIds)->count();
        $assetsPrev = Asset::whereIn('company_id', $companyIds)
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        // Active vs inactive companies
        $activeCompanies = User::where('role', 'company')
            ->where('created_by', $user->id)
            ->where('is_active', '1')   // adjust field if needed
            ->count();
        $healthPct = $companiesNow > 0
            ? round(($activeCompanies / $companiesNow) * 100, 1)
            : 100;

        // Plan requests for their companies
        $pendingPlanRequests = \App\Models\PlanRequest::whereIn('company_id', $companyIds)
            ->where('status', 'pending')
            ->count();
        $planReqPrev = \App\Models\PlanRequest::whereIn('company_id', $companyIds)
            ->where('status', 'pending')
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();

        return [
            $this->card(
                'Managed Companies',
                $companiesNow,
                $this->percentChange($companiesNow, $companiesPrev),
                $this->progress($companiesNow, max($companiesNow, 1)),
                'bi-buildings'
            ),
            $this->card(
                'Total Assets',
                $assetsNow,
                $this->percentChange($assetsNow, $assetsPrev),
                $this->progress($assetsNow, max($assetsNow, 1)),
                'bi-box-seam'
            ),
            $this->card(
                'Account Health',
                "{$healthPct}%",
                $healthPct >= 80 ? '+Good' : '-Needs attention',
                (int) $healthPct,
                'bi-shield-check',
                ['activeCompanies' => $activeCompanies, 'total' => $companiesNow]
            ),
            $this->card(
                'Pending Plan Requests',
                $pendingPlanRequests,
                $this->percentChange($pendingPlanRequests, $planReqPrev),
                $this->progress($pendingPlanRequests, max($pendingPlanRequests + 5, 1)),
                'bi-file-earmark-check',
                ['urgent' => $pendingPlanRequests > 0]
            ),
        ];
    }

    private function companyStats(User $user): array
    {
        $companyId = $user->getCompany();
        $company   = User::find($companyId);
        $plan      = Plan::find($company->requested_plan);
        $prev      = now()->subMonth();

        /* Assets */
        $assetsNow       = Asset::where('company_id', $companyId)->where('status', 'active')->count();
        $assetsLastMonth = Asset::where('company_id', $companyId)
            ->whereMonth('created_at', $prev->month)->whereYear('created_at', $prev->year)->count();
        $assetsLimit     = $plan->max_assets;
        $assetsUsagePct  = $assetsLimit ? round(($assetsNow / max($assetsLimit, 1)) * 100, 1) : 0;

        /* Purchase Orders */
        $poNow       = PurchaseOrder::where('company_id', $companyId)->whereYear('created_at', now()->year)->count();
        $poLastMonth = PurchaseOrder::where('company_id', $companyId)
            ->whereMonth('created_at', $prev->month)->whereYear('created_at', $prev->year)->count();
        $totalPOCash = PurchaseOrderItem::where('company_id', $companyId)
            ->whereYear('created_at', now()->year)->sum('total_price');

        /* Users */
        $usersNow       = User::where('created_by', $companyId)->count();
        $usersLastMonth = User::where('created_by', $companyId)
            ->whereMonth('created_at', $prev->month)->whereYear('created_at', $prev->year)->count();
        $usersLimit     = $plan->max_users;
        $usersUsagePct  = $usersLimit ? round(($usersNow / max($usersLimit, 1)) * 100, 1) : 0;

        /* Health */
        $healthyAssets = Asset::where('company_id', $companyId)->where('status', 'active')->count();

        return [
            $this->card(
                'Company Assets',
                $assetsLimit ? "{$assetsNow} / {$assetsLimit}" : $assetsNow,
                $this->percentChange($assetsNow, $assetsLastMonth),
                $assetsLimit ? $this->progress($assetsNow, $assetsLimit) : 100,
                'bi-box-seam',
                ['used' => $assetsNow, 'limit' => $assetsLimit, 'percent' => $assetsUsagePct]
            ),
            $this->card(
                'Purchase Orders',
                $poNow,
                $this->percentChange($poNow, $poLastMonth),
                $this->progress($poNow, max($poLastMonth, 1)),
                'bi-receipt',
                ['totalPOCash' => $totalPOCash]
            ),
            $this->card(
                'Active Users',
                $usersLimit ? "{$usersNow} / {$usersLimit}" : $usersNow,
                $this->percentChange($usersNow, $usersLastMonth),
                $usersLimit ? $this->progress($usersNow, $usersLimit) : 100,
                'bi-people-fill',
                ['used' => $usersNow, 'limit' => $usersLimit, 'percent' => $usersUsagePct]
            ),
            $this->card(
                'Asset Health',
                "{$healthyAssets}/{$assetsNow}",
                $assetsNow > 0 ? '+' . round(($healthyAssets / $assetsNow) * 100, 1) . '%' : '0%',
                $this->progress($healthyAssets, max($assetsNow, 1)),
                'bi-activity'
            ),
        ];
    }

    private function employeeStats(User $user): array
    {
        return [
            $this->card('Assigned Assets',   Asset::where('responsible_person', $user->id)->count(), '0%', 50, 'bi-laptop'),
            $this->card('Pending Checkouts', 0, '0%', 30, 'bi-arrow-left-right'),
            $this->card('Open Requests',     0, '0%', 40, 'bi-inbox'),
            $this->card('Profile Status', 'Active', '+0%', 90, 'bi-person-check'),
        ];
    }

    // ─── Assets (scoped) ────────────────────────────────────────────────────

    private function assets(User $user): array
    {
        if ($user->role === 'employee') {
            return Asset::with('category')
                ->where('responsible_person', $user->id)
                ->latest()->take(5)->get()->toArray();
        }

        if ($user->role === 'superadmin') {
            return Asset::with('category')->latest()->take(5)->get()->toArray();
        }

        if ($user->role === 'reseller') {
            $companyIds = User::where('role', 'company')
                ->where('created_by', $user->id)->pluck('id');
            return Asset::with('category')
                ->whereIn('company_id', $companyIds)
                ->latest()->take(5)->get()->toArray();
        }

        return Asset::with('category')
            ->where('company_id', $user->getCompany())
            ->latest()->take(5)->get()->toArray();
    }

    // ─── Purchase Orders chart (scoped + guarded) ────────────────────────────

    public function purchaseOrders(Request $request)
    {
        $user = $request->user();

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

        if (! $can('procurement:manage')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $year  = $request->query('year', date('Y'));
        $query = DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.po_id')
            ->whereYear('po.created_at', $year);

        if ($user->role === 'reseller') {
            $companyIds = User::where('role', 'company')
                ->where('created_by', $user->id)->pluck('id');
            $query->whereIn('po.company_id', $companyIds);
        } elseif ($user->role !== 'superadmin') {
            $query->where('po.company_id', $user->getCompany());
        }

        $rows = $query
            ->selectRaw('po.currency, MONTH(po.created_at) as month, SUM(poi.total_price) as total')
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

    // ─── Activity ────────────────────────────────────────────────────────────

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
        return Asset::latest()->take(5)->get()
            ->map(fn($a) => ['user' => 'System', 'action' => "Asset {$a->name} added", 'time' => $a->created_at->diffForHumans()])
            ->toArray();
    }

    private function resellerActivity(User $user): array
    {
        return User::where('created_by', $user->id)->latest()->take(5)->get()
            ->map(fn($u) => ['user' => $u->username, 'action' => 'Company account created', 'time' => $u->created_at->diffForHumans()])
            ->toArray();
    }

    private function companyActivity(User $user): array
    {
        return Asset::where('company_id', $user->getCompany())->latest()->take(5)->get()
            ->map(fn($a) => ['user' => 'System', 'action' => "Asset {$a->name} registered", 'time' => $a->created_at->diffForHumans()])
            ->toArray();
    }

    private function employeeActivity(User $user): array
    {
        return [['user' => $user->username, 'action' => 'Logged in', 'time' => now()->diffForHumans()]];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function card($title, $value, $change, $percent, $icon, $meta = null): array
    {
        $card = compact('title', 'value', 'change', 'percent', 'icon');
        if ($meta) $card['meta'] = $meta;
        return $card;
    }

    private function percentChange($current, $previous): string
    {
        if ($previous == 0) return $current > 0 ? '+100%' : '0%';
        $change = (($current - $previous) / $previous) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }

    private function progress($value, $max): int
    {
        if ($max == 0) return 0;
        return min(100, round(($value / $max) * 100));
    }
}