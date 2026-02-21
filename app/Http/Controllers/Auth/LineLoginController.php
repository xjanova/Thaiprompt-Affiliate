<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LineLoginLog;
use App\Models\MobileAuthToken;
use App\Models\User;
use App\Services\LineService;
use App\Services\LineTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LineLoginController extends Controller
{
    protected LineService $lineService;

    protected LineTokenService $tokenService;

    public function __construct(LineService $lineService, LineTokenService $tokenService)
    {
        $this->lineService = $lineService;
        $this->tokenService = $tokenService;
    }

    /**
     * Redirect to LINE Login
     *
     * รองรับทั้งการ login ปกติและ mobile app login
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->lineService->isConfigured()) {
            // ถ้ามาจาก mobile auth flow ให้ redirect กลับไปหน้า error
            if ($request->has('mobile_token')) {
                return redirect()->route('mobile-login.show', [
                    'token' => $request->get('mobile_token'),
                    'state' => $request->get('state'),
                ])->with('error', 'LINE Login is not configured.');
            }

            return redirect()->route('login')
                ->with('error', 'LINE Login is not configured. Please contact administrator.');
        }

        // Generate and store state for CSRF protection
        $state = Str::random(40);
        Session::put('line_login_state', $state);

        // Store intended URL for redirect after login
        if ($request->has('redirect')) {
            Session::put('line_login_redirect', $request->get('redirect'));
        }

        // Store referral code if present
        if ($request->has('ref')) {
            Session::put('line_login_referral', $request->get('ref'));
        }

        // เก็บ origin page เพื่อ redirect กลับเมื่อเกิด error
        // ถ้ามาจากหน้า register จะกลับไปหน้า register แทนหน้า login
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/register')) {
            Session::put('line_login_origin', 'register');
        } else {
            Session::put('line_login_origin', 'login');
        }

        // เก็บ mobile auth token ถ้ามาจาก mobile app (สำหรับ LINE login)
        if ($request->has('mobile_token') && $request->has('state')) {
            Session::put('line_mobile_token', $request->get('mobile_token'));
            Session::put('line_mobile_state', $request->get('state'));

            Log::info('LINE Login from mobile app', [
                'mobile_token' => substr($request->get('mobile_token'), 0, 10).'...',
                'mobile_state' => $request->get('state'),
            ]);
        }

        $authUrl = $this->lineService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle LINE Login callback
     *
     * ✅ รองรับทั้ง 2 flows:
     * 1. Login flow (guest) — ผู้ใช้ใหม่/เดิมเข้าสู่ระบบด้วย LINE
     * 2. Link flow (authenticated) — ผู้ใช้ที่ login แล้วต้องการเชื่อมต่อ LINE
     *    (เมื่อ link() redirect ไป LINE OAuth แล้ว LINE redirect กลับมาที่นี่)
     */
    public function callback(Request $request): RedirectResponse
    {
        // ✅ ตรวจสอบว่าเป็น link mode หรือไม่ (มาจาก line-required page → link())
        // link() เก็บ state เป็น 'line_link_state' (ไม่ใช่ 'line_login_state')
        // ดังนั้นต้อง forward ไป linkCallback() เพื่อใช้ state key ที่ถูกต้อง
        if (Session::get('line_link_mode') && Auth::check()) {
            Log::info('LINE callback: detected link mode — forwarding to linkCallback()', [
                'user_id' => Auth::id(),
            ]);

            return $this->linkCallback($request);
        }

        // ดึง origin page เพื่อ redirect กลับเมื่อเกิด error
        $origin = Session::get('line_login_origin', 'login');
        $errorRoute = $origin === 'register' ? 'register' : 'login';
        $referralCode = Session::get('line_login_referral');

        // สร้าง redirect URL พร้อม referral code ถ้ามี
        $getErrorRedirect = function ($errorMessage) use ($errorRoute, $referralCode) {
            $redirect = redirect()->route($errorRoute);
            if ($errorRoute === 'register' && $referralCode) {
                $redirect = redirect()->route($errorRoute, ['ref' => $referralCode]);
            }

            return $redirect->with('error', $errorMessage);
        };

        // Verify state to prevent CSRF
        $state = Session::get('line_login_state');
        if (! $state || $state !== $request->get('state')) {
            Log::warning('LINE Login state mismatch', [
                'expected' => $state,
                'received' => $request->get('state'),
            ]);
            Session::forget('line_login_origin');

            return $getErrorRedirect('การยืนยันตัวตนไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
        }

        Session::forget('line_login_state');

        // Check for errors
        if ($request->has('error')) {
            $lineError = $request->get('error');
            $errorDesc = $request->get('error_description', 'Unknown error');

            Log::warning('LINE Login error', [
                'error' => $lineError,
                'error_description' => $errorDesc,
            ]);

            Session::forget('line_login_origin');

            // แปลง error message ให้เข้าใจง่าย
            $userMessage = match ($lineError) {
                'access_denied' => 'คุณยกเลิกการเข้าสู่ระบบด้วย LINE กรุณาลองใหม่อีกครั้ง',
                'invalid_request' => 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง',
                'unauthorized_client' => 'ระบบ LINE ยังไม่พร้อมใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                'server_error' => 'เกิดข้อผิดพลาดจาก LINE กรุณาลองใหม่ภายหลัง',
                default => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบด้วย LINE: '.$errorDesc,
            };

            return $getErrorRedirect($userMessage);
        }

        // Get authorization code
        $code = $request->get('code');
        if (! $code) {
            Session::forget('line_login_origin');

            return $getErrorRedirect('ไม่ได้รับรหัสยืนยันจาก LINE กรุณาลองใหม่อีกครั้ง');
        }

        try {
            // Exchange code for access token
            $tokenData = $this->lineService->getAccessToken($code);
            $accessToken = $tokenData['access_token'];

            // Get user profile
            $profile = $this->lineService->getUserProfile($accessToken);
            $lineUserId = $profile['userId'];
            $displayName = $profile['displayName'] ?? 'LINE User';
            $pictureUrl = $profile['pictureUrl'] ?? null;
            $email = $profile['email'] ?? null;

            // Find existing user by LINE user ID
            $user = User::where('line_user_id', $lineUserId)->first();

            if ($user) {
                // Update LINE info (without token)
                $user->update([
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                    'line_linked_at' => now(),
                    'line_verified' => true,
                ]);

                // Store encrypted access token securely
                $expiresIn = $tokenData['expires_in'] ?? null;
                $this->tokenService->storeAccessToken($user, $accessToken, $expiresIn);

                // Log action
                LineLoginLog::logAction($lineUserId, 'login', $user->id, [
                    'display_name' => $displayName,
                ]);

                // Login user
                Auth::login($user);

                // Regenerate session เพื่อป้องกัน session fixation + สร้าง CSRF token ใหม่
                $request->session()->regenerate();

                // ตรวจสอบว่ามาจาก mobile app หรือไม่
                $mobileToken = Session::get('line_mobile_token');
                $mobileState = Session::get('line_mobile_state');

                if ($mobileToken && $mobileState) {
                    // ล้าง session
                    Session::forget('line_mobile_token');
                    Session::forget('line_mobile_state');
                    Session::forget('line_login_redirect');

                    // หา MobileAuthToken และ authorize
                    return $this->authorizeMobileApp($user, $mobileToken, $mobileState);
                }

                // Redirect to intended page (user home - App-Like Interface)
                $redirect = Session::get('line_login_redirect', route('user.home'));
                Session::forget('line_login_redirect');

                return redirect($redirect)
                    ->with('success', 'เข้าสู่ระบบด้วย LINE สำเร็จ!');
            }

            // New user - store LINE profile in session and redirect to registration
            // Store LINE profile data in session for registration process
            Session::put('line_temp_profile', [
                'line_user_id' => $lineUserId,
                'line_display_name' => $displayName,
                'line_picture_url' => $pictureUrl,
                'line_access_token' => $accessToken,
            ]);

            // Log action
            LineLoginLog::logAction($lineUserId, 'register_required', null, [
                'display_name' => $displayName,
            ]);

            // Get referral code from session if exists
            $referralCode = Session::get('line_login_referral');
            $redirectUrl = route('register');
            if ($referralCode) {
                $redirectUrl .= '?ref='.$referralCode;
            }

            return redirect($redirectUrl)
                ->with('info', 'กรุณากรอกข้อมูลเพื่อสมัครสมาชิก');

        } catch (\Exception $e) {
            Log::error('LINE Login callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Session::forget('line_login_origin');

            return $getErrorRedirect('เกิดข้อผิดพลาดในการเข้าสู่ระบบด้วย LINE กรุณาลองใหม่อีกครั้ง');
        }
    }

    /**
     * Unlink LINE account
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->line_user_id) {
            return back()->with('error', 'บัญชี LINE ไม่ได้เชื่อมต่ออยู่');
        }

        $lineUserId = $user->line_user_id;

        // Log action
        LineLoginLog::logAction($lineUserId, 'unlink', $user->id);

        // Revoke access token securely
        $this->tokenService->revokeAccessToken($user);

        // Unlink LINE account
        $user->update([
            'line_user_id' => null,
            'line_display_name' => null,
            'line_picture_url' => null,
            'line_linked_at' => null,
            'line_verified' => false,
        ]);

        return back()->with('success', 'ยกเลิกการเชื่อมต่อบัญชี LINE เรียบร้อยแล้ว');
    }

    /**
     * Link existing account with LINE
     */
    public function link(Request $request): RedirectResponse
    {
        if (! $this->lineService->isConfigured()) {
            return back()->with('error', 'LINE Login is not configured.');
        }

        // Generate state
        $state = Str::random(40);
        Session::put('line_link_state', $state);
        Session::put('line_link_mode', true);

        $authUrl = $this->lineService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle LINE linking callback
     */
    public function linkCallback(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนเชื่อมต่อบัญชี LINE');
        }

        // Verify state
        $state = Session::get('line_link_state');
        if (! $state || $state !== $request->get('state')) {
            return redirect()->route('user.profile')
                ->with('error', 'Invalid state parameter.');
        }

        Session::forget('line_link_state');
        Session::forget('line_link_mode');

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('user.profile')
                ->with('error', 'Authorization code not received.');
        }

        try {
            Log::info('LINE linkCallback: เริ่มแลก code สำหรับ access token', [
                'user_id' => Auth::id(),
                'has_code' => ! empty($code),
            ]);

            // Exchange code for access token
            $tokenData = $this->lineService->getAccessToken($code);
            $accessToken = $tokenData['access_token'];

            Log::info('LINE linkCallback: แลก token สำเร็จ ดึง profile...', [
                'user_id' => Auth::id(),
            ]);

            // Get user profile
            $profile = $this->lineService->getUserProfile($accessToken);
            $lineUserId = $profile['userId'];
            $displayName = $profile['displayName'] ?? 'LINE User';
            $pictureUrl = $profile['pictureUrl'] ?? null;

            Log::info('LINE linkCallback: ได้ profile แล้ว', [
                'line_user_id' => $lineUserId,
                'display_name' => $displayName,
            ]);

            // Check if LINE account is already linked
            $existingUser = User::where('line_user_id', $lineUserId)
                ->where('id', '!=', Auth::id())
                ->first();

            if ($existingUser) {
                return redirect()->route('user.profile')
                    ->with('error', 'บัญชี LINE นี้ถูกเชื่อมต่อกับบัญชีอื่นอยู่แล้ว');
            }

            // Link LINE account
            $user = Auth::user();
            $user->update([
                'line_user_id' => $lineUserId,
                'line_display_name' => $displayName,
                'line_picture_url' => $pictureUrl,
                'line_linked_at' => now(),
                'line_verified' => true,
            ]);

            Log::info('LINE linkCallback: อัพเดท user สำเร็จ เก็บ token...', [
                'user_id' => $user->id,
                'line_user_id' => $lineUserId,
            ]);

            // Store encrypted access token securely
            $expiresIn = $tokenData['expires_in'] ?? null;
            $this->tokenService->storeAccessToken($user, $accessToken, $expiresIn);

            // Log action
            LineLoginLog::logAction($lineUserId, 'link', $user->id, [
                'display_name' => $displayName,
            ]);

            // ✅ Redirect กลับไปหน้าที่ผู้ใช้ต้องการ (เช่น wallet)
            // RequireLineUid middleware เก็บ URL ไว้ใน 'line_redirect_after'
            $redirectAfter = session()->pull('line_redirect_after');

            Log::info('LINE linkCallback: เชื่อมต่อสำเร็จ! redirect ไป ' . ($redirectAfter ?? 'profile'), [
                'user_id' => $user->id,
            ]);

            return redirect($redirectAfter ?? route('user.profile'))
                ->with('success', 'เชื่อมต่อบัญชี LINE สำเร็จ!');

        } catch (\Exception $e) {
            Log::error('LINE linking error — DETAILED', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line_number' => $e->getLine(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('user.profile')
                ->with('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อบัญชี LINE: ' . $e->getMessage());
        }
    }

    /**
     * Auth code expiry (seconds)
     */
    private const AUTH_CODE_EXPIRY = 60;

    /**
     * Handle LINE Login callback สำหรับ Mobile App
     *
     * รับ GET request จาก LINE OAuth แล้ว redirect ไป app deep link
     * เพราะ LINE OAuth รองรับแค่ HTTPS redirect URI ไม่รองรับ custom scheme
     */
    public function mobileCallback(Request $request): RedirectResponse|View
    {
        // รับ code และ state จาก LINE OAuth
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');

        // ถ้า LINE ส่ง error กลับมา
        if ($error) {
            Log::warning('LINE Mobile Login error from LINE', [
                'error' => $error,
                'error_description' => $errorDescription,
            ]);

            // Redirect ไป app deep link พร้อม error
            $deepLink = 'thaiprompt://login?'.http_build_query([
                'error' => $error,
                'error_description' => $errorDescription ?? 'LINE Login failed',
            ]);

            return $this->renderMobileRedirectPage($deepLink, 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ LINE');
        }

        // ตรวจสอบว่ามี code หรือไม่
        if (! $code) {
            Log::warning('LINE Mobile Login - missing code');

            $deepLink = 'thaiprompt://login?'.http_build_query([
                'error' => 'missing_code',
                'error_description' => 'Authorization code not received',
            ]);

            return $this->renderMobileRedirectPage($deepLink, 'ไม่ได้รับ authorization code');
        }

        Log::info('LINE Mobile Login callback received', [
            'has_code' => ! empty($code),
            'state' => $state,
        ]);

        // สร้าง deep link URL พร้อม code และ state
        $deepLink = 'thaiprompt://login?'.http_build_query([
            'code' => $code,
            'state' => $state ?? '',
        ]);

        return $this->renderMobileRedirectPage($deepLink, 'กำลังกลับสู่แอป...');
    }

    /**
     * แสดงหน้า redirect ไป mobile app
     *
     * @param  string  $deepLink  URL ของ deep link
     * @param  string  $message  ข้อความที่แสดง
     */
    protected function renderMobileRedirectPage(string $deepLink, string $message): View
    {
        return view('auth.line-mobile-redirect', [
            'deepLink' => $deepLink,
            'message' => $message,
        ]);
    }

    /**
     * Authorize mobile app หลังจาก LINE login สำเร็จ
     *
     * สร้าง auth_code และ redirect กลับไปแอพผ่าน deep link
     *
     * @param  User  $user  ผู้ใช้ที่ login สำเร็จ
     * @param  string  $mobileToken  login_token จาก mobile app (raw, unhashed)
     * @param  string  $mobileState  state จาก mobile app
     */
    protected function authorizeMobileApp(User $user, string $mobileToken, string $mobileState): RedirectResponse|View
    {
        // Hash token ก่อน query (ฐานข้อมูลเก็บแบบ hash)
        $loginTokenHash = hash('sha256', $mobileToken);

        // หา MobileAuthToken
        $authToken = MobileAuthToken::where('login_token', $loginTokenHash)
            ->where('state', $mobileState)
            ->whereNull('used_at')
            ->first();

        if (! $authToken) {
            Log::warning('Mobile auth token not found for LINE login', [
                'token_hash' => substr($loginTokenHash, 0, 10).'...',
                'state' => $mobileState,
            ]);

            return view('auth.mobile-login-error', [
                'error' => 'expired',
                'message' => 'Session หมดอายุแล้ว กรุณาเริ่มต้นใหม่จากแอพ',
            ]);
        }

        // ตรวจสอบ expiry
        if ($authToken->isLoginTokenExpired()) {
            Log::warning('Mobile auth token expired for LINE login', [
                'token_id' => $authToken->id,
            ]);

            return view('auth.mobile-login-error', [
                'error' => 'expired',
                'message' => 'Session หมดอายุแล้ว กรุณาเริ่มต้นใหม่จากแอพ',
            ]);
        }

        // สร้าง auth code
        $authCode = Str::random(64);

        // อัพเดท token
        $authToken->update([
            'user_id' => $user->id,
            'auth_code' => hash('sha256', $authCode),
            'auth_code_expires_at' => now()->addSeconds(self::AUTH_CODE_EXPIRY),
        ]);

        Log::info('Mobile app authorized via LINE login', [
            'user_id' => $user->id,
            'device_name' => $authToken->device_name,
        ]);

        // สร้าง deep link URL
        $redirectUrl = 'thaiprompt://auth?'.http_build_query([
            'code' => $authCode,
            'state' => $mobileState,
        ]);

        // แสดงหน้า redirect ที่จะ auto-redirect ไปแอพ
        return view('auth.mobile-login-redirect', [
            'redirectUrl' => $redirectUrl,
        ]);
    }
}
