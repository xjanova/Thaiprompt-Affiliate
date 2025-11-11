<?php

namespace App\Services;

use App\Models\User;
use App\Models\MlmProspect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LINE Achievement & Badge System
 *
 * Gamification features for LINE OA signup
 * - Achievement tracking
 * - Badge earning
 * - Milestone celebrations
 * - Leaderboard integration
 * - Progress rewards
 */
class LineAchievementService
{
    private const CACHE_PREFIX = 'line:achievement:';
    private const CACHE_TTL = 86400; // 24 hours

    // Achievement definitions
    private const ACHIEVEMENTS = [
        // Signup achievements
        'first_signup' => [
            'name' => 'นักสรรหาคนแรก',
            'description' => 'สรรหาสมาชิกคนแรกสำเร็จ',
            'icon' => '🌟',
            'points' => 100,
            'category' => 'signup',
        ],
        'fast_signup' => [
            'name' => 'สายฟ้าแลบ',
            'description' => 'สมัครสมาชิกสำเร็จภายใน 3 นาที',
            'icon' => '⚡',
            'points' => 50,
            'category' => 'signup',
        ],
        'perfect_signup' => [
            'name' => 'ไร้ที่ติ',
            'description' => 'กรอกข้อมูลถูกต้องครั้งแรก ทุกช่อง',
            'icon' => '💯',
            'points' => 150,
            'category' => 'signup',
        ],

        // Team building achievements
        'team_5' => [
            'name' => 'ผู้นำทีมเล็ก',
            'description' => 'สรรหาสมาชิก 5 คนสำเร็จ',
            'icon' => '👥',
            'points' => 200,
            'category' => 'team',
        ],
        'team_10' => [
            'name' => 'ผู้นำทีมใหญ่',
            'description' => 'สรรหาสมาชิก 10 คนสำเร็จ',
            'icon' => '👨‍👩‍👧‍👦',
            'points' => 500,
            'category' => 'team',
        ],
        'team_25' => [
            'name' => 'สร้างกองทัพ',
            'description' => 'สรรหาสมาชิก 25 คนสำเร็จ',
            'icon' => '🏆',
            'points' => 1000,
            'category' => 'team',
        ],
        'team_50' => [
            'name' => 'ผู้สร้างอาณาจักร',
            'description' => 'สรรหาสมาชิก 50 คนสำเร็จ',
            'icon' => '👑',
            'points' => 2500,
            'category' => 'team',
        ],
        'team_100' => [
            'name' => 'ตำนานนักสรรหา',
            'description' => 'สรรหาสมาชิก 100 คนสำเร็จ',
            'icon' => '🌈',
            'points' => 5000,
            'category' => 'team',
        ],

        // Streak achievements
        'streak_3' => [
            'name' => 'ต่อเนื่อง 3 วัน',
            'description' => 'สรรหาสมาชิกต่อเนื่อง 3 วันติดต่อกัน',
            'icon' => '🔥',
            'points' => 150,
            'category' => 'streak',
        ],
        'streak_7' => [
            'name' => 'ต่อเนื่อง 1 สัปดาห์',
            'description' => 'สรรหาสมาชิกต่อเนื่อง 7 วันติดต่อกัน',
            'icon' => '🔥',
            'points' => 350,
            'category' => 'streak',
        ],
        'streak_30' => [
            'name' => 'ต่อเนื่อง 1 เดือน',
            'description' => 'สรรหาสมาชิกต่อเนื่อง 30 วันติดต่อกัน',
            'icon' => '🔥',
            'points' => 1000,
            'category' => 'streak',
        ],

        // Quality achievements
        'zero_dropout' => [
            'name' => 'คุณภาพสูง',
            'description' => 'ไม่มีผู้สมัครหลุดออก (10 คนแรก)',
            'icon' => '💎',
            'points' => 300,
            'category' => 'quality',
        ],
        'helper' => [
            'name' => 'ผู้ช่วยเหลือ',
            'description' => 'ช่วยเหลือผู้สมัคร 20 ครั้ง',
            'icon' => '🤝',
            'points' => 200,
            'category' => 'quality',
        ],

        // Special achievements
        'night_owl' => [
            'name' => 'นกฮูก',
            'description' => 'สรรหาสมาชิกหลัง 22:00 น.',
            'icon' => '🦉',
            'points' => 100,
            'category' => 'special',
        ],
        'early_bird' => [
            'name' => 'นกกางเขน',
            'description' => 'สรรหาสมาชิกก่อน 06:00 น.',
            'icon' => '🐦',
            'points' => 100,
            'category' => 'special',
        ],
        'weekend_warrior' => [
            'name' => 'นักรบวันหยุด',
            'description' => 'สรรหาสมาชิก 5 คนในวันหยุด',
            'icon' => '⚔️',
            'points' => 150,
            'category' => 'special',
        ],
    ];

    public function __construct(
        private LineFlexMessageService $flexMessageService,
        private LineService $lineService
    ) {}

