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

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse(
                'Unauthenticated',
                401
            );
        }

        $licenses = License::withCount([
            'assignments'
        ])
        ->latest()
        ->get();

        return $this->successResponse(
            $licenses,
            'Licenses retrieved successfully'
        );
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

        
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'version' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:255',
                'license_key' => 'nullable|string|max:255',

                'seats_purchased' => 'required|integer|min:1',

                'purchase_order_number' => 'nullable|string',
                'purchase_cost' => 'nullable|numeric|min:0',
                'purchase_date' => 'nullable|date',

                'license_type' => [
                    'required',
                    'in:Perpetual,Subscription,Open_source,Trial,OEM,Enterprise'
                ],

                'expiration_date' => 'nullable|date',
                'is_renewable' => 'boolean',

                'vendor_id' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:255'
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $license = License::create([
            ...$validator->validated(),
            'seats_assigned' => 0,
        ]);

        return $this->successResponse(
            $license,
            'License created successfully',
            201
        );
    }


    public function show($id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse(
                'Unauthenticated',
                401
            );
        }

        $license = License::with([
            'assignments.assignable'
        ])->find($id);

        if (!$license) {
            return $this->errorResponse(
                'License not found',
                404
            );
        }

        return $this->successResponse(
            $license,
            'License retrieved successfully'
        );
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:update');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $license = License::find($id);

        if (!$license) {
            return $this->errorResponse(
                'License not found',
                404
            );
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255',
                'version' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:255',
                'license_key' => 'nullable|string|max:255',

                'seats_purchased' => 'required|integer|min:1',

                'purchase_order_number' => 'nullable|string',
                'purchase_cost' => 'nullable|numeric|min:0',
                'purchase_date' => 'nullable|date',

                'license_type' => [
                    'required',
                    'in:Perpetual,Subscription,Open_source,Trial,OEM,Enterprise'
                ],

                'expiration_date' => 'nullable|date',
                'is_renewable' => 'boolean',

                'vendor_id' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:255'
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $license->update(
            $validator->validated()
        );

        return $this->successResponse(
            $license,
            'License updated successfully'
        );
    }

    public function destroy($id)
    {

        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'asset:delete');
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $license = License::find($id);

        if (!$license) {
            return $this->errorResponse(
                'License not found',
                404
            );
        }

        $license->delete();

        return $this->successResponse(
            null,
            'License deleted successfully'
        );
    }

    public function assignToAsset(Request $request, $licenseId)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'asset_id' => [
                    'required',
                    'exists:assets,id'
                ]
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $license = License::findOrFail(
            $licenseId
        );

        if (
            $license->seats_assigned >=
            $license->seats_purchased
        ) {
            return $this->errorResponse(
                'No seats available',
                422
            );
        }

        LicenseAssignment::create([
            'license_id' => $license->id,
            'assignable_type' => Asset::class,
            'assignable_id' => $request->asset_id,
            'assigned_at' => now(),
        ]);

        $license->increment(
            'seats_assigned'
        );

        return $this->successResponse(
            null,
            'License assigned successfully'
        );
    }

    public function assignToUser(Request $request, $licenseId)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => [
                    'required',
                    'exists:users,id'
                ]
            ]
        );

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $license = License::findOrFail(
            $licenseId
        );

        if (
            $license->seats_assigned >=
            $license->seats_purchased
        ) {
            return $this->errorResponse(
                'No seats available',
                422
            );
        }

        LicenseAssignment::create([
            'license_id' => $license->id,
            'assignable_type' => User::class,
            'assignable_id' => $request->user_id,
            'assigned_at' => now(),
        ]);

        $license->increment(
            'seats_assigned'
        );

        return $this->successResponse(
            null,
            'License assigned successfully'
        );
    }

    public function assetLicenses($assetId)
    {
        $asset = Asset::with([
            'licenses'
        ])->findOrFail($assetId);

        return $this->successResponse(
            $asset->licenses,
            'Asset licenses retrieved successfully'
        );
    }
}
