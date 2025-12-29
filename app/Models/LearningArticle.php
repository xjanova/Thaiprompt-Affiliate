<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LearningArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'thumbnail',
        'video_url',
        'estimated_duration',
        'order',
        'views',
        'is_published',
        'is_featured',
        'difficulty',
        'course_level',
        'require_quiz_pass',
        'min_quiz_score',
        'unlock_condition',
        'unlock_after_days',
        'points_reward',
        'coin_reward',
        'money_reward',
        'exp_reward',
        'pv_value',
        'max_reward_budget',
        'total_rewards_given',
        'budget_exhausted_at',
        'badge_reward_id',
        'tags',
        'metadata',
        'published_at',
        'created_by',
        'updated_by',
        'instructor_id',
        'instructor_commission',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'require_quiz_pass' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'budget_exhausted_at' => 'datetime',
        'views' => 'integer',
        'estimated_duration' => 'integer',
        'order' => 'integer',
        'course_level' => 'integer',
        'min_quiz_score' => 'integer',
        'unlock_after_days' => 'integer',
        'points_reward' => 'integer',
        'coin_reward' => 'decimal:2',
        'money_reward' => 'decimal:2',
        'exp_reward' => 'integer',
        'pv_value' => 'decimal:2',
        'max_reward_budget' => 'decimal:2',
        'total_rewards_given' => 'decimal:2',
        'badge_reward_id' => 'integer',
        'instructor_id' => 'integer',
        'instructor_commission' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(LearningCategory::class, 'category_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get prerequisites for this article
     */
    public function prerequisites()
    {
        return $this->belongsToMany(
            LearningArticle::class,
            'article_prerequisites',
            'article_id',
            'prerequisite_article_id'
        )->withPivot('is_required')->withTimestamps();
    }

    /**
     * Get articles that require this as prerequisite
     */
    public function dependentArticles()
    {
        return $this->belongsToMany(
            LearningArticle::class,
            'article_prerequisites',
            'prerequisite_article_id',
            'article_id'
        )->withPivot('is_required')->withTimestamps();
    }

    /**
     * Get user progress for this article
     */
    public function userProgress()
    {
        return $this->hasMany(UserArticleProgress::class, 'article_id');
    }

    /**
     * Alias for userProgress (for withCount queries)
     */
    public function progressRecords()
    {
        return $this->hasMany(UserArticleProgress::class, 'article_id');
    }

    /**
     * Get progress for a specific user
     */
    public function progressForUser($userId)
    {
        return $this->userProgress()->where('user_id', $userId)->first();
    }

    /**
     * Get permissions
     */
    public function permissions()
    {
        return $this->hasMany(ArticlePermission::class, 'article_id');
    }

    /**
     * Get quizzes for this article
     */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'article_id')->orderBy('order');
    }

    /**
     * Get active quizzes
     */
    public function activeQuizzes()
    {
        return $this->quizzes()->where('is_active', true);
    }

    /**
     * Get required quizzes
     */
    public function requiredQuizzes()
    {
        return $this->quizzes()->where('is_required', true)->where('is_active', true);
    }

    /**
     * Get certificates for this article
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'article_id');
    }

    /**
     * Scope: published articles
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: featured articles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope: by difficulty
     */
    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Check if user has access to this article
     */
    public function userHasAccess($user)
    {
        // If no permissions set, allow all
        if ($this->permissions()->count() === 0) {
            return true;
        }

        // Check role permissions
        $rolePermissions = $this->permissions()
            ->where('permission_type', 'role')
            ->pluck('permission_value')
            ->toArray();

        if (in_array($user->role, $rolePermissions)) {
            return true;
        }

        // Check user permissions
        $userPermissions = $this->permissions()
            ->where('permission_type', 'user')
            ->pluck('permission_value')
            ->toArray();

        if (in_array($user->id, $userPermissions)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can unlock this article
     */
    public function canUnlock($user)
    {
        // First check if user has access
        if (! $this->userHasAccess($user)) {
            return false;
        }

        // Get required prerequisites
        $requiredPrerequisites = $this->prerequisites()
            ->wherePivot('is_required', true)
            ->get();

        // If no prerequisites, can unlock
        if ($requiredPrerequisites->isEmpty()) {
            return true;
        }

        // Check if all required prerequisites are completed
        foreach ($requiredPrerequisites as $prerequisite) {
            $progress = $prerequisite->progressForUser($user->id);

            if (! $progress || $progress->status !== 'completed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Increment views
     */
    public function incrementViews()
    {
        $this->increment('views');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if ($this->estimated_duration < 60) {
            return $this->estimated_duration.' นาที';
        }

        $hours = floor($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;

        if ($minutes === 0) {
            return $hours.' ชั่วโมง';
        }

        return $hours.' ชั่วโมง '.$minutes.' นาที';
    }

    /**
     * Get difficulty label
     */
    public function getDifficultyLabelAttribute()
    {
        $labels = [
            'beginner' => 'เริ่มต้น',
            'intermediate' => 'ปานกลาง',
            'advanced' => 'ขั้นสูง',
        ];

        return $labels[$this->difficulty] ?? $this->difficulty;
    }

    /**
     * Get course level label
     */
    public function getCourseLevelLabelAttribute(): string
    {
        $labels = [
            1 => 'ระดับ 1 (เริ่มต้น)',
            2 => 'ระดับ 2 (พื้นฐาน)',
            3 => 'ระดับ 3 (กลาง)',
            4 => 'ระดับ 4 (ก้าวหน้า)',
            5 => 'ระดับ 5 (ขั้นสูง)',
        ];

        return $labels[$this->course_level] ?? 'ระดับ '.$this->course_level;
    }

    /**
     * Get unlock condition label
     */
    public function getUnlockConditionLabelAttribute(): string
    {
        $labels = [
            'none' => 'ไม่มีเงื่อนไข',
            'prerequisite' => 'ต้องเรียนจบคอร์สก่อนหน้า',
            'quiz_pass' => 'ต้องผ่านแบบทดสอบ',
            'time_based' => 'ปลดล็อคตามเวลา',
            'manual' => 'ปลดล็อคโดยผู้ดูแล',
        ];

        return $labels[$this->unlock_condition] ?? 'ไม่ทราบ';
    }

    /**
     * Check if this article has quiz requirement
     */
    public function hasQuizRequirement(): bool
    {
        return $this->require_quiz_pass &&
               $this->requiredQuizzes()->exists();
    }

    /**
     * Get previous article in the same category
     */
    public function getPreviousArticle(): ?LearningArticle
    {
        return self::where('category_id', $this->category_id)
            ->where(function ($query) {
                $query->where('course_level', '<', $this->course_level ?? 1)
                    ->orWhere(function ($q) {
                        $q->where('course_level', $this->course_level ?? 1)
                            ->where('order', '<', $this->order);
                    });
            })
            ->where('is_published', true)
            ->orderBy('course_level', 'desc')
            ->orderBy('order', 'desc')
            ->first();
    }

    /**
     * Get next article in the same category
     */
    public function getNextArticle(): ?LearningArticle
    {
        return self::where('category_id', $this->category_id)
            ->where(function ($query) {
                $query->where('course_level', '>', $this->course_level ?? 1)
                    ->orWhere(function ($q) {
                        $q->where('course_level', $this->course_level ?? 1)
                            ->where('order', '>', $this->order);
                    });
            })
            ->where('is_published', true)
            ->orderBy('course_level', 'asc')
            ->orderBy('order', 'asc')
            ->first();
    }

    /**
     * Scope: by course level
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('course_level', $level);
    }

    /**
     * Scope: ordered by level
     */
    public function scopeOrderedByLevel($query)
    {
        return $query->orderBy('course_level')
            ->orderBy('order');
    }

    /**
     * Get the instructor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * ตรวจสอบว่างบประมาณหมดหรือไม่
     */
    public function isBudgetExhausted(): bool
    {
        if ($this->max_reward_budget === null) {
            return false; // ไม่จำกัดงบประมาณ
        }

        return $this->total_rewards_given >= $this->max_reward_budget;
    }

    /**
     * ดึงงบประมาณที่เหลือ
     */
    public function getRemainingBudget(): ?float
    {
        if ($this->max_reward_budget === null) {
            return null; // ไม่จำกัดงบประมาณ
        }

        return max(0, $this->max_reward_budget - $this->total_rewards_given);
    }

    /**
     * คำนวณรางวัลที่จะได้รับ (ตรวจสอบงบประมาณด้วย)
     *
     * @param  float  $multiplier  ตัวคูณรางวัล
     */
    public function calculateRewards(float $multiplier = 1.0): array
    {
        $rewards = [
            'points' => (int) ($this->points_reward * $multiplier),
            'coins' => (float) ($this->coin_reward * $multiplier),
            'money' => (float) ($this->money_reward * $multiplier),
            'exp' => (int) ($this->exp_reward * $multiplier),
            'pv' => (float) ($this->pv_value * $multiplier),
        ];

        // ตรวจสอบงบประมาณ
        if ($this->isBudgetExhausted()) {
            $rewards['coins'] = 0;
            $rewards['money'] = 0;
        }

        return $rewards;
    }

    /**
     * บันทึกการจ่ายรางวัล
     *
     * @param  float  $amount  จำนวนที่จ่าย
     */
    public function recordRewardGiven(float $amount): void
    {
        $this->total_rewards_given += $amount;

        // เช็คว่างบประมาณหมดหรือยัง
        if ($this->max_reward_budget !== null &&
            $this->total_rewards_given >= $this->max_reward_budget &&
            $this->budget_exhausted_at === null
        ) {
            $this->budget_exhausted_at = now();
        }

        $this->save();
    }

    /**
     * รีเซ็ตงบประมาณ
     *
     * @param  float|null  $newBudget  งบประมาณใหม่
     */
    public function resetBudget(?float $newBudget = null): void
    {
        $this->total_rewards_given = 0;
        $this->budget_exhausted_at = null;

        if ($newBudget !== null) {
            $this->max_reward_budget = $newBudget;
        }

        $this->save();
    }

    /**
     * รางวัลรวมทั้งหมดที่จะได้
     */
    public function getTotalRewardsLabelAttribute(): string
    {
        $parts = [];

        if ($this->points_reward > 0) {
            $parts[] = "{$this->points_reward} แต้ม";
        }
        if ($this->coin_reward > 0) {
            $parts[] = number_format($this->coin_reward, 2).' Coins';
        }
        if ($this->money_reward > 0) {
            $parts[] = '฿'.number_format($this->money_reward, 2);
        }
        if ($this->exp_reward > 0) {
            $parts[] = "{$this->exp_reward} EXP";
        }
        if ($this->pv_value > 0) {
            $parts[] = number_format($this->pv_value, 2).' PV';
        }

        return empty($parts) ? 'ไม่มีรางวัล' : implode(' + ', $parts);
    }
}
