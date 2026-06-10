<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Traits\ApiResponser;

class VendorController extends Controller
{
    use ApiResponser;

    private function authUser() { return auth()->user(); }

    private function hasAccess($user, string $role, string $permission): bool
    {
        return $user->roles()->where('name', $role)->exists()
            || $user->roles()->whereHas('permissions', fn($q) =>
                $q->where('name', $permission)
            )->exists();
    }

    private function validationRules(int $ignoreId = 0): array
    {
        return [
            'vendor_code'            => ['required', 'string', 'max:20',
                                         Rule::unique('vendors')->ignore($ignoreId)],
            'vendor_name'            => 'required|string|max:255',
            'vendor_type'            => 'required|string|in:supplier,service_provider,contractor',
            'company_reg_number'     => 'nullable|string|max:100',
            'tax_id'                 => 'nullable|string|max:100',
            'contact_person'         => 'nullable|string|max:255',
            'email'                  => 'nullable|email|max:255',
            'phone'                  => 'nullable|string|max:50',
            'mobile'                 => 'nullable|string|max:50',
            'fax'                    => 'nullable|string|max:50',
            'street'                 => 'nullable|string|max:255',
            'city'                   => 'nullable|string|max:100',
            'state'                  => 'nullable|string|max:100',
            'postal_code'            => 'nullable|string|max:20',
            'country'                => 'nullable|string|max:100',
            'region'                 => 'nullable|string|max:50',
            'bank_name'              => 'nullable|string|max:255',
            'bank_account_number'    => 'nullable|string|max:100',
            'bank_swift_code'        => 'nullable|string|max:50',
            'bank_iban'              => 'nullable|string|max:100',
            'vendor_account_group'   => 'required|string|in:creditor,one_time',
            'reconciliation_account' => 'nullable|string|max:50',
            'sort_key'               => 'nullable|string|max:50',
            'payment_terms'          => 'nullable|string|max:50',
            'payment_method'         => 'nullable|string|max:50',
            'credit_limit'           => 'nullable|numeric|min:0',
            'is_one_time_vendor'     => 'boolean',
            'is_approved'            => 'boolean',
            'company_code'           => 'required|string|max:10',
        ];
    }

    public function index()
    {
        $user = $this->authUser();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        if (!$this->hasAccess($user, 'company', 'vendor:manage'))
            return $this->errorResponse('Permission Denied', 403);

        return $this->successResponse(
            Vendor::where('company_id', $user->getCompany())->get(),
            'Vendors retrieved successfully'
        );
    }

    public function show(int $id)
    {
        $user = $this->authUser();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        if (!$this->hasAccess($user, 'company', 'vendor:manage'))
            return $this->errorResponse('Permission Denied', 403);

        $vendor = Vendor::where('id', $id)->where('company_id', $user->getCompany())->first();
        if (!$vendor) return $this->errorResponse('Vendor not found', 404);

        return $this->successResponse($vendor, 'Vendor retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = $this->authUser();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        if (!$this->hasAccess($user, 'company', 'vendor:create'))
            return $this->errorResponse('Permission Denied', 403);

        $validated = $request->validate($this->validationRules());

        try {
            DB::beginTransaction();

            $vendor = Vendor::create(array_merge($validated, [
                'company_id'          => $user->getCompany(),
                'created_by'          => $user->id,
                'status'              => 'active',
                'outstanding_balance' => 0,
                'is_approved'         => $validated['is_approved']        ?? false,
                'is_one_time_vendor'  => $validated['is_one_time_vendor'] ?? false,
            ]));

            $vendor->companyCodes()->create([
                'company_code'           => $validated['company_code'],
                'reconciliation_account' => $validated['reconciliation_account'] ?? null,
                'payment_terms'          => $validated['payment_terms'] ?? null,
                'is_blocked'             => false,
            ]);

            DB::commit();
            return $this->successResponse($vendor, 'Vendor created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create vendor: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id)
    {
        $user = $this->authUser();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        if (!$this->hasAccess($user, 'company', 'vendor:edit'))
            return $this->errorResponse('Permission Denied', 403);

        $vendor = Vendor::where('id', $id)->where('company_id', $user->getCompany())->first();
        if (!$vendor) return $this->errorResponse('Vendor not found', 404);

        $validated = $request->validate($this->validationRules($vendor->id));

        try {
            DB::beginTransaction();

            $vendor->update($validated);

            $vendor->companyCodes()->updateOrCreate(
                ['company_code' => $validated['company_code']],
                [
                    'reconciliation_account' => $validated['reconciliation_account'] ?? null,
                    'payment_terms'          => $validated['payment_terms'] ?? null,
                ]
            );

            DB::commit();
            return $this->successResponse($vendor->fresh(), 'Vendor updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update vendor: ' . $e->getMessage(), 500);
        }
    }

    public function delete(int $id)
    {
        $user = $this->authUser();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        if (!$this->hasAccess($user, 'company', 'vendor:delete'))
            return $this->errorResponse('Permission Denied', 403);

        $vendor = Vendor::where('id', $id)->where('company_id', $user->getCompany())->first();
        if (!$vendor) return $this->errorResponse('Vendor not found', 404);

        $vendor->delete();
        return $this->successResponse(null, 'Vendor deleted successfully');
    }
}