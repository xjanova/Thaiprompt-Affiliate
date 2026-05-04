<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FortuneMonthlyFreeClaim;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneMonthlyClaimService;
use Illuminate\Http\Request;

/**
 * FortuneMonthlyClaimController
 *
 * Landing page สำหรับลิงก์ในโพสต์กลุ่ม Facebook
 * "🎁 รับสิทธิ์ดูไพ่ฟรี 1 ใบประจำเดือน"
 *
 * Flow:
 *   1. ลูกค้าคลิกลิงก์ในโพสต์กลุ่ม → มาที่หน้านี้
 *   2. หน้านี้แสดงปุ่ม "เปิด Messenger รับสิทธิ์"
 *   3. กดปุ่ม → m.me/{page}?ref=MONTHLY_CLAIM_2026-05
 *   4. Messenger เปิด → webhook รับ referral → grant credit + ส่งข้อความ
 *
 * Note: ไม่ต้อง verify group membership ผ่าน API (Meta บล็อก)
 *       Trust ว่าลิงก์อยู่แค่ในโพสต์กลุ่มเท่านั้น (private group)
 */
class FortuneMonthlyClaimController extends Controller
{
    public function __construct(
        protected FortuneMonthlyClaimService $claimService
    ) {}

    /**
     * แสดง landing page
     */
    public function show(Request $request)
    {
        $settings = FortuneTellingSetting::getSettings();

        // ตรวจ toggle
        if (! ($settings->monthly_free_claim_enabled ?? false)) {
            return view('frontend.fortune.monthly-claim-disabled', [
                'settings' => $settings,
            ]);
        }

        $messengerUrl = $this->claimService->messengerUrl();
        $monthKey = FortuneMonthlyFreeClaim::currentMonthKey();
        $thaiMonth = $this->thaiMonth($monthKey);

        return view('frontend.fortune.monthly-claim', [
            'settings' => $settings,
            'messengerUrl' => $messengerUrl,
            'monthKey' => $monthKey,
            'thaiMonth' => $thaiMonth,
            'groupUrl' => $settings->fortune_group_url ?? null,
        ]);
    }

    /**
     * แปลง YYYY-MM → ชื่อเดือนภาษาไทย + ปี
     */
    protected function thaiMonth(string $monthKey): string
    {
        [$year, $month] = explode('-', $monthKey);
        $months = [
            '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
            '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
            '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม',
        ];

        $thaiYear = (int) $year + 543;

        return ($months[$month] ?? '') . ' ' . $thaiYear;
    }
}
