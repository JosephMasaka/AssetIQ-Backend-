<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ComponentCategory;
use Illuminate\Support\Facades\Validator;

class ComponentCategoryController extends Controller
{
    /**
     * List all component categories for the authenticated user's company
     * GET /api/component-categories
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'components:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $companyId = $user->company_id;

        $categories = ComponentCategory::where('company_id', $user->getCompany())
            ->withCount('components')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get a single component category
     * GET /api/component-categories/{id}
     */
    public function show(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $category = ComponentCategory::where('company_id', $companyId)
            ->withCount('components')
            ->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Create a new component category
     * POST /api/component-categories
     */
    public function store(Request $request)
    {
        $companyId = ($request->user()->getCompany());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = ComponentCategory::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Component category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update a component category
     * PUT /api/component-categories/{id}
     */
    public function update(Request $request, $id)
    {
        $companyId = ($request->user()->getCompany());

        $category = ComponentCategory::where('company_id', $companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Component category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Delete a component category
     * DELETE /api/component-categories/{id}
     */
    public function destroy(Request $request, $id)
    {
        $companyId = ($request->user()->getCompany());

        $category = ComponentCategory::where('company_id', $companyId)->findOrFail($id);

        // Check if category has components
        if ($category->components()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category with existing components'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Component category deleted successfully'
        ]);
    }
}
