<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\License;
use App\Models\LicenseAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenseAssignmentController extends Controller
{
    /**
     * List assignments for an asset
     */
    public function index($assetId)
    {
        $assignments = LicenseAssignment::with('license')
            ->where('assignable_type', Asset::class)
            ->where('assignable_id', $assetId)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Assignments retrieved successfully',
            'data' => $assignments
        ]);
    }

    /**
     * Assign license to asset
     */
    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'license_id' => ['required', 'exists:licenses,id'],
        ]);

        $exists = LicenseAssignment::where('license_id', $request->license_id)
            ->where('assignable_type', Asset::class)
            ->where('assignable_id', $request->asset_id)
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'License already assigned to this asset.'
            ], 422);
        }

        $assignment = LicenseAssignment::create([
            'license_id' => $request->license_id,
            'assignable_type' => Asset::class,
            'assignable_id' => $request->asset_id,
            'assigned_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'License assigned successfully.',
            'data' => $assignment->load('license')
        ]);
    }

    /**
     * Revoke assignment
     */
    public function revoke($id)
    {
        $assignment = LicenseAssignment::findOrFail($id);

        $assignment->update([
            'revoked_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'License assignment revoked successfully.'
        ]);
    }

    /**
     * Delete assignment
     */
    public function destroy($id)
    {
        $assignment = LicenseAssignment::findOrFail($id);

        $assignment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Assignment deleted successfully.'
        ]);
    }
}