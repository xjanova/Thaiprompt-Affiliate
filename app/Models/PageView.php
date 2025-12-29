<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Page View Model
 *
 * เก็บสถิติการเข้าชมหน้าเว็บของผู้ใช้
 * ใช้สำหรับวิเคราะห์พฤติกรรมและปรับปรุง UX
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $url
 * @property string $path
 * @property string|null $route_name
 * @property string|null $page_title
 * @property string $method
 * @property int $status_code
 * @property string|null $referrer
 * @property string|null $referrer_domain
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $platform
 * @property bool $is_robot
 * @property string|null $country
 * @property string|null $country_code
 * @property string|null $city
 * @property int|null $response_time_ms
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property \Carbon\Carbon $viewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PageView extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_views';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'url',
        'path',
        'route_name',
        'page_title',
        'method',
        'status_code',
        'referrer',
        'referrer_domain',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'browser_version',
        'platform',
        'is_robot',
        'country',
        'country_code',
        'city',
        'response_time_ms',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'status_code' => 'integer',
        'is_robot' => 'boolean',
        'response_time_ms' => 'integer',
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Scope: กรองเฉพาะผู้ใช้จริง (ไม่ใช่ bot)
     */
    public function scopeHuman($query)
    {
        return $query->where('is_robot', false);
    }

    /**
     * Scope: กรองตามช่วงเวลา
     */
    public function scopePeriod($query, string $period)
    {
        $start = match ($period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };

        $end = match ($period) {
            'yesterday' => now()->subDay()->endOfDay(),
            default => now(),
        };

        return $query->whereBetween('viewed_at', [$start, $end]);
    }

    /**
     * Scope: กรองตาม device type
     */
    public function scopeDevice($query, string $type)
    {
        return $query->where('device_type', $type);
    }

    // ========================================
    // Static Methods สำหรับ Analytics
    // ========================================

    /**
     * ดึงสถิติภาพรวม
     *
     * @param  string  $period  ช่วงเวลา (today, week, month, etc.)
     */
    public static function getSummary(string $period = '7days'): array
    {
        $query = self::human()->period($period);

        $totalViews = $query->count();
        $uniqueVisitors = $query->distinct('session_id')->count('session_id');
        $uniqueUsers = $query->whereNotNull('user_id')->distinct('user_id')->count('user_id');

        // Page views เฉลี่ยต่อ session
        $avgPagesPerSession = $uniqueVisitors > 0
            ? round($totalViews / $uniqueVisitors, 2)
            : 0;

        return [
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'unique_users' => $uniqueUsers,
            'avg_pages_per_session' => $avgPagesPerSession,
        ];
    }

    /**
     * ดึงหน้าที่มีคนเข้าชมมากที่สุด
     *
     * @param  int  $limit  จำนวนที่จะแสดง
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getTopPages(int $limit = 10, string $period = '7days')
    {
        return self::human()
            ->period($period)
            ->select('path', 'route_name', DB::raw('COUNT(*) as views'))
            ->groupBy('path', 'route_name')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงแหล่งที่มาของ traffic
     *
     * @param  int  $limit  จำนวนที่จะแสดง
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getTopReferrers(int $limit = 10, string $period = '7days')
    {
        return self::human()
            ->period($period)
            ->whereNotNull('referrer_domain')
            ->select('referrer_domain', DB::raw('COUNT(*) as visits'))
            ->groupBy('referrer_domain')
            ->orderByDesc('visits')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสถิติตาม device type
     *
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getDeviceStats(string $period = '7days')
    {
        return self::human()
            ->period($period)
            ->select('device_type', DB::raw('COUNT(*) as views'))
            ->groupBy('device_type')
            ->orderByDesc('views')
            ->get();
    }

    /**
     * ดึงสถิติตามประเทศ
     *
     * @param  int  $limit  จำนวนที่จะแสดง
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getCountryStats(int $limit = 10, string $period = '7days')
    {
        return self::human()
            ->period($period)
            ->whereNotNull('country')
            ->select('country', 'country_code', DB::raw('COUNT(*) as views'))
            ->groupBy('country', 'country_code')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสถิติตาม browser
     *
     * @param  int  $limit  จำนวนที่จะแสดง
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getBrowserStats(int $limit = 10, string $period = '7days')
    {
        return self::human()
            ->period($period)
            ->whereNotNull('browser')
            ->select('browser', DB::raw('COUNT(*) as views'))
            ->groupBy('browser')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงสถิติรายวัน
     *
     * @param  int  $days  จำนวนวัน
     * @return \Illuminate\Support\Collection
     */
    public static function getDailyStats(int $days = 30)
    {
        return self::human()
            ->where('viewed_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(viewed_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as unique_visitors')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * ดึงสถิติรายชั่วโมง (สำหรับวันนี้)
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getHourlyStats()
    {
        return self::human()
            ->whereDate('viewed_at', today())
            ->select(
                DB::raw('HOUR(viewed_at) as hour'),
                DB::raw('COUNT(*) as views')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * ดึง UTM Campaign Stats
     *
     * @param  string  $period  ช่วงเวลา
     * @return \Illuminate\Support\Collection
     */
    public static function getUtmStats(string $period = '30days')
    {
        return self::human()
            ->period($period)
            ->whereNotNull('utm_source')
            ->select(
                'utm_source',
                'utm_medium',
                'utm_campaign',
                DB::raw('COUNT(*) as visits'),
                DB::raw('COUNT(DISTINCT session_id) as unique_visitors')
            )
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('visits')
            ->get();
    }

    /**
     * ดึงข้อมูลผู้ใช้รายเดียว
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getUserActivity(int $userId, int $days = 30)
    {
        return self::where('user_id', $userId)
            ->where('viewed_at', '>=', now()->subDays($days))
            ->orderByDesc('viewed_at')
            ->limit(100)
            ->get();
    }
}
