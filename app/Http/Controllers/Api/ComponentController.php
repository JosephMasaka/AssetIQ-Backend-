<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Component;
use App\Models\ComponentCategory;
use App\Services\ComponentActionService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;

class ComponentController extends Controller
{
    use ApiResponser;

    protected $componentActionService;

    public function __construct(ComponentActionService $componentActionService)
    {
        $this->componentActionService = $componentActionService;
    }

    /**
     * List all components for the authenticated user's company
     * GET /api/components
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

        $query = Component::query()
            ->forCompany($user->getCompany())
            ->with(['category', 'currentAsset', 'createdBy']);

        // Filters
        if ($request->has('lifecycle_status')) {
            $query->where('lifecycle_status', $request->lifecycle_status);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_rotable')) {
            $query->where('is_rotable', $request->boolean('is_rotable'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('component_tag', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $perPage = $request->input('per_page', 25);
        $components = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($components);
    }

    /**
     * Get a single component by ID
     * GET /api/components/{id}
     */
    public function show(Request $request, $id)
    {
        $companyId = ($request->user()->getCompany());

        $component = Component::with([
            'category',
            'currentAsset',
            'createdBy',
            'installations.asset',
            'installations.installedBy',
            'installations.removedBy'
        ])
        ->forCompany($companyId)
        ->findOrFail($id);

        return response()->json($component);
    }

    /**
     * Create a new component
     * POST /api/components
     */
    public function store(Request $request)
    {
        $companyId = ($request->user()->getCompany());
        $userId = $request->user()->id;

        $validator = Validator::make($request->all(), [
            'component_tag' => 'nullable|string|unique:components,component_tag',
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'required|exists:component_categories,id',
            'is_rotable' => 'boolean',
            'quantity_on_hand' => 'required|integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'lifecycle_status' => 'nullable|in:in_stock,installed,in_repair,retired',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Auto-generate component tag if not provided
        if (empty($data['component_tag'])) {
            $data['component_tag'] = $this->generateComponentTag($companyId);
        }

        $data['company_id'] = $companyId;
        $data['created_by'] = $userId;
        $data['lifecycle_status'] = $data['lifecycle_status'] ?? 'in_stock';
        $data['is_rotable'] = $data['is_rotable'] ?? false;
        $data['currency'] = $data['currency'] ?? 'USD';

        $component = Component::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Component created successfully',
            'data' => $component->load(['category', 'createdBy'])
        ], 201);
    }

    /**
     * Update an existing component
     * PUT /api/components/{id}
     */
    public function update(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $component = Component::forCompany($companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'component_tag' => 'nullable|string|unique:components,component_tag,' . $id,
            'name' => 'string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'exists:component_categories,id',
            'is_rotable' => 'boolean',
            'quantity_on_hand' => 'integer|min:0',
            'reorder_point' => 'nullable|integer|min:0',
            'reorder_quantity' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $component->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Component updated successfully',
            'data' => $component->load(['category', 'currentAsset'])
        ]);
    }

    /**
     * Delete a component (soft delete)
     * DELETE /api/components/{id}
     */
    public function destroy(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $component = Component::forCompany($companyId)->findOrFail($id);

        // Prevent deletion if installed
        if ($component->lifecycle_status === 'installed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete an installed component. Remove it from the asset first.'
            ], 400);
        }

        $component->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Component deleted successfully'
        ]);
    }

    /**
     * Install a component on an asset
     * POST /api/components/{id}/install
     */
    public function install(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);
        $userId = $request->user()->id;

        $component = Component::forCompany($companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|exists:assets,id',
            'install_position' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedComponent = $this->componentActionService->install(
                $component,
                $request->asset_id,
                $request->install_position,
                $userId,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Component installed successfully',
                'data' => $updatedComponent->load(['currentAsset', 'activeInstallation'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove a component from its current asset
     * POST /api/components/{id}/remove
     */
    public function remove(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);
        $userId = $request->user()->id;

        $component = Component::forCompany($companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'removal_reason' => 'required|in:scheduled_replacement,failed,upgrade,preventive_maintenance,end_of_life,other',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedComponent = $this->componentActionService->remove(
                $component,
                $request->removal_reason,
                $userId,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Component removed successfully',
                'data' => $updatedComponent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Return a component from repair to stock
     * POST /api/components/{id}/return-from-repair
     */
    public function returnFromRepair(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $component = Component::forCompany($companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedComponent = $this->componentActionService->returnFromRepair(
                $component,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Component returned from repair successfully',
                'data' => $updatedComponent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Retire a component
     * POST /api/components/{id}/retire
     */
    public function retire(Request $request, $id)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $component = Component::forCompany($companyId)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedComponent = $this->componentActionService->retire(
                $component,
                $request->notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Component retired successfully',
                'data' => $updatedComponent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get components installed on a specific asset
     * GET /api/assets/{assetId}/components
     */
    public function getByAsset(Request $request, $assetId)
    {
        $companyId = ($request->user()->company_id ?? $request->user()->tenant_id);

        $components = Component::query()
            ->forCompany($companyId)
            ->where('current_asset_id', $assetId)
            ->where('lifecycle_status', 'installed')
            ->with(['category', 'activeInstallation'])
            ->get();

        return response()->json($components);
    }

    /**
     * Get company ID from user (supports both company_id and tenant_id)
     */
    private function getCompanyId(Request $request): int
    {
        $user = $request->user();
        $companyId = $user->company_id ?? $user->tenant_id;

        if (!$companyId) {
            abort(403, 'User company/tenant not found');
        }

        return $companyId;
    }

    /**
     * Generate a unique component tag
     */
    private function generateComponentTag($companyId): string
    {
        $prefix = 'CMP';
        $year = date('Y');

        // Get the last component tag for this company
        $lastComponent = Component::where('company_id', $companyId)
            ->where('component_tag', 'like', "{$prefix}-{$year}-%")
            ->orderBy('component_tag', 'desc')
            ->first();

        if ($lastComponent) {
            // Extract the number and increment
            preg_match('/-(\d+)$/', $lastComponent->component_tag, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }
}
