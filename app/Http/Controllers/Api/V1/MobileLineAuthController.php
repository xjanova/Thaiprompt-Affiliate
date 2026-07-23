<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LineLoginLog;
use App\Models\LineOaSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Mobile LINE Auth Controller
 *
 * จัดการ LINE Login สำหรับ Mobile App แบบ Native
 * ใช้ LINE SDK ใน Mobile App โดยตรง ไม่ต้องเปิด Web Browser
 *
 * Flow:
 * 1. แอพเรียก GET /config เพื่อดึง Channel ID
 * 2. แอพใช้ LINE SDK login และได้ access_token
 * 3. แอพส่ง access_token มาที่ POST /verify
 * 4. Server ตรวจสอบ token กับ LINE API และ issue app token
 */
class MobileLineAuthController extends Controller
{
    /**
     * ดึง LINE Login configuration สำหรับ Mobile App
     */
    public function config(): JsonResponse
    {
        try {
            $settings = LineOaSetting::getActive();

            if (! $settings || ! $settings->isMobileLineLoginConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile LINE Login ยังไม่ถูกตั้งค่า',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'ดึงข้อมูล LINE config สำเร็จ',
                'data' => [
                    'channel_id' => $settings->mobile_login_channel_id,
                    // ไม่ส่ง secret - LINE SDK ไม่ต้องการ
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile LINE config error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล',
            ], 500);
        }
    }

