<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;

class AssetAssignmentController extends Controller
{
    public function index($assetId)
    {
        $user = auth()->user();

        $assignments = AssetAssignment::with('employee')
            ->where('asset_id', $assetId)
            ->where('tenant_id', $user->tenant_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'employee_id' => 'required|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        $assignment = AssetAssignment::create([
            'tenant_id' => $user->tenant_id,
            'asset_id' => $request->asset_id,
            'employee_id' => $request->employee_id,
            'assigned_date' => now(),
            'status' => 'assigned',
            'notes' => $request->notes,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Asset assigned successfully',
            'data' => $assignment
        ]);
    }

    public function returnAsset($id)
    {
        $user = auth()->user();

        $assignment = AssetAssignment::where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $assignment->update([
            'returned_date' => now(),
            'status' => 'returned',
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Asset returned successfully'
        ]);
    }
}