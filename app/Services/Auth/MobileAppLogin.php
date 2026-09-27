<?php

namespace App\Services\Auth;

use App\Models\FacebookOAuthSetting;
use App\Models\GoogleOAuthSetting;
use App\Models\MobileAuthToken;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 📱 ตัวกลาง "ล็อกอินแอปผ่านเว็บ" (PKCE) ด้วยบัญชีภายนอก — LINE / Facebook / Google
 *
 * เส้นทาง (ใช้ร่วมกันทุกผู้ให้บริการ):
 *   1. แอปเรียก POST /api/v1/auth/mobile/init → ได้ login_token + state (ผูกกับ code_challenge ของแอป)
 *   2. แอปเปิด /mobile-login?token=..&state=..(&provider=..) → เว็บส่งต่อไป /auth/{provider}?mobile_token=..&state=..
 *   3. /auth/{provider} จำ mobile_token/state ไว้ใน session (remember) แล้วพาไปหน้าอนุญาตของผู้ให้บริการ
 *   4. callback ของเว็บ (URI เดิมที่ลงทะเบียนไว้) ล็อกอินสำเร็จ → pull() ออกจาก session → authorize()
 *   5. authorize() ออก auth_code (ใช้ครั้งเดียว อายุ 60 วิ) ให้ token นั้น แล้วพากลับแอป thaiprompt://auth?code=..&state=..
 *   6. แอปแลก auth_code + code_verifier ที่ /api/v1/auth/mobile/exchange (คนที่ไม่มี verifier แลกไม่ได้)
 *
 * 🔐 mobile_token/state อ่านจาก session เท่านั้นตอน callback — ไม่เคยเชื่อค่าจาก query ของ callback
 */
class MobileAppLogin
{
    /**
     * ผู้ให้บริการที่แอปกระโดดไปได้ (whitelist — ค่าอื่นไม่รับ)
     */
    public const PROVIDERS = ['line', 'facebook', 'google'];

    /**
     * อายุ auth_code (วินาที) — แอปต้องแลกให้ทันภายในเวลานี้
     */
    public const AUTH_CODE_TTL_SECONDS = 60;

    /**
     * ชื่อที่แสดงของผู้ให้บริการ (ใช้ใน log / ข้อความ)
     */
    private const LABELS = [
        'line' => 'LINE',
        'facebook' => 'Facebook',
        'google' => 'Google',
    ];

    /**
     * ค่าจาก query ที่ต้องเป็นข้อความเท่านั้น (?x[]=... ส่งมาเป็น array → ถือว่าไม่มี ไม่ให้ระเบิดเป็น 500)
     */
    public static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * ผู้ให้บริการนี้อยู่ใน whitelist หรือไม่
     */
    public static function isSupportedProvider(mixed $provider): bool
    {
        return is_string($provider) && in_array($provider, self::PROVIDERS, true);
    }

    /**
     * ผู้ให้บริการนี้เปิดใช้และตั้งค่าครบหรือยัง (ค่าเดียวกับที่หน้าเว็บใช้ตัดสินแสดงปุ่ม)
     */
    public function isProviderReady(string $provider): bool
    {
        try {
            return match ($provider) {
                'line' => app(LineService::class)->isConfigured(),
                'facebook' => FacebookOAuthSetting::isConfigured(),
                'google' => GoogleOAuthSetting::isConfigured(),
                default => false,
            };
        } catch (\Throwable) {
            // DB/ตั้งค่าพัง = ถือว่ายังไม่พร้อม (ซ่อนปุ่ม ไม่ใช่ 500)
            return false;
        }
    }

    /**
     * สถานะทุกผู้ให้บริการ — ใช้ตอบแอปว่าควรแสดงปุ่มไหน
     *
     * @return array<string, bool> เช่น ['line' => true, 'facebook' => true, 'google' => false]
     */
    public function providerStatus(): array
    {
        $status = [];

        foreach (self::PROVIDERS as $provider) {
            $status[$provider] = $this->isProviderReady($provider);
        }

        return $status;
    }

    /**
     * หา MobileAuthToken ที่ยังรอผู้ใช้ล็อกอินอยู่ (ยังไม่ถูกใช้ + ยังไม่หมดอายุ)
     *
     * @param  string  $loginToken  login_token ดิบจากแอป (DB เก็บเป็น sha256)
     * @param  string  $state  state ที่ออกคู่กับ token
     */
    public function findPending(string $loginToken, string $state): ?MobileAuthToken
    {
        if ($loginToken === '' || $state === '') {
            return null;
        }

        $token = MobileAuthToken::where('login_token', hash('sha256', $loginToken))
            ->where('state', $state)
            ->whereNull('used_at')
            ->first();

        if (! $token || $token->isLoginTokenExpired()) {
            return null;
        }

        return $token;
    }

