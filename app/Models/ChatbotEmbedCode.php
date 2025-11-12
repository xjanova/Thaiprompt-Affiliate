<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChatbotEmbedCode extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'name',
        'embed_type',
        'unique_code',
        'appearance_config',
        'widget_position',
        'primary_color',
        'secondary_color',
        'widget_size',
        'auto_open',
        'auto_open_delay',
        'welcome_message',
        'show_avatar',
        'show_typing_indicator',
        'allowed_domains',
        'blocked_ips',
        'rate_limit_per_minute',
        'enable_file_upload',
        'enable_voice_input',
        'enable_emoji',
        'show_powered_by',
        'total_installations',
        'total_conversations',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'appearance_config' => 'array',
        'allowed_domains' => 'array',
        'blocked_ips' => 'array',
        'auto_open' => 'boolean',
        'show_avatar' => 'boolean',
        'show_typing_indicator' => 'boolean',
        'enable_file_upload' => 'boolean',
        'enable_voice_input' => 'boolean',
        'enable_emoji' => 'boolean',
        'show_powered_by' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->unique_code)) {
                $model->unique_code = Str::random(32);
            }
        });
    }

    /**
     * Get the bot profile
     */
    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class, 'bot_profile_id');
    }

    /**
     * Scope: Only active embed codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if domain is allowed
     */
    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true; // No restriction
        }

        foreach ($this->allowed_domains as $allowedDomain) {
            if (str_contains($domain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        return in_array($ip, $this->blocked_ips ?? []);
    }

    /**
     * Increment installation count
     */
    public function incrementInstallation(): void
    {
        $this->increment('total_installations');
    }

    /**
     * Increment conversation count
     */
    public function incrementConversation(): void
    {
        $this->increment('total_conversations');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get embed script
     */
    public function getEmbedScript(): string
    {
        $config = [
            'botId' => $this->unique_code,
            'type' => $this->embed_type,
            'position' => $this->widget_position,
            'primaryColor' => $this->primary_color,
            'secondaryColor' => $this->secondary_color,
            'size' => $this->widget_size,
            'autoOpen' => $this->auto_open,
            'autoOpenDelay' => $this->auto_open_delay,
            'welcomeMessage' => $this->welcome_message,
            'showAvatar' => $this->show_avatar,
            'showTypingIndicator' => $this->show_typing_indicator,
            'enableFileUpload' => $this->enable_file_upload,
            'enableVoiceInput' => $this->enable_voice_input,
            'enableEmoji' => $this->enable_emoji,
            'showPoweredBy' => $this->show_powered_by,
        ];

        $configJson = json_encode($config);
        $apiUrl = config('app.url');

        return <<<HTML
<script>
  (function() {
    var chatbotConfig = {$configJson};
    var script = document.createElement('script');
    script.src = '{$apiUrl}/chatbot-widget.js';
    script.async = true;
    script.onload = function() {
      window.initChatbot(chatbotConfig);
    };
    document.head.appendChild(script);
  })();
</script>
HTML;
    }

    /**
     * Get embed HTML (for inline type)
     */
    public function getEmbedHtml(): string
    {
        $apiUrl = config('app.url');

        return <<<HTML
<div id="chatbot-{$this->unique_code}"
     data-bot-id="{$this->unique_code}"
     data-primary-color="{$this->primary_color}"
     data-secondary-color="{$this->secondary_color}">
</div>
<script src="{$apiUrl}/chatbot-widget.js"></script>
<script>
  window.initChatbot({
    botId: '{$this->unique_code}',
    type: 'inline',
    containerId: 'chatbot-{$this->unique_code}'
  });
</script>
HTML;
    }

    /**
     * Get share URL
     */
    public function getShareUrl(): string
    {
        return route('chatbot.embed.preview', ['code' => $this->unique_code]);
    }
}
