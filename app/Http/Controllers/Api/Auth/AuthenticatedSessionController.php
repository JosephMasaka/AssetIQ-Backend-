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
        return response()->json($request->user());
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