    /**
     * ตรวจสอบ LINE Access Token จาก Mobile App และ issue app token
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_token' => 'required|string',
            'id_token' => 'nullable|string',
            'device_id' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'referral_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = LineOaSetting::getActive();

            if (! $settings || ! $settings->isMobileLineLoginConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile LINE Login ยังไม่ถูกตั้งค่า',
                ], 400);
            }

            // ตรวจสอบ access_token กับ LINE API
            $lineProfile = $this->verifyLineAccessToken($request->access_token);

            if (! $lineProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'LINE Access Token ไม่ถูกต้องหรือหมดอายุ',
                ], 401);
            }

            $lineUserId = $lineProfile['userId'];
            $displayName = $lineProfile['displayName'] ?? 'LINE User';
            $pictureUrl = $lineProfile['pictureUrl'] ?? null;

            // ค้นหา user ที่มี LINE ID นี้
            $user = User::where('line_user_id', $lineUserId)->first();

            if ($user) {
                // มี user อยู่แล้ว - login
                return $this->loginExistingUser($user, $request, $displayName, $pictureUrl);
            }

            // ไม่มี user - สร้างใหม่
            return $this->registerNewUser($lineUserId, $displayName, $pictureUrl, $request);

        } catch (\Exception $e) {
            Log::error('Mobile LINE verify error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * ตรวจสอบ LINE Access Token กับ LINE API
     */
    protected function verifyLineAccessToken(string $accessToken): ?array
    {
        try {
            // ดึงข้อมูล profile จาก LINE API
            $response = Http::withToken($accessToken)
                ->get('https://api.line.me/v2/profile');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('LINE profile API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('LINE API error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Login user ที่มีอยู่แล้ว
     */
    protected function loginExistingUser(User $user, Request $request, string $displayName, ?string $pictureUrl): JsonResponse
    {
        // อัพเดทข้อมูล LINE
        $user->update([
            'line_display_name' => $displayName,
            'line_picture_url' => $pictureUrl,
            'line_linked_at' => now(),
            'line_verified' => true,
        ]);

        // สร้าง API token
        $deviceName = $request->device_name ?? 'Mobile App';
        $token = $user->createToken($deviceName, ['*'])->plainTextToken;

        // Log action
        LineLoginLog::logAction($user->line_user_id, 'mobile_login', $user->id, [
            'display_name' => $displayName,
            'device_id' => $request->device_id,
            'device_name' => $deviceName,
        ]);

        Log::info('Mobile LINE login successful', [
            'user_id' => $user->id,
            'device_id' => $request->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture' => $user->profile_picture ?? $user->line_picture_url,
                    'member_code' => $user->mlmMember?->member_code,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => false,
            ],
        ]);
    }

    /**
     * สร้าง user ใหม่จาก LINE Login
     */
    protected function registerNewUser(string $lineUserId, string $displayName, ?string $pictureUrl, Request $request): JsonResponse
    {
        // สร้าง email แบบ unique
        $email = 'line_'.Str::random(10).'@line.local';

        // สร้าง user
        $user = User::create([
            'name' => $displayName,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'role' => 'user',
            'line_user_id' => $lineUserId,
            'line_display_name' => $displayName,
            'line_picture_url' => $pictureUrl,
            'line_linked_at' => now(),
            'line_verified' => true,
            'profile_picture' => $pictureUrl,
            // Default theme settings
            'menu_theme_preference' => 'arrow-x',
            'arrow_x_mode' => 'classic',
        ]);

        // ตั้งค่า role หลัง create
        $user->role = 'user';
        $user->save();

        // สร้าง MLM Member
        $this->createMlmMember($user, $request->referral_code);

        // สร้าง API token
        $deviceName = $request->device_name ?? 'Mobile App';
        $token = $user->createToken($deviceName, ['*'])->plainTextToken;

        // Log action
        LineLoginLog::logAction($lineUserId, 'mobile_register', $user->id, [
            'display_name' => $displayName,
            'device_id' => $request->device_id,
            'referral_code' => $request->referral_code,
        ]);

        Log::info('Mobile LINE registration successful', [
            'user_id' => $user->id,
            'device_id' => $request->device_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'สมัครสมาชิกสำเร็จ',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_picture' => $user->profile_picture,
                    'member_code' => $user->mlmMember?->member_code,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => true,
            ],
        ]);
    }

    /**
     * สร้าง MLM Member สำหรับ user ใหม่
     *
     * 🐛 Fix 2026-07-24: เดิม path นี้ตั้งแค่ unilevel_sponsor_id แต่ไม่จัดวาง binary tree เลย
     * → สมาชิกจากแอปมือถือหายจากผัง binary + PV ไม่ไหลขึ้น leg ของ upline
     * (อาการเดียวกับ incident มิ.ย. 2026 ฝั่งดูดวง) — ตอนนี้จัดวาง binary พร้อม fallback
     * วางใต้ sponsor ตรง แบบเดียวกับ FortuneAffiliateService::enrollUserUnderSponsor
     */
    protected function createMlmMember(User $user, ?string $referralCode): void
    {
        try {
            // Idempotent: ถ้ามี member อยู่แล้ว ห้ามสร้างซ้ำ/ห้าม re-parent
            if (MlmMember::where('user_id', $user->id)->exists()) {
                return;
            }

            $parentMember = null;

            // หา sponsor จาก referral code
            if (! empty($referralCode)) {
                $parentMember = MlmMember::where('member_code', $referralCode)->first();
            }

            // ถ้าไม่มี referral code ใช้ default sponsor
            if (! $parentMember) {
                $defaultSponsorCode = \App\Models\Setting::get('default_sponsor_member_code');
                if (! empty($defaultSponsorCode)) {
                    $parentMember = MlmMember::where('member_code', $defaultSponsorCode)->first();
                }
            }

            // หา default plan
            $defaultPlan = MlmPlan::where('is_default', true)->first()
                ?? MlmPlan::first();

            if (! $defaultPlan) {
                $defaultPlan = MlmPlan::create([
                    'name' => 'Default Plan',
                    'name_th' => 'แผนมาตรฐาน',
                    'slug' => 'default-plan',
                    'type' => 'hybrid',
                    'is_active' => true,
                    'is_default' => true,
                ]);
            }

            // สร้าง MLM Member (ยังไม่วาง binary — placeNewMember จะจัดการ)
            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $defaultPlan->id,
                'original_sponsor_id' => $parentMember?->id,
                'unilevel_sponsor_id' => $parentMember?->id,
                'unilevel_level' => $parentMember ? $parentMember->unilevel_level + 1 : 1,
                'unilevel_path' => $parentMember
                    ? $parentMember->unilevel_path.'/'.$parentMember->id
                    : null,
                'binary_sponsor_id' => $parentMember?->id,
                'member_code' => MlmMember::generateMemberCode(),
                'status' => 'active',
                'is_qualified' => true,
                'joined_at' => now(),
            ]);

            // root member (ไม่มี sponsor) — path ต้องอ้าง member id ของตัวเอง ไม่ใช่ user id
            if (! $parentMember) {
                $member->update(['unilevel_path' => '/'.$member->id]);
            }

            // อัพเดท direct referral count
            if ($parentMember) {
                $parentMember->increment('total_direct_referrals');

                // วาง binary ผ่านศูนย์กลาง (fallback BFS + retry กัน race + อัพเดทสถิติครบ)
                $placementResult = app(\App\Services\MlmBinaryService::class)
                    ->placeNewMember($member, $parentMember);

                if (! $placementResult) {
                    Log::warning('Mobile LINE: วาง binary ไม่สำเร็จ (member ยังอยู่ใน unilevel ปกติ)', [
                        'member_id' => $member->id,
                        'sponsor_member_id' => $parentMember->id,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to create MLM member for LINE user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
