<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponser;

class UserController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $users = User::where('created_by', $user->getCompany())->get();

        return response()->json([
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $company = User::where('id', $user->getCompany())->first();
        if (!$company) {
            return $this->errorResponse('Company not found', 404);
        }

        $plan = Plan::where('id', $company->requested_plan)->first();
        if (!$plan) {
            return $this->errorResponse('Plan not found', 404);
        }

        // Count active users for this company
        $usersCount = User::where('created_by', $user->getCompany())
            ->where('is_active', '1')
            ->count();

        $usersLimit = $plan->max_users; // null = unlimited

        if ($usersLimit !== null && $usersCount >= $usersLimit) {
            return $this->errorResponse('User Plan Limit Reached. Please upgrade plan', 403);
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:50',
            'department' => 'nullable|string|max:255',
            'job_title'  => 'nullable|string|max:255',
            'is_active'  => 'nullable|boolean',
            'password'   => 'required|min:6',
            'role'       => 'required|integer',
        ]);

        $role = Role::where('id', $request->role)
            ->where('created_by', $user->getCompany())
            ->first();

        if (!$role) {
            return $this->errorResponse('Invalid role selected', 422);
        }

        $validated['password'] = bcrypt($validated['password']);
        $validated['created_by'] = $user->getCompany();

        $created = User::create($validated);
        $created->assignRole($role);

        return $this->successResponse($created, 'User created successfully');
    }



    public function destroy($id)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:delete');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function resetPassword($id)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $user = User::findOrFail($id);
        $user->password = Hash::make('123456');
        $user->save();

        return response()->json(['message' => 'Password reset']);
    }

    public function employees()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $users = User::where('created_by', $user->getCompany())->where('is_active', 1)->get();

        return response()->json([
            'data' => $users
        ]);
    }
}
