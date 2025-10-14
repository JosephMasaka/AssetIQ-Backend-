<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssetHistory;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponser;

class AssetHistoryController extends Controller
{
    use ApiResponser;
    /** 
     * Fetch all asset histories (optionally filtered by asset_id)
     */
    public function index(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset history:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $assetId = $id;

        $query = AssetHistory::where('company_id', $user->getCompany());
        if ($assetId) $query->where('asset_id', $assetId);

        $histories = $query->get();

        return $this->successResponse($histories, 'Asset histories retrieved successfully');
    }
}
