<?php

namespace App\Services;

use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserArticleProgress;
use App\Models\VideoCoin;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CourseProgressionService
 *
 * บริการจัดการระบบเลื่อนคลาสเรียน
 * รวมถึงการตรวจสอบเงื่อนไขการปลดล็อค และการให้รางวัล
 *
 * @package App\Services
 */
class CourseProgressionService
{
    /**
     * เงื่อนไขการปลดล็อค
     */
    public const UNLOCK_NONE = 'none';
    public const UNLOCK_PREREQUISITE = 'prerequisite';
    public const UNLOCK_QUIZ_PASS = 'quiz_pass';
    public const UNLOCK_TIME_BASED = 'time_based';
    public const UNLOCK_MANUAL = 'manual';

    /**
     * ตรวจสอบว่าผู้ใช้สามารถเข้าถึงคอร์สได้หรือไม่
     *
     * @param User $user ผู้ใช้
     * @param LearningArticle $article บทความ/คอร์ส
     * @return array ['can_access' => bool, 'reason' => string, 'requirements' => array]
     */
    public function canAccessArticle(User $user, LearningArticle $article): array
    {
        // ถ้าเป็น admin สามารถเข้าถึงได้ทั้งหมด
        if ($user->role === 'admin' || $user->hasRole('admin')) {
            return [
                'can_access' => true,
                'reason' => 'admin_access',
                'requirements' => [],
            ];
        }

        // ตรวจสอบ permission พื้นฐาน
        if (!$article->userHasAccess($user)) {
            return [
                'can_access' => false,
                'reason' => 'no_permission',
                'requirements' => ['ต้องมีสิทธิ์เข้าถึงคอร์สนี้'],
            ];
        }

        // ตรวจสอบตามเงื่อนไขการปลดล็อค
        switch ($article->unlock_condition ?? self::UNLOCK_NONE) {
            case self::UNLOCK_NONE:
                return [
                    'can_access' => true,
                    'reason' => 'no_condition',
                    'requirements' => [],
                ];

            case self::UNLOCK_PREREQUISITE:
                return $this->checkPrerequisiteCondition($user, $article);

            case self::UNLOCK_QUIZ_PASS:
                return $this->checkQuizPassCondition($user, $article);

            case self::UNLOCK_TIME_BASED:
                return $this->checkTimeBasedCondition($user, $article);

            case self::UNLOCK_MANUAL:
                return $this->checkManualUnlock($user, $article);

            default:
                return [
                    'can_access' => true,
                    'reason' => 'unknown_condition',
                    'requirements' => [],
                ];
        }
    }

    /**
     * ตรวจสอบเงื่อนไข Prerequisite
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array
     */
    protected function checkPrerequisiteCondition(User $user, LearningArticle $article): array
    {
        $requiredPrerequisites = $article->prerequisites()
            ->wherePivot('is_required', true)
            ->get();

        if ($requiredPrerequisites->isEmpty()) {
            return [
                'can_access' => true,
                'reason' => 'no_prerequisites',
                'requirements' => [],
            ];
        }

        $incompletePrerequisites = [];

        foreach ($requiredPrerequisites as $prerequisite) {
            $progress = $prerequisite->progressForUser($user->id);

            if (!$progress || $progress->status !== 'completed') {
                $incompletePrerequisites[] = [
                    'id' => $prerequisite->id,
                    'title' => $prerequisite->title,
                    'slug' => $prerequisite->slug,
                ];
            }

            // ถ้า prerequisite ต้องการให้ผ่าน quiz ด้วย
            if ($prerequisite->require_quiz_pass) {
                $quizResult = $this->checkQuizPassForArticle($user, $prerequisite);
                if (!$quizResult['passed']) {
                    $incompletePrerequisites[] = [
                        'id' => $prerequisite->id,
                        'title' => $prerequisite->title . ' (ต้องผ่านแบบทดสอบ)',
                        'slug' => $prerequisite->slug,
                        'quiz_required' => true,
                    ];
                }
            }
        }

        if (!empty($incompletePrerequisites)) {
            return [
                'can_access' => false,
                'reason' => 'prerequisites_incomplete',
                'requirements' => $incompletePrerequisites,
            ];
        }

        return [
            'can_access' => true,
            'reason' => 'prerequisites_completed',
            'requirements' => [],
        ];
    }

