<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanRequest;
use App\Models\Plan;
use App\Models\User;
use App\Models\Coupon;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlanRequestController extends Controller
{
    use ApiResponser;

    // ─── Shared scope helper ─────────────────────────────────────────────────
    // Builds a base query scoped correctly for superadmin, reseller, or company.

    private function scopedQuery(User $user)
    {
        $query = PlanRequest::with([
            'company:id,username,email',
            'reseller:id,username,email',
            'currentPlan:id,name,price',
            'requestedPlan:id,name,price',
            'reviewer:id,username',
            'coupon:id,code,discount_type,discount_value',
        ]);

        if ($user->role === 'superadmin') {
            // Sees everything
            return $query;
        }

        if ($user->role === 'reseller') {
            // Only requests for their managed companies
            return $query->where('reseller_id', $user->id);
        }

        // Company role: only their own requests
        return $query->where('company_id', $user->getCompany());
    }

    // ─── INDEX ───────────────────────────────────────────────────────────────

    /**
     * GET /api/plan-requests
     * Query params: status, per_page, search
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->can('plan request:manage')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $query = $this->scopedQuery($user);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Search by company name or plan name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('company', fn($sub) =>
                        $sub->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                    )->orWhereHas('requestedPlan', fn($sub) =>
                        $sub->where('name', 'like', "%{$search}%")
                    );
                });
            }

            $perPage      = (int) $request->get('per_page', 15);
            $planRequests = $query->latest()->paginate($perPage);

            return $this->successResponse($planRequests, 'Plan requests fetched successfully');

        } catch (\Exception $e) {
            Log::error('PlanRequest index error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch plan requests', 500);
        }
    }

    // ─── SHOW ────────────────────────────────────────────────────────────────

    /**
     * GET /api/plan-requests/{id}
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->can('plan request:manage')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $planRequest = $this->scopedQuery($user)->findOrFail($id);
            return $this->successResponse($planRequest, 'Plan request retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Plan request not found', 404);
        } catch (\Exception $e) {
            Log::error('PlanRequest show error: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve plan request', 500);
        }
    }

    // ─── STORE ───────────────────────────────────────────────────────────────

    /**
     * POST /api/plan-requests
     * Called by a company user to request a plan upgrade/downgrade.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->can('plan request:create')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validator = Validator::make($request->all(), [
            'requested_plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle'     => 'nullable|in:monthly,annual',
            'reason'            => 'nullable|string|max:1000',
            'coupon_code'       => 'nullable|string|exists:coupons,code',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $companyId = $user->getCompany();
            $company   = User::findOrFail($companyId);

            // Prevent duplicate pending requests
            $existing = PlanRequest::where('company_id', $companyId)
                ->where('status', PlanRequest::STATUS_PENDING)
                ->exists();

            if ($existing) {
                return $this->errorResponse(
                    'You already have a pending plan request. Please wait for it to be reviewed.',
                    409
                );
            }

            // Resolve coupon
            $couponId = null;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::where('code', $request->coupon_code)
                    ->where('is_active', true)
                    ->first();

                if (! $coupon) {
                    return $this->errorResponse('Invalid or inactive coupon code', 422);
                }
                $couponId = $coupon->id;
            }

            // Snapshot the plan price at request time
            $requestedPlan = Plan::findOrFail($request->requested_plan_id);
            $quotedPrice   = $requestedPlan->price;

            // Apply coupon discount to quoted price if present
            if ($couponId && $coupon) {
                $quotedPrice = $coupon->discount_type === 'percent'
                    ? $quotedPrice * (1 - ($coupon->discount_value / 100))
                    : max(0, $quotedPrice - $coupon->discount_value);
            }

            // Resolve reseller: who created this company?
            $resellerId = User::where('id', $companyId)->value('created_by');
            // Only set if the creator is actually a reseller
            if ($resellerId) {
                $creatorRole = User::where('id', $resellerId)->value('role');
                if ($creatorRole !== 'reseller') $resellerId = null;
            }

            $planRequest = PlanRequest::create([
                'company_id'         => $companyId,
                'reseller_id'        => $resellerId,
                'current_plan_id'    => $company->requested_plan,
                'requested_plan_id'  => $request->requested_plan_id,
                'billing_cycle'      => $request->billing_cycle ?? 'monthly',
                'reason'             => $request->reason,
                'coupon_id'          => $couponId,
                'quoted_price'       => $quotedPrice,
                'currency'           => 'KSH',
                'status'             => PlanRequest::STATUS_PENDING,
            ]);

            return $this->successResponse(
                $planRequest->load(['requestedPlan', 'currentPlan', 'coupon']),
                'Plan request submitted successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('PlanRequest store error: ' . $e->getMessage());
            return $this->errorResponse('Failed to submit plan request', 500);
        }
    }

    // ─── APPROVE ─────────────────────────────────────────────────────────────

    /**
     * POST /api/plan-requests/{id}/approve
     * Superadmin or reseller approves the request and activates the new plan.
     */
    public function approve(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->can('plan request:update')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $planRequest = $this->scopedQuery($user)->findOrFail($id);

            if (! $planRequest->isPending()) {
                return $this->errorResponse(
                    "Cannot approve a request that is already {$planRequest->status}.",
                    422
                );
            }

            DB::transaction(function () use ($planRequest, $user) {
                // Update the plan request
                $planRequest->update([
                    'status'      => PlanRequest::STATUS_APPROVED,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);

                // Activate the new plan on the company
                User::where('id', $planRequest->company_id)
                    ->update(['requested_plan' => $planRequest->requested_plan_id]);
            });

            return $this->successResponse(
                $planRequest->fresh(['requestedPlan', 'company', 'reviewer']),
                'Plan request approved and plan activated successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Plan request not found', 404);
        } catch (\Exception $e) {
            Log::error('PlanRequest approve error: ' . $e->getMessage());
            return $this->errorResponse('Failed to approve plan request', 500);
        }
    }

    // ─── REJECT ──────────────────────────────────────────────────────────────

    /**
     * POST /api/plan-requests/{id}/reject
     */
    public function reject(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->can('plan request:update')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        $validator = Validator::make($request->all(), [
            'rejection_note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $planRequest = $this->scopedQuery($user)->findOrFail($id);

            if (! $planRequest->isPending()) {
                return $this->errorResponse(
                    "Cannot reject a request that is already {$planRequest->status}.",
                    422
                );
            }

            $planRequest->update([
                'status'         => PlanRequest::STATUS_REJECTED,
                'reviewed_by'    => $user->id,
                'reviewed_at'    => now(),
                'rejection_note' => $request->rejection_note,
            ]);

            return $this->successResponse(
                $planRequest->fresh(['requestedPlan', 'company', 'reviewer']),
                'Plan request rejected'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Plan request not found', 404);
        } catch (\Exception $e) {
            Log::error('PlanRequest reject error: ' . $e->getMessage());
            return $this->errorResponse('Failed to reject plan request', 500);
        }
    }

    // ─── CANCEL ──────────────────────────────────────────────────────────────

    /**
     * POST /api/plan-requests/{id}/cancel
     * Company cancels their own pending request.
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user->can('plan request:update')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            // Company can only cancel their own
            $planRequest = PlanRequest::where('company_id', $user->getCompany())
                ->findOrFail($id);

            if (! $planRequest->isPending()) {
                return $this->errorResponse(
                    "Only pending requests can be cancelled.",
                    422
                );
            }

            $planRequest->update(['status' => PlanRequest::STATUS_CANCELLED]);

            return $this->successResponse($planRequest, 'Plan request cancelled');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Plan request not found', 404);
        } catch (\Exception $e) {
            Log::error('PlanRequest cancel error: ' . $e->getMessage());
            return $this->errorResponse('Failed to cancel plan request', 500);
        }
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    /**
     * GET /api/plan-requests/stats
     * Returns counts by status for badge/dashboard use.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if (! $user->can('plan request:manage')) {
            return $this->errorResponse('Permission Denied', 403);
        }

        try {
            $base = $this->scopedQuery($user);

            $counts = (clone $base)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return $this->successResponse([
                'pending'   => (int) ($counts['pending']   ?? 0),
                'approved'  => (int) ($counts['approved']  ?? 0),
                'rejected'  => (int) ($counts['rejected']  ?? 0),
                'cancelled' => (int) ($counts['cancelled'] ?? 0),
                'total'     => $counts->sum(),
            ], 'Plan request stats fetched');

        } catch (\Exception $e) {
            Log::error('PlanRequest stats error: ' . $e->getMessage());
            return $this->errorResponse('Failed to fetch stats', 500);
        }
    }
}