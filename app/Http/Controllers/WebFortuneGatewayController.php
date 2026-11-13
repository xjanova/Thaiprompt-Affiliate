<?php

namespace App\Http\Controllers;

use App\Models\FortunePage;
use App\Models\FortuneWebHandoff;
use App\Models\User;
use App\Services\FacebookWebhookService;
use App\Services\Fortune\FortunePageContext;
use App\Services\Fortune\WebFunnelTracker;
use App\Services\FortuneAffiliateService;
use App\Services\FortuneWebLinkService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * WebFortuneGatewayController
 *
 * 🌐 (2026-07-24) ประตูทางเข้า "ดูดวงบนเว็บ" จากบอท FB/LINE
 *
 * 🔐 (2026-11-03) T13 — ยกเครื่องความปลอดภัยสำหรับโหมด FB web-only:
 *
 * 1. GET  /web-fortune/go?tk=   → ไม่มีผลข้างเคียงใด ๆ (ไม่สร้าง user ไม่ login ไม่กิน token)
 *    แค่ตรวจ token แล้ว render หน้า interstitial ที่ auto-submit POST (มี CSRF)
 *    ⇒ crawler ของ FB (facebookexternalhit/Facebot) และ prefetch ไม่สร้าง/ล็อกอินบัญชี
 * 2. POST /web-fortune/go       → ใช้ token จริง:
 *    - jti ใช้ได้ครั้งเดียว (ตาราง fortune_web_link_uses — ไม่ใช่ Cache ที่ deploy ล้างทิ้ง)
 *      ยกเว้นรีเฟรชจาก session เดิมที่ล็อกอินเป็นคนเดียวกันอยู่แล้ว
 *    - lock ต่อ PSID ก่อน resolveOrCreateWebUser (กดซ้อน 2 ครั้งต้องได้ user เดียว)
 *    - ปฏิเสธบัญชีหลังบ้าน (FortuneBackOfficeAccount) / hotel admin / ระงับ / ต้องใช้ 2FA
 *    - Auth::login(remember=false) — ห้ามฝาก remember cookie อายุยาวจากลิงก์ที่ส่งต่อกันได้
 *    - (รีวิว) login CSRF: GET auto-submit เฉพาะเปิดจากแอปแชท/ไม่ cross-site และไม่มีลูกค้าคนอื่นล็อกอินอยู่
 *      สลับบัญชีลูกค้า = ต้องกดยืนยันเอง (confirm_switch)
 *
 * LINE (ห้ามกระทบ): token 72 ชม. กดซ้ำได้ remember=true และทางที่ไม่ผ่าน → SSO แบบเดิม
 *    - session wf_psid / wf_page (ให้ FacebookLoginController ผูก ASID เข้า user เดิม)
 *    - เติม thaiprompt_user_id ลงแถว handoff (เฉพาะ PSID ตรงกัน) + users.fortune_origin_page_id (ครั้งเดียว)
 * 3. ทางที่ไม่ใช่ happy path ห้ามจบที่หน้า login ของ Thaiprompt → /web-fortune/expired
 *    มีปุ่ม "ส่งลิงก์ใหม่เข้าแชท" (POST /web-fortune/resend) — ลิงก์ใหม่เข้า inbox ของ PSID เจ้าของเท่านั้น
 * 4. throttle นับต่อ hash ของ token (wfgo) แทนต่อ IP (ลูกค้ามือถือใช้ CGNAT IP เดียวกันเยอะ)
 *
 * ห้ามกระทบ LINE: isEnabledFor('line') = enable_web_fortune_button เหมือนเดิม
 * และ resend ใช้ได้เฉพาะ facebook (LINE push มีโควต้า 300/เดือน — ห้ามยิง)
 *
 * 🔁 (2026-11-03 รีวิว) เส้นใหม่ทั้งหมดข้างบน (interstitial / single-use / expired / resend / wf_psid)
 *    ใช้เฉพาะ token ของ FB **และ** ธง facebook_web_only เปิดเท่านั้น
 *    LINE ทุกกรณี และ FB ตอนธงปิด = เส้นเดิมของ e2a9e0868 (legacyGo): GET ตรวจ token → หา/สร้างบัญชี
 *    → Auth::login(remember=true) → เย็บตัวตนข้ามสาขา → SSO · ทางที่ไม่ผ่าน → SSO
 *    ด่านความปลอดภัยที่คงไว้ทุกเส้น: isDeniedAccount (ทีมงาน/hotel admin/ระงับ/2FA → ไปล็อกอินเองที่ SSO)
 *    ไม่สลับ session ทีมงาน · regex กัน // (open redirect) · จำกัด platform · lock ต่อ PSID กันบัญชีซ้ำ
 */
class WebFortuneGatewayController extends Controller
{
    /** จำนวนครั้งต่อ token ต่อนาที (นับรวม GET + POST) */
    public const GO_MAX_PER_TOKEN_PER_MINUTE = 20;

    /** เส้นเดิม (ธงปิด / LINE): เพดานเดิมของ route GET /web-fortune/go = 30 ครั้ง/นาที/IP */
    public const LEGACY_GO_MAX_PER_IP_PER_MINUTE = 30;

    /** ส่งลิงก์ใหม่ได้กี่ครั้งต่อ PSID ต่อชั่วโมง */
    public const RESEND_MAX_PER_HOUR = 3;

    /** ล็อกต่อ PSID (วินาที) / รอ lock ได้นานสุด (วินาที) */
    public const PSID_LOCK_SECONDS = 15;

    public const PSID_LOCK_WAIT_SECONDS = 8;

