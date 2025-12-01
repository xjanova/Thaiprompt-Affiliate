<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VideoMissionRankLimit Model
 *
 * กำหนดจำนวนภารกิจที่แต่ละ Rank สามารถทำได้
 * รวมถึงโบนัสและสิทธิพิเศษตาม Rank
 *
 * @property int $id
 * @property int $rank_id ID ของ Rank
 * @property int $daily_mission_limit ภารกิจต่อวัน
 * @property int $weekly_mission_limit ภารกิจต่อสัปดาห์
 * @property int $monthly_mission_limit ภารกิจต่อเดือน
 * @property float|null $daily_reward_limit รางวัลสูงสุดต่อวัน
 * @property float $reward_multiplier ตัวคูณรางวัล
 * @property bool $can_access_premium_missions เข้าภารกิจ premium ได้
 * @property bool $is_active สถานะใช้งาน
 */
class VideoMissionRankLimit extends Model
{
    use HasFactory;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_mission_rank_limits';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'rank_id',
        'daily_mission_limit',
        'weekly_mission_limit',
        'monthly_mission_limit',
        'daily_reward_limit',
        'weekly_reward_limit',
        'monthly_reward_limit',
        'reward_multiplier',
        'bonus_reward_percentage',
        'bonus_exp_percentage',
        'can_access_premium_missions',
        'can_access_featured_missions',
        'skip_cooldown',
        'cooldown_reduction_percent',
        'priority_in_queue',
        'is_active',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Integer
        'daily_mission_limit' => 'integer',
        'weekly_mission_limit' => 'integer',
        'monthly_mission_limit' => 'integer',
        'bonus_exp_percentage' => 'integer',
        'cooldown_reduction_percent' => 'integer',
        'priority_in_queue' => 'integer',

        // Decimal
        'daily_reward_limit' => 'decimal:2',
        'weekly_reward_limit' => 'decimal:2',
        'monthly_reward_limit' => 'decimal:2',
        'reward_multiplier' => 'decimal:2',
        'bonus_reward_percentage' => 'decimal:2',

        // Boolean
        'can_access_premium_missions' => 'boolean',
        'can_access_featured_missions' => 'boolean',
        'skip_cooldown' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'daily_mission_limit' => 5,
        'weekly_mission_limit' => 20,
        'monthly_mission_limit' => 60,
        'reward_multiplier' => 1.00,
        'bonus_reward_percentage' => 0,
        'bonus_exp_percentage' => 0,
        'can_access_premium_missions' => false,
        'can_access_featured_missions' => true,
        'skip_cooldown' => false,
        'cooldown_reduction_percent' => 0,
        'priority_in_queue' => 0,
        'is_active' => true,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * ความสัมพันธ์กับ Rank
     *
     * @return BelongsTo
     */
    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    // ==================== SCOPES ====================

    /**
     * เฉพาะที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== METHODS ====================

    /**
     * ดึง limit สำหรับ Rank (สร้างค่าเริ่มต้นถ้ายังไม่มี)
     *
     * ถ้า user ไม่มี rank (rank_id = 0 หรือ null)
     * จะ return default limit object โดยไม่บันทึกลง database
     *
     * @param int|null $rankId
     * @return self
     */
    public static function getForRank(?int $rankId): self
    {
        // ถ้าไม่มี rank ให้ return default limit (ไม่บันทึกลง DB)
        if (!$rankId || $rankId <= 0) {
            $defaultLimit = new static();
            $defaultLimit->rank_id = null;
            $defaultLimit->daily_mission_limit = 5;
            $defaultLimit->weekly_mission_limit = 20;
            $defaultLimit->monthly_mission_limit = 60;
            $defaultLimit->reward_multiplier = 1.00;
            $defaultLimit->bonus_reward_percentage = 0;
            $defaultLimit->bonus_exp_percentage = 0;
            $defaultLimit->can_access_premium_missions = false;
            $defaultLimit->can_access_featured_missions = true;
            $defaultLimit->skip_cooldown = false;
            $defaultLimit->cooldown_reduction_percent = 0;
            $defaultLimit->priority_in_queue = 0;
            $defaultLimit->is_active = true;

            return $defaultLimit;
        }

        // ตรวจสอบว่า Rank มีอยู่จริงหรือไม่
        $rankExists = \App\Models\Rank::where('id', $rankId)->exists();
        if (!$rankExists) {
            // Rank ไม่มีอยู่ ให้ return default
            return static::getForRank(null);
        }

        return static::firstOrCreate(
            ['rank_id' => $rankId],
            [
                'daily_mission_limit' => 5,
                'weekly_mission_limit' => 20,
                'monthly_mission_limit' => 60,
                'reward_multiplier' => 1.00,
            ]
        );
    }

    /**
     * ตรวจสอบว่า user ยังทำภารกิจได้อีกหรือไม่ (รายวัน)
     *
     * @param User $user
     * @return array ['can' => bool, 'remaining' => int, 'limit' => int]
     */
    public function checkDailyLimit(User $user): array
    {
        $today = now()->toDateString();

        $completedToday = VideoMissionCompletion::where('user_id', $user->id)
            ->where('completion_date', $today)
            ->where('status', 'verified')
            ->count();

        $remaining = max(0, $this->daily_mission_limit - $completedToday);

        return [
            'can' => $remaining > 0,
            'remaining' => $remaining,
            'limit' => $this->daily_mission_limit,
            'completed' => $completedToday,
        ];
    }

