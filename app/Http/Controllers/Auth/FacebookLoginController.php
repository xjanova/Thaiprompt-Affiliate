<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialLoginRefusedException;
use App\Http\Controllers\Controller;
use App\Models\FacebookOAuthSetting;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\User;
use App\Models\Wallet;
use App\Rules\NotReservedEmailDomain;
use App\Services\Auth\MobileAppLogin;
use App\Services\Auth\SocialLoginGuard;
use App\Services\FacebookWebhookService;
use App\Services\FortuneAffiliateService;
use App\Support\LocalRedirect;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

/**
 * Facebook OAuth Login Controller
 *
 * ใช้ Laravel Socialite เชื่อมกับ Facebook Login
 *
 * Flow:
 * 1. ลูกค้ากด "Login with Facebook" → /auth/facebook
 * 2. Redirect ไป Facebook OAuth
 * 3. FB redirect กลับมา /auth/facebook/callback พร้อม code
 * 4. Socialite แลก code → user data (id, name, email, avatar)
 * 5. Match กับ User ที่มีอยู่:
 *    a. facebook_user_id ตรง → existing user
 *    b. email ตรง → link FB ให้ existing user
 *    c. map ASID→PSID ผ่าน Graph ids_for_pages → บัญชีที่บอทสมัครให้ตอนดูดวง
 *    d. ไม่เจอ → สร้างใหม่ + Wallet + MlmMember
 * 6. login + redirect ไป intended URL (default: /user/wallet)
 */