    /** role ที่ห้าม auto-login จาก Magic Link เด็ดขาด */
    public const DENIED_ROLES = ['admin', 'super_admin', 'staff', 'moderator', 'manager', 'support'];

    /** เหตุผลที่หน้า expired รู้จัก */
    public const REASONS = ['expired', 'used', 'invalid', 'denied', 'staff_session', 'busy', 'error', 'throttled'];

    /** ข้อความในปุ่มที่ส่งเข้าแชท (ห้ามพูดถึงเงินเด็ดขาด — FB แบนเพจที่มีสลิปในแชท) */
    public const RESEND_TEXT = 'แม่หมอเปิดห้องรอไว้ให้แล้วนะคะ 🌙 กดปุ่มด้านล่างเพื่อเข้ามาคุยกับแม่หมอต่อได้เลยค่ะ';

    public const RESEND_BUTTON_TITLE = '🔮 เข้าห้องแม่หมอ';

    /** cache ผลเช็คคอลัมน์ users.fortune_origin_page_id (T14 เพิ่ม — ก่อน migrate ต้องไม่พัง) */
    protected static ?bool $hasOriginPageColumn = null;

    /**
     * GET — ตรวจ token แล้วแสดงหน้า interstitial (ไม่มีผลข้างเคียงใด ๆ)
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function go(Request $request, FortuneWebLinkService $linkService)
    {
        $token = (string) $request->query('tk', '');

        // 🔁 LINE / ธง FB web-only ปิด → เส้นเดิม (GET ล็อกอินแล้วส่งต่อ SSO ทันที)
        if (! $this->usesWebOnlyFlow($linkService, $token)) {
            return $this->legacyGo($request, $linkService, $token);
        }

        if ($throttled = $this->throttleToken($token)) {
            return $throttled;
        }

        $payload = $linkService->verifyToken($token);
        if (! $payload) {
            return $this->badToken($linkService, $token);
        }

        // สวิตช์ของแพลตฟอร์มนี้ปิด → พฤติกรรมเดิม (ส่งไป SSO เฉย ๆ ไม่ auto-login)
        if (! $linkService->isEnabledFor($payload['p'])) {
            return redirect()->away($linkService->getSsoUrl());
        }

        // 🔐 (รีวิว) กัน login CSRF: auto-submit เฉพาะการเปิดจากแอปแชทจริง และไม่สลับบัญชีลูกค้าที่ล็อกอินอยู่เงียบ ๆ
        $switchFrom = $this->switchingFrom($payload);

        return $this->noStore(response()->view('web-fortune.interstitial', [
            'token' => $token,
            'autoSubmit' => $switchFrom === null && ! $this->isCrossSiteNavigation($request),
            'switchFromName' => $switchFrom?->name,
        ]));
    }

    /**
     * POST — ใช้ token: login ลูกค้าแล้วส่งต่อ SSO ของเว็บจันทรา
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function consume(Request $request, FortuneWebLinkService $linkService)
    {
        $token = (string) $request->input('tk', '');

        // 🔁 LINE / ธง FB web-only ปิด → เส้นเดิม (POST นี้ไม่มีในเวอร์ชันเดิม — ทำเหมือน GET เดิมทุกอย่าง)
        if (! $this->usesWebOnlyFlow($linkService, $token)) {
            return $this->legacyGo($request, $linkService, $token);
        }

        if ($throttled = $this->throttleToken($token)) {
            return $throttled;
        }

        $ssoUrl = $linkService->getSsoUrl();

        $payload = $linkService->verifyToken($token);
        if (! $payload) {
            return $this->badToken($linkService, $token);
        }

        if (! $linkService->isEnabledFor($payload['p'])) {
            return redirect()->away($ssoUrl);
        }

        $isFacebook = $payload['p'] === 'facebook';

        // 🛡️ เบราว์เซอร์นี้ล็อกอินเป็นทีมงานอยู่ (แอดมินเพจกดลิงก์ลูกค้าจาก Business Suite)
        //    ห้ามสลับ session ทีมงานไปเป็นลูกค้า และห้ามกิน token — ลูกค้าตัวจริงยังใช้ลิงก์ได้
        $current = Auth::user();
        if ($current instanceof User && $this->isDeniedAccount($current)) {
            Log::warning('WebFortune: session ปัจจุบันเป็นบัญชีทีมงาน — ไม่ใช้ token ลูกค้า', [
                'current_user_id' => $current->id,
            ]);

            // LINE: คงทางเดิม (ไป SSO) — หน้า expired ของ LINE ขอลิงก์ใหม่ไม่ได้
            return $isFacebook ? $this->toExpired($token, 'staff_session') : redirect()->away($ssoUrl);
        }

        // 🔐 (รีวิว) login CSRF: เบราว์เซอร์นี้ล็อกอินเป็นลูกค้าคนอื่นอยู่ → ต้องกดยืนยันสลับบัญชีเองก่อน
        //    (ไม่ Auth::logout เงียบ ๆ) — ยังไม่กิน token · LINE คงพฤติกรรมเดิม
        if ($isFacebook && (string) $request->input('confirm_switch') !== '1') {
            $switchFrom = $this->switchingFrom($payload);
            if ($switchFrom !== null) {
                return $this->noStore(response()->view('web-fortune.interstitial', [
                    'token' => $token,
                    'autoSubmit' => false,
                    'switchFromName' => $switchFrom->name,
                ]));
            }
        }

        // ใช้ token ครั้งเดียว (เฉพาะ FB) — เก็บใน DB (cache:clear ตอน deploy ต้องไม่ทำให้ลิงก์เก่าใช้ซ้ำได้)
        // LINE: ลิงก์ในประวัติแชทกดซ้ำได้ภายในอายุ token เหมือนเดิม (ห้ามกระทบ LINE)
        $jti = $isFacebook ? (string) $payload['j'] : null;
        if ($jti !== null && ! $this->claimJti($jti, (int) $payload['exp'])) {
            $owner = $this->jtiOwner($jti);

            // รีเฟรชใน in-app browser จาก session เดิม (ล็อกอินเป็น user เดียวกันอยู่แล้ว) → ไปต่อได้
            if ($current instanceof User && $owner !== null && $owner === (int) $current->id) {
                return $this->redirectToSso($ssoUrl, $payload);
            }

            Log::info('WebFortune: token ถูกใช้ไปแล้ว — ไม่ login', [
                'platform' => $payload['p'],
                'same_session' => false,
            ]);

            return $this->toExpired($token, 'used');
        }

        // หา/สร้างบัญชีจาก platform id — lock ต่อ PSID กันสร้างซ้ำตอนกดซ้อน
        try {
            $user = Cache::lock('wf:psid:'.$payload['u'], self::PSID_LOCK_SECONDS)
                ->block(self::PSID_LOCK_WAIT_SECONDS, function () use ($payload) {
                    // ส่ง pg เป็น argument ที่ 3 (T14 เพิ่มพารามิเตอร์นี้ — ก่อนหน้านั้น PHP ตัดทิ้งเอง)
                    return app(FortuneAffiliateService::class)
                        ->resolveOrCreateWebUser($payload['p'], $payload['u'], $payload['pg'] ?? null);
                });
        } catch (LockTimeoutException $e) {
            // คืน token ให้ลองใหม่ได้ — ยังไม่มีใครถูก login
            $this->releaseJti($jti);
            Log::warning('WebFortune: รอ lock PSID ไม่ทัน', ['platform' => $payload['p']]);

            return $this->toExpired($token, 'busy');
        } catch (\Throwable $e) {
            $this->releaseJti($jti);
            Log::error('WebFortune: resolveOrCreateWebUser ล้มเหลว', [
                'platform' => $payload['p'],
                'error' => $e->getMessage(),
            ]);

            return $this->toExpired($token, 'error');
        }

        if (! $user instanceof User) {
            $this->releaseJti($jti);

            return $isFacebook ? $this->toExpired($token, 'error') : redirect()->away($ssoUrl);
        }

        // 🔒 ตรวจสิทธิ์ — Magic Link มีไว้สำหรับลูกค้าบอทเท่านั้น (token ถูกเผาไปแล้ว ตั้งใจ)
        //    รวมบัญชีที่ต้องใช้ 2FA ตอนล็อกอิน / ถูกระงับ (ด่านเดียวกับ Facebook/Google login)
        if ($this->isDeniedAccount($user)) {
            Log::warning('WebFortune: ปฏิเสธ auto-login บัญชีที่ไม่ใช่ลูกค้า', ['user_id' => $user->id]);

            // LINE: ทางเดิม = ส่งไป SSO ให้ล็อกอินเอง (ผ่านรหัสผ่าน/2FA ตามปกติ)
            return $isFacebook ? $this->toExpired($token, 'denied') : redirect()->away($ssoUrl);
        }

        // ล็อกอิน Thaiprompt — SSO ขั้นถัดไปจะ auto-approve เพราะมี session แล้ว
        // FB: ไม่ remember (ลิงก์ส่งต่อกันได้) · LINE: remember เหมือนเดิม (ห้ามกระทบ LINE)
        if ($current instanceof User && (int) $current->id !== (int) $user->id) {
            Auth::logout();
        }
        Auth::login($user, ! $isFacebook);
        $request->session()->regenerate();

        if ($jti !== null) {
            $this->setJtiOwner($jti, (int) $user->id);
        }

        // 🪪 hint (อ่อน) ว่าเบราว์เซอร์นี้เพิ่งมาจาก Magic Link ของ PSID นี้ (เฉพาะ FB)
        //    🔒 (รีวิว) ไม่ใช้ผูกตัวตนแล้ว — FacebookLoginController ใช้แค่ "ไม่ต่อสายงานตอนสมัคร"
        if ($isFacebook) {
            $request->session()->put('wf_psid', $payload['u']);
            $request->session()->put('wf_page', $payload['pg'] ?? null);
        }

        $this->stampOriginPage($user, $payload);
        $this->attachHandoff($user, $payload);

        // 🔗 (2026-08-15) เย็บความจำข้ามสาขา — ครอบ try/catch: พังตรงนี้ห้ามทำให้เข้าเว็บไม่ได้
        try {
            if ($isFacebook && ! empty($user->facebook_user_id)) {
                app(\App\Services\Fortune\CrossPageIdentityService::class)
                    ->link('facebook', (string) $payload['u'], (string) $user->facebook_user_id);
            }
        } catch (\Throwable $e) {
            Log::warning('🔗 เย็บตัวตนข้ามสาขาไม่สำเร็จ (เข้าเว็บสำเร็จตามปกติ)', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('WebFortune: ล็อกอินอัตโนมัติสำเร็จ → ส่งต่อ SSO', [
            'user_id' => $user->id,
            'platform' => $payload['p'],
            'page' => $payload['pg'] ?? null,
            'handoff' => isset($payload['h']),
        ]);

        // 📈 (2026-11-14) ตัวนับ FB → เว็บ ขั้น 2 — แตะลิงก์แล้วเข้าได้จริง (LINE ไม่นับ)
        if ($isFacebook) {
            WebFunnelTracker::linkOpened(
                (string) $payload['u'],
                (int) $user->id,
                is_numeric($payload['pg'] ?? null) ? (int) $payload['pg'] : null,
                $user->wasRecentlyCreated || ($user->created_at !== null && $user->created_at->gt(now()->subMinutes(2))),
            );
        }

        return $this->redirectToSso($ssoUrl, $payload);
    }

    /**
     * GET — หน้าลิงก์หมดอายุ/ใช้แล้ว/เสีย (ไม่มีผลข้างเคียง)
     *
     * @return \Illuminate\Http\Response
     */
    public function expired(Request $request, FortuneWebLinkService $linkService)
    {
        // 🔁 ธงปิด = ไม่มีหน้านี้ในเส้นเดิม → ส่งไป SSO เฉย ๆ (ไม่จำ PSID ลง session ไม่มีปุ่มส่งลิงก์ใหม่)
        if (! $linkService->fbWebOnlyEnabled()) {
            return redirect()->away($linkService->getSsoUrl());
        }

        $token = (string) $request->query('tk', '');
        $reason = (string) $request->query('reason', 'expired');
        if (! in_array($reason, self::REASONS, true)) {
            $reason = 'expired';
        }

        // ลายเซ็นยังถูก (หมดอายุไม่เกิน 14 วันก็ได้) + เป็น FB + ระบบเปิด → ขอลิงก์ใหม่เข้าแชทได้
        $payload = $token !== '' ? $linkService->verifyToken($token, true) : null;
        $canResend = $payload !== null
            && $payload['p'] === 'facebook'
            && ! in_array($reason, ['denied', 'staff_session', 'invalid'], true)
            && $linkService->isEnabledFor('facebook');

        $current = Auth::user();
        $canContinue = $current instanceof User && ! $this->isDeniedAccount($current);

        // 🔐 (รีวิว) ลิงก์ของลูกค้า FB (ลายเซ็นถูก) → จำ PSID แบบอ่อนไว้ใน session
        //    ถ้าลูกค้าไปล็อกอิน Facebook เอง FacebookLoginController จะไม่ต่อสายงานตอนสมัคร
        //    (ลูกค้าเพจสาขาหา PSID ด้วย ids_for_pages ไม่เจอ — ห้ามล็อกใต้ผู้แนะนำเริ่มต้น)
        $isFacebookLink = ($payload['p'] ?? null) === 'facebook';
        if ($isFacebookLink && ! in_array($reason, ['denied', 'staff_session', 'invalid'], true)) {
            $request->session()->put('wf_psid_weak', (string) $payload['u']);
        }

        return $this->noStore(response()->view('web-fortune.expired', [
            'token' => $canResend ? $token : '',
            'reason' => $reason,
            'platform' => $payload['p'] ?? null,
            'canResend' => $canResend,
            'canContinue' => $canContinue,
            'resendStatus' => session('wf_resend_status'),
            // ลิงก์ของลูกค้า FB → ใช้ปุ่ม "ส่งลิงก์ใหม่เข้าแชท" อย่างเดียว (ล็อกอิน FB เองได้บัญชีซ้ำ — M1/M5)
            'facebookLoginUrl' => $isFacebookLink ? null : $this->facebookFallbackUrl(),
        ]));
    }

