<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentInstallation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ComponentActionService
 *
 * Owns all lifecycle transitions for Components, mirroring the role
 * AssetActionService plays for Assets. Transitions modeled on:
 *
 *  - Maximo: a rotable item goes in_stock -> installed -> (failed) -> in_repair
 *    -> in_stock (repaired & returned to spares) -> installed again. A
 *    non-rotable item goes in_stock -> installed -> retired (scrapped, no
 *    repair loop).
 *  - SAP PM: removal always requires a reason code (here: removal_reason
 *    enum) for maintenance reporting/MTBF analysis later.
 *
 * Every install/remove call is wrapped in DB::transaction with a row lock
 * on the component, which is what actually enforces "only one active
 * installation at a time" — the constraint the migration deliberately left
 * out of the schema as not portable across DB engines.
 */
class ComponentActionService
{
    /**
     * Install a component onto a parent asset.
     *
     * Fails if the component is already installed elsewhere — caller must
     * remove() it first. This mirrors Maximo's behavior: you cannot have
     * one physical part simultaneously "on" two pieces of equipment.
     */
    public function install(
        Component $component,
        int $assetId,
        ?string $position = null,
        ?int $userId = null,
        ?string $notes = null
    ): Component {
        return DB::transaction(function () use ($component, $assetId, $position, $userId, $notes) {
            $locked = Component::where('id', $component->id)->lockForUpdate()->first();

            if ($locked->lifecycle_status === 'installed') {
                throw ValidationException::withMessages([
                    'component' => "Component {$locked->component_tag} is already installed on asset #{$locked->current_asset_id}. Remove it first.",
                ]);
            }

            if ($locked->lifecycle_status === 'retired') {
                throw ValidationException::withMessages([
                    'component' => "Component {$locked->component_tag} is retired and cannot be installed.",
                ]);
            }

            ComponentInstallation::create([
                'company_id' => $locked->company_id,
                'component_id' => $locked->id,
                'asset_id' => $assetId,
                'install_position' => $position,
                'installed_at' => now(),
                'installed_by' => $userId,
                'notes' => $notes,
            ]);

            $locked->update([
                'lifecycle_status' => 'installed',
                'current_asset_id' => $assetId,
                'install_position' => $position,
            ]);

            // If this was spare stock, decrement on-hand count.
            if ($locked->quantity_on_hand > 0) {
                $locked->decrement('quantity_on_hand');
            }

            return $locked->refresh();
        });
    }

    /**
     * Remove a component from its current asset.
     *
     * Branches on is_rotable + reason, same as Maximo's rotable-item logic:
     *  - 'failed' + rotable      -> in_repair (goes to repair facility)
     *  - 'failed' + non-rotable  -> retired (scrapped, no repair loop)
     *  - anything else           -> in_stock (back to spares, healthy)
     */
    public function remove(
        Component $component,
        string $reason = 'scheduled_replacement',
        ?int $userId = null,
        ?string $notes = null
    ): Component {
        return DB::transaction(function () use ($component, $reason, $userId, $notes) {
            $locked = Component::where('id', $component->id)->lockForUpdate()->first();

            if ($locked->lifecycle_status !== 'installed') {
                throw ValidationException::withMessages([
                    'component' => "Component {$locked->component_tag} is not currently installed.",
                ]);
            }

            $active = ComponentInstallation::where('component_id', $locked->id)
                ->active()
                ->latest('installed_at')
                ->first();

            if ($active) {
                $active->update([
                    'removed_at' => now(),
                    'removal_reason' => $reason,
                    'removed_by' => $userId,
                    'notes' => $notes ?? $active->notes,
                ]);
            }

            $newStatus = match (true) {
                $reason === 'failed' && $locked->is_rotable => 'in_repair',
                $reason === 'failed' && ! $locked->is_rotable => 'retired',
                default => 'in_stock',
            };

            $locked->update([
                'lifecycle_status' => $newStatus,
                'current_asset_id' => null,
                'install_position' => null,
            ]);

            if ($newStatus === 'in_stock') {
                $locked->increment('quantity_on_hand');
            }

            return $locked->refresh();
        });
    }

    /**
     * Mark a component as repaired and returned to spares stock.
     * Only valid from in_repair — mirrors Maximo's repair-facility return flow.
     */
    public function returnFromRepair(Component $component, ?string $notes = null): Component
    {
        return DB::transaction(function () use ($component, $notes) {
            $locked = Component::where('id', $component->id)->lockForUpdate()->first();

            if ($locked->lifecycle_status !== 'in_repair') {
                throw ValidationException::withMessages([
                    'component' => "Component {$locked->component_tag} is not currently in repair.",
                ]);
            }

            $locked->update([
                'lifecycle_status' => 'in_stock',
                'last_serviced_at' => now(),
                'notes' => $notes ?? $locked->notes,
            ]);
            $locked->increment('quantity_on_hand');

            return $locked->refresh();
        });
    }

    /**
     * Retire a component permanently (non-destructive — soft state change,
     * same convention as delete_asset setting lifecycle_status = 'retired'
     * rather than hard-deleting the row).
     */
    public function retire(Component $component, ?string $notes = null): Component
    {
        return DB::transaction(function () use ($component, $notes) {
            $locked = Component::where('id', $component->id)->lockForUpdate()->first();

            if ($locked->lifecycle_status === 'installed') {
                $this->remove($locked, 'scheduled_replacement', null, 'Auto-removed prior to retirement.');
                $locked->refresh();
            }

            $locked->update([
                'lifecycle_status' => 'retired',
                'notes' => $notes ?? $locked->notes,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Search components for a company. Used by both the UI and the AI
     * tool-calling layer — always scoped by company_id (see Component::scopeForCompany).
     */
    public function search(int $companyId, array $filters = [])
    {
        $query = Component::query()->forCompany($companyId)->with(['category', 'currentAsset']);

        if (! empty($filters['status'])) {
            $query->where('lifecycle_status', $filters['status']);
        }
        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (! empty($filters['component_tag'])) {
            $query->where('component_tag', $filters['component_tag']);
        }
        if (! empty($filters['current_asset_id'])) {
            $query->where('current_asset_id', $filters['current_asset_id']);
        }
        if (! empty($filters['low_stock']) && $filters['low_stock']) {
            $query->lowStock();
        }

        return $query->limit($filters['limit'] ?? 25)->get();
    }
}