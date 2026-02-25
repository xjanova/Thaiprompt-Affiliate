<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FortuneCommission;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;

/**
 * FortuneReferralDashboardController
 *
 * หน้าจัดการคอมมิชชั่นดูดวงสำหรับ user
 * - คอมมิชชั่นดูดวง (Level 1 + Level 2)
 * - หน้าชวนเพื่อนดูดวง (marketing + referral link)
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
            'pageTitle' => 'ชวนเพื่อนดูดวง',
        ]);
    }
}
