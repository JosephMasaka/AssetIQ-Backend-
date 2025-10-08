<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{

    use ApiResponser;

    public function index()
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'vendor:manage');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $vendors = Vendor::where('company_id', $user->getCompany())->get();

        return $this->successResponse($vendors, 'Vendors retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'vendor:create');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        // Validate request
        $validated = $request->validate([
            'vendor_code' => 'required|string|max:20|unique:vendors',
            'vendor_name' => 'required|string|max:255',
            'vendor_type' => 'required|string|in:supplier,service_provider,contractor',
            'company_reg_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'fax' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_swift_code' => 'nullable|string|max:50',
            'bank_iban' => 'nullable|string|max:100',
            'vendor_account_group' => 'required|string|in:creditor,one_time',
            'reconciliation_account' => 'nullable|string|max:50',
            'sort_key' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_one_time_vendor' => 'boolean',
            'is_approved' => 'boolean',
            'company_code' => 'required|string|max:10',
        ]);

        try {
            DB::beginTransaction();
            Log::info($validated['company_code']);
            // Create vendor
            $vendor = Vendor::create(array_merge($validated, [
                'company_id' => $user->getCompany(),
                'created_by' => $user->id,
                'status' => 'active',
                'outstanding_balance' => 0,
                'is_approved' => $validated['is_approved'] ?? false,
                'is_one_time_vendor' => $validated['is_one_time_vendor'] ?? false,
            ]));

            // Create default company code assignment
            $vendor->companyCodes()->create([
                'company_code' => $validated['company_code'],
                'reconciliation_account' => $validated['reconciliation_account'],
                'payment_terms' => $validated['payment_terms'],
                'is_blocked' => false,
            ]);

            DB::commit();

            return $this->successResponse($vendor, 'Vendor created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create vendor: ' . $e->getMessage(), 500);
        }
    }
}