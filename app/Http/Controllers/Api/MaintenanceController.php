<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maintenance;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Get all maintenance records with filtering and pagination
     * GET /api/maintenance
     */
    public function index(Request $request)
    {
        $query = Maintenance::with(['asset:id,asset_tag,asset_name'])
            ->where('company_id', auth()->user()->company_id);

        // Filter by asset
        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('maintenance_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('maintenance_date', '<=', $request->date_to);
        }

        // Filter by performed_by
        if ($request->has('performed_by')) {
            $query->where('performed_by', 'like', '%' . $request->performed_by . '%');
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', '%' . $search . '%')
                  ->orWhere('performed_by', 'like', '%' . $search . '%')
                  ->orWhereHas('asset', function ($q) use ($search) {
                      $q->where('asset_tag', 'like', '%' . $search . '%')
                        ->orWhere('asset_name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'maintenance_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $maintenances = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $maintenances
        ]);
    }

    /**
     * Get a single maintenance record
     * GET /api/maintenance/{id}
     */
    public function show($id)
    {
        $maintenance = Maintenance::with(['asset:id,asset_tag,asset_name'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $maintenance
        ]);
    }

    /**
     * Get all maintenance records for a specific asset
     * GET /api/maintenance/asset/{asset_id}
     */
    public function getByAsset($asset_id)
    {
        $maintenances = Maintenance::where('asset_id', $asset_id)
            ->where('company_id', auth()->user()->company_id)
            ->orderBy('maintenance_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $maintenances
        ]);
    }

    /**
     * Create a new maintenance record
     * POST /api/maintenance
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_date' => 'required|date',
            'type' => ['required', 'string', Rule::in(['preventive', 'corrective', 'emergency', 'routine', 'inspection'])],
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'performed_by' => 'nullable|string|max:255',
        ]);

        // Verify asset belongs to the same company
        $asset = Asset::where('id', $validated['asset_id'])
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();

        $validated['company_id'] = auth()->user()->company_id;
        $validated['created_by'] = auth()->id();

        $maintenance = Maintenance::create($validated);
        $maintenance->load('asset:id,asset_tag,asset_name');

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully',
            'data' => $maintenance
        ], 201);
    }

    /**
     * Update a maintenance record
     * PUT/PATCH /api/maintenance/{id}
     */
    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'asset_id' => 'sometimes|required|exists:assets,id',
            'maintenance_date' => 'sometimes|required|date',
            'type' => ['sometimes', 'required', 'string', Rule::in(['preventive', 'corrective', 'emergency', 'routine', 'inspection'])],
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
            'performed_by' => 'nullable|string|max:255',
        ]);

        // Verify asset belongs to the same company if asset_id is being updated
        if (isset($validated['asset_id'])) {
            Asset::where('id', $validated['asset_id'])
                ->where('company_id', auth()->user()->company_id)
                ->firstOrFail();
        }

        $maintenance->update($validated);
        $maintenance->load('asset:id,asset_tag,asset_name');

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record updated successfully',
            'data' => $maintenance
        ]);
    }

    /**
     * Delete a maintenance record
     * DELETE /api/maintenance/{id}
     */
    public function destroy($id)
    {
        $maintenance = Maintenance::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record deleted successfully'
        ]);
    }

    /**
     * Get maintenance statistics
     * GET /api/maintenance/statistics
     */
    public function statistics(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $year = $request->get('year', date('Y'));
        $assetId = $request->get('asset_id');

        $query = Maintenance::where('company_id', $companyId)
            ->whereYear('maintenance_date', $year);

        if ($assetId) {
            $query->where('asset_id', $assetId);
        }

        // Total maintenance count
        $totalCount = $query->count();

        // Total maintenance cost
        $totalCost = $query->sum('cost');

        // Maintenance by type
        $byType = Maintenance::where('company_id', $companyId)
            ->whereYear('maintenance_date', $year)
            ->when($assetId, fn($q) => $q->where('asset_id', $assetId))
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(cost) as total_cost'))
            ->groupBy('type')
            ->get();

        // Monthly breakdown
        $monthly = Maintenance::where('company_id', $companyId)
            ->whereYear('maintenance_date', $year)
            ->when($assetId, fn($q) => $q->where('asset_id', $assetId))
            ->select(
                DB::raw('MONTH(maintenance_date) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(cost) as total_cost')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top assets by maintenance count
        $topAssets = Maintenance::where('maintenances.company_id', $companyId)
            ->whereYear('maintenance_date', $year)
            ->join('assets', 'maintenances.asset_id', '=', 'assets.id')
            ->select(
                'assets.id',
                'assets.asset_tag',
                'assets.asset_name',
                DB::raw('COUNT(*) as maintenance_count'),
                DB::raw('SUM(maintenances.cost) as total_cost')
            )
            ->groupBy('assets.id', 'assets.asset_tag', 'assets.asset_name')
            ->orderByDesc('maintenance_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => $totalCount,
                'total_cost' => (float) $totalCost,
                'by_type' => $byType,
                'monthly_breakdown' => $monthly,
                'top_assets' => $topAssets
            ]
        ]);
    }

    /**
     * Get upcoming maintenance (if scheduled maintenance exists)
     * GET /api/maintenance/upcoming
     */
    public function upcoming(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $days = $request->get('days', 30); // Default: next 30 days

        $upcomingMaintenance = Maintenance::with(['asset:id,asset_tag,asset_name'])
            ->where('company_id', $companyId)
            ->whereBetween('maintenance_date', [now(), now()->addDays($days)])
            ->orderBy('maintenance_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $upcomingMaintenance
        ]);
    }

    /**
     * Bulk delete maintenance records
     * POST /api/maintenance/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:maintenances,id'
        ]);

        $deleted = Maintenance::where('company_id', auth()->user()->company_id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} maintenance record(s) deleted successfully",
            'deleted_count' => $deleted
        ]);
    }
}
