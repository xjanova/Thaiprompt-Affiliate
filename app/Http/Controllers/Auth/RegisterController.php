<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MlmMember;
use App\Models\LeadLock;
use App\Models\LineLoginLog;
use App\Models\LineOaSetting;
use App\Models\LineSignupReward;
use App\Models\RecruitCustomization;
use App\Models\Setting;
use App\Services\LineService;
use App\Services\LineTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * แสดงฟอร์มสมัครสมาชิก
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $referralCode = $request->query('ref');

        // ดึงข้อมูลผู้แนะนำเริ่มต้น (MLM)
        $defaultSponsorName = null;
        if (empty($referralCode)) {
            $defaultSponsorCode = Setting::get('default_sponsor_member_code');
            if (!empty($defaultSponsorCode)) {
                $defaultSponsor = MlmMember::where('member_code', $defaultSponsorCode)->with('user')->first();
                if ($defaultSponsor && $defaultSponsor->user) {
                    $defaultSponsorName = $defaultSponsor->user->name;
                }
            }
        }

        // ตรวจสอบ LINE profile ใน session (สำหรับการสมัครผ่าน LINE)
        $lineProfile = Session::get('line_temp_profile');

        // ดึงรางวัลการสมัครสมาชิกที่ active สำหรับสมัครฟรี
        $signupRewards = LineSignupReward::getAvailableRewards(null);

        return view('auth.register', compact('referralCode', 'defaultSponsorName', 'lineProfile', 'signupRewards'));
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
            'referral_code' => ['nullable', 'string', 'exists:mlm_members,member_code'],
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
            // LINE profile data (without token - will be stored encrypted separately)
            $userData['line_user_id'] = $lineProfile['line_user_id'];
            $userData['line_display_name'] = $lineProfile['line_display_name'];
            $userData['line_picture_url'] = $lineProfile['line_picture_url'];
            $userData['line_linked_at'] = now();
            $userData['line_verified'] = true;

            // Use LINE picture as profile picture
            if (!empty($lineProfile['line_picture_url'])) {
                $userData['profile_picture'] = $lineProfile['line_picture_url'];
            }
        }

        $user = User::create($userData);

        // Store encrypted LINE access token if available
        if ($lineProfile && !empty($lineProfile['line_access_token'])) {
            $tokenService = app(LineTokenService::class);
            $tokenService->storeAccessToken($user, $lineProfile['line_access_token']);
        }

        // Create MLM member account
        $parentMember = null;
        $referralCode = $validated['referral_code'] ?? null;

        // If no referral code provided, use default sponsor from settings
        if (empty($referralCode)) {
            $referralCode = Setting::get('default_sponsor_member_code');
        }

        if (!empty($referralCode)) {
            $parentMember = MlmMember::where('member_code', $referralCode)->first();
        }

        // Get default MLM plan
        $defaultPlan = \App\Models\MlmPlan::where('is_default', true)->first();
        if (!$defaultPlan) {
            $defaultPlan = \App\Models\MlmPlan::first(); // Fallback to any plan
        }

        $mlmMember = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $defaultPlan?->id,
            'unilevel_sponsor_id' => $parentMember?->id,
            'unilevel_level' => $parentMember ? $parentMember->unilevel_level + 1 : 1,
            'unilevel_path' => $parentMember ? $parentMember->unilevel_path . '/' . $parentMember->id : '/' . $user->id,
            'member_code' => MlmMember::generateMemberCode(),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now(),
        ]);

        // Update parent's referral count
        if ($parentMember) {
            $parentMember->increment('total_direct_referrals');
        }

        // Handle Recruit Page Lead Conversion Tracking
        $this->handleLeadConversion($request, $parentMember, $mlmMember);

        // Log LINE registration if applicable
        if ($lineProfile) {
            LineLoginLog::logAction(
                $lineProfile['line_user_id'],
                'register',
                $user->id,
                [
                    'display_name' => $lineProfile['line_display_name'],
                    'member_code' => $mlmMember->member_code,
                    'sponsor_id' => $parentMember?->id,
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
            ->with('success', 'ลงทะเบียนสำเร็จ! ยินดีต้อนรับสู่ระบบ MLM');
    }

    /**
     * จัดการ Lead Conversion Tracking จากหน้า Recruit
     *
     * เมื่อผู้ใช้สมัครสมาชิกผ่านหน้า Recruit จะทำการ:
     * - Mark LeadLock เป็น "converted"
     * - Increment total_conversions ของ RecruitCustomization
     * - Update conversion rate
     *
     * @param Request $request
     * @param MlmMember|null $parentMember แม่ทีมที่แนะนำ
     * @param MlmMember $newMember สมาชิกใหม่ที่สมัคร
     * @return void
     */
    protected function handleLeadConversion(Request $request, ?MlmMember $parentMember, MlmMember $newMember): void
    {
        try {
            // สร้าง visitor identifier แบบเดียวกับ RecruitController
            $visitorIdentifier = hash('sha256', $request->ip() . '|' . ($request->userAgent() ?? ''));

            // หา LeadLock ที่ active สำหรับ visitor นี้
            $leadLock = LeadLock::getActiveLock($visitorIdentifier);

            if ($leadLock) {
                // Mark LeadLock as converted
                $leadLock->markAsConverted($newMember->id);

                // Increment conversions ของ RecruitCustomization
                if ($leadLock->teamLeader) {
                    $customization = RecruitCustomization::where('user_id', $leadLock->team_leader_id)->first();
                    if ($customization) {
                        $customization->incrementConversions();
                    }
                }

                \Log::info('Lead conversion tracked successfully', [
                    'lead_lock_id' => $leadLock->id,
                    'team_leader_id' => $leadLock->team_leader_id,
                    'new_member_id' => $newMember->id,
                    'visitor_identifier' => $visitorIdentifier,
                ]);
            }

        } catch (\Exception $e) {
            // Don't fail registration if conversion tracking fails
            \Log::error('Failed to track lead conversion', [
                'error' => $e->getMessage(),
                'new_member_id' => $newMember->id,
                'parent_member_id' => $parentMember?->id,
            ]);
        }
    }
}