class FacebookLoginController extends Controller
{
    /**
     * Redirect ไป Facebook OAuth
     *
     * 📱 (2026-09-27) รองรับแอป: /auth/facebook?mobile_token=..&state=.. (มาจาก /mobile-login)
     *    ใช้ App ID/Secret และ callback /auth/facebook/callback ตัวเดิมของเว็บ — ไม่มี redirect URI ใหม่
     */
    public function redirect(Request $request): RedirectResponse|View
    {
        $mobileLogin = app(MobileAppLogin::class);
        $mobileToken = MobileAppLogin::text($request->query('mobile_token'));
        $mobileState = MobileAppLogin::text($request->query('state'));
        $fromApp = $request->has('mobile_token');

        $setting = $this->loadSetting();
        if (! $setting) {
            if ($fromApp && $mobileToken !== '' && $mobileState !== '') {
                return $mobileLogin->backToLoginPage(
                    ['token' => $mobileToken, 'state' => $mobileState],
                    'ยังไม่เปิดให้เข้าสู่ระบบด้วย Facebook — กรุณาใช้วิธีอื่น'
                );
            }

            return redirect()->route('login')
                ->with('error', 'Facebook Login ยังไม่ได้ตั้งค่า — กรุณาแจ้งผู้ดูแลระบบ');
        }

        if ($fromApp) {
            // ลิงก์จากแอปต้องชี้ token ที่ยังรอล็อกอินอยู่จริง — ไม่งั้นไม่พาไป Facebook เลย
            if (! $mobileLogin->findPending($mobileToken, $mobileState)) {
                return $mobileLogin->expiredView();
            }

            $mobileLogin->remember('facebook', $mobileToken, $mobileState);
        } else {
            // ล็อกอินเว็บปกติ — ล้างร่องรอยจากแอปที่อาจค้างอยู่ใน session
            $mobileLogin->forget('facebook');
        }

        // เก็บ intended URL เพื่อ redirect หลัง login (default: wallet)
        // 🔐 เฉพาะหน้าในเว็บเราเท่านั้น (กัน open redirect ผ่าน ?redirect=https://เว็บอื่น)
        Session::forget('facebook_login_redirect');
        if ($target = LocalRedirect::sanitize($request->get('redirect'))) {
            Session::put('facebook_login_redirect', $target);
        }

        // เก็บ referral code ถ้ามี (สำหรับสมัครใหม่ผ่านลิงก์)
        if ($request->has('ref')) {
            Session::put('facebook_login_referral', $request->get('ref'));
        }

        // เก็บ origin page (login/register) เพื่อ redirect กลับ on error
        $referer = $request->headers->get('referer', '');
        Session::put('facebook_login_origin', str_contains($referer, '/register') ? 'register' : 'login');

        return Socialite::driver('facebook')
            ->scopes($setting->getScopes())
            ->redirect();
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function callback(Request $request): RedirectResponse|View
    {
        // 🔗 (2026-08-28) เส้น "เชื่อมเพจด้วย Facebook" ของหลังบ้าน ใช้ redirect_uri ตัวเดียวกับที่นี่
        //    (เป็น URI เดียวที่ whitelist ไว้ในแอป — เพิ่มตัวใหม่ต้องไปแก้ตั้งค่าบน developers.facebook.com)
        //    แยกออกจากล็อกอินลูกค้าด้วย state ที่ขึ้นต้นด้วย pageconnect:
        //    ⚠️ ต้องเช็คก่อน Socialite เสมอ ไม่งั้น Socialite จะกิน code ไปสร้างบัญชีลูกค้าให้แทน
        $state = (string) $request->get('state', '');

        if (str_starts_with($state, \App\Http\Controllers\Admin\FortunePagesController::OAUTH_STATE_PREFIX)) {
            return app(\App\Http\Controllers\Admin\FortunePagesController::class)
                ->handleOAuthCallback($request);
        }

        // 📱 มาจากแอปไหม — อ่านจาก session เท่านั้น (ใช้ครั้งเดียว) ไม่เชื่อค่าใน query ของ callback
        $mobileLogin = app(MobileAppLogin::class);
        $mobile = $mobileLogin->pull('facebook');

        // Load setting + apply runtime config (สำคัญ! Socialite อ่าน config ตอน driver init)
        $setting = $this->loadSetting();
        $errorOrigin = Session::get('facebook_login_origin', 'login');
        $errorRoute = $errorOrigin === 'register' ? 'register' : 'login';

        // ผิดพลาด → แอปกลับหน้าเข้าสู่ระบบของแอป · เว็บกลับหน้าเดิม (login/register)
        $fail = function (string $message) use ($mobile, $mobileLogin, $errorRoute): RedirectResponse {
            if ($mobile) {
                Session::forget(['facebook_login_origin', 'facebook_login_referral', 'facebook_login_redirect']);

                return $mobileLogin->backToLoginPage($mobile, $message);
            }

            return redirect()->route($errorRoute)->with('error', $message);
        };

        if (! $setting) {
            return $fail('Facebook Login ยังไม่ได้ตั้งค่า — กรุณาแจ้งผู้ดูแลระบบ');
        }

        // User cancel หรือ error จาก FB
        if ($request->has('error')) {
            Log::info('Facebook OAuth: user denied or error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
                'from_app' => $mobile !== null,
            ]);

            return $fail('การเข้าสู่ระบบด้วย Facebook ถูกยกเลิก');
        }

        try {
            $fbUser = Socialite::driver('facebook')->user();
        } catch (Exception $e) {
            Log::error('Facebook OAuth: callback failed', [
                'error' => $e->getMessage(),
            ]);

            return $fail('ไม่สามารถเชื่อมต่อกับ Facebook ได้ กรุณาลองใหม่');
        }

        // Validate FB user data
        if (empty($fbUser->getId())) {
            Log::warning('Facebook OAuth: missing user ID');

            return $fail('ข้อมูลจาก Facebook ไม่ครบ กรุณาลองใหม่');
        }

        try {
            $user = $this->findOrCreateUser($fbUser);
            // 🔒 ระงับ / ต้องใช้ 2FA → ไม่ให้เข้าทางลัด (เดิมเข้าได้แล้วค่อยโดนเด้งหน้าถัดไป · แอปได้ token ไปด้วย)
            app(SocialLoginGuard::class)->assertCanSignIn($user, 'facebook');
        } catch (SocialLoginRefusedException $e) {
            // ข้อความภาษาไทยที่ตั้งใจให้ผู้ใช้เห็นเท่านั้น
            Log::info('Facebook OAuth: ปฏิเสธการเข้าสู่ระบบ', ['reason' => $e->reason(), 'from_app' => $mobile !== null]);

            return $fail($e->getMessage());
        } catch (\Throwable $e) {
            // อย่างอื่นทั้งหมด (รวม QueryException) ห้ามถึงหน้าจอ — log แล้วแสดงข้อความกลางๆ
            Log::error('Facebook OAuth: findOrCreateUser failed', [
                'fb_user_id' => $fbUser->getId(),
                'exception' => class_basename($e),
                'error' => Str::limit($e->getMessage(), 300),
            ]);

            return $fail('เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่');
        }

        // Login user — เฉพาะเว็บ
        // 📱 มาจากแอป = ไม่ล็อกอินเว็บในเบราว์เซอร์ของมือถือ (Custom Tab แชร์ cookie กับ Chrome — ไม่ทิ้ง session ที่จำไว้)
        if (! $mobile) {
            Auth::login($user, true);
        }

        // 🔗 (2026-08-15) เย็บความจำข้ามสาขา — ตอนนี้เรารู้ทั้ง ASID และ PSID พร้อมกัน
        //    ASID ผูกกับ "แอป" ไม่ใช่ "เพจ" → เป็นกุญแจเดียวที่ใช้บอกได้ว่า
        //    PSID ของสาขา 5 กับของเพจหลัก เป็นคนคนเดียวกัน
        //    (ไม่ใช้ ids_for_pages เพราะโดนล็อกด้วย Business Manager สำหรับเพจสาขา)
        //    ⚠️ non-blocking โดยตั้งใจ — service กลืน error เอง ห้ามให้ล็อกอินพังเพราะเรื่องนี้
        //    🛡️ ครอบ try/catch อีกชั้นนอกเหนือจากใน service — นี่คือเส้นทางล็อกอิน
        //       ฟีเจอร์เสริมห้ามทำให้คนเข้าระบบไม่ได้เด็ดขาด แย่สุดคือเสียความจำข้ามสาขา
        try {
            if (! empty($user->facebook_psid)) {
                app(\App\Services\Fortune\CrossPageIdentityService::class)
                    ->link('facebook', (string) $user->facebook_psid, (string) $fbUser->getId());
            }
        } catch (\Throwable $e) {
            Log::warning('🔗 เย็บตัวตนข้ามสาขาไม่สำเร็จ (ล็อกอินสำเร็จตามปกติ)', [
                'error' => $e->getMessage(),
            ]);
        }

        // Track login stats
        $setting->recordLogin();

        Log::info('Facebook OAuth: login success', [
            'user_id' => $user->id,
            'fb_user_id' => $fbUser->getId(),
            'is_new_user' => $user->wasRecentlyCreated,
            'from_app' => $mobile !== null,
        ]);

        // 📱 มาจากแอป → ออก auth code แล้วพากลับแอป (thaiprompt://auth)
        if ($mobile) {
            Session::forget(['facebook_login_redirect', 'facebook_login_origin', 'facebook_login_referral']);

            return $mobileLogin->authorize($user, $mobile['token'], $mobile['state'], 'facebook');
        }

        // Redirect ไป intended URL หรือ wallet (default)
        $redirectUrl = LocalRedirect::sanitize(Session::pull('facebook_login_redirect')) ?? route('user.wallet.index');
        Session::forget(['facebook_login_origin', 'facebook_login_referral']);

        return redirect($redirectUrl)
            ->with('success', 'เข้าสู่ระบบด้วย Facebook สำเร็จ ยินดีต้อนรับ '.$user->name);
    }

