<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Traits\ApiResponser;

class RoleController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'role:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $search = $request->query('search', '');
        
        $roles = Role::with('permissions') // eager load permissions
            ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
            ->where('created_by', $user->getCompany())
            ->get();

        return $this->successResponse($roles, 'roles retrieved successfully');
    }

    public function permissions()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        // Get company role
        $role = Role::where('name', 'company')->first();
        if (!$role) return $this->errorResponse('Company role not found', 404);

        $permissions = $role->permissions->pluck('name');

        // Group by module
        $grouped = [];

        foreach ($permissions as $perm) {
            if (!str_contains($perm, ':')) continue;

            [$module, $action] = explode(':', $perm);

            $module = trim($module);
            $action = trim($action);

            $grouped[$module][] = [
                "name" => $perm,
                "action" => $action
            ];
        }

        return $this->successResponse($grouped, "Permissions fetched successfully");
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        // Permission Check
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'role:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        // Validate Request
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $companyId = $user->getCompany();

        // Check duplicate role name within same tenant
        if (Role::where('name', $validated['name'])
            ->where('created_by', $companyId)
            ->exists()) 
        {
            return $this->errorResponse('Role name already exists', 422);
        }

        // Create new role
        $role = Role::create([
            'name'       => $validated['name'],
            'guard_name' => 'api',
            'created_by' => $companyId
        ]);

        // Sync Permissions
        $role->syncPermissions($validated['permissions']);

        return $this->successResponse($role->load('permissions'), 'Role created successfully');
    }


}