    /**
     * POST — ส่งลิงก์ใหม่เข้า inbox ของ PSID เจ้าของ token เท่านั้น
     *
     * คนที่ได้ลิงก์มาจากการส่งต่อกดได้ แต่ลิงก์ใหม่ไปเข้าแชทของเจ้าของ ⇒ ไม่ได้อะไร
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request, FortuneWebLinkService $linkService)
    {
        // 🔁 ธงปิด = ไม่มีการส่งลิงก์ใหม่เข้าแชทในเส้นเดิม → ไป SSO (ไม่ยิงข้อความใด ๆ)
        if (! $linkService->fbWebOnlyEnabled()) {
            return redirect()->away($linkService->getSsoUrl());
        }

        $token = (string) $request->input('tk', '');
        $payload = $linkService->verifyToken($token, true);

        $back = fn (string $status) => redirect()
            ->route('web-fortune.expired', ['tk' => $token, 'reason' => (string) $request->input('reason', 'expired')])
            ->with('wf_resend_status', $status);

        // เฉพาะ FB เท่านั้น — LINE push มีโควต้าจำกัด ห้ามยิงจากหน้าเว็บ
        if (! $payload || $payload['p'] !== 'facebook' || ! $linkService->isEnabledFor('facebook')) {
            return $back('unavailable');
        }

        $limitKey = 'wf-resend:'.sha1($payload['p'].'|'.$payload['u']);
        if (RateLimiter::tooManyAttempts($limitKey, self::RESEND_MAX_PER_HOUR)) {
            return $back('limited');
        }
        RateLimiter::hit($limitKey, 3600);

        try {
            [$to, $nonce] = $this->resendDestination($payload);
            $url = $linkService->generateChatLink('facebook', $payload['u'], $to, $nonce, $payload['pg'] ?? null);

            $template = [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => self::RESEND_TEXT,
                        'buttons' => [[
                            'type' => 'web_url',
                            'url' => $url,
                            'title' => self::RESEND_BUTTON_TITLE,
                            'webview_height_ratio' => 'full',
                        ]],
                    ],
                ],
            ];

            $send = fn () => (new FacebookWebhookService)->sendButtonTemplate($payload['u'], $template);

            // สาขาต้นทางจาก token → ใช้ token ของเพจนั้นส่ง (PSID เป็นของเพจเดียวเสมอ)
            $page = ! empty($payload['pg']) ? FortunePage::find($payload['pg']) : null;
            $sent = $page ? FortunePageContext::run($page, $send) : $send();
        } catch (\Throwable $e) {
            Log::warning('WebFortune: ส่งลิงก์ใหม่เข้าแชทไม่สำเร็จ', ['error' => $e->getMessage()]);
            $sent = false;
        }

        Log::info('WebFortune: ขอส่งลิงก์ใหม่เข้าแชท', ['sent' => (bool) $sent, 'page' => $payload['pg'] ?? null]);

        // ส่งไม่ออกส่วนใหญ่ = เกินกรอบ 24 ชม. ของ Messenger → บอกให้ทักแม่หมอก่อน
        return $back($sent ? 'sent' : 'failed');
    }

    /**
     * GET — ทางสำรองหลังเข้าด้วย Facebook: ส่งต่อไป SSO ของเว็บจันทรา
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sso(FortuneWebLinkService $linkService)
    {
        return redirect()->away($linkService->getSsoUrl());
    }

    // ============================================================
    // 🔁 เส้นเดิม (e2a9e0868) — LINE ทุกกรณี และ FB ตอนธง facebook_web_only ปิด
    // ============================================================

    /**
     * token นี้ใช้เส้นใหม่ (FB web-only) ไหม — ธงต้องเปิด และ token ต้องไม่ใช่ของ LINE
     *
     * ธงเปิด + token เสีย/อ่านไม่ออก = เส้นใหม่ (หน้า expired แบบที่เป็นอยู่) · LINE = เส้นเดิมเสมอ
     */
    protected function usesWebOnlyFlow(FortuneWebLinkService $linkService, string $token): bool
    {
        if (! $linkService->fbWebOnlyEnabled()) {
            return false;
        }

        return $this->peekPlatform($linkService, $token) !== 'line';
    }

