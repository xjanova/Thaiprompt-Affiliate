<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FortuneReferralDashboardController
 *
 * หน้าจัดการคอมมิชชั่นดูดวงสำหรับ user
 * - คอมมิชชั่นดูดวง (Level 1 + Level 2)
 * - หน้าชวนเพื่อนดูดวง (marketing + referral link)
 * - ผังสายงานดูดวง (referral tree)
 */
class FortuneReferralDashboardController extends Controller
{
    /**
     * หน้าคอมมิชชั่นดูดวง
     *
     * แสดงรายการคอมมิชชั่นจากบิลดูดวง (fortune_commissions)
     * พร้อม stats cards + filter tabs (ทั้งหมด / Level 1 / Level 2)
     */
    public function commissions(Request $request)
    {
        $user = auth()->user();
        $levelFilter = $request->get('level', 'all');

        // Query คอมมิชชั่นดูดวง
        $query = FortuneCommission::forUser($user->id)
            ->with(['fromUser', 'reading'])
            ->latest();

        if ($levelFilter === '1') {
            $query->level1();
        } elseif ($levelFilter === '2') {
            $query->level2();
        }

        $commissions = $query->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'total' => FortuneCommission::forUser($user->id)->sum('amount'),
            'level1' => FortuneCommission::forUser($user->id)->level1()->sum('amount'),
            'level2' => FortuneCommission::forUser($user->id)->level2()->sum('amount'),
            'this_month' => FortuneCommission::forUser($user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        return view('user.fortune-referral.commissions', [
            'commissions' => $commissions,
            'stats' => $stats,
            'levelFilter' => $levelFilter,
            'pageTitle' => 'คอมมิชชั่นดูดวง',
        ]);
    }

    /**
     * หน้าชวนเพื่อนดูดวง
     *
     * แสดง referral link + ตัวอย่างคำนวณจากค่าจริงที่แอดมินตั้ง
     */
    public function recruit()
    {
        $user = auth()->user();
        $settings = FortuneTellingSetting::getSettings();

        // ดึง referral link จาก FortuneAffiliateService
        $referralLink = '';
        try {
            $affiliateService = app(\App\Services\FortuneAffiliateService::class);
            $referralLink = $affiliateService->generateReferralLink($user) ?? '';
        } catch (\Exception $e) {
            // ถ้ายังไม่มี referral link → ใช้ URL พื้นฐาน
        }

        // คำนวณตัวอย่าง
        $readingPrice = (float) ($settings->deep_reading_price ?? 99);
        $level1Amount = $settings->getFortuneLevel1Amount($readingPrice);
        $level2Amount = $settings->isFortuneLevel2Enabled() ? $settings->getFortuneLevel2Amount($readingPrice) : 0;

        // 📦 (2026-07-15) โหมดค่าแนะนำแยกตามแพคเกจ — ส่งอัตรารายแพคเกจไปให้ view
        //    ถ้าปิดอยู่ $packageRates = null → view แสดงแบบอัตราเดียวเหมือนเดิม
        $packageRates = null;
        if ($settings->isFortunePackageRatesEnabled()) {
            $packageRates = [
                'deep' => [
                    'label' => 'ดูพื้นดวง',
                    'price' => (float) ($settings->deep_reading_price ?? 39),
                    'l1' => $settings->getFortuneLevel1Amount(0, \App\Models\FortuneReading::READING_TYPE_DEEP),
                    'l2_enabled' => $settings->isFortuneLevel2Enabled(\App\Models\FortuneReading::READING_TYPE_DEEP),
                    'l2' => $settings->getFortuneLevel2Amount(0, \App\Models\FortuneReading::READING_TYPE_DEEP),
                ],
                'celtic_cross' => [
                    'label' => 'Celtic Cross',
                    'price' => (float) ($settings->celtic_cross_price ?? 99),
                    'l1' => $settings->getFortuneLevel1Amount(0, \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS),
                    'l2_enabled' => $settings->isFortuneLevel2Enabled(\App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS),
                    'l2' => $settings->getFortuneLevel2Amount(0, \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS),
                ],
            ];
        }

        return view('user.fortune-referral.recruit', [
            'user' => $user,
            'settings' => $settings,
            'referralLink' => $referralLink,
            'readingPrice' => $readingPrice,
            'level1Amount' => $level1Amount,
            'level2Amount' => $level2Amount,
            'level1Type' => $settings->getFortuneLevel1CommissionType(),
            'level2Type' => $settings->getFortuneLevel2CommissionType(),
            'level2Enabled' => $settings->isFortuneLevel2Enabled(),
            'packageRates' => $packageRates,
            'pageTitle' => 'ชวนเพื่อนดูดวง',
        ]);
    }

    /**
     * หน้าผังสายงานดูดวง
     *
     * แสดง tree visualization ของ user ปัจจุบัน
     * โครงสร้าง sponsor → ผู้ถูกแนะนำ พร้อมข้อมูลคอมมิชชั่น
     */
    public function tree()
    {
        $user = auth()->user();

        // หา MlmMember ของ user ปัจจุบัน
        $currentMember = MlmMember::where('user_id', $user->id)->first();

        // สถิติคอมมิชชั่นดูดวงของ user
        $stats = [
            'total' => FortuneCommission::forUser($user->id)->sum('amount'),
            'level1' => FortuneCommission::forUser($user->id)->level1()->sum('amount'),
            'level2' => FortuneCommission::forUser($user->id)->level2()->sum('amount'),
            'referral_count' => $currentMember
                ? MlmMember::where('original_sponsor_id', $currentMember->id)->count()
                : 0,
        ];

        return view('user.fortune-referral.tree', [
            'currentMember' => $currentMember,
            'stats' => $stats,
            'pageTitle' => 'ผังสายงานดูดวง',
        ]);
    }

    /**
     * ดึงข้อมูล tree สำหรับแสดงผัง (JSON API)
     *
     * สร้าง tree data จาก MlmMember ของ user ปัจจุบัน
     * พร้อมข้อมูลคอมมิชชั่นดูดวง
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTreeData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $maxDepth = min((int) $request->get('depth', 5), 10);

        // หา MlmMember ของ user ปัจจุบัน
        $currentMember = MlmMember::where('user_id', $user->id)->first();

        if (! $currentMember) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสมาชิกในระบบ MLM',
            ], 404);
        }

        try {
            $treeData = $this->buildFortuneTree($currentMember, $maxDepth);

            return response()->json([
                'success' => true,
                'data' => $treeData,
            ]);
        } catch (\Exception $e) {
            Log::error('User fortune tree error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดผังสายงาน',
            ], 500);
        }
    }

    /**
     * สร้าง tree data สำหรับผังสายงานดูดวง (User)
     *
     * @param MlmMember $member root member
     * @param int $maxDepth ความลึกสูงสุด
     * @param int $currentDepth ความลึกปัจจุบัน
     * @return array
     */
    private function buildFortuneTree(MlmMember $member, int $maxDepth, int $currentDepth = 0): array
    {
        $member->loadMissing('user');

        // ดึงสรุปคอมมิชชั่นดูดวงของสมาชิกคนนี้
        $commissionStats = FortuneCommission::where('user_id', $member->user_id)
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        $node = [
            'id' => $member->id,
            'name' => $member->user?->name ?? 'Unknown',
            'code' => $member->member_code ?? '-',
            'avatar' => null,
            'status' => $member->status ?? 'active',
            'rank' => $member->rank_name ?? '-',
            'pv' => (float) ($member->personal_pv ?? 0),
            'total_earnings' => (float) ($commissionStats->total_amount ?? 0),
            'commission_count' => (int) ($commissionStats->total_count ?? 0),
            'children' => [],
        ];

        // ดึง children ถ้ายังไม่เกิน depth
        if ($currentDepth < $maxDepth) {
            $children = MlmMember::with('user')
                ->where('original_sponsor_id', $member->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($children as $child) {
                $node['children'][] = $this->buildFortuneTree($child, $maxDepth, $currentDepth + 1);
            }
        }

        return $node;
    }
}