    /**
     * หา user จาก FB data หรือสร้างใหม่
     *
     * Match priority:
     *   1. facebook_user_id ตรง → existing FB-linked user
     *   2. user.email ตรง → link FB ให้ user เดิม เฉพาะบัญชีที่ยืนยันอีเมลแล้ว + ไม่ใช่แอดมิน
     *      (ไม่เข้าเงื่อนไข = SocialLoginRefusedException ให้เข้าด้วยอีเมล+รหัสผ่าน)
     *   3. map ASID→PSID ผ่าน Graph ids_for_pages → บัญชีที่บอทสมัครให้ตอนดูดวง
     *      (facebook_psid หรือ email fb_{psid}@thaiprompt.local)
     *   4. ไม่เจอ → สร้างใหม่
     */
    protected function findOrCreateUser($fbUser): User
    {
        $fbId = $fbUser->getId();
        $fbEmail = $fbUser->getEmail();
        // 🔒 อีเมลจากผู้ให้บริการในโดเมนสงวนของระบบ = ห้ามใช้ทั้งผูกบัญชีเดิมและตั้งเป็นอีเมลบัญชีใหม่
        //   (ไม่งั้นผูก FB เข้ากับบัญชีบอทของคนอื่นได้ด้วยการตั้งอีเมลให้ตรงสูตร)
        if (NotReservedEmailDomain::isReserved($fbEmail)) {
            $fbEmail = null;
        }

        // 1. หา by facebook_user_id (linked แล้ว)
        $user = User::where('facebook_user_id', $fbId)->first();
        if ($user) {
            $this->refreshFacebookFields($user, $fbUser);
            $this->reconcileMissingPsid($user, $fbId);

            return $user;
        }

        // 2. หา by email (เคย register แบบอื่น)
        //    🔒 (2026-09-27) ผูกอัตโนมัติได้เฉพาะบัญชีที่ "ยืนยันอีเมลแล้ว" และ "ไม่ใช่แอดมิน"
        //       ไม่งั้นคนร้ายสมัคร/แก้โปรไฟล์ใส่อีเมลเหยื่อไว้ก่อน → เหยื่อกด Facebook = ตัวตนเหยื่อถูกผูกเข้าบัญชีคนร้าย
        //       ไม่เข้าเงื่อนไข = ปฏิเสธ (ไม่ผูก ไม่สร้างบัญชีซ้ำ) ให้เข้าด้วยอีเมล+รหัสผ่าน
        if ($fbEmail) {
            $user = User::where('email', $fbEmail)->first();
            if ($user) {
                $guard = app(SocialLoginGuard::class);
                $guard->assertCanAutoLinkByEmail($user, 'facebook');
                // ระงับ / 2FA → ปฏิเสธก่อนผูก
                $guard->assertCanSignIn($user, 'facebook');

                $this->linkFacebookToUser($user, $fbUser);
                $this->reconcileMissingPsid($user, $fbId);

                return $user;
            }
        }

        // 3. ลูกค้าเคย auto-register จากดูดวงทาง Messenger — บัญชีระบุตัวตนด้วย PSID
        //    แต่ OAuth ให้ ASID (คนละ ID space) → map ผ่าน Graph ids_for_pages
        //    ก่อนแก้ (2026-07-16): ข้ามขั้นนี้ไปสร้างบัญชีใหม่เสมอ
        //    → ลูกค้า FB 648 คนเข้าบัญชี/วอลเลตเดิมของตัวเองไม่ได้เลย
        $psid = $this->resolveMessengerPsid($fbId);
        if ($psid) {
            $user = User::findByMessengerPsid($psid);
            if ($user) {
                $this->linkFacebookToUser($user, $fbUser);

                // เก็บ PSID ลงคอลัมน์ ให้ครั้งหน้า match ได้ตั้งแต่ขั้น 1
                if (! $user->facebook_psid && Schema::hasColumn('users', 'facebook_psid')) {
                    $user->update(['facebook_psid' => $psid]);
                }

                Log::info('Facebook OAuth: matched บัญชีที่บอทสมัครให้ ผ่าน ids_for_pages', [
                    'user_id' => $user->id,
                    'fb_user_id' => $fbId,
                    'psid' => $psid,
                ]);

                return $user;
            }
        }

        // 4. สร้าง user ใหม่ + wallet + MLM (เก็บ PSID ด้วยถ้า map ได้
        //    — เผื่อลูกค้า login เว็บก่อนเคยคุยกับบอท ให้บอทหาบัญชีนี้เจอทีหลัง)
        return $this->createNewUserFromFacebook($fbUser, $psid);
    }

