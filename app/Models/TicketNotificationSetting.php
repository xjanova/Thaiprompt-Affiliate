<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_on_new_ticket',
        'email_on_reply',
        'email_on_status_change',
        'email_on_assignment',
        'email_on_sla_breach',
        'notify_on_new_ticket',
        'notify_on_reply',
        'notify_on_status_change',
        'notify_on_assignment',
        'digest_frequency',
        'digest_time',
    ];

    protected $casts = [
        'email_on_new_ticket' => 'boolean',
        'email_on_reply' => 'boolean',
        'email_on_status_change' => 'boolean',
        'email_on_assignment' => 'boolean',
        'email_on_sla_breach' => 'boolean',
        'notify_on_new_ticket' => 'boolean',
        'notify_on_reply' => 'boolean',
        'notify_on_status_change' => 'boolean',
        'notify_on_assignment' => 'boolean',
    ];

    /**
     * Get the user these settings belong to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create settings for a user
     */
    public static function getOrCreateForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'email_on_new_ticket' => true,
                'email_on_reply' => true,
                'email_on_status_change' => true,
                'email_on_assignment' => true,
                'email_on_sla_breach' => true,
                'notify_on_new_ticket' => true,
                'notify_on_reply' => true,
                'notify_on_status_change' => true,
                'notify_on_assignment' => true,
                'digest_frequency' => 'none',
            ]
        );
    }

    /**
     * Check if user should be notified via email for an event
     */
    public function shouldNotifyEmail($event)
    {
        $field = 'email_on_' . $event;
        return $this->$field ?? false;
    }

    /**
     * Check if user should be notified in-app for an event
     */
    public function shouldNotifyInApp($event)
    {
        $field = 'notify_on_' . $event;
        return $this->$field ?? false;
    }
}
