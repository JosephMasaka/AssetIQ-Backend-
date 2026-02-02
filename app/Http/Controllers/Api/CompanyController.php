<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    use ApiResponser;

    /**
     * Fetch all companies
     */
    public function getCompanies()
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canManage    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'company:manage');
        })->exists();

        if ($isSuperAdmin || $canManage) {
            $companies = User::whereHas('roles', function ($q) {
                $q->where('name', 'company');
            })
            ->where('created_by', $user->id)
            ->get();

            return $this->successResponse($companies, 'Companies retrieved successfully');
        }

        return $this->errorResponse('Permission Denied', 403);
    }

    /**
     * Create a company
     */
    public function createCompany(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canCreate    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'company:create');
        })->exists();

        if (!($isSuperAdmin || $canCreate)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $company = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'plan_expire_date' => now()->addMonth(),
        ]);

        $company->assignRole('company');

        return $this->successResponse($company, 'Company created successfully', 201);
    }

    /**
     * Update a company
     */
    public function updateCompany(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canUpdate    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'company:update');
        })->exists();

        if (!($isSuperAdmin || $canUpdate)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'id'       => 'required|exists:users,id',
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes','email', Rule::unique('users')->ignore($request->id)],
            'password' => 'nullable|min:6',
        ]);

        $company = User::findOrFail($validated['id']);

        if (isset($validated['name'])) {
            $company->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $company->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $company->password = Hash::make($validated['password']);
        }

        $company->save();

        return $this->successResponse($company, 'Company updated successfully');
    }

    /**
     * Delete a company
     */
    public function deleteCompany(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $isSuperAdmin = $user->roles()->where('name', 'superadmin')->exists();
        $canDelete    = $user->roles()->whereHas('permissions', function ($q) {
            $q->where('name', 'company:delete');
        })->exists();

        if (!($isSuperAdmin || $canDelete)) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validated = $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $company = User::findOrFail($validated['id']);
        $company->delete();

        return $this->successResponse(null, 'Company deleted successfully');
    }
}
