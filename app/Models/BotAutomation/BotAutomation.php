<?php

namespace App\Models\BotAutomation;

use App\Models\User;
use App\Models\AiBotProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BotAutomation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'ai_bot_profile_id',
        'name',
        'description',
        'automation_type',
        'trigger_type',
        'schedule_type',
        'cron_expression',
        'scheduled_at',
        'schedule_config',
        'content_source',
        'template_id',
        'custom_content',
        'ai_generation_prompt',
        'target_platforms',
        'settings',
        'is_active',
        'is_rental',
        'last_executed_at',
        'next_execution_at',
        'total_executions',
        'successful_executions',
        'failed_executions',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'schedule_config' => 'array',
        'target_platforms' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_rental' => 'boolean',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
    ];

    /**
     * Get the user that owns the automation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI bot profile
     */
    public function aiBotProfile(): BelongsTo
    {
        return $this->belongsTo(AiBotProfile::class);
    }

    /**
     * Get the content template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(BotContentTemplate::class, 'template_id');
    }

    /**
     * Get all scheduled posts
     */
    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(BotScheduledPost::class, 'automation_id');
    }

    /**
     * Get all executions
     */
    public function executions(): HasMany
    {
        return $this->hasMany(BotAutomationExecution::class, 'automation_id');
    }

    /**
     * Get marketplace listing
     */
    public function marketplaceListing(): HasMany
    {
        return $this->hasMany(BotMarketplaceListing::class, 'automation_id');
    }

    /**
     * Get support conversations
     */
    public function supportConversations(): HasMany
    {
        return $this->hasMany(BotSupportConversation::class, 'automation_id');
    }

    /**
     * Get sales conversations
     */
    public function salesConversations(): HasMany
    {
        return $this->hasMany(BotSalesConversation::class, 'automation_id');
    }

    /**
     * Scope: Only active automations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By automation type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('automation_type', $type);
    }

    /**
     * Scope: Due for execution
     */
    public function scopeDueForExecution($query)
    {
        return $query->where('is_active', true)
                     ->where('next_execution_at', '<=', now());
    }

    /**
     * Calculate next execution time
     */
    public function calculateNextExecution(): ?Carbon
    {
        if ($this->schedule_type === 'once') {
            return null; // One-time execution
        }

        $now = now();

        return match($this->schedule_type) {
            'hourly' => $now->addHour(),
            'daily' => $now->addDay()->setTime(
                $this->schedule_config['hour'] ?? 9,
                $this->schedule_config['minute'] ?? 0
            ),
            'weekly' => $now->addWeek(),
            'monthly' => $now->addMonth(),
            'cron' => $this->getNextCronExecution(),
            default => null,
        };
    }

    /**
     * Get next cron execution time
     */
    protected function getNextCronExecution(): ?Carbon
    {
        if (!$this->cron_expression) {
            return null;
        }

        try {
            $cron = new \Cron\CronExpression($this->cron_expression);
            return Carbon::instance($cron->getNextRunDate());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Record execution
     */
    public function recordExecution(bool $success = true): void
    {
        $this->increment('total_executions');

        if ($success) {
            $this->increment('successful_executions');
        } else {
            $this->increment('failed_executions');
        }

        $this->update([
            'last_executed_at' => now(),
            'next_execution_at' => $this->calculateNextExecution(),
        ]);
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->total_executions === 0) {
            return 0;
        }

        return ($this->successful_executions / $this->total_executions) * 100;
    }
}
