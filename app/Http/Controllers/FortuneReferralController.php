<?php

namespace App\Http\Controllers;

use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FortuneReferralController
 *
 * จัดการ landing page สำหรับลิงก์เชิญเพื่อนดูดวง
 * เมื่อเพื่อนกดลิงก์ → แสดงปุ่ม LINE deep link (จับคู่ 100% ผ่าน token)
 */
class FortuneReferralController extends Controller
{
    /**
     * Landing page — เมื่อเพื่อนกดลิงก์เชิญ
     *
     * แสดง:
     * - ปุ่ม LINE deep link (เปิดแชท OA พร้อม ref_{token})
     * - QR code + รหัสอ้างอิงสำหรับ browser (fallback)
     * - Countdown หมดอายุ 24 ชม.
     */
    /**
     * สร้างข้อมูลคอมมิชชั่นสำหรับแสดงในหน้า landing
     */
    private function buildCommissionInfo(?FortuneTellingSetting $settings, ?MlmGlobalSetting $mlmSettings): array
    {
        $info = [
            'enabled' => false,
            'level1' => null,
            'level2' => null,
            'level1_display' => '',
            'level2_display' => '',
            'has_level2' => false,
        ];

        if (! $settings || ! ($settings->fortune_affiliate_enabled ?? false)) {
            return $info;
        }

        $info['enabled'] = true;

        // Level 1 commission
        $l1Type = $settings->fortune_level1_commission_type ?? 'fixed';
        $l1Amount = $settings->fortune_level1_commission_amount ?? 0;
        if ($l1Type === 'percent') {
            $info['level1_display'] = number_format($l1Amount, 0).'%';
        } else {
            $info['level1_display'] = number_format($l1Amount, 0).' บาท';
        }
        $info['level1'] = $l1Amount;

        // Level 2 commission
        if ($settings->fortune_level2_enabled ?? false) {
            $l2Type = $settings->fortune_level2_commission_type ?? 'fixed';
            $l2Amount = $settings->fortune_level2_commission_amount ?? 0;
            if ($l2Type === 'percent') {
                $info['level2_display'] = number_format($l2Amount, 0).'%';
            } else {
                $info['level2_display'] = number_format($l2Amount, 0).' บาท';
            }
            $info['level2'] = $l2Amount;
            $info['has_level2'] = true;
        }

        return $info;
    }

    public function landing(Request $request, string $token)
    {
        // หา referral จาก token
        $referral = FortuneReferral::where('referral_token', $token)->first();

        $errorData = [
            'error' => null,
            'referral' => null,
            'referrerName' => null,
            'lineDeepLink' => null,
            'lineAddFriendUrl' => null,
            'referralCode' => null,
            'expiresAt' => null,
            'commissionInfo' => ['enabled' => false],
        ];

        if (! $referral) {
            $errorData['error'] = 'ลิงก์ไม่ถูกต้องหรือหมดอายุแล้ว';

            return view('fortune.invite-landing', $errorData);
        }

        // ตรวจหมดอายุ
        if ($referral->isExpired()) {
            $errorData['error'] = 'ลิงก์นี้หมดอายุแล้ว กรุณาขอลิงก์ใหม่จากผู้เชิญ';

            return view('fortune.invite-landing', $errorData);
        }

        // ตรวจว่าจับคู่แล้วหรือยัง (followed/converted)
        if ($referral->status !== FortuneReferral::STATUS_PENDING) {
            $errorData['error'] = 'ลิงก์นี้ถูกใช้งานแล้ว';

            return view('fortune.invite-landing', $errorData);
        }

        // ดึงข้อมูลคนเชิญ
        $referrerName = $referral->referrerUser?->name ?? 'เพื่อนของคุณ';

        // ดึง LINE OA settings
        $settings = FortuneTellingSetting::getSettings();
        $basicId = $settings->line_bot_basic_id;

        // สร้าง LINE deep link (จับคู่ 100%)
        $lineDeepLink = null;
        $lineAddFriendUrl = null;

        if ($basicId) {
            if (! str_starts_with($basicId, '@')) {
                $basicId = '@'.$basicId;
            }

            // Deep link: เปิดแชท OA พร้อม pre-fill "ref_{token}"
            $lineDeepLink = $referral->generateDeepLink($basicId);

            // Fallback: ลิงก์เพิ่มเพื่อนปกติ (สำหรับ browser)
            $lineAddFriendUrl = 'https://line.me/R/ti/p/'.$basicId;
        }

        Log::info('Fortune Referral: เปิด landing page', [
            'referral_id' => $referral->id,
            'referrer_user_id' => $referral->referrer_user_id,
        ]);

        // ดึงข้อมูลแผนการตลาดสำหรับแสดงในหน้า landing
        $mlmSettings = MlmGlobalSetting::first();
        $commissionInfo = $this->buildCommissionInfo($settings, $mlmSettings);

        return view('fortune.invite-landing', [
            'error' => null,
            'referral' => $referral,
            'referrerName' => $referrerName,
            'lineDeepLink' => $lineDeepLink,
            'lineAddFriendUrl' => $lineAddFriendUrl,
            'referralCode' => 'ref_'.$referral->referral_token,
            'expiresAt' => $referral->expires_at?->toIso8601String(),
            'settings' => $settings,
            'commissionInfo' => $commissionInfo,
        ]);
    }
}
