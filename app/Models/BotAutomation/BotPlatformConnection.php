<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class BotPlatformConnection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'platform_id',
        'account_name',
        'account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'credentials',
        'settings',
        'is_active',
        'auto_reconnect',
        'last_connected_at',
        'last_post_at',
        'total_posts',
        'error_message',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'auto_reconnect' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_post_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'credentials',
    ];

    /**
     * Get the user that owns the connection
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the platform
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(BotSocialPlatform::class, 'platform_id');
    }

    /**
     * Get all scheduled posts using this connection
     */
    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(BotScheduledPost::class, 'platform_connection_id');
    }

    /**
     * Scope: Only active connections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return now()->greaterThan($this->token_expires_at);
    }

    /**
     * Check if connection is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isTokenExpired();
    }

    /**
     * Update last post timestamp
     */
    public function recordPost(): void
    {
        $this->increment('total_posts');
        $this->update(['last_post_at' => now()]);
    }
}
