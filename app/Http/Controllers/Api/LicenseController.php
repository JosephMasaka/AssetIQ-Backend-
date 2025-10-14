<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\License;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponser;

class LicenseController extends Controller
{
    use ApiResponser;

    public function index($assetId)
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

        $licenses = License::where('asset_id', $assetId)->latest()->get();

        return $this->successResponse($licenses, 'licenses retrieved successfully');
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

        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|exists:assets,id',
            'license_key' => 'required|string|max:255',
            'vendor' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'type' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $license = License::create([
            'asset_id' => $request->asset_id,
            'license_key' => $request->license_key,
            'vendor' => $request->vendor,
            'issue_date' => $request->issue_date,
            'expiry_date' => $request->expiry_date,
            'type' => $request->type,
            'notes' => $request->notes,
            'created_by' => $user->id,
            'company_id' => $user->getCompany(),
        ]);

        return $this->successResponse($license, 'License created successfully', 201);
    }
}
