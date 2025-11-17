<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MlmGlobalSetting;
use Illuminate\Http\Request;

class MlmGlobalSettingController extends Controller
{
    public function index()
    {
        $settings = MlmGlobalSetting::where('is_visible', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        // Calculate current commission percentage for overpay warning
        $currentCommissionPercentage = $this->calculateTotalCommissionPercentage();

        return view('admin.mlm.settings.index', compact('settings', 'currentCommissionPercentage'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = MlmGlobalSetting::where('key', $key)->first();

            if ($setting && $setting->is_editable) {
                // Convert value based on type
                $finalValue = match($setting->type) {
                    'boolean' => $value ? '1' : '0',
                    'json', 'array' => is_string($value) ? $value : json_encode($value),
                    default => $value,
                };

                $setting->update(['value' => $finalValue]);
            }
        }

        MlmGlobalSetting::clearCache();

        return redirect()
            ->route('admin.mlm.settings.index')
            ->with('success', 'MLM Global Settings updated successfully');
    }

    /**
     * Calculate total commission percentage for overpay warning
     */
    protected function calculateTotalCommissionPercentage(): float
    {
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);
        $binaryMatchPercentage = MlmGlobalSetting::get('binary_match_percentage', 0);

        $unilevelTotal = 0;
        if (is_array($unilevelLevels)) {
            foreach ($unilevelLevels as $level) {
                $unilevelTotal += $level['percentage'] ?? 0;
            }
        }

        // Estimate binary (assuming 50% weak leg)
        $binaryEstimate = $binaryMatchPercentage * 0.5;

        return $unilevelTotal + $binaryEstimate;
    }

    /**
     * Get settings for AJAX
     */
    public function getSettings(Request $request)
    {
        $group = $request->get('group');

        if ($group) {
            $settings = MlmGlobalSetting::getByGroup($group);
        } else {
            $settings = MlmGlobalSetting::getAll();
        }

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Preview commission calculation
     */
    public function previewCalculation(Request $request)
    {
        $validated = $request->validate([
            'sales_amount' => 'required|numeric|min:0',
            'pv_rate' => 'nullable|numeric|min:0',
            'member_depth' => 'nullable|integer|min:1',
            'binary_pairs' => 'nullable|integer|min:0',
            'use_constraints' => 'nullable|boolean',
            'check_overpay' => 'nullable|boolean',
        ]);

        $salesAmount = $validated['sales_amount'];
        $pvRate = $validated['pv_rate'] ?? MlmGlobalSetting::get('global_pv_rate', 1);
        $memberDepth = $validated['member_depth'] ?? 10;
        $binaryPairs = $validated['binary_pairs'] ?? 0;
        $useConstraints = $validated['use_constraints'] ?? true;
        $checkOverpay = $validated['check_overpay'] ?? true;

        $commissionPerPv = MlmGlobalSetting::get('commission_per_pv', 1);
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);
        $binaryPairCommission = MlmGlobalSetting::get('binary_pair_commission', 100);
        $binaryMatchPercentage = MlmGlobalSetting::get('binary_match_percentage', 50);

        // Get constraints
        $maxPerLevel = MlmGlobalSetting::get('unilevel_max_commission_per_level', null);
        $maxPerDay = MlmGlobalSetting::get('binary_max_commission_per_day', null);
        $maxPairsPerDay = MlmGlobalSetting::get('binary_max_pairs_per_day', null);

        $pv = $salesAmount / $pvRate;

        // Unilevel calculation with constraints
        $unilevelCommissions = [];
        $unilevelTotal = 0;
        $breakdown = [];
        $constraintsApplied = 0;

        if (is_array($unilevelLevels)) {
            foreach ($unilevelLevels as $level) {
                $levelNum = $level['level'] ?? 0;

                if ($levelNum > $memberDepth) {
                    continue;
                }

                $percentage = $level['percentage'] ?? 0;
                $commission = ($pv * $percentage) / 100;
                $originalCommission = $commission;

                $constraintUsed = null;

                // Apply level constraint
                if ($useConstraints && $maxPerLevel && $commission > $maxPerLevel) {
                    $commission = $maxPerLevel;
                    $constraintUsed = "Max/Level: ฿{$maxPerLevel}";
                    $constraintsApplied++;
                }

                $unilevelCommissions[] = [
                    'level' => $levelNum,
                    'percentage' => $percentage,
                    'commission' => $commission,
                ];

                $breakdown[] = [
                    'label' => "Unilevel Level {$levelNum}",
                    'percentage' => $percentage,
                    'amount_before_constraint' => $originalCommission,
                    'constraint_used' => $constraintUsed,
                    'final_amount' => $commission,
                ];

                $unilevelTotal += $commission;
            }
        }

        // Binary calculation with constraints
        $actualPairs = min($binaryPairs, floor($pv * 0.5)); // Can't have more pairs than available PV
        $originalBinaryCommission = $actualPairs * $binaryPairCommission;
        $binaryCommission = $originalBinaryCommission;
        $binaryConstraintUsed = null;

        // Apply binary constraints
        if ($useConstraints) {
            if ($maxPairsPerDay && $actualPairs > $maxPairsPerDay) {
                $actualPairs = $maxPairsPerDay;
                $binaryCommission = $actualPairs * $binaryPairCommission;
                $binaryConstraintUsed = "Max Pairs/Day: {$maxPairsPerDay}";
                $constraintsApplied++;
            }

            if ($maxPerDay && $binaryCommission > $maxPerDay) {
                $binaryCommission = $maxPerDay;
                $binaryConstraintUsed = "Max/Day: ฿{$maxPerDay}";
                $constraintsApplied++;
            }
        }

        if ($binaryCommission > 0) {
            $breakdown[] = [
                'label' => "Binary Commission ({$actualPairs} pairs)",
                'percentage' => $binaryMatchPercentage,
                'amount_before_constraint' => $originalBinaryCommission,
                'constraint_used' => $binaryConstraintUsed,
                'final_amount' => $binaryCommission,
            ];
        }

        $totalCommission = $unilevelTotal + $binaryCommission;
        $totalPercentage = $salesAmount > 0 ? ($totalCommission / $salesAmount) * 100 : 0;

        return response()->json([
            'success' => true,
            'total_pv' => $pv,
            'unilevel_commission' => $unilevelTotal,
            'binary_commission' => $binaryCommission,
            'total_commission' => $totalCommission,
            'total_percentage' => $totalPercentage,
            'is_overpay' => $checkOverpay && $totalPercentage > 50,
            'warning_level' => $totalPercentage > 50 ? 'danger' : ($totalPercentage > 40 ? 'warning' : 'safe'),
            'constraints_applied' => $constraintsApplied,
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Update placement strategy and MLM system toggles
     *
     * สำหรับ Theme Customizer - MLM Tab
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePlacement(Request $request)
    {
        $validated = $request->validate([
            'auto_placement_type' => 'required|in:left_to_right,balanced,weak_leg,fill_by_level',
            'auto_placement' => 'required|boolean',
            'binary_enabled' => 'required|boolean',
            'unilevel_enabled' => 'required|boolean',
        ]);

        // อัพเดทการตั้งค่าแต่ละค่า
        MlmGlobalSetting::set('auto_placement_type', $validated['auto_placement_type']);
        MlmGlobalSetting::set('auto_placement', $validated['auto_placement']);
        MlmGlobalSetting::set('binary_enabled', $validated['binary_enabled']);
        MlmGlobalSetting::set('unilevel_enabled', $validated['unilevel_enabled']);

        return response()->json([
            'success' => true,
            'message' => 'MLM placement settings updated successfully',
            'settings' => [
                'auto_placement_type' => $validated['auto_placement_type'],
                'auto_placement' => $validated['auto_placement'],
                'binary_enabled' => $validated['binary_enabled'],
                'unilevel_enabled' => $validated['unilevel_enabled'],
            ],
        ]);
    }
}
