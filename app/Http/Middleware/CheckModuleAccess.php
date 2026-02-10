<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Model\User;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $moduleKey)
    {
        $user = auth()->user();

        if (!$user || !$user->hasModule($moduleKey)) {
            return response()->json(['message' => 'Your plan does not allow this module'], 403);
        }

        return $next($request);
    }

}
