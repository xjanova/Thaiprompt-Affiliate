<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountActive;
use App\Models\GoogleOAuthSetting;
use App\Models\MlmMember;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Rules\NotReservedEmailDomain;
use App\Services\Auth\MobileAppLogin;
use App\Services\FortuneAffiliateService;
use App\Support\LocalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;
use Throwable;

/**
 * เข้าสู่ระบบด้วย Google (Laravel Socialite — driver google)
 *
 * Flow เว็บ:
 *   1. /auth/google → หน้าเลือกบัญชีของ Google (state กัน CSRF เก็บใน session โดย Socialite)
 *   2. Google ส่งกลับ /auth/google/callback?code=..&state=..
 *   3. จับคู่บัญชี: google_id ตรง → บัญชีเดิม
 *                  อีเมลตรง + Google ยืนยันอีเมลแล้วเท่านั้น → ผูก Google ให้บัญชีเดิม
 *                  ไม่เข้าเงื่อนไข → สร้างบัญชีใหม่ + Wallet + MlmMember (sponsor จาก ?ref=)
 *   4. ล็อกอิน → หน้าที่ขอไว้ (?redirect= เฉพาะหน้าในเว็บ) หรือหน้าแรกผู้ใช้
 *
 * Flow แอป: /auth/google?mobile_token=..&state=.. (มาจาก /mobile-login) → เหมือนข้างบน
 *   แต่ข้อ 4 ออก auth code ให้แอปผ่าน MobileAppLogin แล้วพากลับ thaiprompt://auth
 *
 * ค่า client id / secret มาจาก DB (GoogleOAuthSetting) — ยังไม่ตั้งค่า = ปุ่มซ่อน + route นี้พากลับหน้าเข้าสู่ระบบ
 */
class GoogleLoginController extends Controller
{
    public function __construct(protected MobileAppLogin $mobileLogin) {}

