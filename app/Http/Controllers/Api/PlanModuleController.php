<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Module;
use App\Models\PlanModule;
use Illuminate\Http\Request;

class PlanModuleController extends Controller
{
    // GET /api/planmodule
    public function index()
    {
        $planModules = PlanModule::with(['plan', 'module'])
        ->orderBy('id', 'asc')
        ->get();

        return response()->json($planModules);
    }

    // POST /api/planmodule/create
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id'   => 'required|exists:plans,id',
            'modules'   => 'required|array',
            'modules.*' => 'exists:modules,id',
        ]);

        $planId = $validated['plan_id'];
        $moduleIds = $validated['modules'];

        // Remove old mappings
        PlanModule::where('plan_id', $planId)->delete();

        // Insert new mappings
        foreach ($moduleIds as $moduleId) {
            PlanModule::create([
                'plan_id' => $planId,
                'module_id' => $moduleId,
            ]);
        }

        // Return updated plan with modules
        $plan = Plan::with('modules')->findOrFail($planId);

        return response()->json([
            'message' => 'Plan modules updated successfully',
            'data' => $plan,
        ]);
    }
}