    /**
     * ตรวจสอบเงื่อนไขผ่าน Quiz
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array
     */
    protected function checkQuizPassCondition(User $user, LearningArticle $article): array
    {
        // หา article ก่อนหน้าในหมวดหมู่เดียวกัน
        $previousArticle = LearningArticle::where('category_id', $article->category_id)
            ->where('course_level', '<', $article->course_level ?? 1)
            ->orderBy('course_level', 'desc')
            ->orderBy('order', 'desc')
            ->first();

        if (!$previousArticle) {
            // ถ้าเป็นคอร์สแรก ให้เข้าได้
            return [
                'can_access' => true,
                'reason' => 'first_in_category',
                'requirements' => [],
            ];
        }

        $quizResult = $this->checkQuizPassForArticle($user, $previousArticle);

        if (!$quizResult['passed']) {
            return [
                'can_access' => false,
                'reason' => 'quiz_not_passed',
                'requirements' => [
                    [
                        'type' => 'quiz',
                        'article_id' => $previousArticle->id,
                        'article_title' => $previousArticle->title,
                        'article_slug' => $previousArticle->slug,
                        'min_score' => $previousArticle->min_quiz_score ?? 70,
                        'best_score' => $quizResult['best_score'] ?? 0,
                    ],
                ],
            ];
        }

        return [
            'can_access' => true,
            'reason' => 'quiz_passed',
            'requirements' => [],
        ];
    }

    /**
     * ตรวจสอบการผ่าน Quiz สำหรับบทความ
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array ['passed' => bool, 'best_score' => int, 'attempts' => int]
     */
    public function checkQuizPassForArticle(User $user, LearningArticle $article): array
    {
        $requiredQuizzes = $article->requiredQuizzes;

        if ($requiredQuizzes->isEmpty()) {
            return [
                'passed' => true,
                'best_score' => 100,
                'attempts' => 0,
            ];
        }

        $minScore = $article->min_quiz_score ?? 70;
        $allPassed = true;
        $lowestScore = 100;
        $totalAttempts = 0;

        foreach ($requiredQuizzes as $quiz) {
            $bestAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->completed()
                ->orderBy('percentage', 'desc')
                ->first();

            $attemptCount = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->count();

            $totalAttempts += $attemptCount;

            if (!$bestAttempt || $bestAttempt->percentage < $minScore) {
                $allPassed = false;
            }

            if ($bestAttempt) {
                $lowestScore = min($lowestScore, $bestAttempt->percentage);
            } else {
                $lowestScore = 0;
            }
        }

        return [
            'passed' => $allPassed,
            'best_score' => $lowestScore,
            'attempts' => $totalAttempts,
        ];
    }

    /**
     * ตรวจสอบเงื่อนไขเวลา
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array
     */
    protected function checkTimeBasedCondition(User $user, LearningArticle $article): array
    {
        $unlockDays = $article->unlock_after_days ?? 0;

        if ($unlockDays <= 0) {
            return [
                'can_access' => true,
                'reason' => 'no_time_restriction',
                'requirements' => [],
            ];
        }

        // หาวันที่สมัครหรือเริ่มเรียน
        $startDate = $user->created_at;

        // ถ้ามี progress record ใช้วันที่เริ่มเรียนแรกใน category
        $firstProgress = UserArticleProgress::where('user_id', $user->id)
            ->whereHas('article', function ($query) use ($article) {
                $query->where('category_id', $article->category_id);
            })
            ->orderBy('started_at')
            ->first();

        if ($firstProgress && $firstProgress->started_at) {
            $startDate = $firstProgress->started_at;
        }

        $unlockDate = $startDate->copy()->addDays($unlockDays);

        if (now()->lt($unlockDate)) {
            return [
                'can_access' => false,
                'reason' => 'time_locked',
                'requirements' => [
                    [
                        'type' => 'time',
                        'unlock_date' => $unlockDate->format('Y-m-d H:i:s'),
                        'days_remaining' => now()->diffInDays($unlockDate),
                    ],
                ],
            ];
        }

        return [
            'can_access' => true,
            'reason' => 'time_unlocked',
            'requirements' => [],
        ];
    }

