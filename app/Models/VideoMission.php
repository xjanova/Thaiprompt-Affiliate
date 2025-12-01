<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * VideoMission Model
 *
 * ภารกิจดูคลิปรับรางวัล - แต่ละภารกิจประกอบด้วยวิดีโอที่ต้องดู
 * และรางวัลที่จะได้รับเมื่อดูครบตามเงื่อนไข
 *
 * @property int $id
 * @property string $title ชื่อภารกิจ
 * @property string|null $title_th ชื่อภารกิจภาษาไทย
 * @property string|null $description รายละเอียด
 * @property string|null $description_th รายละเอียดภาษาไทย
 * @property string $video_url URL ของวิดีโอ
 * @property string $video_type ประเภทวิดีโอ (youtube, vimeo, etc.)
 * @property int $required_watch_seconds เวลาที่ต้องดู (วินาที)
 * @property string $reward_type ประเภทรางวัล
 * @property string $frequency ความถี่ที่ทำได้ (daily, weekly, etc.)
 * @property bool $is_active สถานะใช้งาน
 * @property \Carbon\Carbon|null $start_at เวลาเริ่มเปิด
 * @property \Carbon\Carbon|null $end_at เวลาปิด
 */
class VideoMission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_missions';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        // ข้อมูลพื้นฐาน
        'title',
        'title_th',
        'description',
        'description_th',
        'thumbnail',
        'icon',

        // ข้อมูลวิดีโอ
        'video_url',
        'video_type',
        'video_id',
        'embed_code',
        'video_duration_seconds',

        // เงื่อนไขการดู
        'required_watch_seconds',
        'required_watch_percentage',
        'must_watch_complete',
        'allow_skip',
        'max_skip_seconds',
        'allow_speed_change',
        'allowed_speeds',

        // รางวัล
        'reward_type',
        'reward_money',
        'reward_coins',
        'reward_points',
        'reward_tpix',
        'reward_exp',

        // คุณสมบัติผู้ทำ
        'min_rank_id',
        'max_rank_id',
        'min_user_level',
        'min_days_registered',
        'require_kyc',
        'require_email_verified',
        'require_phone_verified',

        // จำกัดการทำ
        'frequency',
        'cooldown_minutes',
        'max_completions_per_user',
        'max_total_completions',
        'daily_limit_global',

        // ช่วงเวลา
        'start_at',
        'end_at',
        'available_days',
        'available_time_start',
        'available_time_end',

        // ป้องกันโกง
        'anti_cheat_enabled',
        'require_focus',
        'detect_tab_switch',
        'max_tab_switches',
        'require_interaction',
        'interaction_interval_seconds',
        'verify_completion',
        'verification_questions',

        // สถานะ
        'is_active',
        'is_featured',
        'is_premium',
        'priority',
        'sort_order',

        // สถิติ
        'view_count',
        'completion_count',
        'total_rewards_given',
        'max_reward_budget',
        'budget_exhausted_at',
        'budget_warning_threshold',

        // หมวดหมู่
        'category',
        'tags',

        // Audit
        'created_by',
        'updated_by',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Boolean
        'must_watch_complete' => 'boolean',
        'allow_skip' => 'boolean',
        'allow_speed_change' => 'boolean',
        'require_kyc' => 'boolean',
        'require_email_verified' => 'boolean',
        'require_phone_verified' => 'boolean',
        'anti_cheat_enabled' => 'boolean',
        'require_focus' => 'boolean',
        'detect_tab_switch' => 'boolean',
        'require_interaction' => 'boolean',
        'verify_completion' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_premium' => 'boolean',

        // Integer
        'video_duration_seconds' => 'integer',
        'required_watch_seconds' => 'integer',
        'required_watch_percentage' => 'integer',
        'max_skip_seconds' => 'integer',
        'min_user_level' => 'integer',
        'min_days_registered' => 'integer',
        'cooldown_minutes' => 'integer',
        'max_completions_per_user' => 'integer',
        'max_total_completions' => 'integer',
        'daily_limit_global' => 'integer',
        'max_tab_switches' => 'integer',
        'interaction_interval_seconds' => 'integer',
        'priority' => 'integer',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'completion_count' => 'integer',
        'reward_points' => 'integer',
        'reward_exp' => 'integer',

        // Decimal
        'reward_money' => 'decimal:2',
        'reward_coins' => 'decimal:2',
        'reward_tpix' => 'decimal:8',
        'total_rewards_given' => 'decimal:2',
        'max_reward_budget' => 'decimal:2',
        'budget_warning_threshold' => 'integer',
        'budget_exhausted_at' => 'datetime',

        // DateTime
        'start_at' => 'datetime',
        'end_at' => 'datetime',

        // Time
        'available_time_start' => 'string',
        'available_time_end' => 'string',

        // JSON
        'allowed_speeds' => 'array',
        'available_days' => 'array',
        'verification_questions' => 'array',
        'tags' => 'array',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'video_type' => 'youtube',
        'required_watch_percentage' => 80,
        'reward_type' => 'coins',
        'frequency' => 'daily',
        'min_user_level' => 1,
        'anti_cheat_enabled' => true,
        'require_focus' => true,
        'detect_tab_switch' => true,
        'max_tab_switches' => 3,
        'is_active' => true,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * ความสัมพันธ์กับ Rank ขั้นต่ำ
     *
     * @return BelongsTo
     */
    public function minRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'min_rank_id');
    }

    /**
     * ความสัมพันธ์กับ Rank สูงสุด
     *
     * @return BelongsTo
     */
    public function maxRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'max_rank_id');
    }

    /**
     * ความสัมพันธ์กับผู้สร้าง
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ความสัมพันธ์กับผู้แก้ไขล่าสุด
     *
     * @return BelongsTo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * ความสัมพันธ์กับการทำภารกิจ
     *
     * @return HasMany
     */
    public function completions(): HasMany
    {
        return $this->hasMany(VideoMissionCompletion::class, 'mission_id');
    }

    /**
     * ดึงการทำสำเร็จของ user
     *
     * @return HasMany
     */
    public function userCompletions(): HasMany
    {
        return $this->completions()->where('status', 'verified');
    }

    // ==================== SCOPES ====================

    /**
     * เฉพาะภารกิจที่เปิดใช้งาน
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * เฉพาะภารกิจที่อยู่ในช่วงเวลา
     */
    public function scopeAvailable($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', $now);
            });
    }

    /**
     * เฉพาะภารกิจแนะนำ
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * เฉพาะภารกิจ premium
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * เรียงตามลำดับ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * กรองตามหมวดหมู่
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * กรองตาม Rank ของ user
     */
    public function scopeForRank($query, int $rankId, int $rankLevel)
    {
        return $query->where(function ($q) use ($rankId, $rankLevel) {
            $q->whereNull('min_rank_id')
                ->orWhereHas('minRank', function ($subQ) use ($rankLevel) {
                    $subQ->where('level', '<=', $rankLevel);
                });
        })->where(function ($q) use ($rankId, $rankLevel) {
            $q->whereNull('max_rank_id')
                ->orWhereHas('maxRank', function ($subQ) use ($rankLevel) {
                    $subQ->where('level', '>=', $rankLevel);
                });
        });
    }

    // ==================== ACCESSORS ====================

    /**
     * ดึงชื่อตาม locale
     *
     * @return string
     */
    public function getDisplayTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return ($locale === 'th' && $this->title_th) ? $this->title_th : $this->title;
    }

    /**
     * ดึงรายละเอียดตาม locale
     *
     * @return string|null
     */
    public function getDisplayDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return ($locale === 'th' && $this->description_th) ? $this->description_th : $this->description;
    }

    /**
     * ดึง URL รูปปก
     *
     * @return string|null
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            // ถ้าเป็น YouTube ใช้ thumbnail อัตโนมัติ
            if ($this->video_type === 'youtube' && $this->video_id) {
                return "https://img.youtube.com/vi/{$this->video_id}/maxresdefault.jpg";
            }
            return null;
        }

        if (str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }

    /**
     * ดึง video ID จาก URL
     *
     * @return string|null
     */
    public function getVideoIdAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        // Extract video ID from URL
        return $this->extractVideoId($this->video_url);
    }

    /**
     * ดึง embed URL
     *
     * @return string|null
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $videoId = $this->video_id;
        if (!$videoId) {
            return null;
        }

        switch ($this->video_type) {
            case 'youtube':
                return "https://www.youtube.com/embed/{$videoId}?enablejsapi=1&controls=0&disablekb=1&fs=0&modestbranding=1&rel=0";
            case 'vimeo':
                return "https://player.vimeo.com/video/{$videoId}?api=1";
            case 'direct':
                return $this->video_url;
            default:
                return $this->video_url;
        }
    }

    /**
     * รวมรางวัลทั้งหมดเป็นข้อความ
     *
     * @return string
     */
    public function getRewardSummaryAttribute(): string
    {
        $rewards = [];

        if ($this->reward_money > 0) {
            $rewards[] = '฿' . number_format($this->reward_money, 2);
        }
        if ($this->reward_coins > 0) {
            $rewards[] = number_format($this->reward_coins) . ' Coins';
        }
        if ($this->reward_points > 0) {
            $rewards[] = number_format($this->reward_points) . ' แต้ม';
        }
        if ($this->reward_tpix > 0) {
            $rewards[] = number_format($this->reward_tpix, 4) . ' TPIX';
        }
        if ($this->reward_exp > 0) {
            $rewards[] = number_format($this->reward_exp) . ' EXP';
        }

        return implode(' + ', $rewards) ?: 'ไม่มีรางวัล';
    }

    /**
     * แปลงเวลาที่ต้องดูเป็นรูปแบบอ่านง่าย
     *
     * @return string
     */
    public function getRequiredWatchTimeFormattedAttribute(): string
    {
        $seconds = $this->required_watch_seconds;

        if ($seconds < 60) {
            return $seconds . ' วินาที';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds > 0) {
            return $minutes . ' นาที ' . $remainingSeconds . ' วินาที';
        }

        return $minutes . ' นาที';
    }

    /**
     * ดึงสถานะการเปิดใช้งาน
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->start_at && $now->lt($this->start_at)) {
            return 'scheduled';
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * ดึงสีสถานะ
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'scheduled' => 'blue',
            'expired' => 'gray',
            'inactive' => 'red',
            default => 'gray',
        };
    }

    /**
     * ดึง label สถานะภาษาไทย
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'เปิดใช้งาน',
            'scheduled' => 'รอเปิด',
            'expired' => 'หมดอายุ',
            'inactive' => 'ปิดใช้งาน',
            default => 'ไม่ทราบ',
        };
    }

    // ==================== METHODS ====================

    /**
     * ตรวจสอบว่า user มีสิทธิ์ทำภารกิจนี้หรือไม่
     *
     * @param User $user
     * @return array ['eligible' => bool, 'reason' => string|null]
     */
    public function checkUserEligibility(User $user): array
    {
        // ตรวจสอบสถานะภารกิจ
        if (!$this->is_active) {
            return ['eligible' => false, 'reason' => 'ภารกิจนี้ปิดใช้งานแล้ว'];
        }

        // ตรวจสอบช่วงเวลา
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) {
            return ['eligible' => false, 'reason' => 'ภารกิจยังไม่เปิดให้ทำ'];
        }
        if ($this->end_at && $now->gt($this->end_at)) {
            return ['eligible' => false, 'reason' => 'ภารกิจหมดอายุแล้ว'];
        }

        // ตรวจสอบวันที่เปิดให้ทำ
        if ($this->available_days) {
            $today = $now->dayOfWeek;
            if (!in_array($today, $this->available_days)) {
                return ['eligible' => false, 'reason' => 'ภารกิจไม่เปิดให้ทำในวันนี้'];
            }
        }

        // ตรวจสอบเวลา
        if ($this->available_time_start && $this->available_time_end) {
            $currentTime = $now->format('H:i:s');
            if ($currentTime < $this->available_time_start || $currentTime > $this->available_time_end) {
                return ['eligible' => false, 'reason' => 'ภารกิจไม่เปิดให้ทำในเวลานี้'];
            }
        }

        // ตรวจสอบ Rank
        $userRank = $user->currentRank;
        if ($this->min_rank_id && $userRank) {
            $minRank = $this->minRank;
            if ($minRank && $userRank->level < $minRank->level) {
                return ['eligible' => false, 'reason' => "ต้องมี Rank อย่างน้อย {$minRank->display_name}"];
            }
        }
        if ($this->max_rank_id && $userRank) {
            $maxRank = $this->maxRank;
            if ($maxRank && $userRank->level > $maxRank->level) {
                return ['eligible' => false, 'reason' => 'Rank ของคุณสูงเกินไปสำหรับภารกิจนี้'];
            }
        }

        // ตรวจสอบจำนวนวันที่สมัคร
        if ($this->min_days_registered > 0) {
            $daysRegistered = $user->created_at->diffInDays($now);
            if ($daysRegistered < $this->min_days_registered) {
                return ['eligible' => false, 'reason' => "ต้องสมัครมาแล้วอย่างน้อย {$this->min_days_registered} วัน"];
            }
        }

        // ตรวจสอบ KYC
        if ($this->require_kyc && !$user->is_kyc_verified) {
            return ['eligible' => false, 'reason' => 'ต้องยืนยันตัวตน (KYC) ก่อน'];
        }

        // ตรวจสอบ Email verified
        if ($this->require_email_verified && !$user->hasVerifiedEmail()) {
            return ['eligible' => false, 'reason' => 'ต้องยืนยันอีเมลก่อน'];
        }

        // ตรวจสอบ Phone verified
        if ($this->require_phone_verified && !$user->phone_verified_at) {
            return ['eligible' => false, 'reason' => 'ต้องยืนยันเบอร์โทรศัพท์ก่อน'];
        }

        // ตรวจสอบ Premium
        if ($this->is_premium) {
            // ถ้า rank มี can_access_premium_missions
            $rankLimit = VideoMissionRankLimit::where('rank_id', $user->current_rank_id)->first();
            if (!$rankLimit || !$rankLimit->can_access_premium_missions) {
                return ['eligible' => false, 'reason' => 'ภารกิจนี้สำหรับสมาชิก Premium เท่านั้น'];
            }
        }

        // ตรวจสอบจำนวนครั้งที่ทำ
        if ($this->max_completions_per_user) {
            $userCompletions = $this->completions()
                ->where('user_id', $user->id)
                ->where('status', 'verified')
                ->count();

            if ($userCompletions >= $this->max_completions_per_user) {
                return ['eligible' => false, 'reason' => 'คุณทำภารกิจนี้ครบจำนวนครั้งแล้ว'];
            }
        }

        // ตรวจสอบจำนวนครั้งรวมทั้งระบบ
        if ($this->max_total_completions) {
            if ($this->completion_count >= $this->max_total_completions) {
                return ['eligible' => false, 'reason' => 'ภารกิจนี้มีผู้ทำครบจำนวนแล้ว'];
            }
        }

        // ตรวจสอบงบประมาณ (Budget)
        if ($this->isBudgetExhausted()) {
            return ['eligible' => false, 'reason' => 'งบประมาณรางวัลของภารกิจนี้หมดแล้ว'];
        }

        if (!$this->hasBudgetForNextReward()) {
            return ['eligible' => false, 'reason' => 'งบประมาณไม่เพียงพอสำหรับรางวัล'];
        }

        // ตรวจสอบ frequency
        $canDoNow = $this->canUserDoNow($user);
        if (!$canDoNow['can']) {
            return ['eligible' => false, 'reason' => $canDoNow['reason']];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * ตรวจสอบว่า user สามารถทำภารกิจได้ตอนนี้หรือไม่ (ตาม frequency)
     *
     * @param User $user
     * @return array ['can' => bool, 'reason' => string|null, 'next_available_at' => Carbon|null]
     */
    public function canUserDoNow(User $user): array
    {
        $lastCompletion = $this->completions()
            ->where('user_id', $user->id)
            ->where('status', 'verified')
            ->latest('completed_at')
            ->first();

        if (!$lastCompletion) {
            return ['can' => true, 'reason' => null, 'next_available_at' => null];
        }

        $now = now();
        $lastCompletedAt = $lastCompletion->completed_at;

        switch ($this->frequency) {
            case 'once':
                return [
                    'can' => false,
                    'reason' => 'ภารกิจนี้ทำได้ครั้งเดียว',
                    'next_available_at' => null,
                ];

            case 'daily':
                if ($lastCompletedAt->isSameDay($now)) {
                    return [
                        'can' => false,
                        'reason' => 'ภารกิจนี้ทำได้วันละครั้ง',
                        'next_available_at' => $now->copy()->addDay()->startOfDay(),
                    ];
                }
                break;

            case 'weekly':
                if ($lastCompletedAt->isSameWeek($now)) {
                    return [
                        'can' => false,
                        'reason' => 'ภารกิจนี้ทำได้สัปดาห์ละครั้ง',
                        'next_available_at' => $now->copy()->addWeek()->startOfWeek(),
                    ];
                }
                break;

            case 'monthly':
                if ($lastCompletedAt->isSameMonth($now)) {
                    return [
                        'can' => false,
                        'reason' => 'ภารกิจนี้ทำได้เดือนละครั้ง',
                        'next_available_at' => $now->copy()->addMonth()->startOfMonth(),
                    ];
                }
                break;

            case 'unlimited':
                if ($this->cooldown_minutes > 0) {
                    $nextAvailable = $lastCompletedAt->copy()->addMinutes($this->cooldown_minutes);
                    if ($now->lt($nextAvailable)) {
                        $minutesLeft = $now->diffInMinutes($nextAvailable);
                        return [
                            'can' => false,
                            'reason' => "ต้องรออีก {$minutesLeft} นาที",
                            'next_available_at' => $nextAvailable,
                        ];
                    }
                }
                break;
        }

        return ['can' => true, 'reason' => null, 'next_available_at' => null];
    }

    /**
     * ดึงจำนวนครั้งที่ user ทำได้อีก
     *
     * @param User $user
     * @return int|null null = ไม่จำกัด
     */
    public function getRemainingCompletions(User $user): ?int
    {
        if (!$this->max_completions_per_user) {
            return null;
        }

        $completed = $this->completions()
            ->where('user_id', $user->id)
            ->where('status', 'verified')
            ->count();

        return max(0, $this->max_completions_per_user - $completed);
    }

    /**
     * เพิ่ม view count
     *
     * @return void
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * เพิ่ม completion count
     *
     * @return void
     */
    public function incrementCompletionCount(): void
    {
        $this->increment('completion_count');
    }

    /**
     * เพิ่มรางวัลที่แจกไป
     *
     * @param float $amount
     * @return void
     */
    public function addRewardsGiven(float $amount): void
    {
        $this->increment('total_rewards_given', $amount);

        // ตรวจสอบว่าถึง budget หรือยัง
        $this->refresh();
        if ($this->isBudgetExhausted()) {
            $this->markBudgetExhausted();
        }
    }

    // ==================== BUDGET MANAGEMENT ====================

    /**
     * ตรวจสอบว่างบประมาณหมดหรือยัง
     *
     * @return bool
     */
    public function isBudgetExhausted(): bool
    {
        // ถ้าไม่มีการกำหนด budget = ไม่จำกัด
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            return false;
        }

        return $this->total_rewards_given >= $this->max_reward_budget;
    }

    /**
     * ตรวจสอบว่างบประมาณใกล้หมดหรือยัง (ตาม threshold)
     *
     * @return bool
     */
    public function isBudgetNearExhaustion(): bool
    {
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            return false;
        }

        $threshold = $this->budget_warning_threshold ?? 80;
        $usedPercentage = ($this->total_rewards_given / $this->max_reward_budget) * 100;

        return $usedPercentage >= $threshold;
    }

    /**
     * ดึงงบประมาณที่เหลือ
     *
     * @return float|null null = ไม่จำกัด
     */
    public function getRemainingBudget(): ?float
    {
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            return null;
        }

        return max(0, $this->max_reward_budget - $this->total_rewards_given);
    }

    /**
     * ดึง % งบที่ใช้ไป
     *
     * @return float|null null = ไม่จำกัด
     */
    public function getBudgetUsedPercentage(): ?float
    {
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            return null;
        }

        return min(100, ($this->total_rewards_given / $this->max_reward_budget) * 100);
    }

    /**
     * Mark ว่างบประมาณหมดแล้ว
     *
     * @return void
     */
    public function markBudgetExhausted(): void
    {
        if (!$this->budget_exhausted_at) {
            $this->update([
                'budget_exhausted_at' => now(),
            ]);
        }
    }

    /**
     * ตรวจสอบว่ามีงบเพียงพอสำหรับรางวัลครั้งต่อไปหรือไม่
     *
     * @param float|null $nextRewardAmount จำนวนรางวัลที่จะแจกครั้งต่อไป (null = ใช้ค่าเริ่มต้น)
     * @return bool
     */
    public function hasBudgetForNextReward(?float $nextRewardAmount = null): bool
    {
        // ถ้าไม่มี budget limit = ไม่จำกัด
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            return true;
        }

        // ถ้าระบุจำนวนรางวัลให้ใช้ค่านั้น ถ้าไม่ให้คำนวณจากรางวัลของภารกิจ
        $rewardAmount = $nextRewardAmount ?? $this->calculateTotalRewardValue();

        $remaining = $this->getRemainingBudget();

        return $remaining >= $rewardAmount;
    }

    /**
     * คำนวณมูลค่ารางวัลทั้งหมดต่อครั้ง
     *
     * @return float
     */
    public function calculateTotalRewardValue(): float
    {
        // รวมทุก reward type เป็นมูลค่าเดียว
        // สำหรับ budget ใช้ค่าหลักเป็น money + coins
        return (float) $this->reward_money + (float) $this->reward_coins;
    }

    /**
     * ดึงจำนวนคนที่ทำได้อีก (จากงบประมาณ)
     *
     * @return int|null null = ไม่จำกัด
     */
    public function getRemainingSlots(): ?int
    {
        if (!$this->max_reward_budget || $this->max_reward_budget <= 0) {
            // ตรวจสอบ max_total_completions แทน
            if ($this->max_total_completions) {
                return max(0, $this->max_total_completions - $this->completion_count);
            }
            return null;
        }

        $rewardPerCompletion = $this->calculateTotalRewardValue();
        if ($rewardPerCompletion <= 0) {
            return null;
        }

        $remaining = $this->getRemainingBudget();
        return (int) floor($remaining / $rewardPerCompletion);
    }

    /**
     * Extract video ID จาก URL
     *
     * @param string|null $url
     * @return string|null
     */
    protected function extractVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        // YouTube
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * คำนวณรางวัลพร้อม multiplier
     *
     * @param float $multiplier
     * @return array
     */
    public function calculateRewards(float $multiplier = 1.0): array
    {
        return [
            'money' => round($this->reward_money * $multiplier, 2),
            'coins' => round($this->reward_coins * $multiplier, 2),
            'points' => (int) round($this->reward_points * $multiplier),
            'tpix' => round($this->reward_tpix * $multiplier, 8),
            'exp' => (int) round($this->reward_exp * $multiplier),
        ];
    }
}
