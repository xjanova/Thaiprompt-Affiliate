<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm(Request $request)
    {
        $referralCode = $request->query('ref');
        return view('auth.register', compact('referralCode'));
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'referral_code' => ['nullable', 'string', 'exists:affiliates,referral_code'],
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        // Create affiliate account
        $parentAffiliate = null;
        if (!empty($validated['referral_code'])) {
            $parentAffiliate = Affiliate::where('referral_code', $validated['referral_code'])->first();
        }

        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'parent_id' => $parentAffiliate?->id,
            'referral_code' => Affiliate::generateReferralCode(),
            'level' => $parentAffiliate ? $parentAffiliate->level + 1 : 1,
            'status' => 'active',
        ]);

        // Update user with affiliate_id
        $user->update(['affiliate_id' => $affiliate->id]);

        // Update parent's referral count
        if ($parentAffiliate) {
            $parentAffiliate->increment('total_referrals');
        }

        // Log the user in
        Auth::login($user);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Registration successful! Welcome to TP-Affiliate.');
    }
}
