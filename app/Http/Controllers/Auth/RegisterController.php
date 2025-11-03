<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Affiliate;
use App\Models\LineLoginLog;
use App\Models\LineOaSetting;
use App\Models\Setting;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm(Request $request)
    {
        $referralCode = $request->query('ref');

        // Get default sponsor info
        $defaultSponsorName = null;
        if (empty($referralCode)) {
            $defaultSponsorCode = Setting::get('default_sponsor_referral_code');
            if (!empty($defaultSponsorCode)) {
                $defaultSponsor = Affiliate::where('referral_code', $defaultSponsorCode)->with('user')->first();
                if ($defaultSponsor && $defaultSponsor->user) {
                    $defaultSponsorName = $defaultSponsor->user->name;
                }
            }
        }

        // Check if LINE profile exists in session (for LINE registration flow)
        $lineProfile = Session::get('line_temp_profile');

        return view('auth.register', compact('referralCode', 'defaultSponsorName', 'lineProfile'));
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        // Check if LINE registration is required
        $lineRequired = LineOaSetting::isRequired();
        $lineProfile = Session::get('line_temp_profile');

        if ($lineRequired && !$lineProfile) {
            return redirect()->route('register')
                ->with('error', 'กรุณาเข้าสู่ระบบด้วย LINE ก่อนสมัครสมาชิก');
        }

        // Validate basic fields
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'referral_code' => ['nullable', 'string', 'exists:affiliates,referral_code'],
        ];

        $validated = $request->validate($rules);

        // If LINE profile exists, check for duplicate LINE user
        if ($lineProfile) {
            $existingLineUser = User::where('line_user_id', $lineProfile['line_user_id'])->first();
            if ($existingLineUser) {
                Session::forget('line_temp_profile');
                return redirect()->route('login')
                    ->with('error', 'บัญชี LINE นี้ถูกใช้งานแล้ว กรุณาเข้าสู่ระบบ');
            }
        }

        // Create user with LINE info if available
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ];

        if ($lineProfile) {
            // LINE profile data
            $userData['line_user_id'] = $lineProfile['line_user_id'];
            $userData['line_display_name'] = $lineProfile['line_display_name'];
            $userData['line_picture_url'] = $lineProfile['line_picture_url'];
            $userData['line_access_token'] = $lineProfile['line_access_token'];
            $userData['line_linked_at'] = now();
            $userData['line_verified'] = true;

            // Use LINE picture as profile picture
            if (!empty($lineProfile['line_picture_url'])) {
                $userData['profile_picture'] = $lineProfile['line_picture_url'];
            }
        }

        $user = User::create($userData);

        // Create affiliate account
        $parentAffiliate = null;
        $referralCode = $validated['referral_code'] ?? null;

        // If no referral code provided, use default sponsor from settings
        if (empty($referralCode)) {
            $referralCode = Setting::get('default_sponsor_referral_code');
        }

        if (!empty($referralCode)) {
            $parentAffiliate = Affiliate::where('referral_code', $referralCode)->first();
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

        // Log LINE registration if applicable
        if ($lineProfile) {
            LineLoginLog::logAction(
                $lineProfile['line_user_id'],
                'register',
                $user->id,
                [
                    'display_name' => $lineProfile['line_display_name'],
                    'referral_code' => $affiliate->referral_code,
                    'parent_id' => $parentAffiliate?->id,
                ]
            );

            // Send LINE welcome message
            try {
                $lineService = app(LineService::class);
                $lineService->sendRegistrationSuccessMessage($user);
            } catch (\Exception $e) {
                \Log::error('Failed to send LINE registration message', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Clear LINE temp profile from session
            Session::forget('line_temp_profile');
            Session::forget('line_login_referral');
        }

        // Log the user in
        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', 'ลงทะเบียนสำเร็จ! ยินดีต้อนรับสู่ระบบ Affiliate');
    }
}