    /**
     * Check and award achievements for completed signup
     */
    public function checkSignupAchievements(User $sponsor, MlmProspect $prospect): array
    {
        $achievements = [];

        // Check first signup
        if ($this->isFirstSignup($sponsor)) {
            $achievements[] = 'first_signup';
        }

        // Check fast signup (< 3 minutes)
        if ($this->isFastSignup($prospect)) {
            $achievements[] = 'fast_signup';
        }

        // Check perfect signup (no errors)
        if ($this->isPerfectSignup($prospect)) {
            $achievements[] = 'perfect_signup';
        }

        // Check team size achievements
        $teamSize = $this->getTeamSize($sponsor);
        foreach ([100, 50, 25, 10, 5] as $size) {
            if ($teamSize === $size && !in_array("team_{$size}", $achievements)) {
                $achievements[] = "team_{$size}";
                break;
            }
        }

        // Check streak achievements
        $streak = $this->getSignupStreak($sponsor);
        foreach ([30, 7, 3] as $days) {
            if ($streak === $days && !in_array("streak_{$days}", $achievements)) {
                $achievements[] = "streak_{$days}";
                break;
            }
        }

        // Check special time-based achievements
        $hour = now()->hour;
        if ($hour >= 22 || $hour < 6) {
            if ($hour >= 22) {
                $achievements[] = 'night_owl';
            } else {
                $achievements[] = 'early_bird';
            }
        }

        // Check weekend warrior
        if (now()->isWeekend() && $this->getWeekendSignups($sponsor) === 5) {
            $achievements[] = 'weekend_warrior';
        }

        // Award new achievements
        $newAchievements = [];
        foreach ($achievements as $achievementKey) {
            if (!$this->hasAchievement($sponsor, $achievementKey)) {
                $this->awardAchievement($sponsor, $achievementKey);
                $newAchievements[] = $achievementKey;
            }
        }

        // Send celebration messages
        foreach ($newAchievements as $achievementKey) {
            $this->sendAchievementMessage($sponsor, $achievementKey);
        }

        return $newAchievements;
    }

    /**
     * Award achievement to user
     */
    private function awardAchievement(User $sponsor, string $achievementKey): void
    {
        $achievement = self::ACHIEVEMENTS[$achievementKey] ?? null;

        if (!$achievement) {
            return;
        }

        $userAchievements = $this->getUserAchievements($sponsor);
        $userAchievements[] = [
            'key' => $achievementKey,
            'earned_at' => now()->toIso8601String(),
            'points' => $achievement['points'],
        ];

        // Update cache
        $cacheKey = $this->getCacheKey($sponsor->id);
        Cache::put($cacheKey, $userAchievements, now()->addSeconds(self::CACHE_TTL));

        // Update user total points (if you have a points system)
        // $sponsor->increment('achievement_points', $achievement['points']);

        Log::info('Achievement awarded', [
            'user_id' => $sponsor->id,
            'achievement' => $achievementKey,
            'points' => $achievement['points'],
        ]);
    }