    /**
     * ตรวจสอบการปลดล็อคแบบ Manual
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array
     */
    protected function checkManualUnlock(User $user, LearningArticle $article): array
    {
        // ตรวจสอบจาก metadata ของ user หรือจาก permission table
        $userPermission = $article->permissions()
            ->where('permission_type', 'user')
            ->where('permission_value', $user->id)
            ->first();

        if ($userPermission) {
            return [
                'can_access' => true,
                'reason' => 'manually_unlocked',
                'requirements' => [],
            ];
        }

        return [
            'can_access' => false,
            'reason' => 'manual_unlock_required',
            'requirements' => [
                [
                    'type' => 'manual',
                    'message' => 'คอร์สนี้ต้องได้รับการปลดล็อคจากผู้ดูแลระบบ',
                ],
            ],
        ];
    }

    /**
     * ดึงคอร์สถัดไปที่ปลดล็อคได้
     *
     * @param User $user
     * @param LearningCategory|null $category หมวดหมู่ (ถ้าไม่ระบุจะดึงทั้งหมด)
     * @return Collection
     */
    public function getUnlockedArticles(User $user, ?LearningCategory $category = null): Collection
    {
        $query = LearningArticle::published()->ordered();

        if ($category) {
            $query->where('category_id', $category->id);
        }

        $articles = $query->get();

        return $articles->filter(function ($article) use ($user) {
            $access = $this->canAccessArticle($user, $article);
            return $access['can_access'];
        });
    }

    /**
     * ดึงคอร์สที่ยังล็อคอยู่พร้อมเหตุผล
     *
     * @param User $user
     * @param LearningCategory|null $category
     * @return Collection
     */
    public function getLockedArticles(User $user, ?LearningCategory $category = null): Collection
    {
        $query = LearningArticle::published()->ordered();

        if ($category) {
            $query->where('category_id', $category->id);
        }

        $articles = $query->get();

        return $articles->map(function ($article) use ($user) {
            $access = $this->canAccessArticle($user, $article);
            $article->access_info = $access;
            return $article;
        })->filter(function ($article) {
            return !$article->access_info['can_access'];
        });
    }

    /**
     * ดึง progress ของผู้ใช้ในหมวดหมู่
     *
     * @param User $user
     * @param LearningCategory $category
     * @return array
     */
    public function getCategoryProgress(User $user, LearningCategory $category): array
    {
        $articles = $category->publishedArticles;
        $totalArticles = $articles->count();

        if ($totalArticles === 0) {
            return [
                'total_articles' => 0,
                'completed_articles' => 0,
                'in_progress_articles' => 0,
                'locked_articles' => 0,
                'progress_percentage' => 0,
                'current_level' => 0,
                'max_level' => 0,
            ];
        }

        $completedCount = 0;
        $inProgressCount = 0;
        $lockedCount = 0;
        $currentLevel = 0;
        $maxLevel = $articles->max('course_level') ?? 1;

        foreach ($articles as $article) {
            $progress = $article->progressForUser($user->id);
            $access = $this->canAccessArticle($user, $article);

            if ($progress && $progress->status === 'completed') {
                $completedCount++;
                $currentLevel = max($currentLevel, $article->course_level ?? 1);
            } elseif ($progress && $progress->status === 'in_progress') {
                $inProgressCount++;
            } elseif (!$access['can_access']) {
                $lockedCount++;
            }
        }

        return [
            'total_articles' => $totalArticles,
            'completed_articles' => $completedCount,
            'in_progress_articles' => $inProgressCount,
            'locked_articles' => $lockedCount,
            'progress_percentage' => round(($completedCount / $totalArticles) * 100, 1),
            'current_level' => $currentLevel,
            'max_level' => $maxLevel,
        ];
    }

