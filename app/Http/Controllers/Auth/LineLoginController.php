<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LineLoginLog;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    protected LineService $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    /**
     * Redirect to LINE Login
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (!$this->lineService->isConfigured()) {
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

        $authUrl = $this->lineService->getAuthorizationUrl($state);

        return redirect($authUrl);
    }

    /**
     * Handle LINE Login callback
     */
    public function callback(Request $request): RedirectResponse
    {
        // Verify state to prevent CSRF
        $state = Session::get('line_login_state');
        if (!$state || $state !== $request->get('state')) {
            Log::warning('LINE Login state mismatch', [
                'expected' => $state,
                'received' => $request->get('state'),
            ]);
            return redirect()->route('login')
                ->with('error', 'Invalid state parameter. Please try again.');
        }

        Session::forget('line_login_state');

        // Check for errors
        if ($request->has('error')) {
            Log::warning('LINE Login error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);
            return redirect()->route('login')
                ->with('error', 'LINE Login failed: ' . $request->get('error_description', 'Unknown error'));
        }

        // Get authorization code
        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('login')
                ->with('error', 'Authorization code not received.');
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
                // Update LINE info
                $user->update([
                    'line_display_name' => $displayName,
                    'line_picture_url' => $pictureUrl,
                    'line_access_token' => $accessToken,
                    'line_linked_at' => now(),
                    'line_verified' => true,
                ]);

                // Log action
                LineLoginLog::logAction($lineUserId, 'login', $user->id, [
                    'display_name' => $displayName,
                ]);

                // Login user
                Auth::login($user);

                // Redirect to intended page
                $redirect = Session::get('line_login_redirect', route('user.dashboard'));
                Session::forget('line_login_redirect');

                return redirect($redirect)
                    ->with('success', 'เข้าสู่ระบบด้วย LINE สำเร็จ!');
            }

            // New user - redirect to LINE registration guide
            // Log action
            LineLoginLog::logAction($lineUserId, 'register_required', null, [
                'display_name' => $displayName,
            ]);

            return redirect()->route('line.register.guide')
                ->with('info', 'กรุณาเพิ่มเพื่อน LINE Official Account และแชทกับบอทเพื่อสมัครสมาชิก');

        } catch (\Exception $e) {
            Log::error('LINE Login callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'เกิดข้อผิดพลาดในการเข้าสู่ระบบด้วย LINE กรุณาลองอีกครั้ง');
        }
    }

    /**
     * Unlink LINE account
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->line_user_id) {
            return back()->with('error', 'บัญชี LINE ไม่ได้เชื่อมต่ออยู่');
        }

        $lineUserId = $user->line_user_id;

        // Log action
        LineLoginLog::logAction($lineUserId, 'unlink', $user->id);

        // Unlink LINE account
        $user->update([
            'line_user_id' => null,
            'line_display_name' => null,
            'line_picture_url' => null,
            'line_access_token' => null,
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
        if (!$this->lineService->isConfigured()) {
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
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบก่อนเชื่อมต่อบัญชี LINE');
        }

        // Verify state
        $state = Session::get('line_link_state');
        if (!$state || $state !== $request->get('state')) {
            return redirect()->route('user.profile')
                ->with('error', 'Invalid state parameter.');
        }

        Session::forget('line_link_state');
        Session::forget('line_link_mode');

        $code = $request->get('code');
        if (!$code) {
            return redirect()->route('user.profile')
                ->with('error', 'Authorization code not received.');
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
                'line_access_token' => $accessToken,
                'line_linked_at' => now(),
                'line_verified' => true,
            ]);

            // Log action
            LineLoginLog::logAction($lineUserId, 'link', $user->id, [
                'display_name' => $displayName,
            ]);

            return redirect()->route('user.profile')
                ->with('success', 'เชื่อมต่อบัญชี LINE สำเร็จ!');

        } catch (\Exception $e) {
            Log::error('LINE linking error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('user.profile')
                ->with('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อบัญชี LINE');
        }
    }
}