    /**
     * Send achievement celebration message
     */
    private function sendAchievementMessage(User $sponsor, string $achievementKey): void
    {
        $achievement = self::ACHIEVEMENTS[$achievementKey] ?? null;

        if (!$achievement || !$sponsor->line_user_id) {
            return;
        }

        $message = [
            'type' => 'flex',
            'altText' => "🎉 คุณได้รับ Achievement: {$achievement['name']}!",
            'contents' => [
                'type' => 'bubble',
                'size' => 'mega',
                'header' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $achievement['icon'],
                            'size' => '5xl',
                            'align' => 'center',
                        ],
                    ],
                    'backgroundColor' => '#FFD43B',
                    'height' => '100px',
                    'paddingAll' => 'xl',
                ],
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => 'Achievement ใหม่!',
                            'size' => 'sm',
                            'color' => '#999999',
                        ],
                        [
                            'type' => 'text',
                            'text' => $achievement['name'],
                            'weight' => 'bold',
                            'size' => 'xl',
                            'color' => '#FFD43B',
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'text',
                            'text' => $achievement['description'],
                            'size' => 'sm',
                            'color' => '#666666',
                            'wrap' => true,
                            'margin' => 'md',
                        ],
                        [
                            'type' => 'separator',
                            'margin' => 'xl',
                        ],
                        [
                            'type' => 'box',
                            'layout' => 'baseline',
                            'margin' => 'lg',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '⭐ คะแนนที่ได้รับ',
                                    'size' => 'sm',
                                    'color' => '#999999',
                                    'flex' => 0,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => '+' . number_format($achievement['points']),
                                    'size' => 'lg',
                                    'weight' => 'bold',
                                    'color' => '#FFD43B',
                                    'align' => 'end',
                                ],
                            ],
                        ],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => [
                        [
                            'type' => 'button',
                            'action' => [
                                'type' => 'message',
                                'label' => 'ดู Achievement ทั้งหมด',
                                'text' => 'แสดง achievements',
                            ],
                            'style' => 'link',
                        ],
                    ],
                ],
            ],
        ];

        $this->lineService->sendFlexMessage($sponsor->line_user_id, $message);
    }

    /**
     * Get all achievements for user
     */
    public function getUserAchievements(User $user): array
    {
        $cacheKey = $this->getCacheKey($user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            // In real implementation, fetch from database
            return [];
        });
    }

    /**
     * Check if user has achievement
     */
    private function hasAchievement(User $user, string $achievementKey): bool
    {
        $achievements = $this->getUserAchievements($user);

        foreach ($achievements as $achievement) {
            if ($achievement['key'] === $achievementKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total achievement points
     */
    public function getTotalPoints(User $user): int
    {
        $achievements = $this->getUserAchievements($user);
        $total = 0;

        foreach ($achievements as $achievement) {
            $total += $achievement['points'] ?? 0;
        }

        return $total;
    }

    /**
     * Create achievements display message
     */
    public function createAchievementsDisplay(User $user): array
    {
        $userAchievements = $this->getUserAchievements($user);
        $totalPoints = $this->getTotalPoints($user);

        // Group achievements by category
        $categories = [
            'signup' => [],
            'team' => [],
            'streak' => [],
            'quality' => [],
            'special' => [],
        ];

        foreach ($userAchievements as $earned) {
            $achievement = self::ACHIEVEMENTS[$earned['key']] ?? null;
            if ($achievement) {
                $category = $achievement['category'];
                $categories[$category][] = $achievement;
            }
        }

        // Build Flex Message content
        $contents = [
            [
                'type' => 'text',
                'text' => 'Achievements ของคุณ',
                'weight' => 'bold',
                'size' => 'xl',
            ],
            [
                'type' => 'text',
                'text' => "คะแนนรวม: {$totalPoints} แต้ม",
                'size' => 'sm',
                'color' => '#FFD43B',
                'margin' => 'md',
            ],
            [
                'type' => 'separator',
                'margin' => 'xl',
            ],
        ];

        $categoryNames = [
            'signup' => '📝 การสมัคร',
            'team' => '👥 สร้างทีม',
            'streak' => '🔥 ต่อเนื่อง',
            'quality' => '💎 คุณภาพ',
            'special' => '⭐ พิเศษ',
        ];

        foreach ($categories as $category => $achievements) {
            if (empty($achievements)) {
                continue;
            }

            $contents[] = [
                'type' => 'text',
                'text' => $categoryNames[$category],
                'weight' => 'bold',
                'margin' => 'xl',
            ];

            foreach ($achievements as $achievement) {
                $contents[] = [
                    'type' => 'box',
                    'layout' => 'baseline',
                    'margin' => 'md',
                    'contents' => [
                        [
                            'type' => 'text',
                            'text' => $achievement['icon'],
                            'flex' => 0,
                        ],
                        [
                            'type' => 'text',
                            'text' => $achievement['name'],
                            'size' => 'sm',
                            'flex' => 4,
                        ],
                        [
                            'type' => 'text',
                            'text' => "+{$achievement['points']}",
                            'size' => 'sm',
                            'color' => '#FFD43B',
                            'align' => 'end',
                            'flex' => 1,
                        ],
                    ],
                ];
            }
        }

        return [
            'type' => 'flex',
            'altText' => 'Achievements ของคุณ',
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'contents' => $contents,
                ],
            ],
        ];
    }

    // Helper methods for achievement checks

    private function isFirstSignup(User $sponsor): bool
    {
        return MlmProspect::where('sponsor_user_id', $sponsor->id)
            ->where('status', 'completed')
            ->count() === 1;
    }

    private function isFastSignup(MlmProspect $prospect): bool
    {
        if (!$prospect->conversation_started_at) {
            return false;
        }

        $minutes = $prospect->conversation_started_at->diffInMinutes($prospect->conversation_updated_at);
        return $minutes <= 3;
    }

    private function isPerfectSignup(MlmProspect $prospect): bool
    {
        return ($prospect->conversation_retry_count ?? 0) === 0;
    }

    private function getTeamSize(User $sponsor): int
    {
        return MlmProspect::where('sponsor_user_id', $sponsor->id)
            ->where('status', 'completed')
            ->count();
    }

    private function getSignupStreak(User $sponsor): int
    {
        // Calculate consecutive days with at least one signup
        // This is a simplified version
        return 0; // Implement based on your needs
    }

    private function getWeekendSignups(User $sponsor): int
    {
        $startOfWeekend = now()->startOfWeek()->addDays(5); // Saturday
        $endOfWeekend = now()->startOfWeek()->addDays(6)->endOfDay(); // Sunday

        return MlmProspect::where('sponsor_user_id', $sponsor->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startOfWeekend, $endOfWeekend])
            ->count();
    }

    private function getCacheKey(int $userId): string
    {
        return self::CACHE_PREFIX . $userId;
    }
}
