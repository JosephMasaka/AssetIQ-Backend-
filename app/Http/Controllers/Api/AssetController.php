<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssetController extends Controller
{

    use ApiResponser;

    /**
     * List all assets.
     */
    public function index()
    {
        $user = auth()->user();

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

        $assets = Asset::with('category')->where('company_id', $user->getCompany())->latest()->get();

        return $this->successResponse($assets, 'assets retrieved successfully');
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

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

        $plan = Plan::where('id', $company->requested_plan)->first();
        if (!$plan) {
            return $this->errorResponse('Plan not found', 404);
        }

        // Count active users for this company
        $assetsCount = Asset::where('created_by', $user->getCompany())
            ->where('status', 'active')
            ->count();

        $assetsLimit = $plan->max_assets; // null = unlimited

        if ($assetsLimit !== null && $assetsCount >= $assetsLimit) {
            return $this->errorResponse('Assets Plan Limit Reached. Please upgrade plan', 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'asset_img' => 'nullable|nullable|file|mimes:jpg,jpeg,png|max:2048',
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

            // ✅ Handle image upload - Remove spaces completely
            // if ($request->hasFile('asset_img')) {
            //     $image = $request->file('asset_img');
                
            //     // Remove ALL spaces and special characters from filename
            //     $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            //     $extension = $image->getClientOriginalExtension();
                
            //     // Clean the filename - replace spaces with underscores and remove special chars
            //     $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            //     $imageName = time() . '_' . $cleanName . '.' . $extension;
                
            //     // Store in public storage
            //     $image->storeAs('assets', $imageName, 'public');
                
            //     // Generate URL
            //     $imagePath = Storage::disk('public')->url('assets/' . $imageName);
            // }

            if ($request->hasFile('asset_img')) {
                $image = $request->file('asset_img');
                $path = public_path('assets/assets');
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                $fileName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                $image->move($path, $fileName);
                $imagePath = url("assets/assets/{$fileName}");
            }

            $asset = Asset::create([
                'asset_code' => $request->asset_code,
                'name' => $request->name,
                'asset_img' => $imagePath,
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
        $user = auth()->user();

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
