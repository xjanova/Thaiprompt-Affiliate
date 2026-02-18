<?php

namespace App\Services;

use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     * @param FortuneReading $reading บันทึกการดูดวง
     * @param string $platformUserId User ID ของ platform (LINE/Facebook/etc.)
     * @param LineFortuneService|null $lineService LINE service (เฉพาะ LINE platform)
     * @param string|null $platform ชื่อ platform: 'line', 'facebook', etc.
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
                // User มีอยู่แล้ว — link FortuneReading เท่านั้น
                if (! $reading->user_id) {
                    $reading->update(['user_id' => $existingUser->id]);
                }

                Log::info('Fortune Affiliate: User มีอยู่แล้ว ข้ามการสร้าง', [
                    'user_id' => $existingUser->id,
                    'platform' => $platform,
                    'platform_user_id' => $platformUserId,
                ]);

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
        $userData = [
            'name' => $profile['name'] ?? 'User',
            'email' => $email,
            'password' => Hash::make('12345678'),
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

            // หา default MLM plan
            $defaultPlan = MlmPlan::where('is_default', true)->first();
            if (! $defaultPlan) {
                Log::warning('Fortune Affiliate: ไม่พบ default MLM plan — ข้าม MLM enrollment');

                return null;
            }

            // หาตำแหน่ง binary แบบ auto-placement
            $binaryService = app(MlmBinaryService::class);
            $placement = $binaryService->findPlacementPosition($sponsor);

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
                'binary_parent_id' => $placement['parent_id'],
                'binary_position' => $placement['position'],
                // Status
                'status' => 'active',
                'joined_at' => now(),
                'member_code' => MlmMember::generateMemberCode(),
                'is_qualified' => true,
            ]);

            // Update sponsor stats
            $sponsor->increment('total_direct_referrals');

            // Update binary parent stats
            $binaryParent = MlmMember::find($placement['parent_id']);
            if ($binaryParent) {
                if ($placement['position'] === 'left') {
                    $binaryParent->increment('left_leg_members');
                } else {
                    $binaryParent->increment('right_leg_members');
                }
            }

            // Mark referral as converted
            if ($referral) {
                $referral->markAsConverted($user);
            }

            Log::info('Fortune Affiliate: สร้าง MlmMember สำเร็จ', [
                'member_id' => $member->id,
                'member_code' => $member->member_code,
                'sponsor_id' => $sponsor->id,
                'binary_position' => $placement['position'],
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
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'contents' => [
                                    ['type' => 'text', 'text' => '🔒', 'size' => 'sm', 'flex' => 1],
                                    ['type' => 'text', 'text' => 'รหัสผ่าน: 12345678', 'size' => 'sm', 'color' => '#FF6B6B', 'flex' => 5],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => '⚠️ กรุณาเปลี่ยนรหัสผ่านทันทีเมื่อเข้าระบบ',
                        'size' => 'xs',
                        'color' => '#FF6B6B',
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
        $preview = $this->settings->calculateFortuneCommissionPreview();
        $price = $preview['price'];

        // สร้างข้อความตัวอย่างรายได้ (แสดง 3 levels)
        $earningContents = [];
        $earningContents[] = [
            'type' => 'text',
            'text' => "เพื่อนจ่ายดูดวง {$price} บาท คุณได้รับ:",
            'size' => 'xs',
            'color' => '#666666',
            'wrap' => true,
            'margin' => 'md',
        ];

        $levelsToShow = min(3, count($preview['levels'] ?? []));
        for ($i = 0; $i < $levelsToShow; $i++) {
            $level = $preview['levels'][$i];
            $earningContents[] = [
                'type' => 'text',
                'text' => "Level {$level['level']} ({$level['percentage']}%): {$level['amount']} บาท",
                'size' => 'xs',
                'color' => $i === 0 ? '#333333' : '#888888',
                'weight' => $i === 0 ? 'bold' : 'regular',
                'margin' => 'sm',
            ];
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
                            'text' => 'ชวนเพื่อนดูดวง รับรายได้!',
                            'weight' => 'bold',
                            'size' => 'lg',
                            'color' => '#333333',
                        ],
                        [
                            'type' => 'text',
                            'text' => 'แชร์ลิงก์ให้เพื่อน → เพื่อนดูดวง → คุณได้คอมมิชชั่นทุกครั้ง',
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
                    $earningContents
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
                ],
            ],
        ];

        $lineService->sendRichMessage($lineUserId, [
            'alt_text' => '💰 ชวนเพื่อนดูดวง รับรายได้!',
            'contents' => $flex,
        ]);
    }

    // ============================================================
    // Referral Link
    // ============================================================

    /**
     * สร้างลิงก์เชิญเพื่อนพร้อม referral tracking
     */
    public function generateReferralLink(User $user, ?MlmMember $member = null): string
    {
        // หา member ถ้าไม่ได้ส่งมา
        if (! $member) {
            $member = MlmMember::where('user_id', $user->id)->first();
        }

        // สร้าง FortuneReferral record
        $referral = FortuneReferral::createForUser($user, $member);

        return url('/fortune/invite/'.$referral->referral_token);
    }
}
