<?php

namespace App\Http\Controllers;

use App\Services\FortuneAffiliateService;
use App\Services\FortuneWebLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * WebFortuneGatewayController
 *
 * 🌐 (2026-07-24) ประตูทางเข้า "ดูดวงฟรีบนเว็บ" จากบอท FB/LINE
 *
 * Flow: ลูกค้ากด Magic Link จากบอท → ตรวจ token → หา/สร้างบัญชี (จาก PSID/LINE id)
 *   → ล็อกอิน Thaiprompt อัตโนมัติ → redirect ไป SSO juntraweb (Passport auto-approve)
 *   → ลูกค้าถึงเว็บจันทราแบบล็อกอินแล้ว ไม่ต้องกรอกอะไรเลย
 *
 * ระบบทั้งหมดอยู่หลังสวิตช์ enable_web_fortune_button (default OFF)
 */
class WebFortuneGatewayController extends Controller
{
    /**
     * รับ Magic Link จากบอท แล้วพาลูกค้าเข้าเว็บจันทราแบบล็อกอินอัตโนมัติ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function go(Request $request, FortuneWebLinkService $linkService)
    {
        $ssoUrl = $linkService->getSsoUrl();

        // ปิดระบบอยู่ → พาไปหน้า SSO เฉยๆ (ลูกค้าล็อกอินเองได้ ไม่ dead-end)
        if (! $linkService->isEnabled()) {
            return redirect()->away($ssoUrl);
        }

        // ตรวจ token (ลายเซ็น + วันหมดอายุ)
        $payload = $linkService->verifyToken($request->query('tk'));
        if (! $payload) {
            Log::info('WebFortune: token ไม่ผ่านการตรวจสอบ — ส่งไปหน้า SSO ปกติ', [
                'ip' => $request->ip(),
            ]);

            return redirect()->away($ssoUrl);
        }

        // หา/สร้างบัญชีจาก platform id (reuse ระบบสมัครอัตโนมัติของบอท)
        try {
            $user = app(FortuneAffiliateService::class)
                ->resolveOrCreateWebUser($payload['p'], $payload['u']);
        } catch (\Throwable $e) {
            Log::error('WebFortune: resolveOrCreateWebUser ล้มเหลว', [
                'platform' => $payload['p'],
                'error' => $e->getMessage(),
            ]);
            $user = null;
        }

        if (! $user) {
            return redirect()->away($ssoUrl);
        }

        // 🔒 ห้าม auto-login เข้าบัญชีระดับแอดมิน — Magic Link มีไว้สำหรับลูกค้าบอทเท่านั้น
        //   (กันเคสบัญชีแอดมินผูก LINE id/PSID ไว้ แล้วลิงก์หลุดไปถึงคนอื่น)
        try {
            if ($user->isSuperAdmin() || in_array($user->role ?? '', ['admin', 'super_admin'], true)) {
                Log::warning('WebFortune: ปฏิเสธ auto-login บัญชีระดับแอดมิน', ['user_id' => $user->id]);

                return redirect()->away($ssoUrl);
            }
        } catch (\Throwable $e) {
            // เช็ค role ไม่ได้ → เลือกทางปลอดภัย ไม่ auto-login
            return redirect()->away($ssoUrl);
        }

        // ล็อกอิน Thaiprompt — SSO ขั้นถัดไปจะ auto-approve เพราะมี session แล้ว
        Auth::login($user, true);
        $request->session()->regenerate();

        Log::info('WebFortune: ล็อกอินอัตโนมัติสำเร็จ → ส่งต่อ SSO', [
            'user_id' => $user->id,
            'platform' => $payload['p'],
            'to' => $payload['to'] ?? null,
        ]);

        // 🌳 (2026-07-25) path ปลายทางจาก token (เช่น /mlm = ผังงาน) — ผ่าน HMAC + whitelist regex แล้ว
        //   ส่งต่อเป็น ?to= ให้ SSO ของ juntraweb พาลูกค้าลงหน้าที่ต้องการหลังล็อกอิน
        if (! empty($payload['to'])) {
            $sep = str_contains($ssoUrl, '?') ? '&' : '?';

            return redirect()->away($ssoUrl.$sep.'to='.urlencode($payload['to']));
        }

        return redirect()->away($ssoUrl);
    }
}
