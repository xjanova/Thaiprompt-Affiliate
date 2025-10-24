<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineOaWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_oa_config_id',
        'event_type',
        'line_user_id',
        'user_id',
        'payload',
        'processed',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the LINE OA config
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(LineOaConfig::class, 'line_oa_config_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(?string $error = null): bool
    {
        $this->processed = true;
        $this->processed_at = now();
        if ($error) {
            $this->processing_error = $error;
        }
        return $this->save();
    }

    /**
     * Check if processed
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * Check if has error
     */
    public function hasError(): bool
    {
        return !empty($this->processing_error);
    }

    /**
     * Get event data
     */
    public function getEventData(string $key, $default = null)
    {
        return data_get($this->payload, $key, $default);
    }

    /**
     * Scope for unprocessed
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    /**
     * Scope for processed
     */
    public function scopeProcessed($query)
    {
        return $query->where('processed', true);
    }

    /**
     * Scope by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope with errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereNotNull('processing_error');
    }
}
