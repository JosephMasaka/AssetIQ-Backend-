<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember_me'))) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            return response()->json([
                'message' => 'Account inactive'
            ], 403);
        }

        return response()->json([
            'user' => $user
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        try {

            $permissions = collect();

            // Direct permissions
            if ($user->permissions) {
                $permissions = $permissions->merge(
                    $user->permissions->pluck('name')
                );
            }

            // Role permissions
            foreach ($user->roles as $role) {

                if ($role->permissions) {
                    $permissions = $permissions->merge(
                        $role->permissions->pluck('name')
                    );
                }
            }

            $permissions = $permissions
                ->unique()
                ->values()
                ->toArray();

        } catch (\Throwable $e) {

            \Log::error($e);

            $permissions = [];
        }

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->username,
            'email'       => $user->email,
            'tenant_id'   => $user->tenant_id,
            'permissions' => $permissions,
            'roles'       => $user->roles->pluck('name'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out'
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

    public function loginAs(Request $request)
    {
        $admin = $request->user();

        $user = User::findOrFail($request->user_id);

        if ($admin->hasRole('reseller') && !$user->hasRole('company')) {
            return response()->json(['error' => 'Not allowed'], 403);
        }

        // store impersonation in session
        session([
            'impersonator_id' => $admin->id
        ]);

        Auth::login($user);

        return response()->json([
            'user' => $user,
            'impersonated' => true
        ]);
    }

    public function stopImpersonation(Request $request)
    {
        $admin = User::find(session('impersonator_id'));

        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        Auth::login($admin);

        session()->forget('impersonator_id');

        return response()->json([
            'user' => $admin,
            'impersonated' => false
        ]);
    }

}
