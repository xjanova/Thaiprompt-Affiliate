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
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function show(Request $request): View|RedirectResponse
    {
        $loginToken = $request->get('token');
        $state = $request->get('state');

        if (!$loginToken || !$state) {
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

        if (!$mobileAuthToken) {
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
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'token' => 'required|string',
            'state' => 'required|string',
        ]);

        // ตรวจสอบ credentials
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ])) {
            return back()
                ->withInput(['email' => $request->email, 'token' => $request->token, 'state' => $request->state])
                ->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }

        // Authorize app
        return $this->authorize($request);
    }

    /**
     * อนุมัติให้แอพเข้าถึง (หลัง login หรือถ้า login อยู่แล้ว)
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function authorize(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'state' => 'required|string',
        ]);

        $loginToken = $request->token;
        $state = $request->state;

        // ค้นหา token
        $loginTokenHash = hash('sha256', $loginToken);
        $mobileAuthToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $state)
            ->whereNull('used_at')
            ->first();

        if (!$mobileAuthToken || $mobileAuthToken->isLoginTokenExpired()) {
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

        Log::info('Mobile login authorized', [
            'user_id' => Auth::id(),
            'device_id' => $mobileAuthToken->device_id,
            'ip' => $request->ip(),
        ]);

        // Redirect กลับแอพ
        $redirectUrl = 'thaiprompt://auth?' . http_build_query([
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
     *
     * @param Request $request
     * @return RedirectResponse
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
        $redirectUrl = 'thaiprompt://auth?' . http_build_query([
            'error' => 'access_denied',
            'state' => $state,
        ]);

        return redirect($redirectUrl);
    }
}
