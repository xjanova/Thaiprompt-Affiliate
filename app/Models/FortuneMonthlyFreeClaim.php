<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneMonthlyFreeClaim Model
 *
 * บันทึกการ claim สิทธิ์ดูฟรีรายเดือน 1 แถว = 1 user/เดือน
 * UNIQUE(psid, platform, month_key) → กดซ้ำเดือนเดียวกันไม่ได้
 *
 * @property int $id
 * @property string $psid
 * @property string $platform
 * @property string $month_key
 * @property int $credits_granted
 * @property string $source
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property int|null $fortune_user_credit_id
 * @property \Carbon\Carbon $claimed_at
 */
class FortuneMonthlyFreeClaim extends Model
{
    protected $table = 'fortune_monthly_free_claims';

    protected $fillable = [
        'psid',
        'platform',
        'month_key',
        'credits_granted',
        'source',
        'ip',
        'user_agent',
        'referrer',
        'fortune_user_credit_id',
        'claimed_at',
    ];

    protected $casts = [
        'credits_granted' => 'integer',
        'claimed_at' => 'datetime',
    ];

    protected $attributes = [
        'platform' => 'facebook',
        'credits_granted' => 1,
        'source' => 'group_post',
    ];

    /**
     * คืน month_key ของเดือนปัจจุบัน (Asia/Bangkok)
     *
     * รูปแบบ "YYYY-MM" — เปลี่ยนเองทุกวันที่ 1 ของเดือน
     */
    public static function currentMonthKey(): string
    {
        return Carbon::now('Asia/Bangkok')->format('Y-m');
    }

    /**
     * ตรวจว่า user นี้ claim เดือนปัจจุบันแล้วหรือยัง
     */
    public static function hasClaimedThisMonth(string $psid, string $platform = 'facebook'): bool
    {
        return static::where('psid', $psid)
            ->where('platform', $platform)
            ->where('month_key', static::currentMonthKey())
            ->exists();
    }

    /**
     * ดึงรายการ claim ของ user นี้ทุกเดือน (เรียงล่าสุดก่อน)
     */
    public static function historyForUser(string $psid, string $platform = 'facebook', int $limit = 12)
    {
        return static::where('psid', $psid)
            ->where('platform', $platform)
            ->orderByDesc('month_key')
            ->limit($limit)
            ->get();
    }

    /**
     * ความสัมพันธ์กับ FortuneUserCredit ที่ได้รับเครดิต
     */
    public function fortuneUserCredit(): BelongsTo
    {
        return $this->belongsTo(FortuneUserCredit::class);
    }
}
