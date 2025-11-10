<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAnalyticsSetting extends Model
{
    protected $fillable = [
        'store_id',
        'retention_days',
        'auto_cleanup_enabled',
        'track_visits',
        'track_unique_visitors',
        'track_session_duration',
        'anonymize_ip',
        'respect_dnt',
        'email_weekly_reports',
        'email_monthly_reports',
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'auto_cleanup_enabled' => 'boolean',
        'track_visits' => 'boolean',
        'track_unique_visitors' => 'boolean',
        'track_session_duration' => 'boolean',
        'anonymize_ip' => 'boolean',
        'respect_dnt' => 'boolean',
        'email_weekly_reports' => 'boolean',
        'email_monthly_reports' => 'boolean',
    ];

    /**
     * Get the store
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'store_id');
    }

    /**
     * Get or create settings for a store
     */
    public static function getForStore(VendorStore $store): self
    {
        return self::firstOrCreate(
            ['store_id' => $store->id],
            [
                'retention_days' => 90,
                'auto_cleanup_enabled' => true,
                'track_visits' => true,
                'track_unique_visitors' => true,
                'track_session_duration' => true,
                'anonymize_ip' => true,
                'respect_dnt' => true,
            ]
        );
    }
}