    /**
     * Magic Link แบบเดิม: ตรวจ token → หา/สร้างบัญชี → ล็อกอิน (remember) → ส่งต่อ SSO
     *
     * ทุกทางที่ไม่ผ่าน → SSO เฉย ๆ (ลูกค้าล็อกอินเองได้ ไม่ dead-end) เหมือนเดิม
     * ต่างจากเดิมเฉพาะด่านความปลอดภัยที่ตั้งใจคงไว้ (ดู docblock ของคลาส)
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    protected function legacyGo(Request $request, FortuneWebLinkService $linkService, string $token)
    {
        $ssoUrl = $linkService->getSsoUrl();

        // เพดานเดิมของ route (30/นาที/IP) — route ตอนนี้ตั้งกว้างไว้ให้เส้นใหม่ที่นับต่อ token
        $limitKey = 'wfgo-legacy:'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($limitKey, self::LEGACY_GO_MAX_PER_IP_PER_MINUTE)) {
            return response('Too Many Attempts.', 429, [
                'Retry-After' => (string) RateLimiter::availableIn($limitKey),
            ]);
        }
        RateLimiter::hit($limitKey, 60);

        // ปิดระบบอยู่ → พาไปหน้า SSO เฉย ๆ (ลูกค้าล็อกอินเองได้ ไม่ dead-end)
        if (! $linkService->isEnabled()) {
            return redirect()->away($ssoUrl);
        }

        // ตรวจ token (ลายเซ็น + วันหมดอายุ + platform)
        $payload = $linkService->verifyToken($token !== '' ? $token : null);
        if (! $payload) {
            Log::info('WebFortune: token ไม่ผ่านการตรวจสอบ — ส่งไปหน้า SSO ปกติ', [
                'ip' => $request->ip(),
            ]);

            return redirect()->away($ssoUrl);
        }

        // 🛡️ (คงไว้) เบราว์เซอร์นี้ล็อกอินเป็นทีมงานอยู่ → ห้ามสลับ session ทีมงานไปเป็นลูกค้า
        $current = Auth::user();
        if ($current instanceof User && $this->isDeniedAccount($current)) {
            Log::warning('WebFortune: session ปัจจุบันเป็นบัญชีทีมงาน — ไม่ใช้ token ลูกค้า', [
                'current_user_id' => $current->id,
            ]);

            return redirect()->away($ssoUrl);
        }

        // หา/สร้างบัญชีจาก platform id — (คงไว้) lock ต่อ PSID กันกดซ้อนแล้วได้บัญชีซ้ำ
        try {
            $user = Cache::lock('wf:psid:'.$payload['u'], self::PSID_LOCK_SECONDS)
                ->block(self::PSID_LOCK_WAIT_SECONDS, function () use ($payload) {
                    return app(FortuneAffiliateService::class)
                        ->resolveOrCreateWebUser($payload['p'], $payload['u']);
                });
        } catch (LockTimeoutException $e) {
            Log::warning('WebFortune: รอ lock PSID ไม่ทัน', ['platform' => $payload['p']]);
            $user = null;
        } catch (\Throwable $e) {
            Log::error('WebFortune: resolveOrCreateWebUser ล้มเหลว', [
                'platform' => $payload['p'],
                'error' => $e->getMessage(),
            ]);
            $user = null;
        }

        if (! $user instanceof User) {
            return redirect()->away($ssoUrl);
        }

        // 🔒 ห้าม auto-login บัญชีทีมงาน/แอดมิน/ระงับ/ต้องใช้ 2FA — ไปล็อกอินเองที่ SSO
        if ($this->isDeniedAccount($user)) {
            Log::warning('WebFortune: ปฏิเสธ auto-login บัญชีที่ไม่ใช่ลูกค้า', ['user_id' => $user->id]);

            return redirect()->away($ssoUrl);
        }

        // ล็อกอิน Thaiprompt (remember เหมือนเดิม) — SSO ขั้นถัดไปจะ auto-approve เพราะมี session แล้ว
        Auth::login($user, true);
        $request->session()->regenerate();

        // 🔗 (2026-08-15) เย็บความจำข้ามสาขา — พังตรงนี้ห้ามทำให้เข้าเว็บไม่ได้
        try {
            if ($payload['p'] === 'facebook' && ! empty($user->facebook_user_id)) {
                app(\App\Services\Fortune\CrossPageIdentityService::class)
                    ->link('facebook', (string) $payload['u'], (string) $user->facebook_user_id);
            }
        } catch (\Throwable $e) {
            Log::warning('🔗 เย็บตัวตนข้ามสาขาไม่สำเร็จ (เข้าเว็บสำเร็จตามปกติ)', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('WebFortune: ล็อกอินอัตโนมัติสำเร็จ → ส่งต่อ SSO', [
            'user_id' => $user->id,
            'platform' => $payload['p'],
            'to' => $payload['to'] ?? null,
        ]);

        // 🌳 path ปลายทางจาก token (ผ่าน HMAC + regex แล้ว) — ส่งต่อเป็น ?to= ของ SSO
        if (! empty($payload['to'])) {
            $sep = str_contains($ssoUrl, '?') ? '&' : '?';

            return redirect()->away($ssoUrl.$sep.'to='.urlencode($payload['to']));
        }

        return redirect()->away($ssoUrl);
    }

    // ============================================================
    // ตัวช่วย
    // ============================================================

    /**
     * บัญชีที่ห้าม auto-login จาก Magic Link: บัญชีหลังบ้าน (เกณฑ์กลาง) บัญชีที่ถูกระงับ
     * และบัญชีที่ต้องยืนยัน 2 ขั้นตอนตอนล็อกอิน (ด่านเดียวกับ Facebook/Google login — กัน 2FA ถูกข้าม)
     * เช็คไม่ได้ (exception) = ถือว่าห้าม (fail-closed)
     *
     * 🔐 (รีวิว) ไม่เหมารวม "มี role_id" แล้ว — ลูกค้าที่เปิดร้าน (role_id = seller) ยังเข้าเว็บได้
     */
    protected function isDeniedAccount(User $user): bool
    {
        try {
            if ($user->isSuperAdmin() || \App\Support\FortuneBackOfficeAccount::is($user)) {
                return true;
            }
            if (in_array((string) ($user->role ?? ''), self::DENIED_ROLES, true)) {
                return true;
            }

            app(\App\Services\Auth\SocialLoginGuard::class)->assertCanSignIn($user, 'web_fortune');

            return false;
        } catch (\Throwable $e) {
            // SocialLoginRefusedException (ระงับ/2FA) หรือเช็คไม่ได้ = ห้าม
            return true;
        }
    }

