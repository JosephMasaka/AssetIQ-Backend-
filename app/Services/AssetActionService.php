<?php
namespace App\Services;

use App\Models\Asset;
use App\Models\Vendor;
use App\Models\AssetCategory;

class AssetActionService
{
    public function execute(string $action, array $args, int $companyId): array
    {
        return match ($action) {
            'create_asset' => $this->createAsset($args, $companyId),
            'update_asset' => $this->updateAsset($args, $companyId),
            'search_assets' => $this->searchAssets($args, $companyId),
            'delete_asset' => $this->deleteAsset($args, $companyId),
            default => ['error' => "Unknown action: $action"],
        };
    }

    private function createAsset(array $args, int $companyId): array
    {
        // category must belong to this company
        if (!empty($args['category_id'])) {
            $validCategory = AssetCategory::where('company_id', $companyId)
                ->where('id', $args['category_id'])
                ->exists();

            if (!$validCategory) {
                return ['success' => false, 'message' => 'Invalid category for this company.'];
            }
        }

        $asset = Asset::create([
            'asset_code'        => $args['asset_code'] ?? $this->generateAssetCode($companyId),
            'name'               => $args['name'],
            'description'        => $args['description'] ?? null,
            'category_id'        => $args['category_id'] ?? null,
            'serial_number'      => $args['serial_number'] ?? null,
            'acquisition_date'   => $args['acquisition_date'] ?? now(),
            'purchase_cost'      => $args['purchase_cost'] ?? null,
            'location'           => $args['location'] ?? null,
            'responsible_person' => $args['responsible_person'] ?? null,
            'status'             => $args['status'] ?? 'active',
            'lifecycle_status'   => $args['lifecycle_status'] ?? 'in_use',
            'useful_life_years'  => $args['useful_life_years'] ?? null,
            'company_id'         => $companyId, // forced, never trust model input
            'created_by'         => auth()->id(),
        ]);

        // attach vendor if provided (many-to-many)
        if (!empty($args['vendor_id'])) {
            $vendorValid = Vendor::where('company_id', $companyId)
                ->where('id', $args['vendor_id'])
                ->exists();

            if ($vendorValid) {
                $asset->vendors()->attach($args['vendor_id']);
            }
        }

        return [
            'success' => true,
            'asset_id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'message' => "Created asset \"{$asset->name}\" ({$asset->asset_code})"
        ];
    }

    private function updateAsset(array $args, int $companyId): array
    {
        $asset = Asset::where('company_id', $companyId)->find($args['asset_id']);

        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found or not in your company.'];
        }

        // whitelist updatable fields — never let the model touch company_id/created_by
        $allowed = [
            'name', 'description', 'category_id', 'serial_number', 'location',
            'responsible_person', 'status', 'lifecycle_status', 'purchase_cost',
            'warranty_start_date', 'warranty_end_date', 'useful_life_years',
        ];

        $fields = array_intersect_key($args['fields'] ?? [], array_flip($allowed));

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No valid fields provided to update.'];
        }

        $asset->update($fields);

        return [
            'success' => true,
            'message' => "Updated asset \"{$asset->name}\" ({$asset->asset_code}): " . implode(', ', array_keys($fields))
        ];
    }

    private function searchAssets(array $args, int $companyId): array
    {
        $query = Asset::where('company_id', $companyId)->with(['category', 'vendors']);

        if (!empty($args['status'])) {
            $query->where('status', $args['status']);
        }
        if (!empty($args['lifecycle_status'])) {
            $query->where('lifecycle_status', $args['lifecycle_status']);
        }
        if (!empty($args['category_id'])) {
            $query->where('category_id', $args['category_id']);
        }
        if (!empty($args['due_for_maintenance'])) {
            // maintenance is tracked on a related model, not a column on assets
            $query->whereHas('maintenances', fn($q) =>
                $q->where('next_due_date', '<=', now())
                  ->where('status', '!=', 'completed')
            );
        }
        if (!empty($args['location'])) {
            $query->where('location', 'like', "%{$args['location']}%");
        }

        $results = $query->limit(20)->get([
            'id', 'asset_code', 'name', 'status', 'lifecycle_status',
            'category_id', 'location', 'purchase_cost', 'acquisition_date'
        ]);

        return [
            'count' => $results->count(),
            'results' => $results->toArray()
        ];
    }

    private function deleteAsset(array $args, int $companyId): array
    {
        if (empty($args['confirmed'])) {
            return ['success' => false, 'message' => 'Deletion requires explicit confirmation.'];
        }

        $asset = Asset::where('company_id', $companyId)->find($args['asset_id']);

        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found.'];
        }

        // AssetIQ models retirement/disposal explicitly — prefer that over a hard/soft delete
        $asset->update([
            'lifecycle_status' => 'retired',
            'retirement_date'  => now(),
            'retirement_reason' => $args['reason'] ?? 'Decommissioned via AI Copilot',
        ]);

        return [
            'success' => true,
            'message' => "Retired asset \"{$asset->name}\" ({$asset->asset_code})"
        ];
    }

    private function generateAssetCode(int $companyId): string
    {
        $count = Asset::where('company_id', $companyId)->count() + 1;
        return 'AST-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}