<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaxCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaxCodeController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'tex code:manage');
        })->exists();

        // if (!$canManage) {
        //     return $this->errorResponse('Permission Denied', 403);
        // }

        $taxCodes = TaxCode::where('company_id', $user->getCompany())->get();

        return response()->json([
            'message' => 'Tax Codes fetched successfully',
            'data' => $taxCodes
        ], 201);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->errorResponse('Unauthenticated', 401);

        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'tex code:create');
        })->exists();

        // if (!$canManage) {
        //     return $this->errorResponse('Permission Denied', 403);
        // }

        $validated = $request->validate([
            'code' => 'required|string|unique:tax_codes,code|max:10',
            'description' => 'required|string',
            'tax_type' => 'required|in:INPUT_VAT,OUTPUT_VAT,WITHHOLDING,EXEMPT,NONE',
            'rate' => 'required|numeric|min:0',
            'active' => 'boolean',
            'country' => 'required'
        ]);

        $validated['company_id'] = $user->getCompany();
        $validated['created_by'] = $user->id;

        $taxCode = TaxCode::create($validated);

        return response()->json([
            'message' => 'Tax Code created successfully',
            'data' => $taxCode
        ], 201);
    }
}
