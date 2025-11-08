<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'account_id', 'platform_id', 'sync_type', 'sync_status',
        'items_processed', 'items_created', 'items_updated', 'items_failed',
        'error_message', 'error_details',
        'started_at', 'completed_at', 'duration_seconds', 'triggered_by',
    ];

    protected $casts = [
        'error_details' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'account_id');
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlatform::class, 'platform_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('sync_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