    /**
     * ตรวจสอบว่า user ยังทำภารกิจได้อีกหรือไม่ (รายสัปดาห์)
     *
     * @param User $user
     * @return array
     */
    public function checkWeeklyLimit(User $user): array
    {
        $weekNumber = now()->weekOfYear;
        $year = now()->year;

        $completedThisWeek = VideoMissionCompletion::where('user_id', $user->id)
            ->where('completion_week', $weekNumber)
            ->where('completion_year', $year)
            ->where('status', 'verified')
            ->count();

        $remaining = max(0, $this->weekly_mission_limit - $completedThisWeek);

        return [
            'can' => $remaining > 0,
            'remaining' => $remaining,
            'limit' => $this->weekly_mission_limit,
            'completed' => $completedThisWeek,
        ];
    }

    /**
     * ตรวจสอบว่า user ยังทำภารกิจได้อีกหรือไม่ (รายเดือน)
     *
     * @param User $user
     * @return array
     */
    public function checkMonthlyLimit(User $user): array
    {
        $month = now()->month;
        $year = now()->year;

        $completedThisMonth = VideoMissionCompletion::where('user_id', $user->id)
            ->where('completion_month', $month)
            ->where('completion_year', $year)
            ->where('status', 'verified')
            ->count();

        $remaining = max(0, $this->monthly_mission_limit - $completedThisMonth);

        return [
            'can' => $remaining > 0,
            'remaining' => $remaining,
            'limit' => $this->monthly_mission_limit,
            'completed' => $completedThisMonth,
        ];
    }

    /**
     * ตรวจสอบทุก limit
     *
     * @param User $user
     * @return array
     */
    public function checkAllLimits(User $user): array
    {
        $daily = $this->checkDailyLimit($user);
        $weekly = $this->checkWeeklyLimit($user);
        $monthly = $this->checkMonthlyLimit($user);

        $canDoMore = $daily['can'] && $weekly['can'] && $monthly['can'];

        $reason = null;
        if (!$daily['can']) {
            $reason = 'คุณทำภารกิจครบโควต้ารายวันแล้ว';
        } elseif (!$weekly['can']) {
            $reason = 'คุณทำภารกิจครบโควต้ารายสัปดาห์แล้ว';
        } elseif (!$monthly['can']) {
            $reason = 'คุณทำภารกิจครบโควต้ารายเดือนแล้ว';
        }

        return [
            'can_do_more' => $canDoMore,
            'reason' => $reason,
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
        ];
    }

    /**
     * ตรวจสอบรางวัลไม่เกิน limit (รายวัน)
     *
     * @param User $user
     * @param float $rewardAmount
     * @return array ['can' => bool, 'remaining' => float|null]
     */
    public function checkDailyRewardLimit(User $user, float $rewardAmount): array
    {
        if (!$this->daily_reward_limit) {
            return ['can' => true, 'remaining' => null];
        }

        $today = now()->toDateString();

        $earnedToday = VideoMissionCompletion::where('user_id', $user->id)
            ->where('completion_date', $today)
            ->where('status', 'verified')
            ->where('reward_given', true)
            ->sum('reward_money');

        $remaining = max(0, $this->daily_reward_limit - $earnedToday);

        return [
            'can' => ($earnedToday + $rewardAmount) <= $this->daily_reward_limit,
            'remaining' => $remaining,
            'limit' => $this->daily_reward_limit,
            'earned' => $earnedToday,
        ];
    }

    /**
     * คำนวณ cooldown ตาม reduction percent
     *
     * @param int $originalCooldownMinutes
     * @return int
     */
    public function calculateCooldown(int $originalCooldownMinutes): int
    {
        if ($this->skip_cooldown) {
            return 0;
        }

        if ($this->cooldown_reduction_percent <= 0) {
            return $originalCooldownMinutes;
        }

        $reduction = $originalCooldownMinutes * ($this->cooldown_reduction_percent / 100);
        return max(0, (int) round($originalCooldownMinutes - $reduction));
    }

    /**
     * คำนวณรางวัลพร้อม multiplier และ bonus
     *
     * @param float $baseReward
     * @return float
     */
    public function calculateReward(float $baseReward): float
    {
        $multiplied = $baseReward * $this->reward_multiplier;
        $bonus = $multiplied * ($this->bonus_reward_percentage / 100);

        return round($multiplied + $bonus, 2);
    }

    /**
     * คำนวณ EXP พร้อม bonus
     *
     * @param int $baseExp
     * @return int
     */
    public function calculateExp(int $baseExp): int
    {
        if ($this->bonus_exp_percentage <= 0) {
            return $baseExp;
        }

        $bonus = $baseExp * ($this->bonus_exp_percentage / 100);
        return (int) round($baseExp + $bonus);
    }

    /**
     * ดึงสรุป limit ทั้งหมด
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'rank' => $this->rank ? $this->rank->display_name : 'ไม่ระบุ',
            'daily_limit' => $this->daily_mission_limit,
            'weekly_limit' => $this->weekly_mission_limit,
            'monthly_limit' => $this->monthly_mission_limit,
            'reward_multiplier' => $this->reward_multiplier,
            'bonus_percentage' => $this->bonus_reward_percentage,
            'can_access_premium' => $this->can_access_premium_missions,
            'skip_cooldown' => $this->skip_cooldown,
        ];
    }
}
