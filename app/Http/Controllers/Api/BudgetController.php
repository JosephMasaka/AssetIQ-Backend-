<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLineItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Budget::with(['costCenter', 'creator'])
            ->where('company_id', $request->user()->company_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('budget_type')) {
            $query->where('budget_type', $request->budget_type);
        }

        if ($request->has('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        $budgets = $query->latest()->paginate(20);

        return response()->json($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'budget_type' => 'required|in:operational,capital,project',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'fiscal_year' => 'required|integer',
            'period_type' => 'required|in:monthly,quarterly,annual',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'total_budget' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'line_items' => 'nullable|array',
            'line_items.*.line_item_name' => 'required|string',
            'line_items.*.budgeted_amount' => 'required|numeric|min:0',
            'line_items.*.gl_account_id' => 'nullable|exists:general_ledger_accounts,id',
            'line_items.*.asset_category_id' => 'nullable|exists:asset_categories,id',
            'line_items.*.description' => 'nullable|string',
        ]);

        $budget = Budget::create([
            ...$validated,
            'budget_code' => $this->generateBudgetCode(),
            'available_amount' => $validated['total_budget'],
            'status' => 'draft',
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
        ]);

        if ($request->has('line_items')) {
            foreach ($request->line_items as $item) {
                BudgetLineItem::create([
                    'budget_id' => $budget->id,
                    ...$item,
                ]);
            }
        }

        return response()->json([
            'message' => 'Budget created successfully',
            'budget' => $budget->load('lineItems')
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $budget = Budget::with([
            'costCenter',
            'lineItems.glAccount',
            'lineItems.assetCategory',
            'creator'
        ])->findOrFail($id);

        return response()->json($budget);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $budget = Budget::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'total_budget' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,approved,active,closed',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['total_budget'])) {
            $validated['available_amount'] = $validated['total_budget']
                - $budget->allocated_amount
                - $budget->committed_amount
                - $budget->actual_amount;
        }

        $budget->update($validated);

        return response()->json([
            'message' => 'Budget updated successfully',
            'budget' => $budget->fresh(['costCenter', 'lineItems'])
        ]);
    }

    public function utilization(int $id): JsonResponse
    {
        $budget = Budget::with('lineItems')->findOrFail($id);

        $utilization = [
            'budget_code' => $budget->budget_code,
            'name' => $budget->name,
            'total_budget' => $budget->total_budget,
            'allocated_amount' => $budget->allocated_amount,
            'committed_amount' => $budget->committed_amount,
            'actual_amount' => $budget->actual_amount,
            'available_amount' => $budget->available_amount,
            'utilization_percentage' => $budget->total_budget > 0
                ? round(($budget->actual_amount / $budget->total_budget) * 100, 2)
                : 0,
            'commitment_percentage' => $budget->total_budget > 0
                ? round((($budget->committed_amount + $budget->actual_amount) / $budget->total_budget) * 100, 2)
                : 0,
            'line_items' => $budget->lineItems->map(function ($item) {
                return [
                    'line_item_name' => $item->line_item_name,
                    'budgeted_amount' => $item->budgeted_amount,
                    'actual_amount' => $item->actual_amount,
                    'variance' => $item->variance,
                    'variance_percentage' => $item->variance_percentage,
                    'utilization' => $item->budgeted_amount > 0
                        ? round(($item->actual_amount / $item->budgeted_amount) * 100, 2)
                        : 0,
                ];
            }),
        ];

        return response()->json($utilization);
    }

    protected function generateBudgetCode(): string
    {
        $prefix = 'BUD';
        $year = now()->year;
        $sequence = str_pad(Budget::whereYear('created_at', $year)->count() + 1, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$sequence}";
    }
}
