<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CMCSyncLog extends Model
{
    use HasFactory;

    protected $table = 'cmc_sync_logs';

    protected $fillable = [
        'token_id',
        'sync_type',
        'cmc_id',
        'cmc_slug',
        'price_data',
        'market_data',
        'old_price_usd',
        'new_price_usd',
        'price_change_percent',
        'status',
        'error_message',
        'synced_by',
    ];

    protected $casts = [
        'price_data' => 'array',
        'market_data' => 'array',
    ];

    /**
     * Relationships
     */
    public function token()
    {
        return $this->belongsTo(TPIXToken::class, 'token_id');
    }

    public function syncedBy()
    {
        return $this->belongsTo(User::class, 'synced_by');
    }

    /**
     * Scopes
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeManual($query)
    {
        return $query->where('sync_type', 'manual');
    }

    public function scopeAuto($query)
    {
        return $query->where('sync_type', 'auto');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Helpers
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isManual(): bool
    {
        return $this->sync_type === 'manual';
    }

    public function getPriceChangeDirection(): string
    {
        if (!$this->price_change_percent) {
            return 'neutral';
        }

        return $this->price_change_percent > 0 ? 'up' : 'down';
    }

    public function getPriceChangeColor(): string
    {
        $direction = $this->getPriceChangeDirection();

        return match($direction) {
            'up' => 'green',
            'down' => 'red',
            default => 'gray',
        };
    }

    public function getFormattedPriceChange(): string
    {
        if (!$this->price_change_percent) {
            return '0%';
        }

        $sign = $this->price_change_percent > 0 ? '+' : '';
        return $sign . number_format($this->price_change_percent, 2) . '%';
    }
}
