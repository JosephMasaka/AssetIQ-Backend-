<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UnitOfMeasure;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Log;

class UoMController extends Controller
{
    use ApiResponser;

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // 🔹 Check permissions
        $isCompanyAdmin = $user->roles()->where('name', 'company')->exists();
        $canManage = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'uom:manage');
        })->exists();

        if (!($isCompanyAdmin || $canManage)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        // 🔹 Fetch all UoMs for this company
        $uoms = UnitOfMeasure::where('company_id', $user->getCompany())->get();

        return $this->successResponse($uoms, 'Units of Measure retrieved successfully');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        // 🔹 Validate input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:units_of_measure,uom_code',
            'category' => 'nullable|string|max:255',
        ]);

        // 🔹 Create new UoM
        $uom = UnitOfMeasure::create([
            'uom_name'     => $validated['name'],
            'uom_code'     => $validated['code'],
            'uom_category' => $validated['category'] ?? null,
            'company_id'   => $user->getCompany(),
            'created_by'   => $user->id,
        ]);

        return $this->successResponse($uom, 'Unit of Measure created successfully', 201);
    }
}
