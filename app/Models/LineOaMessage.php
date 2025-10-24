<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineOaMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_oa_config_id',
        'user_id',
        'line_user_id',
        'direction',
        'message_type',
        'message_content',
        'message_data',
        'reply_token',
        'status',
        'sent_at',
        'delivered_at',
        'error_message',
    ];

    protected $casts = [
        'message_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
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
     * Check if incoming
     */
    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    /**
     * Check if outgoing
     */
    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    /**
     * Check if sent
     */
    public function isSent(): bool
    {
        return $this->status === 'sent' || $this->status === 'delivered';
    }

    /**
     * Check if delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): bool
    {
        $this->status = 'sent';
        $this->sent_at = now();
        return $this->save();
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): bool
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        return $this->save();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $error): bool
    {
        $this->status = 'failed';
        $this->error_message = $error;
        return $this->save();
    }

    /**
     * Scope for incoming messages
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    /**
     * Scope for outgoing messages
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by message type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }
}
