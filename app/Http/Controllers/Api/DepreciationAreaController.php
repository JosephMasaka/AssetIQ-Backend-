<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DepreciationArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepreciationAreaController extends Controller
{
     use ApiResponser;

    public function index()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'depreciation areas:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $areas = DepreciationArea::orderBy('id', 'asc')->where('company_id', auth()->user()->getCompany())->get();

        return response()->json([
            'data' => $areas
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'depreciation areas:create');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            // 'code' => 'required|string|max:10|unique:account_groups,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_posted_to_gl' => 'required|boolean',
        ]);

        $validated['company_id'] = $user->getCompany();
        $validated['created_by'] = $user->id;

        $area = DepreciationArea::create($validated);

        return response()->json([
            'message' => 'Depreciation Area created successfully',
            'data' => $area
        ], 201);
    }
}
