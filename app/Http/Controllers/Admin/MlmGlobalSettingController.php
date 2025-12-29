<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RebuildUnilevelTreeJob;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmRebuildTask;
use App\Services\MlmTreeRebuildService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                $finalValue = match ($setting->type) {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePlacement(Request $request)
    {
        $validated = $request->validate([
            'auto_placement_type' => 'required|in:left_to_right,balanced,weak_leg,fill_by_level',
            'auto_placement' => 'required|boolean',
            'binary_enabled' => 'required|boolean',
            'unilevel_enabled' => 'required|boolean',
            'genealogy_enabled' => 'nullable|boolean',
            'binary_match_commission' => 'nullable|numeric|min:0|max:100',
            'unilevel_levels' => 'nullable|integer|min:1|max:10',
            'unilevel_max_width' => 'nullable|integer|min:0|max:100',
            'unilevel_percentages' => 'nullable|string',
            'direct_referral_commission' => 'nullable|numeric|min:0|max:100',
            'min_pv_for_commission' => 'nullable|numeric|min:0',
            'commission_per_pv' => 'nullable|numeric|min:0|max:1000',
        ]);

        // อัพเดทการตั้งค่า 3 ระบบหลัก
        MlmGlobalSetting::set('auto_placement_type', $validated['auto_placement_type']);
        MlmGlobalSetting::set('auto_placement', $validated['auto_placement']);
        MlmGlobalSetting::set('binary_enabled', $validated['binary_enabled']);
        MlmGlobalSetting::set('unilevel_enabled', $validated['unilevel_enabled']);

        // อัพเดท Genealogy (ผังสายเลือด) enabled
        if (isset($validated['genealogy_enabled'])) {
            MlmGlobalSetting::set('genealogy_enabled', $validated['genealogy_enabled']);
        }

        // อัพเดทค่าคอมมิชชั่นเพิ่มเติม
        if (isset($validated['binary_match_commission'])) {
            MlmGlobalSetting::set('binary_match_commission', $validated['binary_match_commission']);
        }
        if (isset($validated['unilevel_levels'])) {
            MlmGlobalSetting::set('unilevel_levels', $validated['unilevel_levels']);
        }
        if (isset($validated['unilevel_max_width'])) {
            MlmGlobalSetting::set('unilevel_max_width', $validated['unilevel_max_width']);
        }
        if (isset($validated['unilevel_percentages'])) {
            MlmGlobalSetting::set('unilevel_percentages', $validated['unilevel_percentages']);
        }
        if (isset($validated['direct_referral_commission'])) {
            MlmGlobalSetting::set('direct_referral_commission', $validated['direct_referral_commission']);
        }
        if (isset($validated['min_pv_for_commission'])) {
            MlmGlobalSetting::set('min_pv_for_commission', $validated['min_pv_for_commission']);
        }
        if (isset($validated['commission_per_pv'])) {
            MlmGlobalSetting::set('commission_per_pv', $validated['commission_per_pv']);
        }

        return response()->json([
            'success' => true,
            'message' => 'MLM settings updated successfully',
            'settings' => $validated,
        ]);
    }

    /**
     * อัพเดท Unilevel width และเริ่ม rebuild task
     *
     * เมื่อเปลี่ยน width จะ:
     * 1. บันทึกค่า width ใหม่
     * 2. สร้าง rebuild task
     * 3. dispatch background job
     * 4. return task info สำหรับ tracking
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUnilevelWidth(Request $request)
    {
        $validated = $request->validate([
            'unilevel_max_width' => 'nullable|integer|min:0|max:100',
            'rebuild_tree' => 'required|boolean',
        ]);

        $newWidth = $validated['unilevel_max_width'] === 0 ? null : $validated['unilevel_max_width'];
        $shouldRebuild = $validated['rebuild_tree'];

        // ตรวจสอบว่ามี rebuild task กำลังทำงานอยู่หรือไม่
        $rebuildService = new MlmTreeRebuildService;

        if ($rebuildService->hasRunningTask()) {
            return response()->json([
                'success' => false,
                'error' => 'กำลังมีการ rebuild อยู่ กรุณารอให้เสร็จก่อน',
                'task' => $rebuildService->getLatestTaskStatus(),
            ], 400);
        }

        // บันทึกค่า width ใหม่
        $oldWidth = MlmGlobalSetting::get('unilevel_max_width');
        MlmGlobalSetting::set('unilevel_max_width', $newWidth);

        Log::info('Unilevel width changed', [
            'old_width' => $oldWidth,
            'new_width' => $newWidth,
            'rebuild_requested' => $shouldRebuild,
        ]);

        $response = [
            'success' => true,
            'message' => 'บันทึกค่าความกว้างใหม่เรียบร้อย',
            'old_width' => $oldWidth,
            'new_width' => $newWidth,
            'rebuild_started' => false,
            'task' => null,
        ];

        // ถ้าต้องการ rebuild
        if ($shouldRebuild) {
            $memberCount = MlmMember::count();

            if ($memberCount === 0) {
                $response['message'] .= ' (ไม่มีสมาชิก ไม่ต้อง rebuild)';

                return response()->json($response);
            }

            // สร้าง rebuild task
            $task = MlmRebuildTask::createWithLock(
                MlmRebuildTask::TYPE_UNILEVEL_REBUILD,
                [
                    'unilevel_max_width' => $newWidth,
                    'unilevel_max_depth' => MlmGlobalSetting::get('unilevel_max_depth', 10),
                    'trigger' => 'width_change',
                    'old_width' => $oldWidth,
                ],
                auth()->id()
            );

            if (! $task) {
                return response()->json([
                    'success' => false,
                    'error' => 'ไม่สามารถสร้าง rebuild task ได้ (อาจมี task อื่นกำลังทำงานอยู่)',
                ], 400);
            }

            // Dispatch background job
            RebuildUnilevelTreeJob::dispatch($task->id);

            $response['message'] = 'เริ่มกระบวนการ rebuild tree แล้ว';
            $response['rebuild_started'] = true;
            $response['task'] = $task->getProgressData();
            $response['member_count'] = $memberCount;

            Log::info('Unilevel rebuild task created', [
                'task_id' => $task->id,
                'member_count' => $memberCount,
            ]);
        }

        return response()->json($response);
    }

    /**
     * ดึงสถานะ rebuild task ปัจจุบัน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRebuildStatus(Request $request)
    {
        $type = $request->get('type', MlmRebuildTask::TYPE_UNILEVEL_REBUILD);

        $rebuildService = new MlmTreeRebuildService;
        $taskStatus = $rebuildService->getLatestTaskStatus($type);

        return response()->json([
            'success' => true,
            'has_task' => $taskStatus !== null,
            'task' => $taskStatus,
        ]);
    }

    /**
     * ยกเลิก rebuild task ที่กำลังทำงาน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelRebuild(Request $request)
    {
        $taskId = $request->get('task_id');

        $task = MlmRebuildTask::find($taskId);

        if (! $task) {
            return response()->json([
                'success' => false,
                'error' => 'ไม่พบ task ที่ระบุ',
            ], 404);
        }

        if ($task->status !== MlmRebuildTask::STATUS_RUNNING) {
            return response()->json([
                'success' => false,
                'error' => 'Task ไม่ได้อยู่ในสถานะที่สามารถยกเลิกได้',
            ], 400);
        }

        $task->cancel();

        Log::info('Rebuild task cancelled', [
            'task_id' => $task->id,
            'cancelled_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิก rebuild task เรียบร้อย',
            'task' => $task->getProgressData(),
        ]);
    }

    /**
     * เริ่ม rebuild ใหม่แบบ manual
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function startManualRebuild(Request $request)
    {
        $rebuildService = new MlmTreeRebuildService;

        if ($rebuildService->hasRunningTask()) {
            return response()->json([
                'success' => false,
                'error' => 'กำลังมีการ rebuild อยู่ กรุณารอให้เสร็จก่อน',
                'task' => $rebuildService->getLatestTaskStatus(),
            ], 400);
        }

        $memberCount = MlmMember::count();

        if ($memberCount === 0) {
            return response()->json([
                'success' => false,
                'error' => 'ไม่มีสมาชิกในระบบ',
            ], 400);
        }

        // สร้าง rebuild task
        $task = MlmRebuildTask::createWithLock(
            MlmRebuildTask::TYPE_UNILEVEL_REBUILD,
            [
                'unilevel_max_width' => MlmGlobalSetting::get('unilevel_max_width'),
                'unilevel_max_depth' => MlmGlobalSetting::get('unilevel_max_depth', 10),
                'trigger' => 'manual',
            ],
            auth()->id()
        );

        if (! $task) {
            return response()->json([
                'success' => false,
                'error' => 'ไม่สามารถสร้าง rebuild task ได้',
            ], 400);
        }

        // Dispatch background job
        RebuildUnilevelTreeJob::dispatch($task->id);

        Log::info('Manual rebuild started', [
            'task_id' => $task->id,
            'initiated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เริ่มกระบวนการ rebuild tree แล้ว',
            'task' => $task->getProgressData(),
            'member_count' => $memberCount,
        ]);
    }

    /**
     * ดึงข้อมูลตัวอย่างผลกระทบจากการเปลี่ยน width
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewWidthChange(Request $request)
    {
        $validated = $request->validate([
            'new_width' => 'nullable|integer|min:0|max:100',
        ]);

        $newWidth = $validated['new_width'] === 0 ? null : $validated['new_width'];
        $currentWidth = MlmGlobalSetting::get('unilevel_max_width');

        // นับสมาชิกทั้งหมด
        $totalMembers = MlmMember::count();

        // นับสมาชิกที่มีลูกตรงเกิน width ใหม่
        $affectedSponsors = 0;
        $affectedMembers = 0;

        if ($newWidth !== null && $newWidth > 0) {
            // หาสมาชิกที่มีลูกตรง (unilevel) เกิน width ใหม่
            $sponsorsOverLimit = MlmMember::select('unilevel_sponsor_id')
                ->selectRaw('COUNT(*) as child_count')
                ->whereNotNull('unilevel_sponsor_id')
                ->groupBy('unilevel_sponsor_id')
                ->havingRaw('COUNT(*) > ?', [$newWidth])
                ->get();

            $affectedSponsors = $sponsorsOverLimit->count();
            $affectedMembers = $sponsorsOverLimit->sum(function ($item) use ($newWidth) {
                return $item->child_count - $newWidth;
            });
        }

        return response()->json([
            'success' => true,
            'current_width' => $currentWidth,
            'new_width' => $newWidth,
            'total_members' => $totalMembers,
            'affected_sponsors' => $affectedSponsors,
            'affected_members' => $affectedMembers,
            'needs_rebuild' => $affectedSponsors > 0,
            'estimated_time_seconds' => ceil($totalMembers / 100) * 2, // ประมาณ 2 วินาที ต่อ 100 คน
        ]);
    }
}
