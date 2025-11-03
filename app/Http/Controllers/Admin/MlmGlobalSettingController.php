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
        ]);

        $salesAmount = $validated['sales_amount'];
        $pvRate = MlmGlobalSetting::get('global_pv_rate', 1);
        $commissionPerPv = MlmGlobalSetting::get('commission_per_pv', 1);
        $unilevelLevels = MlmGlobalSetting::get('unilevel_levels', []);
        $binaryPairCommission = MlmGlobalSetting::get('binary_pair_commission', 100);
        $binaryMatchPercentage = MlmGlobalSetting::get('binary_match_percentage', 50);

        $pv = $salesAmount * $pvRate;

        // Unilevel calculation
        $unilevelCommissions = [];
        $unilevelTotal = 0;

        if (is_array($unilevelLevels)) {
            foreach ($unilevelLevels as $level) {
                $levelNum = $level['level'] ?? 0;
                $percentage = $level['percentage'] ?? 0;
                $commission = ($pv * $commissionPerPv * $percentage) / 100;

                $unilevelCommissions[] = [
                    'level' => $levelNum,
                    'percentage' => $percentage,
                    'commission' => $commission,
                ];

                $unilevelTotal += $commission;
            }
        }

        // Binary calculation (estimate)
        $weakLegPv = $pv * 0.5; // Assume 50% balance
        $pairs = floor($weakLegPv);
        $binaryCommission = $pairs * $binaryPairCommission;

        $totalCommission = $unilevelTotal + $binaryCommission;
        $totalPercentage = ($totalCommission / $salesAmount) * 100;

        return response()->json([
            'success' => true,
            'data' => [
                'sales_amount' => $salesAmount,
                'pv' => $pv,
                'unilevel' => [
                    'commissions' => $unilevelCommissions,
                    'total' => $unilevelTotal,
                ],
                'binary' => [
                    'weak_leg_pv' => $weakLegPv,
                    'pairs' => $pairs,
                    'commission' => $binaryCommission,
                ],
                'total_commission' => $totalCommission,
                'total_percentage' => $totalPercentage,
                'is_overpay' => $totalPercentage > 50,
                'warning_level' => $totalPercentage > 50 ? 'danger' : ($totalPercentage > 40 ? 'warning' : 'safe'),
            ],
        ]);
    }
}
