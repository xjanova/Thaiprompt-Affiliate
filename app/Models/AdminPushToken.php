<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🔔 FCM token ของเครื่องที่ติดตั้งแอปแอดมิน
 *
 * ⚠️ token เป็นความลับระดับอุปกรณ์ — ห้าม log / ห้ามคืนใน JSON (ซ่อนไว้ใน $hidden แล้ว)
 *
 * @property int $id
 * @property int $user_id แอดมินเจ้าของเครื่อง
 * @property int|null $access_token_id Sanctum token ที่ใช้ลงทะเบียน
 * @property string $token FCM registration token
 * @property string $platform android | ios | web
 * @property string|null $device_id
 * @property string|null $app_version
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AdminPushToken extends Model
{
    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'admin_push_tokens';

    /**
     * คอลัมน์ที่ mass assign ได้
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'access_token_id',
        'token',
        'platform',
        'device_id',
        'app_version',
    ];

    /**
     * ซ่อน token จากทุก toArray()/JSON
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * การ cast ประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'access_token_id' => 'integer',
    ];

    /**
     * แอดมินเจ้าของเครื่อง
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
