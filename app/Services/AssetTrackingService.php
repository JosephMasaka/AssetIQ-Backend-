<?php

namespace App\Services;

use App\Models\AssetTrackingLog;

class AssetTrackingService
{
    public function log(
        $assetId,
        $action,
        $oldValues = [],
        $newValues = []
    )
    {
        $user = auth()->user();

        return AssetTrackingLog::create([
            'tenant_id' => $user->getCompany(),
            'asset_id' => $assetId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user->id
        ]);
    }
}