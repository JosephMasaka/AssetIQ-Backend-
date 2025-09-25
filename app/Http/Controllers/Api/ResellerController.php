<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponser;

class ResellerController extends Controller
{
    use ApiResponser;

    public function getResellers()
    {
        $user = auth('api')->user();

        // safety: if no user, deny
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // ✅ Instead of Spatie `can()` or `hasRole()`, check via DB or fallback
        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canManage    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'reseller:manage');
        })->exists();

        if ($isSuperAdmin || $canManage) {
            // fetch all users with reseller role safely
            $resellers = User::whereHas('roles', function ($q) {
                $q->where('name', 'reseller');
            })->get();

            return $this->successResponse($resellers, 'Resellers retrieved successfully');
        }

        return $this->errorResponse('Permission Denied', 403);
    }
}
