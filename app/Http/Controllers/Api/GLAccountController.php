<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedger;
use Illuminate\Http\Request;

class GLAccountController extends Controller
{
    /**
     * GET /gl-mapping/gl-accounts
     * List GL Accounts
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'gl accounts:manage',);
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }
        
        $query = GeneralLedger::query()->with('group') ->orderBy('gl_code');

        // optional search
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('gl_code', 'like', "%{$request->search}%");
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->get()
        ], 200);
    }

    /**
     * POST /gl-mapping/gl-accounts/create
     */
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // Check permissions
        // $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'gl accounts:create',);
        })->exists();

        if (!$canManage) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'gl_code' => 'required|string|max:20|unique:gl_accounts,gl_code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',

            'asset_category' => 'nullable|integer',
            'long_text' => 'nullable|string',
            'account_group' => 'nullable|string',
            'reconciliation_type' => 'nullable|string',
            'currency' => 'nullable|string|max:10',
            'sort_key' => 'nullable|string',

            'balance_sheet_account' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $user->getCompany();
        $validated['created_by'] = $user->id;

        $gl = GeneralLedger::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'GL Account created successfully',
            'data'    => $gl
        ], 201);
    }
}
