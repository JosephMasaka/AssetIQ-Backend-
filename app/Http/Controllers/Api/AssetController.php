<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class AssetController extends Controller
{

    use ApiResponser;

    /**
     * List all assets.
     */
    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $assets = Asset::with('category')->latest()->get();

        return $this->successResponse($assets, 'assets retrieved successfully');
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset category:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'asset_code' => 'required|string|max:255|unique:assets,asset_code',
                'description' => 'nullable|string',
                'category_id' => 'required|integer|exists:asset_categories,id',
                'serial_number' => 'nullable|string|max:255',
                'acquisition_date' => 'nullable|date',
                'purchase_cost' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'responsible_person' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,disposed,under_maintenance',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            Log::info($user->getCompany());
            $asset = Asset::create([
                'asset_code' => $request->asset_code,
                'name' => $request->name,
                'description' => $request->description,
                'category_id' => $request->category_id ?? 1, // default category if none
                'serial_number' => $request->serial_number,
                'acquisition_date' => $request->acquisition_date,
                'purchase_cost' => $request->purchase_cost,
                'location' => $request->location,
                'responsible_person' => $request->responsible_person,
                'status' => $request->status ?? 'active',
                'company_id' => $user->getCompany(), // or Auth::user()->company_id if multi-tenant
                'created_by' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Asset created successfully',
                'data' => $asset,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create asset: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }
        
        $asset = Asset::with('category')->find($id);

        if (!$asset) {
            return $this->errorResponse('Asset not found', 404);
        }

        return $this->successResponse($asset, 'Asset retrieved successfully');
    }

}
