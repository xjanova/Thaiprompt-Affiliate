<?php

namespace App\Http\Controllers;

use App\Models\MobileAuthToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mobile Login Controller
 *
 * จัดการหน้าเว็บสำหรับ Mobile App Login
 * Flow: แอพเปิด browser → user login → redirect กลับแอพ
 */
class MobileLoginController extends Controller
{
    /**
     * Auth code expiry (seconds)
     */
    private const AUTH_CODE_EXPIRY = 60;

    /**
     * แสดงหน้า Mobile Login
     */
    public function show(Request $request): View|RedirectResponse
    {
        $loginToken = $request->get('token');
        $state = $request->get('state');

        if (! $loginToken || ! $state) {
            return view('auth.mobile-login-error', [
                'error' => 'invalid_request',
                'message' => 'ลิงก์ไม่ถูกต้อง กรุณาเปิดจากแอพใหม่อีกครั้ง',
            ]);
        }

        // ค้นหา token
        $loginTokenHash = hash('sha256', $loginToken);
        $mobileAuthToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $state)
            ->whereNull('used_at')
            ->first();

        if (! $mobileAuthToken) {
            return view('auth.mobile-login-error', [
                'error' => 'invalid_token',
                'message' => 'Token ไม่ถูกต้องหรือหมดอายุ กรุณาลองใหม่',
            ]);
        }

        // ตรวจสอบ expiry
        if ($mobileAuthToken->isLoginTokenExpired()) {
            $mobileAuthToken->delete();

            return view('auth.mobile-login-error', [
                'error' => 'expired',
                'message' => 'ลิงก์หมดอายุแล้ว กรุณาเปิดจากแอพใหม่อีกครั้ง',
            ]);
        }

        // ถ้า user login อยู่แล้ว → ไปหน้า confirm
        if (Auth::check()) {
            return view('auth.mobile-login-confirm', [
                'user' => Auth::user(),
                'token' => $loginToken,
                'state' => $state,
                'deviceName' => $mobileAuthToken->device_name,
            ]);
        }

        // แสดงหน้า login
        return view('auth.mobile-login', [
            'token' => $loginToken,
            'state' => $state,
            'deviceName' => $mobileAuthToken->device_name,
        ]);
    }

    /**
     * ดำเนินการ Login
     */
    public function login(Request $request): View|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'token' => 'required|string',
            'state' => 'required|string',
        ]);

        // ตรวจสอบ credentials
        if (! Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            Log::warning('Mobile login failed - invalid credentials', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            // Redirect กลับไปหน้า login พร้อม token และ state (preserve query params)
            return redirect()->route('mobile-login.show', [
                'token' => $request->token,
                'state' => $request->state,
            ])
                ->withInput(['email' => $request->email])
                ->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }

        Log::info('Mobile login successful - proceeding to authorize', [
            'user_id' => Auth::id(),
            'email' => $request->email,
        ]);

        // Regenerate session to prevent session fixation
        $request->session()->regenerate();

        // Authorize app
        return $this->authorizeApp($request);
    }

    /**
     * อนุมัติให้แอพเข้าถึง (หลัง login หรือถ้า login อยู่แล้ว)
     *
     * 🩹 (2026-05-08) เปลี่ยนชื่อ authorize() → authorizeApp()
     *   เหตุผล: PHP Fatal Error — signature ขัดกับ Controller::authorize($ability, $arguments)
     *   ที่ inherit มาจาก AuthorizesRequests trait → autoload fail → 500 ทุก route
     */
    public function authorizeApp(Request $request): View|RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'state' => 'required|string',
        ]);

        $loginToken = $request->token;
        $state = $request->state;

        Log::info('Mobile authorize - starting', [
            'token_prefix' => substr($loginToken, 0, 10).'...',
            'state' => $state,
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check(),
        ]);

        // ค้นหา token
        $loginTokenHash = hash('sha256', $loginToken);
        $mobileAuthToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $state)
            ->whereNull('used_at')
            ->first();

        if (! $mobileAuthToken) {
            Log::warning('Mobile authorize - token not found', [
                'token_hash_prefix' => substr($loginTokenHash, 0, 10).'...',
                'state' => $state,
            ]);

            return view('auth.mobile-login-error', [
                'error' => 'invalid_token',
                'message' => 'Token ไม่ถูกต้อง กรุณาเริ่มต้นใหม่จากแอพ',
            ]);
        }

        if ($mobileAuthToken->isLoginTokenExpired()) {
            Log::warning('Mobile authorize - token expired', [
                'token_id' => $mobileAuthToken->id,
                'expires_at' => $mobileAuthToken->login_token_expires_at,
            ]);

            return view('auth.mobile-login-error', [
                'error' => 'expired',
                'message' => 'Session หมดอายุ กรุณาลองใหม่',
            ]);
        }

        // สร้าง auth_code
        $authCode = Str::random(64);

        // อัพเดท token
        $mobileAuthToken->update([
            'auth_code' => hash('sha256', $authCode),
            'auth_code_expires_at' => now()->addSeconds(self::AUTH_CODE_EXPIRY),
            'user_id' => Auth::id(),
        ]);

        Log::info('Mobile login authorized successfully', [
            'user_id' => Auth::id(),
            'device_id' => $mobileAuthToken->device_id,
            'device_name' => $mobileAuthToken->device_name,
            'ip' => $request->ip(),
        ]);

        // Redirect กลับแอพ
        $redirectUrl = 'thaiprompt://auth?'.http_build_query([
            'code' => $authCode,
            'state' => $state,
        ]);

        return view('auth.mobile-login-redirect', [
            'redirectUrl' => $redirectUrl,
            'authCode' => $authCode,
            'state' => $state,
        ]);
    }

    /**
     * ปฏิเสธการ authorize
     */
    public function deny(Request $request): RedirectResponse
    {
        $loginToken = $request->token;
        $state = $request->state;

        if ($loginToken && $state) {
            $loginTokenHash = hash('sha256', $loginToken);
            MobileAuthToken::where('login_token', $loginTokenHash)
                ->where('state', $state)
                ->delete();
        }

        // Redirect กลับแอพพร้อม error
        $redirectUrl = 'thaiprompt://auth?'.http_build_query([
            'error' => 'access_denied',
            'state' => $state,
        ]);

        return redirect($redirectUrl);
    }
}
