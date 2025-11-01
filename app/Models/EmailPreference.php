<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketing_emails',
        'security_alerts',
        'commission_notifications',
        'withdrawal_notifications',
        'system_announcements',
        'weekly_reports',
        'all_emails',
        'preferred_language',
        'custom_preferences',
    ];

    protected $casts = [
        'marketing_emails' => 'boolean',
        'security_alerts' => 'boolean',
        'commission_notifications' => 'boolean',
        'withdrawal_notifications' => 'boolean',
        'system_announcements' => 'boolean',
        'weekly_reports' => 'boolean',
        'all_emails' => 'boolean',
        'custom_preferences' => 'array',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can receive specific email type.
     */
    public function canReceive(string $type): bool
    {
        // If all emails are disabled, return false
        if (!$this->all_emails) {
            return false;
        }

        // Map email types to preference fields
        $typeMap = [
            'marketing' => 'marketing_emails',
            'security' => 'security_alerts',
            'commission' => 'commission_notifications',
            'withdrawal' => 'withdrawal_notifications',
            'announcement' => 'system_announcements',
            'report' => 'weekly_reports',
        ];

        // Check if type exists in map
        if (isset($typeMap[$type])) {
            return $this->{$typeMap[$type]};
        }

        // Check custom preferences
        if ($this->custom_preferences && isset($this->custom_preferences[$type])) {
            return $this->custom_preferences[$type];
        }

        // Default to true for unknown types if all_emails is enabled
        return true;
    }

    /**
     * Disable all email notifications.
     */
    public function disableAll(): void
    {
        $this->update(['all_emails' => false]);
    }

    /**
     * Enable all email notifications.
     */
    public function enableAll(): void
    {
        $this->update([
            'all_emails' => true,
            'marketing_emails' => true,
            'security_alerts' => true,
            'commission_notifications' => true,
            'withdrawal_notifications' => true,
            'system_announcements' => true,
            'weekly_reports' => true,
        ]);
    }

    /**
     * Get or create preference for user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'marketing_emails' => true,
                'security_alerts' => true,
                'commission_notifications' => true,
                'withdrawal_notifications' => true,
                'system_announcements' => true,
                'weekly_reports' => true,
                'all_emails' => true,
                'preferred_language' => 'th',
            ]
        );
    }
}
