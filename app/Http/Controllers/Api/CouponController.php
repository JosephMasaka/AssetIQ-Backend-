<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->search . '%')
                  ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json(
            $query->latest()->paginate(20)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',

            'name' => 'required|string|max:255',

            'type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],

            'value' => 'required|numeric|min:0',

            'usage_limit' => 'nullable|integer|min:1',

            'minimum_amount' => 'nullable|numeric|min:0',

            'maximum_discount' => 'nullable|numeric|min:0',

            'starts_at' => 'nullable|date',

            'expires_at' => 'nullable|date|after:starts_at',

            'plan_ids' => 'nullable|array',

            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;

        $coupon = Coupon::create($validated);

        return response()->json([
            'message' => 'Coupon created successfully',
            'data' => $coupon,
        ]);
    }

    public function show(Coupon $coupon)
    {
        return response()->json($coupon);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')
                    ->ignore($coupon->id),
            ],

            'name' => 'required|string|max:255',

            'type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],

            'value' => 'required|numeric|min:0',

            'usage_limit' => 'nullable|integer|min:1',

            'minimum_amount' => 'nullable|numeric|min:0',

            'maximum_discount' => 'nullable|numeric|min:0',

            'starts_at' => 'nullable|date',

            'expires_at' => 'nullable|date|after:starts_at',

            'plan_ids' => 'nullable|array',

            'is_active' => 'boolean',

            'description' => 'nullable|string',
        ]);

        $coupon->update($validated);

        return response()->json([
            'message' => 'Coupon updated successfully',
            'data' => $coupon,
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully',
        ]);
    }

    /* =========================================
     * VALIDATE COUPON
     * ========================================= */

    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where(
            'code',
            strtoupper($validated['code'])
        )->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json([
                'message' => 'Invalid or expired coupon',
            ], 422);
        }

        if (
            $coupon->minimum_amount &&
            $validated['amount'] < $coupon->minimum_amount
        ) {
            return response()->json([
                'message' => 'Minimum amount not reached',
            ], 422);
        }

        $discount = $coupon->calculateDiscount(
            $validated['amount']
        );

        return response()->json([
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => max(
                0,
                $validated['amount'] - $discount
            ),
        ]);
    }
}