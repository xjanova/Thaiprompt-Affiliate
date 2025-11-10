<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotContentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'content_type',
        'content',
        'media_urls',
        'hashtags',
        'variables',
        'category',
        'is_ai_generated',
        'ai_prompt',
        'is_public',
        'usage_count',
        'rating',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'hashtags' => 'array',
        'variables' => 'array',
        'is_ai_generated' => 'boolean',
        'is_public' => 'boolean',
        'rating' => 'decimal:2',
    ];

    /**
     * Get the user that owns the template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get automations using this template
     */
    public function automations(): HasMany
    {
        return $this->hasMany(BotAutomation::class, 'template_id');
    }

    /**
     * Scope: Only public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Render template with variables
     */
    public function render(array $data = []): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }

    /**
     * Get available variables in template
     */
    public function getAvailableVariables(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->content, $matches);
        return $matches[1] ?? [];
    }
}
