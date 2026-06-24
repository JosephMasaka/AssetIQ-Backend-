<?php

namespace App\Http\Controllers\Api;

use App\Models\Component;
use App\Models\Asset;
use App\Models\ComponentInstallation;
use Illuminate\Http\Request;

/**
 * ComponentInstallationController
 *
 * READ-ONLY by design. Installations are written exclusively through
 * ComponentActionService::install()/remove(), which holds a row lock on the
 * Component and enforces the "only one active installation" invariant.
 * Exposing create/update/delete here would let a client write directly to
 * component_installations and silently desync it from components.lifecycle_status
 * and components.current_asset_id — the exact kind of bug class the service
 * layer was built to prevent. If you need a manual correction to history,
 * do it via tinker/a migration, not this controller.
 */
class ComponentInstallationController extends Controller
{
    /**
     * Full installation history for a single component (its "service record").
     * GET /api/components/{component}/installations
     */
    public function forComponent(Request $request, Component $component)
    {
        $this->authorizeCompany($request, $component->company_id);

        $history = $component->installations()
            ->with(['asset:id,name,asset_tag', 'installedBy:id,name', 'removedBy:id,name'])
            ->orderByDesc('installed_at')
            ->get();

        return response()->json($history);
    }

    /**
     * Full component history for a single asset — "what's been installed
     * on this asset over time" (Maximo's Asset BOM history view).
     * GET /api/assets/{asset}/component-installations
     */
    public function forAsset(Request $request, Asset $asset)
    {
        $this->authorizeCompany($request, $asset->company_id);

        $history = ComponentInstallation::where('asset_id', $asset->id)
            ->with(['component:id,component_tag,name,serial_number', 'installedBy:id,name', 'removedBy:id,name'])
            ->orderByDesc('installed_at')
            ->get();

        return response()->json($history);
    }

    /**
     * Single installation record.
     * GET /api/component-installations/{componentInstallation}
     */
    public function show(Request $request, ComponentInstallation $componentInstallation)
    {
        $this->authorizeCompany($request, $componentInstallation->company_id);

        $componentInstallation->load([
            'component:id,component_tag,name,serial_number',
            'asset:id,name,asset_tag',
            'installedBy:id,name',
            'removedBy:id,name',
        ]);

        return response()->json($componentInstallation);
    }

    /**
     * Currently-active installations across the company — "what's installed
     * where right now," useful for a fleet-wide components view.
     * GET /api/component-installations?status=active
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = ComponentInstallation::where('company_id', $companyId)
            ->with(['component:id,component_tag,name,serial_number', 'asset:id,name,asset_tag']);

        if ($request->query('status') === 'active') {
            $query->active();
        }

        $installations = $query->orderByDesc('installed_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json($installations);
    }

    private function authorizeCompany(Request $request, int $companyId): void
    {
        abort_unless($companyId === $request->user()->company_id, 403);
    }
}