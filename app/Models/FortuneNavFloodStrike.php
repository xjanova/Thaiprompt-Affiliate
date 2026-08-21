<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🚦 FortuneNavFloodStrike — ประวัติ "กดปุ่มรัว" ของลูกค้าแต่ละคน
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
 * @property string|null $last_payload
 */
class FortuneNavFloodStrike extends Model
{
    protected $table = 'fortune_nav_flood_strikes';

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
        'last_payload',
    ];

    protected $casts = [
        'strikes' => 'integer',
        'warned_count' => 'integer',
        'window_started_at' => 'datetime',
        'last_hit_at' => 'datetime',
        'last_warned_at' => 'datetime',
        'banned_at' => 'datetime',
    ];
}
