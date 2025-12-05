<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'user:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'name'       => 'required',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable',
            'department' => 'nullable',
            'job_title'  => 'nullable',
            'is_active'  => 'nullable|boolean'
        ]);

        $validated['password'] = bcrypt('123456');
        $validated['created_by'] = $user->getCompany();
        // $validated['tenant_id'] = $user->tenant_id;

        $created = User::create($validated);

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
}
