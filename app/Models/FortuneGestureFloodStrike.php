<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🎭 FortuneGestureFloodStrike — ประวัติ "ยิงสติกเกอร์/อีโมจิรัว" ของลูกค้าแต่ละคน
 *
 * 1 แถว = 1 ลูกค้า ต่อ 1 ช่องทาง
 *
 * เก็บใน DB ไม่ใช่ Cache เพราะ deploy.sh รัน cache:clear ทุกครั้ง
 * ⇒ ถ้าอยู่ Cache คนป่วนได้รีเซ็ตประวัติฟรีทุกครั้งที่เราพุชโค้ด
 *
 * @property int $id
 * @property string $platform facebook | line
 * @property string $platform_user_id
 * @property string|null $display_name
 * @property int $strikes จำนวนครั้งที่แตะเกณฑ์ในหน้าต่างปัจจุบัน
 * @property \Carbon\Carbon|null $window_started_at
 * @property \Carbon\Carbon|null $last_hit_at
 * @property int $warned_count
 * @property \Carbon\Carbon|null $last_warned_at
 * @property \Carbon\Carbon|null $banned_at
 * @property int $total_hits
 * @property string|null $last_sample
 */
class FortuneGestureFloodStrike extends Model
{
    protected $table = 'fortune_gesture_flood_strikes';

    protected $fillable = [
        'platform',
        'platform_user_id',
        'display_name',
        'strikes',
        'window_started_at',
        'last_hit_at',
        'warned_count',
        'last_warned_at',
        'banned_at',
        'total_hits',
        'last_sample',
    ];

    protected $casts = [
        'strikes' => 'integer',
        'warned_count' => 'integer',
        'total_hits' => 'integer',
        'window_started_at' => 'datetime',
        'last_hit_at' => 'datetime',
        'last_warned_at' => 'datetime',
        'banned_at' => 'datetime',
    ];
}
