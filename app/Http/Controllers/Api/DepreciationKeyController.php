<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepreciationKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepreciationKeyController extends Controller
{
    use ApiResponser;
    
    public function index()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'depreciation keys:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $keys = DepreciationKey::orderBy('id', 'asc')->where('company_id', auth()->user()->getCompany())->get();

        return response()->json([
            'data' => $keys
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'depreciation keys:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string',
            'is_multi_level' => 'required|boolean',
            'allow_change' => 'required|boolean',
            'is_active' => 'required|boolean',
        ]);

        $validated['company_id'] = $user->getCompany();
        $validated['created_by'] = $user->id;

        $area = DepreciationKey::create($validated);

        return response()->json([
            'message' => 'Depreciation Key created successfully',
            'data' => $area
        ], 201);
    }
}
