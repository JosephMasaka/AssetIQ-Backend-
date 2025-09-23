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

        if ($user && $user->can('reseller:manage') || $user->role === 'superadmin') {
            // fetch all users with reseller role
            $resellers = User::where('role','reseller')->get();

            return $this->successResponse($resellers, 'Resellers retrieved successfully');
        } else {
            return $this->errorResponse('Permission Denied', 403);
        }
    }
}
