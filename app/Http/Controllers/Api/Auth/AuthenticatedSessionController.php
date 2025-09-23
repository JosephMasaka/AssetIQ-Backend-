<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

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
}