    /**
     * 🔐 (รีวิว) บัญชีลูกค้าที่ล็อกอินอยู่ในเบราว์เซอร์นี้ ซึ่งไม่ใช่เจ้าของ token FB (= จะถูกสลับบัญชี)
     *
     * อ่านอย่างเดียว ไม่สร้างบัญชี — null = ไม่มีใครล็อกอิน / คนเดียวกัน / ทีมงาน (ดักที่อื่น) / LINE
     */
    protected function switchingFrom(array $payload): ?User
    {
        $current = Auth::user();
        if (! $current instanceof User || $payload['p'] !== 'facebook' || $this->isDeniedAccount($current)) {
            return null;
        }

        try {
            $owner = app(FortuneAffiliateService::class)->findExistingUser((string) $payload['u'], 'facebook');
        } catch (\Throwable) {
            $owner = null;
        }

        return ($owner && (int) $owner->id === (int) $current->id) ? null : $current;
    }

    /**
     * 🔐 (รีวิว) คำขอนี้ถูกพามาจากเว็บอื่น (ไม่ใช่การเปิดจากแอปแชท/พิมพ์ URL เอง) หรือไม่
     *
     * Sec-Fetch-Site: none/same-origin/same-site = เปิดจากแอปหรือเว็บเราเอง → auto-submit ได้
     * cross-site ที่ Referer มาจาก Facebook/Messenger/LINE (link shim) ก็นับเป็นการเปิดจากแอปแชท
     * ไม่มี header (webview เก่า) = ไม่ถือว่า cross-site · หน้า interstitial มีปุ่มให้กดเองเสมอ
     */
    protected function isCrossSiteNavigation(Request $request): bool
    {
        $site = strtolower((string) $request->headers->get('Sec-Fetch-Site', ''));
        if ($site === '' || in_array($site, ['none', 'same-origin', 'same-site'], true)) {
            return false;
        }

        $host = strtolower((string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_HOST));
        foreach (['facebook.com', 'messenger.com', 'fb.com', 'm.me', 'line.me', 'line-apps.com'] as $trusted) {
            if ($host === $trusted || str_ends_with($host, '.'.$trusted)) {
                return false;
            }
        }

        return true;
    }

