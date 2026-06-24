<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consumable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ConsumableController extends Controller
{
    /**
     * GET /api/consumables
     *
     * List consumables for the authenticated user's company, with optional
     * filters mirroring SAP/Maximo-style triage (low stock, criticality,
     * ABC class, expiring batches).
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = Auth::user()->getCompany();

        $query = Consumable::query()
            ->forCompany($companyId);
            // ->with(['location']);

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->boolean('below_safety_stock')) {
            $query->belowSafetyStock();
        }

        if ($request->filled('criticality')) {
            $query->where('criticality', $request->string('criticality'));
        }

        if ($request->filled('abc_classification')) {
            $query->abcClass($request->string('abc_classification'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('lifecycle_status')) {
            $query->where('lifecycle_status', $request->string('lifecycle_status'));
        } else {
            // Default to active items unless the caller explicitly asks otherwise
            $query->active();
        }

        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon((int) $request->input('expiring_days', 30));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('item_no', 'like', "%{$term}%")
                    ->orWhere('manufacturer_part_no', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->input('per_page', 25);

        $consumables = $query->orderBy('name')->paginate($perPage);

        return response()->json($consumables);
    }

    /**
     * GET /api/consumables/{consumable}
     */
    public function show(Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        return response()->json(
            $consumable->load(['location', 'creator', 'updater'])
        );
    }

    /**
     * POST /api/consumables
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['company_id'] = Auth::user()->getCompany();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['lifecycle_status'] = $data['lifecycle_status'] ?? 'active';
        $data['qty'] = $data['qty'] ?? 0;
        $data['qty_reserved'] = $data['qty_reserved'] ?? 0;

        $consumable = Consumable::create($data);

        return response()->json($consumable, 201);
    }

    /**
     * PUT /api/consumables/{consumable}
     */
    public function update(Request $request, Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $validator = Validator::make(
            $request->all(),
            $this->rules(updating: true, consumableId: $consumable->id)
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        $consumable->update($data);

        return response()->json($consumable->refresh());
    }

    /**
     * DELETE /api/consumables/{consumable}
     *
     * Soft-deletes the record. For day-to-day "this item is no longer in
     * use" cases, prefer retire() below — matching the lifecycle_status
     * pattern already used for assets (retired rather than hard-deleted).
     */
    public function destroy(Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $consumable->delete();

        return response()->json(['message' => 'Consumable deleted.']);
    }

    /**
     * PATCH /api/consumables/{consumable}/retire
     *
     * Sets lifecycle_status to 'retired' instead of deleting, consistent
     * with how asset retirement is handled elsewhere (delete_asset action
     * sets lifecycle_status = 'retired' rather than hard-deleting).
     */
    public function retire(Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $consumable->update([
            'lifecycle_status' => 'retired',
            'updated_by' => Auth::id(),
        ]);

        return response()->json($consumable->refresh());
    }

    /**
     * PATCH /api/consumables/{consumable}/reactivate
     */
    public function reactivate(Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $consumable->update([
            'lifecycle_status' => 'active',
            'updated_by' => Auth::id(),
        ]);

        return response()->json($consumable->refresh());
    }

    /**
     * POST /api/consumables/{consumable}/issue
     *
     * Decrements stock when units are issued/checked out (e.g. to a work
     * order or technician). Equivalent to a SAP goods-issue movement.
     */
    public function issue(Request $request, Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['quantity'] > $consumable->qty_available) {
            return response()->json([
                'message' => 'Insufficient available stock for this issue.',
                'qty_available' => $consumable->qty_available,
            ], 422);
        }

        DB::transaction(function () use ($consumable, $validated) {
            $consumable->decrement('qty', $validated['quantity']);
            $consumable->update(['updated_by' => Auth::id()]);

            // If a movement ledger table exists, log it here, e.g.:
            // ConsumableMovement::create([
            //     'consumable_id' => $consumable->id,
            //     'type' => 'issue',
            //     'quantity' => $validated['quantity'],
            //     'notes' => $validated['notes'] ?? null,
            //     'user_id' => Auth::id(),
            // ]);
        });

        return response()->json($consumable->refresh());
    }

    /**
     * POST /api/consumables/{consumable}/return
     *
     * Increments stock when unused units are returned. Equivalent to a
     * SAP goods-receipt / reversal movement.
     */
    public function returnStock(Request $request, Consumable $consumable): JsonResponse
    {
        $this->authorizeCompany($consumable);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $consumable->increment('qty', $validated['quantity']);
        $consumable->update(['updated_by' => Auth::id()]);

        return response()->json($consumable->refresh());
    }

    /**
     * GET /api/consumables/alerts/replenishment
     *
     * SAP-style replenishment proposal list: items at/below reorder point,
     * ordered by criticality then how far below the point they've fallen.
     */
    public function replenishmentAlerts(): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $items = Consumable::forCompany($companyId)
            ->active()
            ->lowStock()
            ->orderByRaw("CASE criticality WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderByRaw('(reorder_point - qty) DESC')
            ->get();

        return response()->json($items);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Guard against cross-tenant access. Assumes route-model binding has
     * already resolved $consumable by primary key; this checks it belongs
     * to the authenticated user's company before any read/write proceeds.
     */
    protected function authorizeCompany(Consumable $consumable): void
    {
        abort_unless(
            $consumable->company_id === Auth::user()->company_id,
            403,
            'You do not have access to this consumable.'
        );
    }

    protected function rules(bool $updating = false, ?int $consumableId = null): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'item_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('consumables', 'item_no')
                    ->where('company_id', $companyId)
                    ->ignore($consumableId),
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'model_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string'],

            'manufacturer' => ['nullable', 'string', 'max:150'],
            'manufacturer_part_no' => ['nullable', 'string', 'max:100'],
            'primary_vendor_id' => ['nullable', 'integer'],

            'unit_of_measure' => ['nullable', 'string', 'max:20'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'moving_average_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],

            'qty' => ['nullable', 'integer', 'min:0'],
            'qty_reserved' => ['nullable', 'integer', 'min:0'],
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'safety_stock' => ['nullable', 'integer', 'min:0'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'lot_size' => ['nullable', 'integer', 'min:1'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],

            'abc_classification' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'xyz_classification' => ['nullable', Rule::in(['X', 'Y', 'Z'])],
            'criticality' => ['nullable', Rule::in(['high', 'medium', 'low'])],

            'condition_code' => ['nullable', 'string', 'max:50'],
            'lifecycle_status' => ['nullable', Rule::in(['active', 'discontinued', 'retired'])],

            'is_batch_tracked' => ['nullable', 'boolean'],
            'batch_no' => ['nullable', 'string', 'max:100'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:manufacture_date'],

            'location_id' => ['nullable', 'integer'],
        ];
    }
}