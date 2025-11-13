<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'prompt',
        'html_content',
        'provider',
        'model',
        'tokens_used',
        'cost',
        'views',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tokens_used' => 'integer',
        'cost' => 'decimal:4',
        'views' => 'integer',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the generated page.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the preview URL for this page.
     */
    public function getPreviewUrlAttribute(): string
    {
        return route('prompt-to-web.preview', $this->slug);
    }

    /**
     * Get the full HTML URL for this page.
     */
    public function getFullUrlAttribute(): string
    {
        return route('prompt-to-web.show', $this->slug);
    }

    /**
     * Scope a query to only include public pages.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include pages by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCostAttribute(): string
    {
        return number_format($this->cost, 4) . ' USD';
    }

    /**
     * Get formatted tokens.
     */
    public function getFormattedTokensAttribute(): string
    {
        return number_format($this->tokens_used) . ' tokens';
    }

    /**
     * Get the short description (first 100 characters).
     */
    public function getShortDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }

        return strlen($this->description) > 100
            ? substr($this->description, 0, 100) . '...'
            : $this->description;
    }
}
