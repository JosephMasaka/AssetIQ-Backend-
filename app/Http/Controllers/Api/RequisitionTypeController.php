<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequisitionType;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class RequisitionTypeController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $types = RequisitionType::where('company_id', $user->getCompany())->get();

        return $this->successResponse($types, 'Requisition types retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type_code' => 'nullable|string|max:50|unique:requisition_types,type_code',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $type = RequisitionType::create([
            'name' => $validated['name'],
            'type_code' => $validated['type_code'] ?? strtoupper(substr($validated['name'], 0, 3)), // auto-generate if empty
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'company_id' => $user->getCompany(),
            'created_by' => $user->id,
            'is_active' => 1,
            'requires_approval' => true,
        ]);

        return $this->successResponse($type, 'Requisition type created successfully', 201);
    }
}