    /**
     * จบคอร์สและให้รางวัล
     *
     * @param User $user
     * @param LearningArticle $article
     * @return array ['success' => bool, 'rewards' => array, 'next_article' => LearningArticle|null]
     */
    public function completeArticle(User $user, LearningArticle $article): array
    {
        try {
            DB::beginTransaction();

            // อัพเดท progress
            $progress = UserArticleProgress::firstOrNew([
                'user_id' => $user->id,
                'article_id' => $article->id,
            ]);

            $wasCompleted = $progress->status === 'completed';
            $progress->markAsCompleted();

            $rewards = [];
            $totalRewardValue = 0;

            // ให้รางวัลเฉพาะเมื่อจบครั้งแรก
            if (!$wasCompleted) {
                // ตรวจสอบงบประมาณ
                $canGiveCoinMoney = !$article->isBudgetExhausted();

                // 1. ให้คะแนน Points
                if ($article->points_reward > 0) {
                    $rewards[] = [
                        'type' => 'points',
                        'value' => $article->points_reward,
                        'label' => "{$article->points_reward} แต้ม",
                    ];
                }

                // 2. ให้ Video Coins (ถ้างบประมาณยังไม่หมด)
                if ($canGiveCoinMoney && $article->coin_reward > 0) {
                    $this->giveCoinReward($user, $article, $article->coin_reward);
                    $rewards[] = [
                        'type' => 'coins',
                        'value' => $article->coin_reward,
                        'label' => number_format($article->coin_reward, 2) . ' Coins',
                    ];
                    $totalRewardValue += $article->coin_reward;
                }

                // 3. ให้เงินสด (THB) เข้า Wallet (ถ้างบประมาณยังไม่หมด)
                if ($canGiveCoinMoney && $article->money_reward > 0) {
                    $this->giveMoneyReward($user, $article, $article->money_reward);
                    $rewards[] = [
                        'type' => 'money',
                        'value' => $article->money_reward,
                        'label' => '฿' . number_format($article->money_reward, 2),
                    ];
                    $totalRewardValue += $article->money_reward;
                }

                // 4. ให้ EXP
                if ($article->exp_reward > 0) {
                    $this->giveExpReward($user, $article->exp_reward);
                    $rewards[] = [
                        'type' => 'exp',
                        'value' => $article->exp_reward,
                        'label' => "{$article->exp_reward} EXP",
                    ];
                }

                // 5. ให้ PV
                if ($article->pv_value > 0) {
                    $this->givePvReward($user, $article, $article->pv_value);
                    $rewards[] = [
                        'type' => 'pv',
                        'value' => $article->pv_value,
                        'label' => number_format($article->pv_value, 2) . ' PV',
                    ];
                }

                // 6. ให้ Badge
                if ($article->badge_reward_id) {
                    $rewards[] = [
                        'type' => 'badge',
                        'badge_id' => $article->badge_reward_id,
                        'label' => 'Badge พิเศษ',
                    ];
                }

                // บันทึกยอดรางวัลที่จ่าย
                if ($totalRewardValue > 0) {
                    $article->recordRewardGiven($totalRewardValue);
                }

                // Log การจบคอร์ส
                Log::info('Course completed', [
                    'user_id' => $user->id,
                    'article_id' => $article->id,
                    'article_title' => $article->title,
                    'rewards' => $rewards,
                    'total_reward_value' => $totalRewardValue,
                ]);
            }

            // หาคอร์สถัดไป
            $nextArticle = $this->getNextArticle($article);

            DB::commit();

            return [
                'success' => true,
                'rewards' => $rewards,
                'next_article' => $nextArticle,
                'message' => 'เรียนจบคอร์สเรียบร้อยแล้ว!',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete course', [
                'user_id' => $user->id,
                'article_id' => $article->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'rewards' => [],
                'next_article' => null,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ให้รางวัล Video Coins
     *
     * @param User $user
     * @param LearningArticle $article
     * @param float $amount
     * @return void
     */
    protected function giveCoinReward(User $user, LearningArticle $article, float $amount): void
    {
        $videoCoin = VideoCoin::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'lifetime_earned' => 0, 'lifetime_spent' => 0, 'lifetime_exchanged' => 0]
        );

        $videoCoin->addCoins(
            $amount,
            'earned_course',
            LearningArticle::class,
            $article->id,
            "รางวัลจบคอร์ส: {$article->title}"
        );
    }

    /**
     * ให้รางวัลเงินสด (THB) เข้า Wallet
     *
     * @param User $user
     * @param LearningArticle $article
     * @param float $amount
     * @return void
     */
    protected function giveMoneyReward(User $user, LearningArticle $article, float $amount): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'THB', 'status' => 'active']
        );

        $balanceBefore = $wallet->balance;
        $wallet->increment('balance', $amount);
        $wallet->increment('total_income', $amount);

        // บันทึก transaction
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'course_reward',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $wallet->balance,
            'status' => 'completed',
            'description' => "รางวัลจบคอร์ส: {$article->title}",
            'reference_type' => LearningArticle::class,
            'reference_id' => $article->id,
        ]);
    }

    /**
     * ให้รางวัล EXP
     *
     * @param User $user
     * @param int $exp
     * @return void
     */
    protected function giveExpReward(User $user, int $exp): void
    {
        // ถ้ามีระบบ Level/EXP ให้เพิ่มที่นี่
        // สำหรับตอนนี้เก็บใน metadata หรือ column exp ของ user
        if (method_exists($user, 'addExp')) {
            $user->addExp($exp);
        } elseif (isset($user->exp)) {
            $user->increment('exp', $exp);
        }
    }

    /**
     * ให้รางวัล PV (Point Value)
     *
     * @param User $user
     * @param LearningArticle $article
     * @param float $pv
     * @return void
     */
    protected function givePvReward(User $user, LearningArticle $article, float $pv): void
    {
        // เชื่อมกับระบบ MLM PV Transaction
        if (class_exists('App\Models\MlmPvTransaction')) {
            $mlmPvTransaction = new \App\Models\MlmPvTransaction();
            $mlmPvTransaction->user_id = $user->id;
            $mlmPvTransaction->pv_amount = $pv;
            $mlmPvTransaction->transaction_type = 'course_reward';
            $mlmPvTransaction->reference_type = LearningArticle::class;
            $mlmPvTransaction->reference_id = $article->id;
            $mlmPvTransaction->description = "PV จบคอร์ส: {$article->title}";
            $mlmPvTransaction->save();
        }

        // ถ้า user มี field personal_pv ให้เพิ่ม
        if (isset($user->personal_pv)) {
            $user->increment('personal_pv', $pv);
        }
    }

    /**
     * หาคอร์สถัดไปในหมวดหมู่
     *
     * @param LearningArticle $currentArticle
     * @return LearningArticle|null
     */
    public function getNextArticle(LearningArticle $currentArticle): ?LearningArticle
    {
        // ลองหาคอร์สในระดับเดียวกันก่อน (ตาม order)
        $nextInSameLevel = LearningArticle::where('category_id', $currentArticle->category_id)
            ->where('course_level', $currentArticle->course_level ?? 1)
            ->where('order', '>', $currentArticle->order)
            ->where('is_published', true)
            ->orderBy('order')
            ->first();

        if ($nextInSameLevel) {
            return $nextInSameLevel;
        }

        // ถ้าไม่มีในระดับเดียวกัน หาระดับถัดไป
        return LearningArticle::where('category_id', $currentArticle->category_id)
            ->where('course_level', '>', $currentArticle->course_level ?? 1)
            ->where('is_published', true)
            ->orderBy('course_level')
            ->orderBy('order')
            ->first();
    }

    /**
     * ดึงสถิติการเรียนของผู้ใช้
     *
     * @param User $user
     * @return array
     */
    public function getUserLearningStats(User $user): array
    {
        $progress = UserArticleProgress::where('user_id', $user->id)->get();

        $totalCompleted = $progress->where('status', 'completed')->count();
        $totalInProgress = $progress->where('status', 'in_progress')->count();
        $totalTimeSpent = $progress->sum('time_spent');

        // หาคะแนน quiz ดีที่สุด
        $quizStats = QuizAttempt::where('user_id', $user->id)
            ->completed()
            ->selectRaw('COUNT(*) as total_attempts')
            ->selectRaw('AVG(percentage) as avg_score')
            ->selectRaw('MAX(percentage) as best_score')
            ->selectRaw('SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count')
            ->first();

        return [
            'courses_completed' => $totalCompleted,
            'courses_in_progress' => $totalInProgress,
            'total_time_spent' => $totalTimeSpent,
            'total_time_formatted' => $this->formatTimeSpent($totalTimeSpent),
            'quiz_stats' => [
                'total_attempts' => $quizStats->total_attempts ?? 0,
                'average_score' => round($quizStats->avg_score ?? 0, 1),
                'best_score' => $quizStats->best_score ?? 0,
                'pass_rate' => $quizStats->total_attempts > 0
                    ? round(($quizStats->passed_count / $quizStats->total_attempts) * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * Format time spent
     *
     * @param int $seconds
     * @return string
     */
    protected function formatTimeSpent(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' วินาที';
        }

        $minutes = floor($seconds / 60);

        if ($minutes < 60) {
            return $minutes . ' นาที';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . ' ชั่วโมง ' . $remainingMinutes . ' นาที';
    }
}
