<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\MlmProspect;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * FortuneAffiliateService
 *
 * จัดการการลงทะเบียนสมาชิกอัตโนมัติสำหรับลูกค้าดูดวง (ทุก platform)
 * - รองรับ LINE + Facebook + platform อื่นๆ
 * - สร้าง User จาก platform profile
 * - สร้าง MlmMember ต่อสายงานคนเชิญ (หรือ Super Admin)
 * - ส่ง Flex Message เชิญชวนเข้าร่วม affiliate (เฉพาะ LINE)
 * - สร้างลิงก์เชิญเพื่อนพร้อม referral tracking
 */
class FortuneAffiliateService
{
    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    // ============================================================
    // Entry Point — เรียกจาก FortuneConversationService
    // ============================================================

    /**
     * ลงทะเบียนลูกค้าดูดวงเป็นสมาชิก Thaiprompt + MLM อัตโนมัติ
     *
     * รองรับทุก platform: LINE, Facebook, และอื่นๆ
     * เรียกหลังจากส่งคำทำนายเชิงลึกสำเร็จ
     * ทุกอย่างอยู่ใน try/catch — ห้าม error กระทบคำทำนาย
     *
     * @param  FortuneReading  $reading  บันทึกการดูดวง
     * @param  string  $platformUserId  User ID ของ platform (LINE/Facebook/etc.)
     * @param  LineFortuneService|null  $lineService  LINE service (เฉพาะ LINE platform)
     * @param  string|null  $platform  ชื่อ platform: 'line', 'facebook', etc.
     */
    public function autoRegisterFromFortune(
        FortuneReading $reading,
        string $platformUserId,
        ?LineFortuneService $lineService = null,
        ?string $platform = null
    ): ?User {
        // Auto-detect platform จาก reading ถ้าไม่ได้ระบุ
        $platform = $platform ?? $reading->platform ?? 'facebook';

        // ตรวจว่าเปิดระบบ affiliate หรือไม่
        if (! $this->settings->isFortuneAffiliateEnabled()) {
            Log::debug('Fortune Affiliate: ระบบ affiliate ปิดอยู่ (fortune_affiliate_enabled = false)', [
                'reading_id' => $reading->id,
                'platform' => $platform,
            ]);

            return null;
        }

        try {
            // ตรวจว่า User มีอยู่แล้วหรือไม่ (ค้นหาตาม platform)
            $existingUser = $this->findExistingUser($platformUserId, $platform, $reading);

            if ($existingUser) {
                // User มีอยู่แล้ว — link FortuneReading
                if (! $reading->user_id) {
                    $reading->update(['user_id' => $existingUser->id]);
                }

                // ตรวจว่ามี MlmMember หรือยัง — ถ้ายังไม่มีต้องสร้างให้
                $existingMember = MlmMember::where('user_id', $existingUser->id)->first();
                if (! $existingMember) {
                    Log::info('Fortune Affiliate: User มีอยู่แล้วแต่ยังไม่มี MlmMember — สร้างให้', [
                        'user_id' => $existingUser->id,
                        'platform_user_id' => $platformUserId,
                    ]);

                    DB::reconnect();
                    $existingMember = $this->createMlmMember($existingUser, $platformUserId);

                    // สร้าง Wallet ทันที (เพื่อรองรับการโอนเงินจากกระเป๋าอื่น)
                    $this->ensureWalletExists($existingUser);

                    // ส่ง Flex Messages ถ้าเป็น LINE
                    if ($platform === 'line' && $lineService && $existingMember) {
                        try {
                            $this->sendWelcomeFlexMessage($platformUserId, $existingUser, $existingMember, $lineService);
                            usleep(100000);
                            $referralLink = $this->generateReferralLink($existingUser, $existingMember);
                            $this->sendAffiliateInviteFlexMessage($platformUserId, $existingUser, $referralLink, $lineService);
                        } catch (\Exception $flexErr) {
                            Log::warning('Fortune Affiliate: ส่ง Flex Message ไม่สำเร็จ (existing user)', [
                                'user_id' => $existingUser->id,
                                'error' => $flexErr->getMessage(),
                            ]);
                        }
                    }
                } else {
                    Log::info('Fortune Affiliate: User มีอยู่แล้ว + มี MlmMember แล้ว', [
                        'user_id' => $existingUser->id,
                        'member_id' => $existingMember->id,
                    ]);
                }

                return $existingUser;
            }

            // ดึง profile จาก platform
            $profile = $this->fetchPlatformProfile($platformUserId, $platform, $lineService, $reading);

            // Reconnect DB (กัน stale connection หลัง AI generation นาน)
            DB::reconnect();

            $user = null;
            $member = null;

            DB::transaction(function () use (&$user, &$member, $platformUserId, $platform, $profile, $reading) {
                // สร้าง User ตาม platform
                $user = $this->createUserFromPlatform($platformUserId, $platform, $profile, $reading);

                // Link FortuneReading
                $reading->update(['user_id' => $user->id]);

                // สร้าง MlmMember (ต่อสายงานคนเชิญหรือ Super Admin)
                $member = $this->createMlmMember($user, $platformUserId);

                // สร้าง Wallet ทันที (เพื่อรองรับการโอนเงินจากกระเป๋าอื่น)
                $this->ensureWalletExists($user);
            });

            if (! $user) {
                return null;
            }

            // ส่ง Flex Messages (เฉพาะ LINE — นอก transaction เพื่อไม่ rollback ถ้าส่งไม่ได้)
            if ($platform === 'line' && $lineService && $member) {
                try {
                    // Flex #1: ข้อความต้อนรับ + รหัสสมาชิก + ปุ่ม LINE Login
                    $this->sendWelcomeFlexMessage($platformUserId, $user, $member, $lineService);

                    // delay เล็กน้อย
                    usleep(100000); // 100ms

                    // Flex #2: ชวนเพื่อน + ตัวอย่างรายได้ + ลิงก์เชิญ
                    $referralLink = $this->generateReferralLink($user, $member);
                    $this->sendAffiliateInviteFlexMessage($platformUserId, $user, $referralLink, $lineService);
                } catch (\Exception $flexErr) {
                    Log::warning('Fortune Affiliate: ส่ง Flex Message ไม่สำเร็จ', [
                        'user_id' => $user->id,
                        'error' => $flexErr->getMessage(),
                    ]);
                }
            }

            Log::info('Fortune Affiliate: ลงทะเบียนสมาชิกอัตโนมัติสำเร็จ', [
                'user_id' => $user->id,
                'member_id' => $member?->id,
                'member_code' => $member?->member_code,
                'reading_id' => $reading->id,
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ]);

            return $user;

        } catch (\Exception $e) {
            Log::error('Fortune Affiliate: ลงทะเบียนอัตโนมัติล้มเหลว', [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 500),
            ]);

            return null;
        }
    }

    // ============================================================
    // Platform User Lookup & Profile
    // ============================================================

    /**
     * ค้นหา User ที่มีอยู่แล้วจาก platform user ID
     *
     * ค้นหาหลายช่องทาง: line_user_id, facebook_user_id, platform_user_id ใน FortuneReading
     */
    protected function findExistingUser(string $platformUserId, string $platform, FortuneReading $reading): ?User
    {
        // 1. ค้นหาตาม platform-specific column
        if ($platform === 'line') {
            $user = User::where('line_user_id', $platformUserId)->first();
            if ($user) {
                return $user;
            }
        } elseif ($platform === 'facebook') {
            // PSID — ครอบทั้งบัญชีที่บอทสร้าง และบัญชีที่ FB OAuth สร้างแล้ว
            // map PSID ได้ (สัญญา lookup รวมอยู่ที่ User::findByMessengerPsid ที่เดียว)
            $user = User::findByMessengerPsid($platformUserId);
            if ($user) {
                return $user;
            }
        }

        // 2. ค้นหาจาก email pattern (line_{id}@thaiprompt.local หรือ fb_{id}@thaiprompt.local)
        $emailPrefix = $platform === 'line' ? 'line_' : 'fb_';
        $user = User::where('email', $emailPrefix.$platformUserId.'@thaiprompt.local')->first();
        if ($user) {
            return $user;
        }

        // 3. ค้นหาจาก FortuneReading ที่ link กับ user อยู่แล้ว
        $linkedReading = FortuneReading::where(function ($q) use ($platformUserId) {
            $q->where('platform_user_id', $platformUserId)
                ->orWhere('facebook_user_id', $platformUserId);
        })
            ->whereNotNull('user_id')
            ->latest()
            ->first();

        if ($linkedReading && $linkedReading->user_id) {
            return User::find($linkedReading->user_id);
        }

        return null;
    }

    /**
     * ดึง profile จาก platform
     */
    protected function fetchPlatformProfile(
        string $platformUserId,
        string $platform,
        ?LineFortuneService $lineService,
        FortuneReading $reading
    ): array {
        $profile = ['name' => null, 'picture_url' => null];

        // ดึง profile จาก LINE API
        if ($platform === 'line' && $lineService) {
            try {
                $lineProfile = $lineService->getUserProfile($platformUserId);
                $profile = [
                    'name' => $lineProfile['name'] ?? $lineProfile['displayName'] ?? null,
                    'picture_url' => $lineProfile['picture_url'] ?? $lineProfile['pictureUrl'] ?? null,
                ];
            } catch (\Exception $e) {
                Log::debug('Fortune Affiliate: ดึง LINE profile ไม่ได้', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ใช้ชื่อจาก FortuneReading ถ้าไม่มีจาก platform API
        if (! $profile['name']) {
            $profile['name'] = $reading->facebook_user_name ?? $reading->user_name ?? 'User';
        }

        return $profile;
    }

    // ============================================================
    // User Creation
    // ============================================================

    /**
     * สร้าง User จาก platform profile (LINE / Facebook / อื่นๆ)
     *
     * ใช้ pattern จาก LineSignupService::createUser()
     */
    protected function createUserFromPlatform(
        string $platformUserId,
        string $platform,
        array $profile,
        ?FortuneReading $reading = null
    ): User {
        // กำหนด email ตาม platform
        $emailPrefix = $platform === 'line' ? 'line_' : 'fb_';
        $email = $emailPrefix.$platformUserId.'@thaiprompt.local';

        // เตรียมข้อมูล base
        // 🔒 (2026-07-16) เดิม Hash::make('12345678') — บัญชีที่บอทสมัครให้ทุกใบรหัสเดียวกัน
        //    + อีเมลเดาได้จากสูตร line_{uid}@thaiprompt.local + LoginController ไม่บล็อก .local
        //    → ใครรู้ LINE UID/FB PSID ของลูกค้า ล็อกอินเป็นคนนั้นได้ พร้อมเข้าถึงวอลเลต
        //    ลูกค้ากลุ่มนี้เข้าเว็บผ่าน /auth/line?redirect=... (OAuth) เสมอ ไม่เคยใช้รหัสผ่าน
        //    → สุ่มรหัสยาวทิ้งไว้ ไม่มีใครรู้ รวมถึงเราเอง
        $userData = [
            'name' => $profile['name'] ?? 'User',
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
        ];

        // เพิ่มข้อมูลเฉพาะ platform
        if ($platform === 'line') {
            $userData['line_user_id'] = $platformUserId;
            if (Schema::hasColumn('users', 'line_display_name')) {
                $userData['line_display_name'] = $profile['name'] ?? null;
            }
            if (Schema::hasColumn('users', 'line_picture_url')) {
                $userData['line_picture_url'] = $profile['picture_url'] ?? null;
            }
            if (Schema::hasColumn('users', 'line_verified')) {
                $userData['line_verified'] = true;
            }
            if (Schema::hasColumn('users', 'line_linked_at')) {
                $userData['line_linked_at'] = now();
            }
        } elseif ($platform === 'facebook' && ctype_digit($platformUserId) && Schema::hasColumn('users', 'facebook_psid')) {
            // เก็บ Messenger PSID เป็นคอลัมน์จริง — เดิมฝังอยู่แค่ในอีเมล
            // fb_{PSID}@thaiprompt.local ทำให้ FB OAuth map กลับมาหาบัญชีนี้ไม่ได้
            // ctype_digit: PSID เป็นตัวเลขล้วน — กัน id แปลกปลอมหลุดเข้า unique column
            // (autoRegisterFromFortune default platform เป็น 'facebook' เมื่อ reading ไม่ระบุ)
            $userData['facebook_psid'] = $platformUserId;
        }

        $user = User::create($userData);

        // ดึง birth_date จาก FortuneReading (ถ้ามี)
        if ($reading?->birth_date && Schema::hasColumn('users', 'date_of_birth')) {
            $user->update(['date_of_birth' => $reading->birth_date]);
        }

        // ใช้ avatar จาก profile (ถ้ามี)
        if (($profile['picture_url'] ?? null) && Schema::hasColumn('users', 'avatar_url')) {
            $user->update(['avatar_url' => $profile['picture_url']]);
        }

        Log::info('Fortune Affiliate: สร้าง User สำเร็จ', [
            'user_id' => $user->id,
            'name' => $user->name,
            'platform' => $platform,
            'platform_user_id' => $platformUserId,
        ]);

        return $user;
    }

    // ============================================================
    // MLM Member Creation
    // ============================================================

    /**
     * สร้าง MlmMember — ต่อสายงานคนเชิญ (FortuneReferral) หรือ Super Admin
     *
     * สร้าง Wallet ให้ User ทันที (ถ้ายังไม่มี)
     * เพื่อรองรับการรับคอมมิชชั่นและการโอนเงินจากกระเป๋าอื่น
     */
    protected function ensureWalletExists(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'currency' => 'THB',
                'status' => 'active',
            ]
        );
    }

    /**
     * ใช้ logic จาก LineSignupService::createMlmMember()
     * รองรับทุก platform — ค้นหา referral จาก platformUserId
     */
    protected function createMlmMember(User $user, string $platformUserId): ?MlmMember
    {
        try {
            // ตรวจว่ามี MlmMember อยู่แล้วหรือไม่
            $existing = MlmMember::where('user_id', $user->id)->first();
            if ($existing) {
                return $existing;
            }

            // หา sponsor จาก FortuneReferral (ถ้ามีคนเชิญ)
            $sponsor = null;
            $referral = FortuneReferral::findActiveByPlatformUserId($platformUserId);

            if ($referral && $referral->referrerMlmMember) {
                $sponsor = $referral->referrerMlmMember;
                Log::info('Fortune Affiliate: พบ sponsor จากลิงก์เชิญ', [
                    'referral_id' => $referral->id,
                    'sponsor_member_id' => $sponsor->id,
                ]);
            }

            // Fallback: Super Admin (user_id = 1)
            if (! $sponsor) {
                $superAdmin = User::find(1);
                if ($superAdmin) {
                    $sponsor = MlmMember::where('user_id', $superAdmin->id)->first();
                }

                if (! $sponsor) {
                    Log::warning('Fortune Affiliate: ไม่พบ Super Admin MlmMember — ข้าม MLM enrollment');

                    return null;
                }
            }

            $member = $this->enrollUserUnderSponsor($user, $sponsor);
            if (! $member) {
                return null;
            }

            // Mark referral as converted
            if ($referral) {
                $referral->markAsConverted($user);
            }

            Log::info('Fortune Affiliate: สร้าง MlmMember สำเร็จ', [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'sponsor_id' => $sponsor->id,
                'from_referral' => $referral !== null,
            ]);

            return $member;

        } catch (\Exception $e) {
            Log::error('Fortune Affiliate: สร้าง MlmMember ล้มเหลว', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ต่อสายงาน: สร้าง MlmMember ให้ $user ใต้ $sponsor ที่ระบุ
     *
     * แกนกลางที่ใช้ร่วมกันระหว่าง auto-register (LINE/FB flow ด้านบน) และ
     * Juntra claim-referral (จันทรา.online/r/{member_code} → juntraweb →
     * POST /api/v1/juntra/mlm/claim-referral). Idempotent: ถ้า user มี
     * MlmMember อยู่แล้ว คืนตัวเดิม — ไม่ re-parent เด็ดขาด (unilevel_path
     * ของทั้ง downline จะพัง + คอมมิชชั่นย้ายสายไม่ได้)
     */
    public function enrollUserUnderSponsor(User $user, MlmMember $sponsor): ?MlmMember
    {
        try {
            $existing = MlmMember::where('user_id', $user->id)->first();
            if ($existing) {
                return $existing;
            }

            // หา default MLM plan
            $defaultPlan = MlmPlan::where('is_default', true)->first();
            if (! $defaultPlan) {
                Log::warning('Fortune Affiliate: ไม่พบ default MLM plan — ข้าม MLM enrollment');

                return null;
            }

            // หาตำแหน่ง binary แบบ auto-placement
            // 🐛 Fix 2026-06-12: placement อาจคืน null (เช่น depth limit เต็ม) — ห้าม access
            // array offset บน null เพราะ Laravel แปลง warning เป็น ErrorException
            // → MlmMember ไม่ถูกสร้างเงียบๆ → คอมมิชชั่นดูดวงไม่จ่ายทั้งระบบ (ตายมา 3.5 เดือน)
            $placement = null;
            try {
                $binaryService = app(MlmBinaryService::class);
                $placement = $binaryService->findPlacementPosition($sponsor);
            } catch (\Throwable $placementErr) {
                Log::warning('Fortune Affiliate: binary placement ล้มเหลว — ใช้ fallback วางใต้ sponsor ตรง', [
                    'user_id' => $user->id,
                    'sponsor_id' => $sponsor->id,
                    'error' => $placementErr->getMessage(),
                ]);
            }

            if (! is_array($placement) || ! isset($placement['parent_id'])) {
                Log::warning('Fortune Affiliate: ไม่พบตำแหน่ง binary — fallback วางใต้ sponsor ตรง (ขาซ้าย)', [
                    'user_id' => $user->id,
                    'sponsor_id' => $sponsor->id,
                ]);
            }

            // Fallback: วางใต้ sponsor ตรงขาซ้าย (unilevel commission ไม่กระทบ —
            // ค่าแนะนำดูดวงใช้ unilevel_sponsor_id เท่านั้น)
            $binaryParentId = $placement['parent_id'] ?? $sponsor->id;
            $binaryPosition = $placement['position'] ?? 'left';

            // สร้าง MLM member
            $member = MlmMember::create([
                'user_id' => $user->id,
                'mlm_plan_id' => $defaultPlan->id,
                // Unilevel structure
                'unilevel_sponsor_id' => $sponsor->id,
                'unilevel_level' => $sponsor->unilevel_level + 1,
                'unilevel_path' => $sponsor->unilevel_path.'/'.$sponsor->id,
                'original_sponsor_id' => $sponsor->id,
                // Binary structure (auto-placement)
                'binary_sponsor_id' => $sponsor->id,
                'binary_parent_id' => $binaryParentId,
                'binary_position' => $binaryPosition,
                // Status
                'status' => 'active',
                'joined_at' => now(),
                'member_code' => MlmMember::generateMemberCode(),
                'is_qualified' => true,
            ]);

            // Update sponsor stats
            $sponsor->increment('total_direct_referrals');

            // Update binary parent stats
            $binaryParent = MlmMember::find($binaryParentId);
            if ($binaryParent) {
                if ($binaryPosition === 'left') {
                    $binaryParent->increment('left_leg_members');
                } else {
                    $binaryParent->increment('right_leg_members');
                }
            }

            // สร้าง Wallet รอไว้รับคอมมิชชั่นทันที
            $this->ensureWalletExists($user);

            Log::info('Fortune Affiliate: enroll ใต้ sponsor สำเร็จ', [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'sponsor_id' => $sponsor->id,
                'binary_position' => $binaryPosition,
            ]);

            return $member;

        } catch (\Exception $e) {
            Log::error('Fortune Affiliate: enroll ใต้ sponsor ล้มเหลว', [
                'user_id' => $user->id,
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ============================================================
    // Flex Messages
    // ============================================================

    /**
     * Flex Message #1: ข้อความต้อนรับ + รหัสสมาชิก + ปุ่ม LINE Login
     */
    protected function sendWelcomeFlexMessage(
        string $lineUserId,
        User $user,
        MlmMember $member,
        LineFortuneService $lineService
    ): void {
        $loginUrl = url('/auth/line');
        $primaryColor = $this->settings->getLineFlexPrimaryColor();

        $flex = [
            'type' => 'bubble',
            'size' => 'mega',
            'hero' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '🎉', 'size' => '4xl', 'align' => 'center'],
                ],
                'backgroundColor' => $primaryColor,
                'paddingAll' => 'lg',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => 'ยินดีต้อนรับสู่ทีม Thaiprompt!',
                        'weight' => 'bold',
                        'size' => 'lg',
                        'color' => $primaryColor,
                    ],
                    [
                        'type' => 'text',
                        'text' => 'คุณ'.($user->name ?? 'User').' เป็นสมาชิกแล้ว',
                        'size' => 'sm',
                        'color' => '#999999',
                        'margin' => 'md',
                    ],
                    ['type' => 'separator', 'margin' => 'lg'],
                    // รหัสสมาชิก
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '🆔', 'size' => 'sm', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'รหัสสมาชิก: '.($member->member_code ?? '-'), 'size' => 'sm', 'color' => '#333333', 'flex' => 5, 'weight' => 'bold'],
                                ],
                            ],
                            // 🔒 (2026-07-16) เอาบรรทัด "รหัสผ่าน: 12345678" ออก
                            //    เดิมบอทประกาศรหัสผ่านให้ลูกค้าเอง และทุกบัญชีใช้รหัสเดียวกัน
                            //    ตอนนี้รหัสถูกสุ่มทิ้ง — เข้าระบบผ่านปุ่ม LINE ด้านล่างอย่างเดียว
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => '🔒 เข้าระบบด้วยปุ่มด้านล่างได้เลย ไม่ต้องใช้รหัสผ่าน',
                        'size' => 'xs',
                        'color' => '#888888',
                        'margin' => 'md',
                        'wrap' => true,
                    ],
                    ['type' => 'separator', 'margin' => 'lg'],
                    [
                        'type' => 'text',
                        'text' => '📝 เข้าระบบเพื่อเพิ่มข้อมูล:',
                        'size' => 'sm',
                        'color' => '#aaaaaa',
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'text',
                        'text' => '• อีเมล  • เบอร์โทร  • เปลี่ยนรหัสผ่าน',
                        'size' => 'xs',
                        'color' => '#999999',
                        'margin' => 'sm',
                        'wrap' => true,
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'uri',
                            'label' => '🚀 เข้าสู่ระบบด้วย LINE',
                            'uri' => $loginUrl,
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => '✅ เข้าได้ทันที ไม่ต้องใช้รหัสผ่าน',
                        'size' => 'xxs',
                        'color' => '#999999',
                        'align' => 'center',
                        'margin' => 'md',
                    ],
                ],
            ],
        ];

        $lineService->sendRichMessage($lineUserId, [
            'alt_text' => '🎉 ยินดีต้อนรับสู่ทีม Thaiprompt!',
            'contents' => $flex,
        ]);
    }

    /**
     * Flex Message #2: ชวนเพื่อนดูดวง → รับคอมมิชชั่น + ตัวอย่างรายได้
     */
    protected function sendAffiliateInviteFlexMessage(
        string $lineUserId,
        User $user,
        string $referralLink,
        LineFortuneService $lineService
    ): void {
        $primaryColor = $this->settings->getLineFlexPrimaryColor();
        $mode = $this->settings->getFortuneCommissionMode();
        $appUrl = config('app.url', 'https://main.thaiprompt.online');

        // ดึงค่าคอมมิชชั่นจาก settings (dynamic)
        // 🐛 (2026-07-15) เดิมอ่าน getFortuneStaticCommissionAmount() = คอลัมน์ "ประตู"
        //    ไม่ใช่คอลัมน์ที่จ่ายจริง + ไม่รู้จักอัตราแยกแพคเกจ → ใช้ helper กลางแทน
        if ($mode === 'static') {
            $commissionText = $this->settings->fortuneLevel1Text(true);
            // ตัวเลขที่เอาไปคูณเป็นตัวอย่างรายได้ — อิงเรต Celtic (แพคเกจหลักที่เราเชียร์)
            // แล้วกำกับไว้ให้ชัดว่าเป็นเคสไหน ห้ามปล่อยให้เข้าใจว่าได้เท่านี้ทุกแพคเกจ
            $commissionAmount = $this->settings->isFortunePackageRatesEnabled()
                ? $this->settings->getFortuneLevel1Amount(0, \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS)
                : $this->settings->getFortuneLevel1Amount((float) ($this->settings->deep_reading_price ?? 0));
            $exampleNote = $this->settings->isFortunePackageRatesEnabled() ? ' (ถ้าเลือก Celtic)' : '';
        } else {
            $preview = $this->settings->calculateFortuneCommissionPreview();
            $level1 = $preview['levels'][0] ?? null;
            $commissionAmount = $level1 ? $level1['amount'] : 0;
            $commissionText = number_format($commissionAmount, 2).' บาท';
        }

        // ข้อความหลัก — ดึงจาก settings หรือใช้ default ที่แปรผันตามค่าคอมมิชชั่น
        $inviteMessage = ! empty($this->settings->fortune_affiliate_invite_message)
            ? $this->settings->fortune_affiliate_invite_message
            : "🌟 แชร์ลิงก์ให้คนอื่น หากเขาดูดวง คุณจะได้รับบิลละ {$commissionText} เข้า Wallet ตลอดไป!";

        $flex = [
            'type' => 'bubble',
            'size' => 'mega',
            'hero' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '💰', 'size' => '4xl', 'align' => 'center'],
                ],
                'backgroundColor' => '#FFB800',
                'paddingAll' => 'lg',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "แชร์ให้เพื่อน รับ {$commissionText} ทุกบิล!",
                        'weight' => 'bold',
                        'size' => 'lg',
                        'color' => '#333333',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => $inviteMessage,
                        'size' => 'xs',
                        'color' => '#888888',
                        'margin' => 'md',
                        'wrap' => true,
                    ],
                    ['type' => 'separator', 'margin' => 'lg'],
                    [
                        'type' => 'text',
                        'text' => '📊 รายได้ของคุณ',
                        'weight' => 'bold',
                        'size' => 'sm',
                        'color' => $primaryColor,
                        'margin' => 'lg',
                    ],
                    [
                        'type' => 'text',
                        'text' => "💎 เพื่อน 1 คนดูดวง = คุณได้ {$commissionText}",
                        'size' => 'xs',
                        'color' => '#333333',
                        'weight' => 'bold',
                        'margin' => 'md',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => '🎯 ชวน 10 คน × ดูดวง 3 ครั้ง = '.number_format($commissionAmount * 10 * 3, 0).' บาท!'.$exampleNote,
                        'size' => 'xs',
                        'color' => '#333333',
                        'margin' => 'sm',
                        'wrap' => true,
                    ],
                    [
                        'type' => 'text',
                        'text' => '✅ ได้เงินเข้า Wallet ทุกครั้งที่เพื่อนดูดวง ตลอดไป!',
                        'size' => 'xs',
                        'color' => '#06C755',
                        'margin' => 'sm',
                        'wrap' => true,
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'uri',
                            'label' => '📢 แชร์ลิงก์เชิญเพื่อน',
                            'uri' => $referralLink,
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📖 ดูวิธีใช้และรายละเอียดเพิ่มเติม',
                            'uri' => $appUrl.'/user/dashboard',
                        ],
                    ],
                ],
            ],
        ];

        $lineService->sendRichMessage($lineUserId, [
            'alt_text' => "💰 แชร์ให้เพื่อน รับ {$commissionText} ทุกบิลตลอดไป!",
            'contents' => $flex,
        ]);
    }

    // ============================================================
    // Referral Link
    // ============================================================

    /**
     * สร้างลิงก์เชิญเพื่อนพร้อม referral tracking + MlmProspect
     *
     * สร้าง FortuneReferral คู่กับ MlmProspect เพื่อ:
     * 1. จับคู่ 100% ผ่าน LINE deep link (ref_{token})
     * 2. แสดงในหน้า "ผู้มุ่งหวัง" ของ user
     * 3. หมดอายุ 24 ชม.
     */
    public function generateReferralLink(User $user, ?MlmMember $member = null): string
    {
        // หา member ถ้าไม่ได้ส่งมา
        if (! $member) {
            $member = MlmMember::where('user_id', $user->id)->first();
        }

        // สร้าง MlmProspect คู่กัน (แสดงในหน้าผู้มุ่งหวัง)
        $prospect = null;
        if ($member) {
            $prospect = MlmProspect::create([
                'sponsor_mlm_member_id' => $member->id,
                'sponsor_user_id' => $user->id,
                'referral_token' => FortuneReferral::generateToken(),
                'status' => 'pending',
                'is_locked' => false,
                'notes' => 'จากระบบเชิญเพื่อนดูดวง',
            ]);
        }

        // สร้าง FortuneReferral record (หมดอายุ 24 ชม.) + เชื่อมกับ prospect
        $referral = FortuneReferral::createForUser($user, $member, $prospect?->id);

        // อัพเดท invitation_url ใน prospect
        if ($prospect) {
            $prospect->update([
                'referral_token' => $referral->referral_token,
                'invitation_url' => url('/fortune/invite/'.$referral->referral_token),
            ]);
        }

        return url('/fortune/invite/'.$referral->referral_token);
    }

    // ============================================================
    // Post-Reading Affiliate Promotion — ส่งทุกครั้งหลังดูดวงเสร็จ
    // ============================================================

    /**
     * ส่งข้อความโปรโมทระบบ affiliate หลังดูดวงเสร็จ (ทุกครั้ง)
     *
     * แตกต่างจาก sendAffiliateInviteFlexMessage() ที่ส่งเฉพาะตอน auto-register (ครั้งแรก)
     * method นี้ส่ง **ทุกครั้ง** ที่ดูดวงเสร็จ เพื่อจูงใจให้แชร์ต่อ
     *
     * แสดง:
     * - ค่าแนะนำดึงจาก settings (ไม่ hardcode)
     * - ตัวอย่างรายได้ (เพื่อน 1 คน, ชวน 10 คน)
     * - ปุ่มแชร์ลิงก์ referral
     * - ปุ่มเข้าเว็บ affiliate
     * - ข้อมูลถอนเงินที่เว็บ (KYC)
     *
     * @param  FortuneReading  $reading  บันทึกการดูดวง
     * @param  string  $platformUserId  User ID ของ platform
     * @param  LineFortuneService|null  $lineService  LINE service
     * @param  string|null  $platform  ชื่อ platform
     */
    public function sendPostReadingAffiliatePromotion(
        FortuneReading $reading,
        string $platformUserId,
        ?LineFortuneService $lineService = null,
        ?string $platform = null
    ): void {
        // ตรวจว่าเปิดระบบ affiliate หรือไม่
        if (! $this->settings->isFortuneAffiliateEnabled()) {
            return;
        }

        // ปัจจุบันรองรับเฉพาะ LINE (platform อื่นขยายภายหลัง)
        $platform = $platform ?? $reading->platform ?? 'facebook';
        if ($platform !== 'line' || ! $lineService) {
            return;
        }

        // ✅ จำกัดความถี่: ส่ง promo ไม่เกิน 1 ครั้ง / 24 ชม. ต่อ user
        $cacheKey = "fortune:affiliate_promo:{$platformUserId}";
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            Log::debug('Fortune Promo: ข้ามเพราะเพิ่งส่งไปไม่ถึง 24 ชม.', [
                'line_user_id' => $platformUserId,
            ]);

            return;
        }

        try {
            // หา User จาก platform user ID
            $user = User::where('line_user_id', $platformUserId)->first();
            if (! $user) {
                Log::debug('Fortune Promo: ไม่พบ User จาก line_user_id ข้ามการส่ง promo', [
                    'line_user_id' => $platformUserId,
                ]);

                return;
            }

            // หา MlmMember
            $member = MlmMember::where('user_id', $user->id)->first();
            if (! $member) {
                Log::debug('Fortune Promo: ไม่พบ MlmMember ข้ามการส่ง promo', [
                    'user_id' => $user->id,
                ]);

                return;
            }

            // สร้าง/ดึง referral link
            $referralLink = $this->generateReferralLink($user, $member);

            // ส่ง Flex Message โปรโมท
            $this->sendPostReadingPromoFlexMessage(
                $platformUserId,
                $user,
                $referralLink,
                $lineService
            );

            // ✅ บันทึกว่าส่งแล้ว (ภายใน 24 ชม. จะไม่ส่งซ้ำ)
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(24));

            Log::info('Fortune Promo: ส่งข้อความโปรโมทหลังดูดวงสำเร็จ', [
                'user_id' => $user->id,
                'reading_id' => $reading->id,
            ]);

        } catch (\Exception $e) {
            // ไม่ให้ error กระทบ flow หลัก
            Log::warning('Fortune Promo: ส่งข้อความโปรโมทล้มเหลว', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Flex Message: โปรโมทหลังดูดวงเสร็จ — แสดงค่าแนะนำ + ตัวอย่างรายได้
     *
     * ดึงค่าคอมมิชชั่นจาก settings:
     * - mode static → getFortuneStaticCommissionAmount()
     * - mode pv → calculateFortuneCommissionPreview() → Level 1 amount
     */
    protected function sendPostReadingPromoFlexMessage(
        string $lineUserId,
        User $user,
        string $referralLink,
        LineFortuneService $lineService
    ): void {
        $primaryColor = $this->settings->getLineFlexPrimaryColor();
        $mode = $this->settings->getFortuneCommissionMode();
        $appUrl = config('app.url', 'https://main.thaiprompt.online');

        // ดึงค่าคอมมิชชั่นจาก settings (dynamic — แปรผันตามที่ตั้งค่าจริง)
        // 🐛 (2026-07-15) เดิมอ่านคอลัมน์ "ประตู" + ไม่รู้จักอัตราแยกแพคเกจ → ใช้ helper กลาง
        if ($mode === 'static') {
            $commissionText = $this->settings->fortuneLevel1Text(true);
            // ตัวเลขที่เอาไปคูณเป็นตัวอย่างรายได้ — อิงเรต Celtic (แพคเกจหลักที่เราเชียร์)
            // แล้วกำกับไว้ให้ชัดว่าเป็นเคสไหน ห้ามปล่อยให้เข้าใจว่าได้เท่านี้ทุกแพคเกจ
            $commissionAmount = $this->settings->isFortunePackageRatesEnabled()
                ? $this->settings->getFortuneLevel1Amount(0, \App\Models\FortuneReading::READING_TYPE_CELTIC_CROSS)
                : $this->settings->getFortuneLevel1Amount((float) ($this->settings->deep_reading_price ?? 0));
            $exampleNote = $this->settings->isFortunePackageRatesEnabled() ? ' (ถ้าเลือก Celtic)' : '';
        } else {
            // PV mode: ใช้ Level 1 amount เป็นตัวอย่าง
            $preview = $this->settings->calculateFortuneCommissionPreview();
            $level1 = $preview['levels'][0] ?? null;
            $commissionAmount = $level1 ? $level1['amount'] : 0;
            $commissionText = number_format($commissionAmount, 2).' บาท';
        }

        // คำนวณตัวอย่างรายได้
        $example10Friends = number_format($commissionAmount * 10 * 3, 0);

        // สร้างเนื้อหาตัวอย่างรายได้
        $earningExamples = [
            [
                'type' => 'text',
                'text' => "💎 เพื่อน 1 คนดูดวง = คุณได้ {$commissionText}",
                'size' => 'xs',
                'color' => '#333333',
                'weight' => 'bold',
                'margin' => 'md',
                'wrap' => true,
            ],
            [
                'type' => 'text',
                'text' => "🎯 ชวน 10 คน × ดูดวง 3 ครั้ง = {$example10Friends} บาท!{$exampleNote}",
                'size' => 'xs',
                'color' => '#333333',
                'margin' => 'sm',
                'wrap' => true,
            ],
            [
                'type' => 'text',
                'text' => '✅ ได้เงินเข้า Wallet ทุกครั้งที่เพื่อนดูดวง ตลอดไป!',
                'size' => 'xs',
                'color' => '#06C755',
                'margin' => 'sm',
                'wrap' => true,
            ],
        ];

        // ถ้า PV mode → แสดง multi-level preview
        if ($mode === 'pv') {
            $preview = $preview ?? $this->settings->calculateFortuneCommissionPreview();
            if (count($preview['levels'] ?? []) > 1) {
                $earningExamples[] = ['type' => 'separator', 'margin' => 'md'];
                $levelsToShow = min(3, count($preview['levels']));
                for ($i = 0; $i < $levelsToShow; $i++) {
                    $level = $preview['levels'][$i];
                    $earningExamples[] = [
                        'type' => 'text',
                        'text' => "Level {$level['level']} ({$level['percentage']}%): ".number_format($level['amount'], 2).' บาท',
                        'size' => 'xs',
                        'color' => $i === 0 ? '#333333' : '#888888',
                        'weight' => $i === 0 ? 'bold' : 'regular',
                        'margin' => 'sm',
                    ];
                }
            }
        }

        $flex = [
            'type' => 'bubble',
            'size' => 'mega',
            'hero' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '💰', 'size' => '4xl', 'align' => 'center'],
                ],
                'backgroundColor' => '#FFB800',
                'paddingAll' => 'lg',
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => array_merge(
                    [
                        [
                            'type' => 'text',
                            'text' => "แชร์ให้เพื่อน รับ {$commissionText} ทุกบิล!",
                            'weight' => 'bold',
                            'size' => 'lg',
                            'color' => '#333333',
                            'wrap' => true,
                        ],
                        [
                            'type' => 'text',
                            'text' => "แชร์ลิงก์ให้คนอื่น หากเขาดูดวง คุณจะได้รับบิลละ {$commissionText} เข้า Wallet ตลอดไป!",
                            'size' => 'xs',
                            'color' => '#888888',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'text',
                            'text' => '📊 ตัวอย่างรายได้',
                            'weight' => 'bold',
                            'size' => 'sm',
                            'color' => $primaryColor,
                            'margin' => 'lg',
                        ],
                    ],
                    $earningExamples,
                    [
                        ['type' => 'separator', 'margin' => 'lg'],
                        [
                            'type' => 'text',
                            'text' => '💸 ถอนเงินได้ที่เว็บไซต์ ดูวิธีใช้และรายละเอียดเพิ่มเติมได้ที่เว็บ',
                            'size' => 'xxs',
                            'color' => '#AAAAAA',
                            'margin' => 'md',
                            'wrap' => true,
                        ],
                    ]
                ),
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => $primaryColor,
                        'action' => [
                            'type' => 'uri',
                            'label' => '📢 แชร์ลิงก์เชิญเพื่อน',
                            'uri' => $referralLink,
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#06C755',
                        'action' => [
                            'type' => 'uri',
                            'label' => '📖 ดูวิธีใช้และรายละเอียดเพิ่มเติม',
                            'uri' => $appUrl.'/user/dashboard',
                        ],
                    ],
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'action' => [
                            'type' => 'message',
                            'label' => '🔮 ดูดวงอีกครั้ง',
                            'text' => 'ดูดวง',
                        ],
                    ],
                ],
            ],
        ];

        $lineService->sendRichMessage($lineUserId, [
            'alt_text' => "💰 แชร์ให้เพื่อน รับ {$commissionText} ทุกบิลตลอดไป!",
            'contents' => $flex,
        ]);
    }
}