    /**
     * token เสีย/หมดอายุ — FB → หน้า expired (มีปุ่มขอลิงก์ใหม่)
     * LINE → SSO แบบเดิม (หน้า expired ขอลิงก์ใหม่ให้ LINE ไม่ได้ — ห้ามกระทบ LINE)
     */
    protected function badToken(FortuneWebLinkService $linkService, string $token)
    {
        if ($this->peekPlatform($linkService, $token) === 'line') {
            return redirect()->away($linkService->getSsoUrl());
        }

        return $this->toExpired($token, $this->reasonForBadToken($linkService, $token));
    }

    /**
     * อ่าน platform ของ token เพื่อเลือก "หน้าปลายทาง" เท่านั้น (ห้ามใช้ยืนยันตัวตน)
     * ลายเซ็นถูก → ค่าจาก payload · ลายเซ็นเสีย → อ่าน p ดิบ ๆ
     */
    protected function peekPlatform(FortuneWebLinkService $linkService, string $token): ?string
    {
        $relaxed = $token !== '' ? $linkService->verifyToken($token, true) : null;
        if ($relaxed) {
            return $relaxed['p'];
        }

        $parts = explode('.', $token, 2);
        $raw = json_decode((string) base64_decode(strtr($parts[0] ?? '', '-_', '+/')), true);

        return is_array($raw) && is_string($raw['p'] ?? null) ? $raw['p'] : null;
    }

