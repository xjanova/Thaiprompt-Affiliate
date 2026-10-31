<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show 2FA setup page
     */
    public function setup()
    {
        $user = Auth::user();
        $status = $this->twoFactorService->getStatus($user);

        return view('user.two-factor.setup', compact('status'));
    }

    /**
     * เปิดใช้งาน 2FA
     *
     * รองรับ Google Authenticator เป็นวิธีหลัก
     */
    public function enable(Request $request)
    {
        // 🔒 เปิดอยู่แล้วแต่ส่งมาเปลี่ยนวิธี = เปลี่ยนการตั้งค่า 2FA → ต้องยืนยันรหัสปัจจุบันก่อน
        if ($guard = $this->requireFreshVerification($request)) {
            return $guard;
        }

        $request->validate([
            // 🐛 (2026-10-29) ตัด email ออก: OtpService ยังไม่มีช่องทางอีเมล (ส่งเข้า SMS ด้วยเบอร์ว่าง → TypeError 500)
            'preferred_method' => ['required', 'in:authenticator,sms,line'],
            'code' => ['required_if:preferred_method,authenticator', 'nullable', 'string', 'size:6'],
        ]);

        $user = Auth::user();
        $preferredMethod = $request->input('preferred_method');

        // สำหรับ Google Authenticator ต้องยืนยันรหัสก่อนเปิดใช้งาน
        if ($preferredMethod === 'authenticator') {
            // ตรวจสอบรหัสจาก authenticator
            $verifyResult = $this->twoFactorService->verifySetupCode($user, $request->input('code'));

            if (! $verifyResult['success']) {
                return redirect()->back()->with('error', $verifyResult['message']);
            }
        }

        $result = $this->twoFactorService->enable($user, $preferredMethod);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // แสดง recovery codes ครั้งเดียวผ่าน session flash
        // 🔒 (2026-10-29) เดิมส่งรหัสใน ?codes= → ค้างในประวัติเบราว์เซอร์ log เซิร์ฟเวอร์ และตาราง page_views (TrackPageView เก็บ fullUrl)
        return redirect()->route('user.two-factor.recovery-codes')
            ->with('recovery_codes', $result['recovery_codes'])
            ->with('success', $result['message']);
    }

    /**
     * สร้าง QR Code สำหรับ Google Authenticator
     */
    public function generateAuthenticatorSetup()
    {
        $user = Auth::user();

        // 🔒 (2026-10-29) เดิมสร้าง secret ใหม่ทับของที่ใช้งานอยู่ได้ทันที (GET) → ใครได้ session ไปก็ผูกแอปตัวเองแทน
        //    หรือแค่ลิงก์จากเว็บอื่นก็ทำให้แอปของเจ้าของบัญชีใช้ไม่ได้ → ต้องปิด 2FA ก่อนตั้งใหม่
        $settings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();
        if ($settings && $settings->enabled) {
            return response()->json(['success' => false, 'message' => 'เปิดใช้ 2FA อยู่แล้ว ถ้าต้องการเปลี่ยนแอป ให้ปิด 2FA ก่อนแล้วตั้งค่าใหม่'], 409);
        }

        $result = $this->twoFactorService->generateAuthenticatorSetup($user);

        return response()->json($result);
    }

    /**
     * ตรวจสอบรหัส Google Authenticator ก่อนเปิดใช้งาน
     */
    public function verifyAuthenticatorSetup(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->verifySetupCode($user, $request->input('code'));

        return response()->json($result);
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        // 🔒 (2026-10-29) เดิมปิดได้ด้วยการกดปุ่มอย่างเดียว → ใครได้ session ไปก็ปิด 2FA แล้วถอน/โอนได้หมด
        if ($guard = $this->requireFreshVerification($request)) {
            return $guard;
        }

        $user = Auth::user();
        $result = $this->twoFactorService->disable($user);

        if (! $result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Show 2FA verification page
     */
    public function verify(Request $request)
    {
        $action = $request->input('action', 'general');
        $redirect = $this->safeRedirect($request->input('redirect'));

        // จำนวนวันที่ "จำอุปกรณ์นี้" ใช้ได้ — null = ผู้ดูแลปิดตัวเลือกนี้ (ไม่แสดงช่องติ๊กที่กดแล้วไม่มีผล)
        $rememberDays = \App\Models\TwoFactorSetting::canRememberDevice()
            ? (int) (\App\Models\TwoFactorSetting::getActive()->remember_device_days ?? 30)
            : null;

        return view('user.two-factor.verify', compact('action', 'redirect', 'rememberDays'));
    }

    /**
     * Send 2FA code
     */
    public function sendCode(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action', 'verification');

        $result = $this->twoFactorService->sendCode($user, $action);

        return response()->json($result);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'action' => ['required', 'string'],
            'remember_device' => ['boolean'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->verifyCode(
            $user,
            $request->input('code'),
            $request->input('action'),
            $request->boolean('remember_device')
        );

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'redirect' => $this->safeRedirect($request->input('redirect')),
        ]);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => ['required', 'string'],
            'action' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $result = $this->twoFactorService->verifyRecoveryCode(
            $user,
            $request->input('recovery_code'),
            $request->input('action')
        );

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'warning' => $result['warning'] ?? null,
            'redirect' => $this->safeRedirect($request->input('redirect')),
        ]);
    }

    /**
     * Show recovery codes
     */
    public function showRecoveryCodes(Request $request)
    {
        // รหัสมาจาก session flash เท่านั้น (แสดงครั้งเดียว) — ไม่รับจาก query string แล้ว
        $codes = array_values(array_filter((array) session('recovery_codes', []), 'is_string'));
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', Auth::id())->first();
        $remaining = $userSettings && $userSettings->recovery_codes ? count($userSettings->recovery_codes) : 0;

        return view('user.two-factor.recovery-codes', compact('codes', 'remaining'));
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        if ($guard = $this->requireFreshVerification($request)) {
            return $guard;
        }

        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings || ! $userSettings->enabled) {
            return redirect()->back()->with('error', '2FA ยังไม่ได้เปิดใช้งาน');
        }

        $codes = $userSettings->generateRecoveryCodes();

        // 🐛 (2026-10-29) เดิมเรียก route('two-factor.recovery-codes') ที่ไม่มีจริง → 500 หลังรหัสเก่าถูกล้างไปแล้ว
        return redirect()->route('user.two-factor.recovery-codes')
            ->with('recovery_codes', $codes)
            ->with('success', 'สร้างรหัสกู้คืนใหม่เรียบร้อยแล้ว');
    }

    /**
     * Remove trusted device
     */
    public function removeTrustedDevice(Request $request, string $fingerprint)
    {
        // 🐛 (2026-10-29) route คือ DELETE /trusted-devices/{fingerprint} แต่เดิมไปอ่านจาก body (device_fingerprint) → ลบไม่ได้
        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings) {
            return response()->json(['success' => false, 'message' => 'ไม่พบการตั้งค่า 2FA'], 404);
        }

        $userSettings->removeTrustedDevice($fingerprint);

        return response()->json(['success' => true, 'message' => 'อุปกรณ์ถูกลบออกเรียบร้อยแล้ว']);
    }

    /**
     * Remove all trusted devices
     */
    public function removeAllTrustedDevices(Request $request)
    {
        $user = Auth::user();
        $userSettings = \App\Models\TwoFactorUserSetting::where('user_id', $user->id)->first();

        if (! $userSettings) {
            return redirect()->back()->with('error', 'ไม่พบการตั้งค่า');
        }

        $userSettings->removeAllTrustedDevices();

        return redirect()->back()->with('success', 'ลบอุปกรณ์ที่เชื่อถือทั้งหมดเรียบร้อยแล้ว');
    }

    /**
     * การเปลี่ยนการตั้งค่า 2FA (ปิด / เปลี่ยนวิธี / สร้างรหัสกู้คืนใหม่) ต้องยืนยันรหัสปัจจุบันใน session นี้ก่อน
     *
     * คืน null = ผ่าน, คืน response = ส่งไปหน้ายืนยัน (action manage_2fa แล้วพากลับหน้าตั้งค่า)
     */
    private function requireFreshVerification(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse|null
    {
        $settings = \App\Models\TwoFactorUserSetting::where('user_id', Auth::id())->first();

        if (! $settings || ! $settings->enabled || $this->twoFactorService->isVerified('manage_2fa')) {
            return null;
        }

        $verifyUrl = route('user.two-factor.verify', ['action' => 'manage_2fa', 'redirect' => route('user.two-factor.setup')]);
        $message = 'ยืนยันรหัส 2FA ปัจจุบันก่อนเปลี่ยนการตั้งค่า 2FA';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'code' => 'TWO_FACTOR_REQUIRED', 'message' => $message, 'verify_url' => $verifyUrl], 403);
        }

        return redirect()->to($verifyUrl)->with('info', $message);
    }

    /**
     * ปลายทางหลังยืนยัน 2FA — รับเฉพาะลิงก์ภายในเว็บนี้
     *
     * 🔒 (2026-10-29) เดิมส่ง ?redirect= ดิบกลับไปให้ JS ทำ window.location.href
     *    → ลิงก์ ?redirect=javascript:... รันโค้ดหลังเหยื่อกรอกรหัส 2FA (XSS) หรือพาไปเว็บปลอม (open redirect)
     */
    private function safeRedirect(?string $url): string
    {
        $url = trim((string) $url);

        // path ภายใน เช่น /user/wallet (กัน //evil.com และ /\evil.com ที่เบราว์เซอร์ตีความเป็นโดเมนอื่น)
        if (preg_match('#^/(?![/\\\\])#', $url)) {
            return $url;
        }

        // URL เต็มที่ host ตรงกับเว็บนี้เท่านั้น
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true) && parse_url($url, PHP_URL_HOST) === request()->getHost()) {
            return $url;
        }

        return route('user.home');
    }
}
