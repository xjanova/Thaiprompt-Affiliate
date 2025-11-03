<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MlmPlanController extends Controller
{
    public function index()
    {
        $plans = MlmPlan::orderBy('sort_order')->get();

        return view('admin.mlm.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.mlm.plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'type' => 'required|in:unilevel,binary,hybrid',
            'color' => 'nullable|string|max:20',
            'joining_fee' => 'nullable|numeric|min:0',
            'requires_joining_fee' => 'boolean',
            'use_pv_system' => 'boolean',
            'global_pv_rate' => 'required|numeric|min:0',
            'global_commission_per_pv' => 'required|numeric|min:0',
            'unilevel_levels' => 'nullable|array',
            'unilevel_max_depth' => 'nullable|integer|min:1|max:50',
            'unilevel_compression' => 'boolean',
            'binary_pair_commission' => 'nullable|numeric|min:0',
            'binary_match_percentage' => 'nullable|numeric|min:0|max:100',
            'binary_spillover' => 'boolean',
            'binary_pairing_type' => 'nullable|in:1:1,2:1',
            'auto_placement' => 'boolean',
            'auto_placement_type' => 'nullable|in:left_to_right,balanced,weak_leg',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $plan = MlmPlan::create($validated);

        return redirect()
            ->route('admin.mlm.plans.index')
            ->with('success', 'MLM Plan created successfully');
    }

    public function edit(MlmPlan $plan)
    {
        return view('admin.mlm.plans.edit', compact('plan'));
    }

    public function update(Request $request, MlmPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'type' => 'required|in:unilevel,binary,hybrid',
            'is_active' => 'boolean',
            'color' => 'nullable|string|max:20',
            'joining_fee' => 'nullable|numeric|min:0',
            'requires_joining_fee' => 'boolean',
            'use_pv_system' => 'boolean',
            'global_pv_rate' => 'required|numeric|min:0',
            'global_commission_per_pv' => 'required|numeric|min:0',
            'unilevel_levels' => 'nullable|array',
            'unilevel_max_depth' => 'nullable|integer|min:1|max:50',
            'unilevel_compression' => 'boolean',
            'binary_pair_commission' => 'nullable|numeric|min:0',
            'binary_match_percentage' => 'nullable|numeric|min:0|max:100',
            'binary_spillover' => 'boolean',
            'binary_pairing_type' => 'nullable|in:1:1,2:1',
            'auto_placement' => 'boolean',
            'auto_placement_type' => 'nullable|in:left_to_right,balanced,weak_leg',
        ]);

        $plan->update($validated);

        return redirect()
            ->route('admin.mlm.plans.index')
            ->with('success', 'MLM Plan updated successfully');
    }

    public function destroy(MlmPlan $plan)
    {
        if ($plan->members()->count() > 0) {
            return back()->with('error', 'Cannot delete plan with existing members');
        }

        $plan->delete();

        return redirect()
            ->route('admin.mlm.plans.index')
            ->with('success', 'MLM Plan deleted successfully');
    }

    public function toggleStatus(MlmPlan $plan)
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $plan->is_active,
        ]);
    }

    public function setDefault(MlmPlan $plan)
    {
        MlmPlan::where('is_default', true)->update(['is_default' => false]);
        $plan->update(['is_default' => true]);

        return redirect()
            ->route('admin.mlm.plans.index')
            ->with('success', 'Default plan updated');
    }
}
