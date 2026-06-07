<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Component;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;

class ComponentController extends Controller
{
    use ApiResponser;

    public function index($assetId)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $components = Component::where('asset_id', $assetId)->latest()->get();

        return $this->successResponse($components, 'components retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:manage');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|exists:assets,id',
            'component_name' => 'required|string|max:255',
            'component_code' => 'required|string|max:255',
            'quantity' => 'nullable|date',
            'serial_number' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'nullable|string|max:255',
            // 'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $component = Component::create([
            'asset_id' => $request->asset_id,
            'component_name' => $request->component_name,
            'component_code' => $request->component_code,
            'quantity' => $request->quantity,
            'serial_number' => $request->serial_number,
            'status' => $request->status,
            // 'notes' => $request->notes,
            'created_by' => $user->id,
            'company_id' => $user->getCompany(),
        ]);

        return $this->successResponse($component, 'component created successfully', 201);
    }
}
