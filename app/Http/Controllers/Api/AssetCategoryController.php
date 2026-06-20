<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AssetCategory;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class AssetCategoryController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset category:manage');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $assetCategories = AssetCategory::where('company_id', $user->getCompany())->get();

        return $this->successResponse($assetCategories, 'asset categories retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset category:create');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        // ✅ Validate input
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_categories', 'code')->where(function ($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                }),
            ],
            'description' => 'string',
            'parent_id'   => ['nullable', 'exists:asset_categories,id'],
        ]);

        // Log::info($user);
        // dd($user);
        // ✅ Create category
        $category = AssetCategory::create([
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'],
            'parent_id'   => $validated['parent_id'] ?? null,
            'company_id'  => $user->getCompany(),
            'created_by'  => $user->id,
        ]);

        return $this->successResponse($category, 'Asset Category created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset category:update');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }
 
        // Scope to the company — prevents cross-tenant edits
        $category = AssetCategory::where('id', $id)
            ->where('company_id', $user->getCompany())
            ->first();
 
        if (!$category) {
            return $this->errorResponse('Asset category not found', 404);
        }
 
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => [
                'required', 'string', 'max:100',
                // Ignore the current record's own code when checking uniqueness
                Rule::unique('asset_categories', 'code')
                    ->ignore($category->id)
                    ->where(fn($q) => $q->where('company_id', $user->company_id)),
            ],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:asset_categories,id'],
        ]);
 
        $category->update([
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'] ?? $category->description,
            'parent_id'   => $validated['parent_id']   ?? $category->parent_id,
        ]);
 
        return $this->successResponse($category->fresh(), 'Asset category updated successfully');
    }
 
    // ─── DELETE ─────────────────────────────────────────────────────────────────
 
    public function delete(int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset category:delete');
        })->exists();
 
        $category = AssetCategory::where('id', $id)
            ->where('company_id', $user->getCompany())
            ->first();
 
        if (!$category) {
            return $this->errorResponse('Asset category not found', 404);
        }
 
        $category->delete();
 
        return $this->successResponse(null, 'Asset category deleted successfully');
    }
}