    /**
     * map App-Scoped ID (จาก OAuth) → Messenger Page-Scoped ID ผ่าน Graph ids_for_pages
     *
     * เงื่อนไขฝั่ง Facebook: app กับเพจต้องอยู่ Business เดียวกัน
     * (ที่นี่เป็น app เดียวกันทั้งบอทและ OAuth — FortuneTellingSetting.facebook_app_id
     * ตรงกับ FacebookOAuthSetting.app_id)
     *
     * คืน null เมื่อ map ไม่ได้ทุกกรณี (ไม่เคยคุยกับเพจ / config ไม่ครบ / API ล่ม)
     * — caller จะ fallback ไปสร้างบัญชีใหม่ตาม flow เดิม
     */
    protected function resolveMessengerPsid(string $appScopedId): ?string
    {
        // เคยได้คำตอบชัดเจนจาก Graph ว่า "ไม่มี mapping" — ไม่ยิงซ้ำทุก login
        // (ไม่ cache กรณี error เพื่อให้ transient failure ได้ลองใหม่ครั้งหน้า)
        $noneKey = 'fb_oauth:psid_none:'.$appScopedId;
        if (Cache::has($noneKey)) {
            return null;
        }

        try {
            $fortune = FortuneTellingSetting::getSettings();
            $pageId = trim((string) ($fortune->facebook_page_id ?? ''));

            // 🔑 (2026-07-28) ต้องใช้ **App Access Token** (app_id|app_secret) เท่านั้น
            //    เดิมส่ง page token → Facebook ตอบ "(#100) Invalid Access Token used" ทุกครั้ง
            //    = ขั้นนี้ล้มเหลว 100% ตั้งแต่วันแรก ลูกค้าที่บอทสมัครให้จึงไม่เคยถูก match เลย
            //    (ยืนยันกับ Graph จริง: page token → 100 Invalid · app token → คืน PSID ถูกต้อง)
            $oauth = FacebookOAuthSetting::getActive();
            $appToken = ($oauth && $oauth->app_id && $oauth->app_secret)
                ? $oauth->app_id.'|'.$oauth->app_secret
                : null;

            if ($pageId === '' || ! $appToken) {
                Log::info('Facebook OAuth: ข้าม ids_for_pages — ไม่มี page_id หรือ app_id/app_secret');

                return null;
            }

            $response = Http::timeout(5)->get(
                'https://graph.facebook.com/'.FacebookWebhookService::GRAPH_API_VERSION."/{$appScopedId}/ids_for_pages",
                ['access_token' => $appToken, 'limit' => 100]
            );

            if (! $response->successful()) {
                Log::warning('Facebook OAuth: ids_for_pages ล้มเหลว', [
                    'status' => $response->status(),
                    'fb_error' => $response->json('error.message'),
                ]);

                return null;
            }

            // ⚠️ (2026-07-28) คำตอบจริงมีแค่ {"data":[{"id":"<PSID>"}]} — **ไม่มี page ติดมาด้วย**
            //    ขอ fields=id,page ไม่ได้: Facebook ต้องการสิทธิ์ pages_read_engagement
            //    ซึ่ง app token ไม่มี → เดิมโค้ดกรอง $row['page']['id'] จึงไม่มีวัน match
            //    (บั๊กที่ 2 — ต่อให้ token ถูกก็ยังคืน null อยู่ดี)
            $candidates = [];

            foreach ($response->json('data', []) as $row) {
                if (empty($row['id'])) {
                    continue;
                }

                // ถ้าวันหนึ่ง Facebook ส่ง page กลับมาด้วย → ใช้ระบุเพจให้ตรงเป๊ะ (แม่นที่สุด)
                if (isset($row['page']['id'])) {
                    if ((string) $row['page']['id'] === $pageId) {
                        return (string) $row['id'];
                    }

                    continue;
                }

                $candidates[] = (string) $row['id'];
            }

            // ไม่มีข้อมูลเพจให้กรอง → ยืนยันด้วยข้อมูลฝั่งเรา (PSID ที่รู้จักคือของเพจเราแน่นอน)
            foreach ($candidates as $candidate) {
                if (User::findByMessengerPsid($candidate)) {
                    return $candidate;
                }
            }

            // เหลือตัวเดียว = ไม่มีอะไรให้สับสน (ลูกค้าที่ยังไม่มีบัญชีในระบบก็เข้าทางนี้)
            if (count($candidates) === 1) {
                return $candidates[0];
            }

            Log::info('Facebook OAuth: ids_for_pages ไม่พบ PSID ของเพจนี้', [
                'fb_user_id' => $appScopedId,
                'pages_returned' => count($response->json('data', [])),
                'candidates' => count($candidates),
            ]);
            Cache::put($noneKey, true, now()->addDay());

            return null;
        } catch (Exception $e) {
            Log::warning('Facebook OAuth: ids_for_pages exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * เติม facebook_psid ให้บัญชีที่ match ขั้น 1/2 ได้แต่ยังไม่มี PSID
     *
     * กันเคสถาวร: ถ้า resolve ล้มเหลวตอน login ครั้งแรก (Graph ล่มชั่วคราว)
     * จะเกิดบัญชีซ้ำที่ถูก match ที่ขั้น 1 ตลอดไปโดยไม่เคยลอง map อีกเลย
     * — เมธอดนี้ลองใหม่ทุก login จนกว่าจะได้ (negative-cache กันยิง API ถี่)
     *
     * ถ้า PSID ชี้ไปบัญชีบอทใบอื่น (บัญชีซ้ำเกิดขึ้นแล้ว) — ห้ามแย่ง PSID มา
     * เพราะวอลเลต/คอมสะสมอยู่ใบโน้น ได้แค่ log ไว้ให้รวมบัญชีด้วยมือ
     */
    protected function reconcileMissingPsid(User $user, string $appScopedId): void
    {
        try {
            if ($user->facebook_psid || ! Schema::hasColumn('users', 'facebook_psid')) {
                return;
            }

            $psid = $this->resolveMessengerPsid($appScopedId);
            if (! $psid) {
                return;
            }

            $botUser = User::findByMessengerPsid($psid);
            if ($botUser && $botUser->id !== $user->id) {
                Log::warning('Facebook OAuth: user นี้มีบัญชีบอทเดิมอีกใบ — ต้องรวมบัญชีด้วยมือ', [
                    'user_id' => $user->id,
                    'bot_user_id' => $botUser->id,
                    'psid' => $psid,
                ]);

                return;
            }

            $user->update(['facebook_psid' => $psid]);

            Log::info('Facebook OAuth: เติม PSID ให้บัญชีที่ link แล้ว', [
                'user_id' => $user->id,
                'psid' => $psid,
            ]);
        } catch (Exception $e) {
            Log::warning('Facebook OAuth: reconcile PSID ล้มเหลว (ไม่กระทบ login)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update facebook fields เมื่อ login ซ้ำ (refresh profile + verified flag)
     */
    protected function refreshFacebookFields(User $user, $fbUser): void
    {
        $user->update([
            'facebook_email' => $fbUser->getEmail() ?: $user->facebook_email,
            'facebook_name' => $fbUser->getName() ?: $user->facebook_name,
            'facebook_picture_url' => $fbUser->getAvatar() ?: $user->facebook_picture_url,
            'facebook_verified' => true,
        ]);
    }

    /**
     * Link Facebook ให้ user เดิม (เคย register email/LINE)
     */
    protected function linkFacebookToUser(User $user, $fbUser): void
    {
        // กัน: facebook_user_id ของ FB คนนี้ถูก link กับ user คนอื่น
        $existingFb = User::where('facebook_user_id', $fbUser->getId())
            ->where('id', '!=', $user->id)
            ->exists();
        if ($existingFb) {
            throw new SocialLoginRefusedException('บัญชี Facebook นี้ถูกผูกกับผู้ใช้อื่นแล้ว', 'identity_taken');
        }

        $user->update([
            'facebook_user_id' => $fbUser->getId(),
            'facebook_email' => $fbUser->getEmail(),
            'facebook_name' => $fbUser->getName(),
            'facebook_picture_url' => $fbUser->getAvatar(),
            'facebook_verified' => true,
            'facebook_linked_at' => now(),
            // ใช้ avatar จาก FB ถ้า user ยังไม่มี
            'profile_picture' => $user->profile_picture ?: $fbUser->getAvatar(),
        ]);

        Log::info('Facebook OAuth: linked to existing user', [
            'user_id' => $user->id,
            'fb_user_id' => $fbUser->getId(),
        ]);
    }

    /**
     * สร้าง user ใหม่จาก Facebook profile + Wallet + MlmMember
     *
     * ใช้ FortuneAffiliateService::ensureWalletExists pattern
     * ผูก sponsor เป็น Super Admin (user_id=1) เป็น default
     */
    protected function createNewUserFromFacebook($fbUser, ?string $psid = null): User
    {
        // ไม่มีอีเมลจริงจาก FB → placeholder ใช้ prefix "fboauth_" แยก namespace
        // จากของบอท (fb_{PSID}@) — ข้างในนี้เป็น ASID คนละ ID space กับ PSID
        // ถ้าใช้สูตรเดียวกันจะแยกไม่ออกว่าเลขข้างในเป็น ID ชนิดไหน
        // (suffix @thaiprompt.local คงไว้ — เป็น marker "บัญชีสังเคราะห์" ที่
        // ระบบ PDPA/rotate-passwords ใช้แยกแยะ และลูกค้าเปลี่ยนเป็นอีเมลจริง
        // ได้เองที่หน้าโปรไฟล์)
        $providerEmail = $fbUser->getEmail();
        $email = $providerEmail && ! NotReservedEmailDomain::isReserved($providerEmail)
            ? $providerEmail
            : 'fboauth_'.$fbUser->getId().'@thaiprompt.local';

        return DB::transaction(function () use ($fbUser, $email, $psid) {
            $userData = [
                'name' => $fbUser->getName() ?: 'Facebook User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // random password — login ผ่าน FB เท่านั้น
                'profile_picture' => $fbUser->getAvatar(),
                'facebook_user_id' => $fbUser->getId(),
                'facebook_email' => $fbUser->getEmail(),
                'facebook_name' => $fbUser->getName(),
                'facebook_picture_url' => $fbUser->getAvatar(),
                'facebook_verified' => true,
                'facebook_linked_at' => now(),
                'email_verified_at' => $fbUser->getEmail() ? now() : null,
            ];

            // เก็บ PSID ถ้า map ได้ — บอทจะหาบัญชีนี้เจอผ่าน findExistingUser
            // ไม่สร้างซ้ำอีกใบตอนลูกค้ามาดูดวงทาง Messenger ทีหลัง
            if ($psid && Schema::hasColumn('users', 'facebook_psid')) {
                $userData['facebook_psid'] = $psid;
            }

            $user = User::create($userData);

            // สร้าง wallet ทันที (รองรับการรับคอมมิชชั่น)
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
            );

            // สร้าง MlmMember ผ่าน FortuneAffiliateService (มี logic ผูก sponsor + binary)
            try {
                $affiliateService = app(FortuneAffiliateService::class);
                // ใช้ reflection เรียก protected method (หรือเปิด method เป็น public ในอนาคต)
                $createMethod = new \ReflectionMethod($affiliateService, 'createMlmMember');
                $createMethod->setAccessible(true);
                $createMethod->invoke($affiliateService, $user, $fbUser->getId());
            } catch (Exception $mlmErr) {
                Log::warning('Facebook OAuth: createMlmMember failed (non-blocking)', [
                    'user_id' => $user->id,
                    'error' => $mlmErr->getMessage(),
                ]);
            }

            Log::info('Facebook OAuth: new user created', [
                'user_id' => $user->id,
                'fb_user_id' => $fbUser->getId(),
                'has_email' => ! empty($fbUser->getEmail()),
            ]);

            return $user;
        });
    }

    /**
     * โหลด setting จาก DB + apply runtime config ให้ Socialite
     *
     * Override config ตอน runtime — เพราะ Socialite อ่าน config('services.facebook.*')
     * ตอน driver instantiation. การเปลี่ยน DB row ไม่ทันที ต้อง Config::set() ตรงๆ
     *
     * Return null ถ้า config ยังไม่พร้อม (admin ยังไม่ได้กรอก/disabled)
     */
    protected function loadSetting(): ?FacebookOAuthSetting
    {
        $setting = FacebookOAuthSetting::getActive();

        if (! $setting || ! $setting->isReady()) {
            return null;
        }

        // Override Socialite config runtime — แทนที่ค่าจาก env ใน config/services.php
        Config::set('services.facebook.client_id', $setting->app_id);
        Config::set('services.facebook.client_secret', $setting->app_secret);
        Config::set('services.facebook.redirect', $setting->getRedirectUri());

        return $setting;
    }
}
