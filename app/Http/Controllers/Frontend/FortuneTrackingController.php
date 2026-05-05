<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\FortuneUserCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🌟 (2026-05-05) FortuneTrackingController
 *
 * Redirect-tracking endpoint สำหรับปุ่ม web_url ใน Messenger button template
 * เนื่องจาก FB ไม่ส่ง postback กลับเมื่อ user คลิก web_url → ใช้ tracking redirect แทน
 *
 * Flow:
 *   1. Bot ส่ง button template "ติดตามเพจ" / "เข้ากลุ่ม" → URL = /fortune/track/...
 *   2. User คลิก → browser request มาที่ controller นี้
 *   3. Controller บันทึก action (Cache + DB) → 302 redirect ไปยัง FB page/group
 *
 * ⚠️ Trust: คลิก = สนใจ (ไม่ได้ verify ว่า follow/join จริง — Meta ไม่เปิด API)
 */
class FortuneTrackingController extends Controller
{
    /**
     * Track Facebook page follow click → redirect to page
     *
     * GET /fortune/track/fb-follow/{psid}
     */
    public function fbFollow(Request $request, string $psid)
    {
        $settings = FortuneTellingSetting::getSettings();
        $pageId = $settings->facebook_page_id ?? null;

        // บันทึก click — มอง user ว่ายืนยัน follow แล้ว (กัน prompt ซ้ำ)
        try {
            $credit = FortuneUserCredit::getOrCreate($psid, 'facebook');
            if (! $credit->hasFacebookFollowed()) {
                $credit->markFacebookFollowed();
                Log::info('Fortune Tracking: user clicked follow page button', [
                    'psid' => $psid,
                    'page_id' => $pageId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Fortune Tracking: fbFollow record failed (non-blocking)', [
                'psid' => $psid,
                'error' => $e->getMessage(),
            ]);
        }

        // Redirect ไปหน้า FB page
        $redirectUrl = $pageId
            ? "https://www.facebook.com/{$pageId}"
            : 'https://www.facebook.com';

        return redirect()->away($redirectUrl, 302);
    }

    /**
     * Track Facebook group invite click → redirect to group
     *
     * GET /fortune/track/fb-group/{psid}
     */
    public function fbGroup(Request $request, string $psid)
    {
        $settings = FortuneTellingSetting::getSettings();
        $groupUrl = $settings->fortune_group_url
            ?? 'https://www.facebook.com/groups/1539006181120751';

        // บันทึก click — Cache 30 วัน (ไม่ต้องสร้างคอลัมน์ใหม่)
        //   key: fb:group_clicked:{psid} → ใช้แสดงสถิติ admin + กัน spam DM ซ้ำ
        try {
            Cache::put("fb:group_clicked:{$psid}", now()->toIso8601String(), now()->addDays(30));

            // บันทึกใน FortuneUserCredit ด้วย (สำหรับ admin query/analytics)
            $credit = FortuneUserCredit::getOrCreate($psid, 'facebook');
            $credit->update([
                'facebook_group_clicked_at' => now(),
            ]);

            Log::info('Fortune Tracking: user clicked group invite button', [
                'psid' => $psid,
                'group_url' => $groupUrl,
            ]);
        } catch (\Throwable $e) {
            // ถ้า column facebook_group_clicked_at ยังไม่ migrate → log warning + ไป fallback (cache)
            Log::warning('Fortune Tracking: fbGroup record failed (non-blocking — cache only)', [
                'psid' => $psid,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->away($groupUrl, 302);
    }
}
