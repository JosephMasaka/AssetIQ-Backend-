<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use App\Models\AssetAssignment;

class AssetAssignmentService
{
    public function assign(
        int $assetId,
        int $employeeId,
        ?string $notes = null
    )
    {
        $user = auth()->user();

        $asset = Asset::where(
            'created_by',
            $user->getCompany()
        )->findOrFail($assetId);

        $employee = User::where(
            'created_by',
            $user->getCompany()
        )->findOrFail($employeeId);

        $existing = AssetAssignment::where(
            'asset_id',
            $assetId
        )
        ->where('status','assigned')
        ->first();

        if ($existing) {
            throw new \Exception(
                'Asset already assigned'
            );
        }

        $assignment = AssetAssignment::create([
            'tenant_id' => $user->getTenantId(),
            'asset_id' => $assetId,
            'employee_id' => $employeeId,
            'assigned_date' => now(),
            'status' => 'assigned',
            'notes' => $notes,
            'created_by' => $user->id
        ]);

        app(AssetTrackingService::class)
            ->log(
                $assetId,
                'Asset Assigned',
                [],
                [
                    'employee_id' => $employeeId
                ]
            );

        return $assignment;
    }
}