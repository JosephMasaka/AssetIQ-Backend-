<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssetAttribute;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponser;

class AssetAttributeController extends Controller
{
    use ApiResponser;

    /** 
     * Fetch all asset attributes (optionally filtered by asset_id)
     */
    public function index(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset attribute:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $assetId = $id;

        $query = AssetAttribute::where('company_id', $user->getCompany());
        if ($assetId) $query->where('asset_id', $assetId);

        $attributes = $query->get();

        return $this->successResponse($attributes, 'Asset attributes retrieved successfully');
    }

    /** 
     * Create new asset attribute
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset attribute:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'key' => 'required|string|max:255',
            'value' => 'nullable|string|max:500',
        ]);

        $attribute = AssetAttribute::create([
            'asset_id' => $validated['asset_id'],
            'key' => $validated['key'],
            'value' => $validated['value'] ?? null,
            'company_id' => $user->getCompany(),
            'created_by' => $user->id,
        ]);

        return $this->successResponse($attribute, 'Asset attribute created successfully', 201);
    }

    /** 
     * Show all attributes for specific asset
     */
    public function show($id)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $attributes = AssetAttribute::where('asset_id', $id)
            ->where('company_id', $user->getCompany())
            ->get();

        return $this->successResponse($attributes, 'Asset attributes retrieved successfully');
    }
}
