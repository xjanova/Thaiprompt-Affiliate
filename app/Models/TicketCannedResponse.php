<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCannedResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'shortcode',
        'content',
        'category_id',
        'tags',
        'is_public',
        'created_by',
        'usage_count',
        'last_used_at',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the category this canned response belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * Get the user who created this canned response
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active responses only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Public responses (available to all staff)
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Responses accessible by a specific user
     */
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('created_by', $userId);
        });
    }

    /**
     * Scope: Search by keyword
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%")
              ->orWhere('shortcode', 'like', "%{$keyword}%");
        });
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Ordered by usage
     */
    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get content with variable replacement
     */
    public function getRenderedContent($variables = [])
    {
        $content = $this->content;

        // Replace common variables
        $defaultVariables = [
            '{user_name}' => auth()->user()->name ?? '',
            '{current_date}' => now()->format('d/m/Y'),
            '{current_time}' => now()->format('H:i'),
        ];

        $allVariables = array_merge($defaultVariables, $variables);

        foreach ($allVariables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        return $content;
    }

    /**
     * Extract available variables from content
     */
    public function getAvailableVariables()
    {
        preg_match_all('/\{([^}]+)\}/', $this->content, $matches);
        return $matches[1] ?? [];
    }
}