    /**
     * จำไว้ใน session ว่าการล็อกอินรอบนี้เริ่มจากแอป
     *
     * ใช้คีย์ {provider}_mobile_token / {provider}_mobile_state (LINE ใช้คีย์ชุดนี้มาแต่เดิม)
     */
    public function remember(string $provider, string $loginToken, string $state): void
    {
        Session::put($provider.'_mobile_token', $loginToken);
        Session::put($provider.'_mobile_state', $state);
    }

    /**
     * ล้างร่องรอยการล็อกอินจากแอปของผู้ให้บริการนี้ (เริ่มล็อกอินเว็บปกติรอบใหม่)
     *
     * กันเคส: เคยเปิดจากแอปแล้วทิ้งไว้ → วันหลังล็อกอินเว็บในเบราว์เซอร์เดียวกัน
     * จะถูกพาไปหน้า "กลับสู่แอป" แทนที่จะเข้าเว็บ
     */
    public function forget(string $provider): void
    {
        Session::forget([$provider.'_mobile_token', $provider.'_mobile_state']);
    }

    /**
     * ดึงข้อมูลการล็อกอินจากแอปออกจาก session (ใช้ครั้งเดียว)
     *
     * @return array{token: string, state: string}|null
     */
    public function pull(string $provider): ?array
    {
        $token = Session::pull($provider.'_mobile_token');
        $state = Session::pull($provider.'_mobile_state');

        if (! is_string($token) || $token === '' || ! is_string($state) || $state === '') {
            return null;
        }

        return ['token' => $token, 'state' => $state];
    }

    /**
     * พากลับหน้าเข้าสู่ระบบของแอป (บนเว็บ) พร้อมข้อความภาษาไทย
     *
     * ใช้ตอนผู้ใช้กดยกเลิกที่ Google/Facebook หรือเกิดข้อผิดพลาดระหว่างทาง
     * — ผู้ใช้ยังเลือกวิธีอื่นต่อได้ (token ยังไม่หมดอายุ) หรือปิดหน้าต่างกลับแอป
     *
     * @param  array{token: string, state: string}  $pending
     */
    public function backToLoginPage(array $pending, string $message): RedirectResponse
    {
        return redirect()->route('mobile-login.show', [
            'token' => $pending['token'],
            'state' => $pending['state'],
        ])->with('error', $message);
    }

    /**
     * หน้าแจ้งว่าลิงก์จากแอปใช้ไม่ได้แล้ว (token ผิด/หมดอายุ/ถูกใช้ไปแล้ว)
     */
    public function expiredView(): View
    {
        return view('auth.mobile-login-error', [
            'error' => 'expired',
            'message' => 'Session หมดอายุแล้ว กรุณาเริ่มต้นใหม่จากแอพ',
        ]);
    }

    /**
     * อนุมัติแอปหลังผู้ใช้ล็อกอินสำเร็จ — ออก auth_code แล้วแสดงหน้าพากลับแอป
     *
     * @param  User  $user  ผู้ใช้ที่ล็อกอินสำเร็จ
     * @param  string  $loginToken  login_token ดิบจากแอป (ไม่ใช่ hash)
     * @param  string  $state  state จากแอป
     * @param  string  $provider  ผู้ให้บริการ (ใช้ใน log เท่านั้น)
     */
    public function authorize(User $user, string $loginToken, string $state, string $provider = 'line'): View
    {
        $label = self::LABELS[$provider] ?? $provider;
        $loginTokenHash = hash('sha256', $loginToken);

        // หา token (DB เก็บแบบ hash)
        $authToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $state)
            ->whereNull('used_at')
            ->first();

        if (! $authToken) {
            Log::warning("Mobile auth token not found for {$label} login", [
                'token_hash' => substr($loginTokenHash, 0, 10).'...',
                'state' => $state,
            ]);

            return $this->expiredView();
        }

        if ($authToken->isLoginTokenExpired()) {
            Log::warning("Mobile auth token expired for {$label} login", [
                'token_id' => $authToken->id,
            ]);

            return $this->expiredView();
        }

        // auth_code ดิบส่งให้แอปทาง deep link เท่านั้น — DB เก็บแค่ hash
        $authCode = Str::random(64);

        $authToken->update([
            'user_id' => $user->id,
            'auth_code' => hash('sha256', $authCode),
            'auth_code_expires_at' => now()->addSeconds(self::AUTH_CODE_TTL_SECONDS),
        ]);

        Log::info("Mobile app authorized via {$label} login", [
            'user_id' => $user->id,
            'device_name' => $authToken->device_name,
        ]);

        $redirectUrl = 'thaiprompt://auth?'.http_build_query([
            'code' => $authCode,
            'state' => $state,
        ]);

        // หน้านี้เด้งเข้าแอปเองอัตโนมัติ (มีปุ่มกดเองสำรอง)
        return view('auth.mobile-login-redirect', [
            'redirectUrl' => $redirectUrl,
        ]);
    }
}
