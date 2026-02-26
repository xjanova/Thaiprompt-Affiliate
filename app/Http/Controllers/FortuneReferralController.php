<?php

namespace App\Http\Controllers;

use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FortuneReferralController
 *
 * จัดการ landing page สำหรับลิงก์เชิญเพื่อนดูดวง
 * เมื่อเพื่อนกดลิงก์ → บันทึก referral → แสดงปุ่มเพิ่มเพื่อน LINE OA
 */
class FortuneReferralController extends Controller
{
    /**
     * Landing page — เมื่อเพื่อนกดลิงก์เชิญ
     */
    public function landing(Request $request, string $token)
    {
        // หา referral จาก token
        $referral = FortuneReferral::where('referral_token', $token)->first();

        if (! $referral) {
            return view('fortune.invite-landing', [
                'error' => 'ลิงก์ไม่ถูกต้องหรือหมดอายุแล้ว',
                'referral' => null,
                'referrerName' => null,
                'lineAddFriendUrl' => null,
                'lineQrCodeUrl' => null,
            ]);
        }

        // ตรวจหมดอายุ
        if ($referral->isExpired()) {
            return view('fortune.invite-landing', [
                'error' => 'ลิงก์นี้หมดอายุแล้ว กรุณาขอลิงก์ใหม่จากผู้เชิญ',
                'referral' => null,
                'referrerName' => null,
                'lineAddFriendUrl' => null,
                'lineQrCodeUrl' => null,
            ]);
        }

        // บันทึก IP + user_agent
        $referral->update([
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
        ]);

        // ดึงข้อมูลคนเชิญ
        $referrerName = $referral->referrerUser?->name ?? 'เพื่อนของคุณ';

        // ดึง LINE OA settings สำหรับลิงก์เพิ่มเพื่อน
        $settings = FortuneTellingSetting::getSettings();

        // สร้าง LINE add friend URL
        $lineAddFriendUrl = null;
        $lineQrCodeUrl = null;

        // ใช้ LINE Bot Basic ID (เช่น @002dqcls) สร้างลิงก์เพิ่มเพื่อน
        // ⚠️ line_channel_id คือ Messaging API Channel ID (ตัวเลข) → ใช้สำหรับ API เท่านั้น
        // ⚠️ line_bot_basic_id คือ LINE OA Basic ID (@xxx) → ใช้สำหรับลิงก์เพิ่มเพื่อน
        $basicId = $settings->line_bot_basic_id;

        if ($basicId) {
            // ถ้าไม่มี @ นำหน้า ให้เติมให้
            if (! str_starts_with($basicId, '@')) {
                $basicId = '@'.$basicId;
            }
            $lineAddFriendUrl = 'https://line.me/R/ti/p/'.$basicId;
        }

        Log::info('Fortune Referral: เปิด landing page', [
            'referral_id' => $referral->id,
            'referrer_user_id' => $referral->referrer_user_id,
            'ip' => $request->ip(),
        ]);

        return view('fortune.invite-landing', [
            'error' => null,
            'referral' => $referral,
            'referrerName' => $referrerName,
            'lineAddFriendUrl' => $lineAddFriendUrl,
            'lineQrCodeUrl' => $lineQrCodeUrl,
            'settings' => $settings,
        ]);
    }
}