    /**
     * พาไปหน้าเลือกบัญชีของ Google
     */
    public function redirect(Request $request): RedirectResponse|View
    {
        $mobileToken = MobileAppLogin::text($request->query('mobile_token'));
        $mobileState = MobileAppLogin::text($request->query('state'));
        $fromApp = $request->has('mobile_token');

        $setting = $this->loadSetting();

        if (! $setting) {
            if ($fromApp && $mobileToken !== '' && $mobileState !== '') {
                return $this->mobileLogin->backToLoginPage(
                    ['token' => $mobileToken, 'state' => $mobileState],
                    'ยังไม่เปิดให้เข้าสู่ระบบด้วย Google — กรุณาใช้วิธีอื่น'
                );
            }

            return redirect()->route('login')
                ->with('error', 'ยังไม่เปิดให้เข้าสู่ระบบด้วย Google — กรุณาแจ้งผู้ดูแลระบบ');
        }

        if ($fromApp) {
            // ลิงก์จากแอปต้องชี้ token ที่ยังรอล็อกอินอยู่จริง — ไม่งั้นไม่พาไป Google เลย
            if (! $this->mobileLogin->findPending($mobileToken, $mobileState)) {
                return $this->mobileLogin->expiredView();
            }

            $this->mobileLogin->remember('google', $mobileToken, $mobileState);
        } else {
            // ล็อกอินเว็บปกติ — ล้างร่องรอยจากแอปที่อาจค้างอยู่ใน session
            $this->mobileLogin->forget('google');
        }

        // ปลายทางหลังล็อกอิน (เฉพาะหน้าในเว็บเรา)
        Session::forget('google_login_redirect');
        if ($target = LocalRedirect::sanitize($request->query('redirect'))) {
            Session::put('google_login_redirect', $target);
        }

        // รหัสผู้แนะนำ (สมัครใหม่ผ่านลิงก์เชิญ)
        Session::forget('google_login_referral');
        $ref = MobileAppLogin::text($request->query('ref'));
        if ($ref !== '' && strlen($ref) <= 64) {
            Session::put('google_login_referral', $ref);
        }

        // มาจากหน้าไหน (login/register) — ใช้พากลับเมื่อผิดพลาด
        $referer = (string) $request->headers->get('referer', '');
        Session::put('google_login_origin', str_contains($referer, '/register') ? 'register' : 'login');

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Google ส่งกลับมาที่นี่ (URI เดียวที่ลงทะเบียนไว้ใน Google Cloud)
     */
    public function callback(Request $request): RedirectResponse|View
    {
        // ข้อมูลการล็อกอินจากแอป อ่านจาก session เท่านั้น (ใช้ครั้งเดียว)
        $mobile = $this->mobileLogin->pull('google');
        $origin = Session::pull('google_login_origin', 'login');
        $errorRoute = $origin === 'register' ? 'register' : 'login';

        $fail = function (string $message) use ($mobile, $errorRoute): RedirectResponse {
            Session::forget(['google_login_redirect', 'google_login_referral']);

            if ($mobile) {
                return $this->mobileLogin->backToLoginPage($mobile, $message);
            }

            return redirect()->route($errorRoute)->with('error', $message);
        };

        $setting = $this->loadSetting();
        if (! $setting) {
            return $fail('ยังไม่เปิดให้เข้าสู่ระบบด้วย Google — กรุณาแจ้งผู้ดูแลระบบ');
        }

        // ผู้ใช้กดยกเลิก / Google ปฏิเสธ
        if ($request->has('error')) {
            $error = MobileAppLogin::text($request->query('error'));

            Log::info('Google OAuth: ผู้ใช้ยกเลิกหรือ Google ส่ง error กลับมา', [
                'error' => Str::limit($error, 64, ''),
                'from_app' => $mobile !== null,
            ]);

            return $fail($error === 'access_denied'
                ? 'คุณยกเลิกการเข้าสู่ระบบด้วย Google'
                : 'Google ไม่อนุญาตให้เข้าสู่ระบบ กรุณาลองใหม่อีกครั้ง');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // state ไม่ตรงกับ session (ลิงก์ค้าง / เปิดคนละเบราว์เซอร์ / ถูกปลอม)
            Log::warning('Google OAuth: state ไม่ตรงกับ session');

            return $fail('การยืนยันตัวตนหมดอายุหรือไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
        } catch (Throwable $e) {
            // ไม่ log ข้อความเต็ม — ไม่ให้ code/secret หลุดลง log
            Log::error('Google OAuth: แลก code กับ Google ไม่สำเร็จ', [
                'exception' => class_basename($e),
            ]);

            return $fail('เชื่อมต่อกับ Google ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        if (trim((string) $googleUser->getId()) === '') {
            Log::warning('Google OAuth: ไม่ได้รับรหัสบัญชีจาก Google');

            return $fail('ข้อมูลจาก Google ไม่ครบ กรุณาลองใหม่อีกครั้ง');
        }

        try {
            $user = $this->findOrCreateUser($googleUser);
        } catch (RuntimeException $e) {
            // ข้อความภาษาไทยที่ตั้งใจให้ผู้ใช้เห็น (เช่น อีเมลนี้ผูกกับ Google บัญชีอื่นแล้ว)
            return $fail($e->getMessage());
        } catch (Throwable $e) {
            Log::error('Google OAuth: หา/สร้างบัญชีไม่สำเร็จ', [
                'exception' => class_basename($e),
                'error' => Str::limit($e->getMessage(), 300),
            ]);

            return $fail('เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง');
        }

        // บัญชีถูกระงับ → ไม่ให้เข้า (ทั้งเว็บและแอป)
        if ($user->isSuspended()) {
            return $fail(EnsureAccountActive::SUSPENDED_MESSAGE);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        try {
            $setting->recordLogin();
        } catch (Throwable) {
            // สถิติพังไม่ขวางการล็อกอิน
        }

        Log::info('Google OAuth: เข้าสู่ระบบสำเร็จ', [
            'user_id' => $user->id,
            'is_new_user' => $user->wasRecentlyCreated,
            'from_app' => $mobile !== null,
        ]);

        // 📱 มาจากแอป → ออก auth code แล้วพากลับแอป
        if ($mobile) {
            Session::forget(['google_login_redirect', 'google_login_referral']);

            return $this->mobileLogin->authorize($user, $mobile['token'], $mobile['state'], 'google');
        }

        $redirectUrl = LocalRedirect::sanitize(Session::pull('google_login_redirect')) ?? route('user.home');
        Session::forget('google_login_referral');

        return redirect($redirectUrl)
            ->with('success', 'เข้าสู่ระบบด้วย Google สำเร็จ ยินดีต้อนรับ '.$user->name);
    }

    /**
     * หา user จากข้อมูล Google หรือสร้างใหม่
     *
     * ลำดับการจับคู่:
     *   1. google_id ตรง → บัญชีเดิม
     *   2. อีเมลตรง "และ Google ยืนยันว่าเป็นเจ้าของอีเมลแล้ว" → ผูก Google ให้บัญชีเดิม
     *      (อีเมลที่ยังไม่ยืนยัน ห้ามผูกเด็ดขาด — ไม่งั้นใครก็ตั้งอีเมลคนอื่นแล้วยึดบัญชีได้)
     *   3. ไม่เข้าเงื่อนไข → สร้างบัญชีใหม่
     *
     * @throws RuntimeException ข้อความภาษาไทยสำหรับแสดงผู้ใช้
     */
    protected function findOrCreateUser(SocialiteUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $avatar = $googleUser->getAvatar() ?: null;
        $email = strtolower(trim((string) $googleUser->getEmail()));
        $emailVerified = $this->emailIsVerified($googleUser);

        // 🔒 อีเมลในโดเมนสงวนของระบบ (บัญชีสังเคราะห์ของบอท) ห้ามใช้ผูก/ตั้งเป็นอีเมลบัญชี
        if ($email === '' || NotReservedEmailDomain::isReserved($email)) {
            $email = '';
        }

        // 1. เคยผูก Google แล้ว
        $user = User::where('google_id', $googleId)->first();
        if ($user) {
            if ($avatar && $user->google_avatar !== $avatar) {
                $user->update(['google_avatar' => $avatar]);
            }

            return $user;
        }

        // 2. อีเมลตรงกับบัญชีเดิม — ผูกให้เฉพาะเมื่อ Google ยืนยันอีเมลแล้ว
        if ($email !== '' && $emailVerified) {
            $user = User::where('email', $email)->first();

            if ($user) {
                if (! empty($user->google_id) && $user->google_id !== $googleId) {
                    Log::warning('Google OAuth: อีเมลนี้ผูกกับบัญชี Google อื่นอยู่แล้ว', ['user_id' => $user->id]);

                    throw new RuntimeException('อีเมลนี้ผูกกับบัญชี Google อื่นอยู่แล้ว กรุณาเข้าสู่ระบบด้วยวิธีเดิม');
                }

                $user->update([
                    'google_id' => $googleId,
                    'google_avatar' => $avatar,
                    'profile_picture' => $user->profile_picture ?: $avatar,
                ]);

                Log::info('Google OAuth: ผูก Google ให้บัญชีเดิม (อีเมลยืนยันแล้ว)', ['user_id' => $user->id]);

                return $user;
            }
        }

        // 3. สร้างบัญชีใหม่ — ใช้อีเมลจาก Google เฉพาะที่ยืนยันแล้ว ไม่งั้นใช้อีเมลสังเคราะห์
        return $this->createNewUser($googleUser, $emailVerified && $email !== '' ? $email : null);
    }

    /**
     * Google ยืนยันว่าผู้ใช้เป็นเจ้าของอีเมลนี้แล้วหรือไม่ (claim email_verified)
     */
    protected function emailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        $flag = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return $flag === true || $flag === 1 || $flag === 'true' || $flag === '1';
    }

    /**
     * สร้างบัญชีใหม่จาก Google + Wallet + MlmMember
     *
     * @param  string|null  $verifiedEmail  อีเมลที่ Google ยืนยันแล้ว (null = ใช้อีเมลสังเคราะห์)
     */
    protected function createNewUser(SocialiteUser $googleUser, ?string $verifiedEmail): User
    {
        $googleId = (string) $googleUser->getId();
        $avatar = $googleUser->getAvatar() ?: null;

        // ไม่มีอีเมลที่เชื่อได้ → อีเมลสังเคราะห์ (โดเมน .local = บัญชีสังเคราะห์ ลูกค้าเปลี่ยนเป็นอีเมลจริงเองได้ที่โปรไฟล์)
        $email = $verifiedEmail ?? 'googleoauth_'.$googleId.'@thaiprompt.local';

        return DB::transaction(function () use ($googleUser, $googleId, $avatar, $email, $verifiedEmail) {
            $user = User::create([
                'name' => Str::limit(trim((string) $googleUser->getName()) ?: 'Google User', 250, ''),
                'email' => $email,
                // รหัสผ่านสุ่ม — บัญชีนี้เข้าผ่าน Google (ตั้งรหัสผ่านเองได้ภายหลังผ่านลืมรหัสผ่าน)
                'password' => Hash::make(Str::random(40)),
                'profile_picture' => $avatar,
                'google_id' => $googleId,
                'google_avatar' => $avatar,
            ]);

            if ($verifiedEmail) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            // Wallet พร้อมรับคอมมิชชั่นทันที
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
            );

            // ต่อสายงาน MLM (ไม่ให้ล้มทั้งการสมัครถ้าส่วนนี้พัง)
            try {
                $sponsor = $this->resolveSponsor();

                if ($sponsor) {
                    app(FortuneAffiliateService::class)->enrollUserUnderSponsor($user, $sponsor);
                } else {
                    Log::warning('Google OAuth: ไม่พบผู้แนะนำ — ข้ามการต่อสายงาน', ['user_id' => $user->id]);
                }
            } catch (Throwable $e) {
                Log::warning('Google OAuth: ต่อสายงาน MLM ไม่สำเร็จ (สมัครสำเร็จตามปกติ)', [
                    'user_id' => $user->id,
                    'error' => Str::limit($e->getMessage(), 300),
                ]);
            }

            Log::info('Google OAuth: สร้างบัญชีใหม่', [
                'user_id' => $user->id,
                'has_verified_email' => $verifiedEmail !== null,
            ]);

            return $user;
        });
    }

    /**
     * ผู้แนะนำของบัญชีใหม่: รหัสจากลิงก์เชิญ (?ref=) → ผู้แนะนำเริ่มต้นในตั้งค่า → Super Admin
     */
    protected function resolveSponsor(): ?MlmMember
    {
        $ref = Session::get('google_login_referral');
        if (is_string($ref) && $ref !== '') {
            $member = MlmMember::where('member_code', $ref)->first();
            if ($member) {
                return $member;
            }
        }

        $defaultCode = Setting::get('default_sponsor_member_code');
        if (! empty($defaultCode)) {
            $member = MlmMember::where('member_code', $defaultCode)->first();
            if ($member) {
                return $member;
            }
        }

        return app(FortuneAffiliateService::class)->defaultSponsor();
    }

    /**
     * โหลดตั้งค่าจาก DB แล้วป้อนให้ Socialite ตอน runtime
     *
     * Socialite อ่าน config('services.google.*') ตอนสร้าง driver จึงต้อง Config::set ก่อนเรียก driver
     * (คีย์อื่นใน services.google เช่น api_key ของ Translate ไม่ถูกแตะ)
     *
     * คืน null ถ้ายังไม่ตั้งค่า / ปิดอยู่
     */
    protected function loadSetting(): ?GoogleOAuthSetting
    {
        $setting = GoogleOAuthSetting::getActive();

        if (! $setting || ! $setting->isReady()) {
            return null;
        }

        Config::set('services.google.client_id', $setting->client_id);
        Config::set('services.google.client_secret', $setting->client_secret);
        Config::set('services.google.redirect', GoogleOAuthSetting::redirectUri());

        return $setting;
    }
}
