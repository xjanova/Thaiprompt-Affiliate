<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountActive;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            // Get authenticated user
            $user = Auth::user();

            // 🔒 (2026-09-25) บัญชีที่ถูกระงับ → ออกจากระบบทันที ไม่ให้ผ่านเข้าไป
            //    (เช็คหลังรหัสผ่านถูกเท่านั้น — คนเดารหัสจะไม่รู้ว่าบัญชีถูกระงับ)
            if ($user && $user->isSuspended()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => EnsureAccountActive::SUSPENDED_MESSAGE,
                ]);
            }

            $request->session()->regenerate();

            // ตรวจสอบว่าต้องการ 2FA สำหรับการ login หรือไม่
            // หากผู้ใช้ยังไม่ได้ตั้งค่า 2FA จะข้ามการตรวจสอบ
            if ($this->twoFactorService->isRequired('login', $user)) {
                // เก็บข้อมูลใน session ก่อน redirect ไป 2FA
                $request->session()->put('2fa_user_id', $user->id);
                $request->session()->put('2fa_action', 'login');

                return redirect()->route('user.two-factor.verify');
            }

            // Redirect based on user role
            // Check if user is super admin or admin role
            if ($user->is_super_admin || $user->role === 'admin' || $user->role === 'super_admin') {
                return redirect()->intended(route('admin.dashboard'));
            }

            // Check if user is seller
            if ($user->role === 'seller') {
                return redirect()->intended(route('seller.dashboard'));
            }

            // Default to user home (App-Like Interface) for regular users
            return redirect()->intended(route('user.home'));
        }

        throw ValidationException::withMessages([
            'email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
