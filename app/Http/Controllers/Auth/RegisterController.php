<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MlmMember;
use App\Models\MlmGlobalSetting;
use App\Models\LeadLock;
use App\Models\LineLoginLog;
use App\Models\LineOaSetting;
use App\Models\LineSignupReward;
use App\Models\RecruitCustomization;
use App\Models\Setting;
use App\Services\LineService;
use App\Services\LineTokenService;
use App\Services\MlmBinaryService;
use App\Services\MlmUnilevelService;
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
     * ✅ เปลี่ยนมาใช้ระบบ LINE Registration ใหม่ทั้งหมด
     * บังคับเพิ่มเพื่อน LINE OA ก่อนสมัคร และ auto-redirect หลังสมัครเสร็จ
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function showRegistrationForm(Request $request)
    {
        $referralCode = $request->query('ref');

        // ✅ Redirect ไปหน้า LINE Registration ใหม่เสมอ
        $url = route('line.registration.register');
        if ($referralCode) {
            $url .= '?ref=' . urlencode($referralCode);
        }

        return redirect($url);
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
            // ⚠️ บังคับใช้ Arrow X Theme v.3 โหมด Classic สำหรับ User ใหม่
            'menu_theme_preference' => 'arrow-x',
            'arrow_x_mode' => 'classic',
            'theme_settings' => [
                'glassOpacity' => 0,
                'glassBlur' => 0,
                'borderOpacity' => 30,
                'shadowIntensity' => 50,
                'glowIntensity' => 60,
                'textShadow' => true,
                'animationSpeed' => 500,
                'hoverScale' => 105,
                'cardRoundness' => 16,
                'buttonRoundness' => 12,
                'primaryHue' => 260,
                'accentHue' => 340,
                'contrast' => 100,
                'backdropSaturate' => 100,
                'perspectiveDepth' => 1000,
            ],
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

        // ⚠️ CRITICAL: ตั้งค่า role หลัง create เพราะ role อยู่ใน guarded
        // ถ้าไม่ตั้งค่า role จะเป็น null ใน memory ทำให้ middleware role:user fail
        $user->role = 'user';
        $user->save();

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

        // ⚠️ CRITICAL: ถ้าไม่มี MlmPlan ให้สร้างอันใหม่
        // เพราะ mlm_members.mlm_plan_id เป็น NOT NULL
        if (!$defaultPlan) {
            $defaultPlan = \App\Models\MlmPlan::create([
                'name' => 'Default Plan',
                'name_th' => 'แผนมาตรฐาน',
                'slug' => 'default-plan',
                'description' => 'Default commission plan',
                'description_th' => 'แผนคอมมิชชันมาตรฐาน',
                'type' => 'hybrid',
                'is_active' => true,
                'is_default' => true,
                'color' => '#6366f1',
                'icon' => 'fa-crown',
                'sort_order' => 1,
                'joining_fee' => 0,
                'requires_joining_fee' => false,
            ]);
            \Log::info('Created default MlmPlan automatically during registration');
        }

        // หาตำแหน่งใน Unilevel Tree (รองรับ width/depth limits และ spillover)
        $unilevelPlacement = $this->findUnilevelPlacementForMember($parentMember);

        $mlmMember = MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $defaultPlan?->id,
            // original_sponsor_id: ผู้แนะนำตรงจริงๆ (ใช้รหัสของใคร) → ใช้สำหรับค่าแนะนำตรง
            'original_sponsor_id' => $parentMember?->id,
            // unilevel_sponsor_id: parent ใน Unilevel tree (อาจ spillover ไปคนอื่น)
            'unilevel_sponsor_id' => $unilevelPlacement['sponsor_id'] ?? $parentMember?->id,
            'unilevel_level' => $unilevelPlacement['level'] ?? ($parentMember ? $parentMember->unilevel_level + 1 : 1),
            'unilevel_path' => $unilevelPlacement['path'] ?? ($parentMember ? $parentMember->unilevel_path . '/' . $parentMember->id : '/' . $user->id),
            // binary_sponsor_id: parent ใน Binary tree (ตามกลยุทธ์ที่ตั้งค่า)
            'binary_sponsor_id' => $parentMember?->id,
            'member_code' => MlmMember::generateMemberCode(),
            'status' => 'active',
            'is_qualified' => true,
            'joined_at' => now(),
        ]);

        // Update original sponsor's direct referral count (ผู้แนะนำตรงจริงๆ)
        // total_direct_referrals นับจากผู้ที่ใช้รหัสแนะนำจริงๆ (original_sponsor)
        // ไม่ใช่ unilevel_sponsor ซึ่งอาจเปลี่ยนไปจากการ spillover
        if ($parentMember) {
            $parentMember->increment('total_direct_referrals');
        }

        // จัดวางใน Binary Tree (ใช้การจัดวางที่ตั้งค่าได้แบบเดิม)
        $this->placeMemberInBinaryTree($mlmMember, $parentMember);

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

        return redirect()->route('user.home')
            ->with('success', 'ลงทะเบียนสำเร็จ! ยินดีต้อนรับสู่ระบบ MLM');
    }

    /**
     * หาตำแหน่งใน Unilevel Tree สำหรับสมาชิกใหม่
     *
     * รองรับ width/depth limits และ auto spillover:
     * - ถ้าผู้แนะนำมีลูกตรงถึง max_width แล้ว → ล้นไปชั้นถัดไปอัตโนมัติ
     * - ใช้ BFS เพื่อเติมเต็มชั้นก่อนไปชั้นถัดไป
     *
     * @param MlmMember|null $sponsor ผู้แนะนำที่ลูกค้าใช้รหัส
     * @return array ['sponsor_id' => int, 'level' => int, 'path' => string]
     */
    protected function findUnilevelPlacementForMember(?MlmMember $sponsor): array
    {
        // ถ้าไม่มี sponsor → คืนค่าเริ่มต้น
        if (!$sponsor) {
            return [
                'sponsor_id' => null,
                'level' => 1,
                'path' => '/',
            ];
        }

        try {
            $unilevelService = new MlmUnilevelService();
            $placement = $unilevelService->findUnilevelPlacement($sponsor);

            if ($placement) {
                \Log::info('Unilevel placement found', [
                    'original_sponsor_id' => $sponsor->id,
                    'actual_sponsor_id' => $placement['sponsor_id'],
                    'level' => $placement['level'],
                    'is_spillover' => $placement['sponsor_id'] !== $sponsor->id,
                ]);
                return $placement;
            }
        } catch (\Exception $e) {
            \Log::error('Unilevel placement error', [
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: วางเป็นลูกตรงของ sponsor เดิม
        return [
            'sponsor_id' => $sponsor->id,
            'level' => $sponsor->unilevel_level + 1,
            'path' => $sponsor->unilevel_path . '/' . $sponsor->id,
        ];
    }

    /**
     * จัดวางสมาชิกใหม่ใน Binary Tree
     *
     * ใช้ algorithm ตาม auto_placement_strategy ที่ตั้งค่าไว้:
     * - fill_level: เรียงเต็มชั้นก่อนไปชั้นถัดไป (BFS - Breadth First Search)
     * - balanced: วางในขาที่มีสมาชิกน้อยกว่า
     * - weak_leg: วางในขาที่มี PV น้อยกว่า
     * - left_first: วางซ้ายก่อนขวา
     * - right_first: วางขวาก่อนซ้าย
     *
     * @param MlmMember $newMember สมาชิกใหม่
     * @param MlmMember|null $sponsor ผู้แนะนำ (sponsor)
     * @return void
     */
    protected function placeMemberInBinaryTree(MlmMember $newMember, ?MlmMember $sponsor): void
    {
        // ตรวจสอบว่าเปิดใช้การจัดวาง Binary อัตโนมัติหรือไม่
        $autoPlaceOnRegister = MlmGlobalSetting::get('binary_auto_place_on_register', true);

        if (!$autoPlaceOnRegister || !$sponsor) {
            return;
        }

        try {
            $binaryService = new MlmBinaryService();

            // หาตำแหน่งที่จะวาง (ใช้ strategy ที่ตั้งค่าไว้)
            $placement = $binaryService->findPlacementPosition($sponsor, null);

            if (!$placement || !isset($placement['parent_id'])) {
                \Log::warning('Binary placement failed - no position found', [
                    'member_id' => $newMember->id,
                    'sponsor_id' => $sponsor->id,
                ]);
                return;
            }

            $parent = MlmMember::find($placement['parent_id']);
            $position = $placement['position'];

            if (!$parent) {
                return;
            }

            // อัพเดทข้อมูล Binary Tree ของสมาชิกใหม่
            $newMember->update([
                'binary_parent_id' => $parent->id,
                'binary_position' => $position,
                'binary_path' => ($parent->binary_path ?? '') . '/' . $position,
            ]);

            // อัพเดทจำนวนสมาชิกในแต่ละขาของ parent
            if ($position === 'left') {
                $parent->increment('left_leg_members');
            } else {
                $parent->increment('right_leg_members');
            }

            // อัพเดท total_team_members ของ upline ทั้งหมดใน Binary Tree
            $this->updateBinaryUplineTeamCounts($newMember);

            \Log::info('Binary placement successful', [
                'member_id' => $newMember->id,
                'parent_id' => $parent->id,
                'position' => $position,
                'binary_path' => $newMember->binary_path,
            ]);

        } catch (\Exception $e) {
            // Log error แต่ไม่ให้กระทบการสมัครสมาชิก
            \Log::error('Binary placement error', [
                'member_id' => $newMember->id,
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * อัพเดทจำนวนสมาชิกทีมของ upline ทั้งหมดใน Binary Tree
     *
     * @param MlmMember $member สมาชิกใหม่
     * @return void
     */
    protected function updateBinaryUplineTeamCounts(MlmMember $member): void
    {
        $currentMember = $member;

        // Traverse up the binary tree and update team counts
        while ($currentMember->binaryParent) {
            $parent = $currentMember->binaryParent;
            $parent->increment('total_team_members');
            $currentMember = $parent;

            // Safety limit เพื่อป้องกัน infinite loop
            if ($parent->id === $currentMember->id) {
                break;
            }
        }
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
