<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarotUserLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'limit_date',
        'free_count',
        'paid_count',
        'session_id',
        'ip_address',
    ];

    protected $casts = [
        'limit_date' => 'date',
        'free_count' => 'integer',
        'paid_count' => 'integer',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(TarotReadingCategory::class, 'category_id');
    }

    /**
     * Check if user can use free reading (with IP and quota checking)
     */
    public static function canUseFreeReading($categoryId, $userId = null, $sessionId = null, $ipAddress = null): bool
    {
        $category = TarotReadingCategory::find($categoryId);

        if (!$category || !$category->is_free_first) {
            return false;
        }

        // Get quota limits from settings
        $settings = TarotSetting::getSettings();
        $maxLimit = $userId ? ($settings['member_daily_limit'] ?? 5) : ($settings['guest_daily_limit'] ?? 1);

        $query = static::where('category_id', $categoryId)
            ->where('limit_date', today());

        // Check by user, session, or IP
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function ($q) use ($sessionId, $ipAddress) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($ipAddress && ($settings['enable_ip_tracking'] ?? true)) {
                    $q->orWhere('ip_address', $ipAddress);
                }
            });
        }

        $limit = $query->first();

        if (!$limit) {
            return true; // No record yet, allow
        }

        return $limit->free_count < $maxLimit;
    }

    /**
     * Get remaining free quota for user/guest
     */
    public static function getRemainingQuota($categoryId, $userId = null, $sessionId = null, $ipAddress = null): int
    {
        $settings = TarotSetting::getSettings();
        $maxLimit = $userId ? ($settings['member_daily_limit'] ?? 5) : ($settings['guest_daily_limit'] ?? 1);

        $query = static::where('category_id', $categoryId)
            ->where('limit_date', today());

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where(function ($q) use ($sessionId, $ipAddress, $settings) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($ipAddress && ($settings['enable_ip_tracking'] ?? true)) {
                    $q->orWhere('ip_address', $ipAddress);
                }
            });
        }

        $limit = $query->first();

        if (!$limit) {
            return $maxLimit;
        }

        $remaining = $maxLimit - $limit->free_count;
        return max(0, $remaining);
    }

    /**
     * Increment free reading count
     */
    public static function incrementFreeReading($categoryId, $userId = null, $sessionId = null, $ipAddress = null)
    {
        $data = [
            'category_id' => $categoryId,
            'limit_date' => today(),
            'free_count' => 1,
        ];

        if ($userId) {
            $data['user_id'] = $userId;
        }
        if ($sessionId) {
            $data['session_id'] = $sessionId;
        }
        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        $limit = static::updateOrCreate(
            [
                'category_id' => $categoryId,
                'limit_date' => today(),
                'user_id' => $userId,
            ],
            $data
        );

        if ($limit->wasRecentlyCreated) {
            $limit->free_count = 1;
        } else {
            $limit->increment('free_count');
        }

        return $limit;
    }

    /**
     * Increment paid reading count
     */
    public static function incrementPaidReading($categoryId, $userId = null, $sessionId = null, $ipAddress = null)
    {
        $data = [
            'category_id' => $categoryId,
            'limit_date' => today(),
            'paid_count' => 1,
        ];

        if ($userId) {
            $data['user_id'] = $userId;
        }
        if ($sessionId) {
            $data['session_id'] = $sessionId;
        }
        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        $limit = static::updateOrCreate(
            [
                'category_id' => $categoryId,
                'limit_date' => today(),
                'user_id' => $userId,
            ],
            $data
        );

        if ($limit->wasRecentlyCreated) {
            $limit->paid_count = 1;
        } else {
            $limit->increment('paid_count');
        }

        return $limit;
    }

    /**
     * Scope for today's limits
     */
    public function scopeToday($query)
    {
        return $query->where('limit_date', today());
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific session
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
