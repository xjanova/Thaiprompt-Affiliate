<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotPlatformIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bot_profile_id',
        'platform_type',
        'platform_name',
        'access_token',
        'refresh_token',
        'webhook_url',
        'webhook_secret',
        'platform_credentials',
        'line_channel_id',
        'line_channel_secret',
        'line_channel_access_token',
        'fb_page_id',
        'fb_app_id',
        'fb_app_secret',
        'telegram_bot_token',
        'telegram_username',
        'discord_bot_token',
        'discord_client_id',
        'enabled_features',
        'platform_settings',
        'auto_reply_enabled',
        'keyword_matching_enabled',
        'ai_fallback_enabled',
        'response_delay_ms',
        'allowed_rooms',
        'blocked_rooms',
        'work_in_all_rooms',
        'can_post_content',
        'admin_rooms',
        'is_active',
        'is_verified',
        'last_connected_at',
        'last_message_at',
        'connection_error',
        'total_messages_received',
        'total_messages_sent',
        'total_users',
    ];

    protected $casts = [
        'platform_credentials' => 'array',
        'enabled_features' => 'array',
        'platform_settings' => 'array',
        'allowed_rooms' => 'array',
        'blocked_rooms' => 'array',
        'admin_rooms' => 'array',
        'auto_reply_enabled' => 'boolean',
        'keyword_matching_enabled' => 'boolean',
        'ai_fallback_enabled' => 'boolean',
        'work_in_all_rooms' => 'boolean',
        'can_post_content' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'last_connected_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'webhook_secret',
        'line_channel_secret',
        'line_channel_access_token',
        'fb_app_secret',
        'telegram_bot_token',
        'discord_bot_token',
    ];

    /**
     * Get the bot profile
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Scope: Only active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only verified integrations
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope: By platform type
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform_type', $platform);
    }

    /**
     * Check if room is allowed
     */
    public function isRoomAllowed(string $roomId): bool
    {
        if ($this->work_in_all_rooms) {
            // Check if in blocked list
            return !in_array($roomId, $this->blocked_rooms ?? []);
        }

        // Only work in allowed rooms
        return in_array($roomId, $this->allowed_rooms ?? []);
    }

    /**
     * Check if is admin room
     */
    public function isAdminRoom(string $roomId): bool
    {
        return in_array($roomId, $this->admin_rooms ?? []);
    }

    /**
     * Increment message received
     */
    public function incrementMessageReceived(): void
    {
        $this->increment('total_messages_received');
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Increment message sent
     */
    public function incrementMessageSent(): void
    {
        $this->increment('total_messages_sent');
    }

    /**
     * Mark as connected
     */
    public function markAsConnected(): void
    {
        $this->update([
            'is_verified' => true,
            'last_connected_at' => now(),
            'connection_error' => null,
        ]);
    }

    /**
     * Mark as disconnected
     */
    public function markAsDisconnected(string $error = null): void
    {
        $this->update([
            'is_verified' => false,
            'connection_error' => $error,
        ]);
    }

    /**
     * Get platform display name
     */
    public function getPlatformDisplayName(): string
    {
        return $this->platform_name ?? ucfirst($this->platform_type);
    }

    /**
     * Get webhook URL for this integration
     */
    public function getWebhookUrl(): string
    {
        return $this->webhook_url ?? route('chatbot.webhook', [
            'platform' => $this->platform_type,
            'integration_id' => $this->id,
        ]);
    }

    /**
     * Verify platform credentials
     */
    public function verifyCredentials(): bool
    {
        return match ($this->platform_type) {
            'line' => !empty($this->line_channel_id) && !empty($this->line_channel_secret) && !empty($this->line_channel_access_token),
            'facebook', 'instagram' => !empty($this->fb_page_id) && !empty($this->access_token),
            'telegram' => !empty($this->telegram_bot_token),
            'discord' => !empty($this->discord_bot_token) && !empty($this->discord_client_id),
            default => !empty($this->access_token),
        };
    }

    /**
     * Get connection status
     */
    public function getConnectionStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->is_verified) {
            return 'not_verified';
        }

        if ($this->connection_error) {
            return 'error';
        }

        if ($this->last_connected_at && $this->last_connected_at->diffInHours(now()) > 24) {
            return 'stale';
        }

        return 'connected';
    }
}