    // ------------------------------------------------------------
    // 🔐 (รีวิว) jti ใช้ครั้งเดียว — เก็บใน DB (fortune_web_link_uses) · ตารางยังไม่มี = Cache
    // ------------------------------------------------------------

    protected static ?bool $hasLinkUsesTable = null;

    protected function linkUsesReady(): bool
    {
        if (self::$hasLinkUsesTable === true) {
            return true;
        }

        try {
            return self::$hasLinkUsesTable = Schema::hasTable('fortune_web_link_uses');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * จอง jti — true = คำขอนี้เป็นคนแรก (insert-or-ignore บน unique jti เป็น atomic)
     */
    protected function claimJti(string $jti, int $exp): bool
    {
        if (! $this->linkUsesReady()) {
            return Cache::add('wf:jti:'.$jti, 'pending', max(60, $exp - now()->timestamp + 3600));
        }

        // เก็บกวาดแถวหมดอายุเป็นครั้งคราว (ไม่ต้องพึ่ง scheduler)
        if (random_int(1, 100) === 1) {
            try {
                DB::table('fortune_web_link_uses')->where('expires_at', '<', now()->subDay())->delete();
            } catch (\Throwable) {
                // ข้ามได้
            }
        }

        return DB::table('fortune_web_link_uses')->insertOrIgnore([
            'jti' => $jti,
            'user_id' => null,
            'expires_at' => Carbon::createFromTimestamp($exp),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    protected function jtiOwner(string $jti): ?int
    {
        $owner = $this->linkUsesReady()
            ? DB::table('fortune_web_link_uses')->where('jti', $jti)->value('user_id')
            : Cache::get('wf:jti:'.$jti);

        return is_numeric($owner) ? (int) $owner : null;
    }

    protected function setJtiOwner(string $jti, int $userId): void
    {
        if ($this->linkUsesReady()) {
            DB::table('fortune_web_link_uses')
                ->where('jti', $jti)
                ->update(['user_id' => $userId, 'updated_at' => now()]);

            return;
        }

        Cache::put('wf:jti:'.$jti, $userId, now()->addDays(2));
    }

    /**
     * คืน token ให้ลองใหม่ได้ (ยังไม่มีใครถูก login ด้วย token นี้)
     */
    protected function releaseJti(?string $jti): void
    {
        if ($jti === null) {
            return;
        }

        try {
            if ($this->linkUsesReady()) {
                DB::table('fortune_web_link_uses')->where('jti', $jti)->whereNull('user_id')->delete();
            }
            Cache::forget('wf:jti:'.$jti);
        } catch (\Throwable) {
            // ข้ามได้
        }
    }

    /**
     * throttle ต่อ token (hash) — คืน response 429 ถ้าเกิน
     */
    protected function throttleToken(string $token)
    {
        // ไม่มี token เลย → นับต่อ IP แทน (กันคำขอเปล่า ๆ แชร์ถังเดียวกันทั้งระบบ)
        $key = $token !== ''
            ? 'wfgo:'.sha1($token)
            : 'wfgo:ip:'.sha1((string) request()->ip());

        if (RateLimiter::tooManyAttempts($key, self::GO_MAX_PER_TOKEN_PER_MINUTE)) {
            return $this->noStore(response()->view('web-fortune.expired', [
                'token' => '',
                'reason' => 'throttled',
                'platform' => null,
                'canResend' => false,
                'canContinue' => false,
                'resendStatus' => null,
                'facebookLoginUrl' => $this->facebookFallbackUrl(),
            ], 429));
        }

        RateLimiter::hit($key, 60);

        return null;
    }

    /**
     * token ไม่ผ่าน → แยกว่า "หมดอายุ" (ลายเซ็นถูก) หรือ "เสีย"
     */
    protected function reasonForBadToken(FortuneWebLinkService $linkService, string $token): string
    {
        if ($token === '') {
            return 'invalid';
        }

        $relaxed = $linkService->verifyToken($token, true);

        return ($relaxed && ! empty($relaxed['expired'])) ? 'expired' : 'invalid';
    }

    protected function toExpired(string $token, string $reason)
    {
        $this->countDeadLink($token, $reason);

        $params = ['reason' => $reason];
        if ($token !== '') {
            $params['tk'] = $token;
        }

        return redirect()->route('web-fortune.expired', $params);
    }

    /**
     * 📈 (2026-11-14) ตัวนับ FB → เว็บ — ลูกค้าแตะลิงก์แต่เข้าไม่ได้ (เหตุผลเก็บไว้ดูว่าตันตรงไหน)
     *
     * ลิงก์+เหตุผลเดียวกันนับครั้งเดียวใน 1 ชม. (ตัวพรีวิวลิงก์ของแอปแชท/กดรีเฟรช ไม่ทำให้ยอดเฟ้อ)
     * อ่าน PSID จาก token ที่ลายเซ็นถูกเท่านั้น (หมดอายุก็อ่านได้) · ห้าม throw
     */
    protected function countDeadLink(string $token, string $reason): void
    {
        try {
            if (! Cache::add('wff:dead:'.sha1($token).':'.$reason, 1, now()->addHour())) {
                return;
            }

            $payload = $token !== '' ? app(FortuneWebLinkService::class)->verifyToken($token, true) : null;
            if ($payload !== null && ($payload['p'] ?? null) !== 'facebook') {
                return;
            }

            WebFunnelTracker::linkDead(
                $payload !== null ? (string) $payload['u'] : null,
                is_numeric($payload['pg'] ?? null) ? (int) $payload['pg'] : null,
                $reason,
            );
        } catch (\Throwable $e) {
            Log::warning('📈 WebFunnel: นับลิงก์ที่ใช้ไม่ได้ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ส่งต่อ SSO พร้อม ?to= — ปลายทางจาก token ก่อน ไม่มีแต่มี handoff → /chat/continue/{h}
     */
    protected function redirectToSso(string $ssoUrl, array $payload)
    {
        $to = $payload['to'] ?? null;
        if (empty($to) && ! empty($payload['h'])) {
            $to = '/chat/continue/'.$payload['h'];
        }

        if (! empty($to) && preg_match(FortuneWebLinkService::TO_PATH_REGEX, $to)) {
            $sep = str_contains($ssoUrl, '?') ? '&' : '?';

            return redirect()->away($ssoUrl.$sep.'to='.urlencode($to));
        }

        return redirect()->away($ssoUrl);
    }

    /**
     * เขียนสาขาต้นทางลง users.fortune_origin_page_id (ครั้งเดียว — เฉพาะตอนยังว่าง)
     */
    protected function stampOriginPage(User $user, array $payload): void
    {
        if ($payload['p'] !== 'facebook' || empty($payload['pg'])) {
            return;
        }

        try {
            if (self::$hasOriginPageColumn === null) {
                self::$hasOriginPageColumn = Schema::hasColumn('users', 'fortune_origin_page_id');
            }
            if (! self::$hasOriginPageColumn) {
                return;
            }

            User::query()
                ->whereKey($user->id)
                ->whereNull('fortune_origin_page_id')
                ->update(['fortune_origin_page_id' => (int) $payload['pg']]);
        } catch (\Throwable $e) {
            Log::warning('WebFortune: บันทึกสาขาต้นทางไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * เติม thaiprompt_user_id ลงแถว handoff — เฉพาะเมื่อ platform + PSID ตรงกับแถว
     */
    protected function attachHandoff(User $user, array $payload): void
    {
        if (empty($payload['h'])) {
            return;
        }

        try {
            $handoff = FortuneWebHandoff::findUsableByNonce($payload['h']);
            if (! $handoff
                || (string) $handoff->platform_user_id !== (string) $payload['u']
                || (string) $handoff->platform !== (string) $payload['p']) {
                return;
            }

            $handoff->forceFill(['thaiprompt_user_id' => $user->id])->save();
        } catch (\Throwable $e) {
            Log::warning('WebFortune: ผูก handoff กับ user ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ปลายทางของลิงก์ใหม่ตอน resend — [to, handoffNonce]
     *
     * - handoff เดิมยังใช้ได้ → ใช้ nonce เดิม
     * - handoff เดิมหมดอายุ/ถูกใช้ → ออกแถวใหม่ (คัด intent เดิมมา) ให้แม่หมอยังต่อเรื่องเดิมได้
     * - ไม่มี handoff → ใช้ to เดิม
     */
    protected function resendDestination(array $payload): array
    {
        $to = $payload['to'] ?? null;
        $h = $payload['h'] ?? null;

        if (! $h) {
            return [$to, null];
        }

        $old = FortuneWebHandoff::query()->where('nonce', $h)->first();
        if ($old && $old->isUsable() && (string) $old->platform_user_id === (string) $payload['u']) {
            $nonce = $old->nonce;
        } else {
            $sameOwner = $old && (string) $old->platform_user_id === (string) $payload['u'];
            $nonce = FortuneWebHandoff::issue([
                'platform' => 'facebook',
                'platform_user_id' => $payload['u'],
                'fortune_page_id' => $payload['pg'] ?? null,
                'thaiprompt_user_id' => $sameOwner ? $old->thaiprompt_user_id : null,
                'intent_text' => $sameOwner ? $old->intent_text : null,
                'intent_topic' => $sameOwner ? $old->intent_topic : null,
                'source' => 'resend',
            ])->nonce;
        }

        // to เดิมเป็นเส้น continue (หรือไม่มี) → ชี้ไป nonce ปัจจุบัน
        if (empty($to) || str_starts_with($to, '/chat/continue/')) {
            $to = '/chat/continue/'.$nonce;
        }

        return [$to, $nonce];
    }

    /**
     * ปุ่ม "เข้าด้วย Facebook" — ล็อกอิน FB แล้วเด้งกลับ /web-fortune/sso → เว็บจันทรา
     */
    protected function facebookFallbackUrl(): string
    {
        try {
            return route('facebook.login', ['redirect' => '/web-fortune/sso']);
        } catch (\Throwable $e) {
            return url('/web-fortune/sso');
        }
    }

    /**
     * หน้าที่มี token ใน URL — ห้าม cache และห้ามส่ง referrer ออกไปเว็บอื่น
     */
    protected function noStore($response)
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
