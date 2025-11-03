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
            'max_total_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'enable_overpay_protection' => 'boolean',

            // Unilevel
            'unilevel_levels' => 'nullable|array',
            'unilevel_max_depth' => 'nullable|integer|min:1|max:50',
            'unilevel_compression' => 'boolean',
            'unilevel_max_commission_per_level' => 'nullable|numeric|min:0',
            'unilevel_max_commission_per_order' => 'nullable|numeric|min:0',

            // Binary
            'binary_pair_commission' => 'nullable|numeric|min:0',
            'binary_match_percentage' => 'nullable|numeric|min:0|max:100',
            'binary_max_pairs_per_day' => 'nullable|numeric|min:0',
            'binary_max_commission_per_day' => 'nullable|numeric|min:0',
            'binary_min_pv_for_commission' => 'nullable|numeric|min:0',
            'binary_flush_percentage' => 'nullable|numeric|min:0|max:100',
            'binary_flush_enabled' => 'boolean',
            'binary_flush_mode' => 'nullable|in:percentage,full,none',
            'binary_carry_forward_pv' => 'boolean',
            'binary_carry_forward_days' => 'nullable|integer|min:1',
            'binary_spillover' => 'boolean',
            'binary_pairing_type' => 'nullable|in:1:1,2:1',
            'binary_placement_preference' => 'nullable|in:left_first,right_first,fill_level,weak_leg,balanced',
            'binary_placement_fill_level_first' => 'boolean',

            // Auto Placement
            'auto_placement' => 'boolean',
            'auto_placement_type' => 'nullable|in:left_to_right,balanced,weak_leg',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Transform unilevel_levels to proper format
        if (isset($validated['unilevel_levels']) && is_array($validated['unilevel_levels'])) {
            $levels = [];
            foreach ($validated['unilevel_levels'] as $level => $percentage) {
                if ($percentage > 0) {
                    $levels[] = [
                        'level' => (int)$level,
                        'percentage' => (float)$percentage
                    ];
                }
            }
            $validated['unilevel_levels'] = $levels;
        }

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
