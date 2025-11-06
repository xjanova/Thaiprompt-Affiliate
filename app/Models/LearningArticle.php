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
        'tags',
        'metadata',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
        'published_at' => 'datetime',
        'views' => 'integer',
        'estimated_duration' => 'integer',
        'order' => 'integer',
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
        if (!$this->userHasAccess($user)) {
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

            if (!$progress || $progress->status !== 'completed') {
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
            return $this->estimated_duration . ' นาที';
        }

        $hours = floor($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;

        if ($minutes === 0) {
            return $hours . ' ชั่วโมง';
        }

        return $hours . ' ชั่วโมง ' . $minutes . ' นาที';
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
}
