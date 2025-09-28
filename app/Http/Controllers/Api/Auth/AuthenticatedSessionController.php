<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember'); // expects true/false from frontend

        // Set TTL: 1 week if remember is checked, else default from config
        $ttl = $remember ? (60 * 24 * 7) : config('jwt.ttl'); // 7 days vs default (usually 60 mins)

        // Try to authenticate with TTL
        if (!$token = auth('api')->setTTL($ttl)->attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();
        // $permissions = $user->getAllPermissions()->pluck('name') ?? collect();
        $permissions = $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values();

        // Check if user is active
        if (!$user->is_active || $user->is_active === 0) {
            auth('api')->invalidate($token);

            return response()->json(['error' => 'Account is inactive. Contact administrator.'], 403);
        }

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $ttl * 60, // in seconds
            'user'         => $user,
            'permissions' => $permissions,
        ]);
    }

    public function refresh()
    {
        try {
            $newToken = auth('api')->refresh();
            return $this->respondWithToken($newToken);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }

    public function me()
    {
        return response()->json(Auth::user());
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Logged out']);
    }

    public function loginAs(Request $request)
    {
        $admin = auth('api')->user();

        if (!$admin) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // 🔹 Role-based impersonation rules
        if ($admin->hasRole('superadmin') && !$user->hasRole('reseller')) {
            return response()->json(['error' => 'Superadmin can only impersonate resellers'], 403);
        }

        if ($admin->hasRole('reseller') && !$user->hasRole('company')) {
            return response()->json(['error' => 'Reseller can only impersonate companies'], 403);
        }

        // collect target user's permissions
        $permissions = $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->values();

        $ttl = config('jwt.ttl');

        // Impersonation token for target user
        $impersonationToken = JWTAuth::fromUser($user, ['impersonated_by' => $admin->id]);

        // Save current admin token for restore
        $adminToken = JWTAuth::fromUser($admin);

        return response()->json([
            'access_token'    => $impersonationToken,
            'token_type'      => 'bearer',
            'expires_in'      => $ttl * 60,
            'user'            => $user,
            'impersonated'    => true,
            'impersonated_by' => $admin->id,
            'admin_token'     => $adminToken,
            'permissions'     => $permissions
        ]);
    }

    public function stopImpersonation(Request $request)
    {
        $adminToken = $request->input('admin_token');

        try {
            $payload = JWTAuth::setToken($adminToken)->getPayload();
            $admin = User::find($payload['sub']);

            if (!$admin) {
                return response()->json(['error' => 'Admin not found'], 404);
            }
            // $permissions = $admin->getAllPermissions()->pluck('name') ?? collect();

            return response()->json([
                'access_token' => $adminToken,
                'token_type'   => 'bearer',
                'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
                'user'         => $admin,
                'impersonated' => false,
                // 'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to stop impersonation'], 401);
        }
    }

}
