<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
            $request->session()->regenerate();

            // Get authenticated user
            $user = Auth::user();

            // Check if 2FA is required for login
            if ($this->twoFactorService->requiresVerification($user, 'login')) {
                // Store intended URL in session before redirecting to 2FA
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

            // Default to user dashboard for regular users
            return redirect()->intended(route('user.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
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
