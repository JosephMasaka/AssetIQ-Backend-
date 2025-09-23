<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ResellerController; // ✅ add this

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Authentication (JWT based)
|--------------------------------------------------------------------------
*/

Route::get('/test', fn() => response()->json(['message' => 'Laravel API is working 🚀']));

// ✅ CSRF route (optional if SPA + cookies)
Route::get('/jwt/csrf-cookie', fn() => response()->json(['csrf_token' => csrf_token()]));

// ------------------------
// Public Auth Routes
// ------------------------
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 requests per minute
    Route::post('/register', [AuthenticatedSessionController::class, 'register']);
});

// ------------------------
// Protected Auth Routes (JWT middleware)
// ------------------------
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);
    // Route::get('/check', [AuthController::class, 'checkAuthenticated']);

    // ✅ Reseller route
    Route::get('/resellers', [ResellerController::class, 'getResellers']);
});
