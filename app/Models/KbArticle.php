<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KbArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'meta_description',
        'keywords',
        'tags',
        'author_id',
        'updated_by',
        'status',
        'is_public',
        'published_at',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'keywords' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['helpfulness_score'];

    /**
     * Get the category this article belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Get the author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the user who last updated this article
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get attachments
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(KbArticleAttachment::class, 'article_id');
    }

    /**
     * Get related tickets
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'kb_article_ticket', 'article_id', 'ticket_id')
            ->withPivot('was_helpful')
            ->withTimestamps();
    }

    /**
     * Scope: Published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Draft articles
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Featured articles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Popular articles
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('view_count', 'desc')->limit($limit);
    }

    /**
     * Scope: Most helpful articles
     */
    public function scopeMostHelpful($query, $limit = 10)
    {
        return $query->selectRaw('*, (helpful_count - not_helpful_count) as score')
            ->orderBy('score', 'desc')
            ->limit($limit);
    }

    /**
     * Scope: Search articles
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%")
              ->orWhere('summary', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope: Full-text search
     */
    public function scopeFullTextSearch($query, $keyword)
    {
        return $query->whereRaw(
            "MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$keyword]
        );
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }

    /**
     * Mark as helpful
     */
    public function markAsHelpful()
    {
        $this->increment('helpful_count');
    }

    /**
     * Mark as not helpful
     */
    public function markAsNotHelpful()
    {
        $this->increment('not_helpful_count');
    }

    /**
     * Get helpfulness score (0-100)
     */
    public function getHelpfulnessScoreAttribute()
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return null;
        }

        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Get reading time estimate (minutes)
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // Average reading speed: 200 words/minute
        return $minutes;
    }

    /**
     * Get excerpt
     */
    public function getExcerptAttribute()
    {
        if ($this->summary) {
            return $this->summary;
        }

        $text = strip_tags($this->content);
        return Str::limit($text, 200);
    }

    /**
     * Generate slug from title
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }

            if (empty($article->author_id)) {
                $article->author_id = auth()->id();
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }

            $article->updated_by = auth()->id();
        });
    }
}
