<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ResellerController extends Controller
{
    use ApiResponser;

    /**
     * Fetch all resellers
     */
    public function getResellers()
    {
        $user = auth('api')->user();
        Log::info($user);
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canManage    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'reseller:manage');
        })->exists();

        if ($isSuperAdmin || $canManage) {
            $resellers = User::whereHas('roles', function ($q) {
                $q->where('name', 'reseller');
                // $q->where('created_by', $user->id);
            })
             ->where('created_by', $user->id)
            ->get();

            return $this->successResponse($resellers, 'Resellers retrieved successfully');
        }else{
            return $this->errorResponse('Permission Denied', 403);
        }
    }

    /**
     * Create a reseller
     */
    public function createReseller(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canCreate    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'reseller:create');
        })->exists();

        if (!($isSuperAdmin || $canCreate)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $reseller = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $reseller->assignRole('reseller');

        return $this->successResponse($reseller, 'Reseller created successfully', 201);
    }

    /**
     * Update a reseller
     */
    public function updateReseller(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canUpdate    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'reseller:update');
        })->exists();

        if (!($isSuperAdmin || $canUpdate)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'id'       => 'required|exists:users,id',
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes','email', Rule::unique('users')->ignore($request->id)],
            'password' => 'nullable|min:6',
        ]);

        $reseller = User::findOrFail($validated['id']);

        if (isset($validated['name'])) {
            $reseller->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $reseller->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $reseller->password = Hash::make($validated['password']);
        }

        $reseller->save();

        return $this->successResponse($reseller, 'Reseller updated successfully');
    }

    /**
     * Delete a reseller
     */
    public function deleteReseller(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canDelete    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'reseller:delete');
        })->exists();

        if (!($isSuperAdmin || $canDelete)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $reseller = User::findOrFail($validated['id']);
        $reseller->delete();

        return $this->successResponse(null, 'Reseller deleted successfully');
    }
}
