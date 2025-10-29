<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{
    /**
     * Show the setup wizard
     */
    public function index()
    {
        // Check if system is already set up
        if (User::where('is_super_admin', true)->exists()) {
            return redirect()->route('login')
                ->with('info', 'System is already set up. Please login.');
        }

        return view('auth.setup');
    }

    /**
     * Handle setup request
     */
    public function store(Request $request)
    {
        // Check if system is already set up
        if (User::where('is_super_admin', true)->exists()) {
            return redirect()->route('login')
                ->with('error', 'System is already set up.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create super admin user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'super_admin',
            'is_super_admin' => true,
        ]);

        // Create affiliate account for super admin
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'referral_code' => Affiliate::generateReferralCode(),
            'level' => 1,
            'status' => 'active',
        ]);

        $user->update(['affiliate_id' => $affiliate->id]);

        // Create default settings
        Setting::set('app_name', 'TP-Affiliate', 'string', 'general');
        Setting::set('app_installed', true, 'boolean', 'general');
        Setting::set('commission_rate', 10, 'integer', 'affiliate');
        Setting::set('multi_level_enabled', true, 'boolean', 'affiliate');

        // Log the user in
        Auth::login($user);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Setup complete! Welcome to TP-Affiliate.');
    }
}
