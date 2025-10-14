<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Maintenance;

class MaintenanceController extends Controller
{
    /** ✅ Get all maintenance records for a specific asset */
    public function getByAsset($asset_id)
    {
        $maintenances = Maintenance::where('asset_id', $asset_id)
            ->orderBy('maintenance_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $maintenances
        ]);
    }

    /** ✅ Create a new maintenance record */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'maintenance_date' => 'required|date',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'performed_by' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = auth()->user()->company_id ?? null;
        $validated['created_by'] = auth()->id();

        $maintenance = Maintenance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully',
            'data' => $maintenance
        ]);
    }

    /** ✅ Delete a maintenance record */
    public function destroy($id)
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record deleted successfully'
        ]);
    }
}
