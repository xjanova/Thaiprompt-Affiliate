<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FortuneDataDeletionRequest Model
 *
 * บันทึกคำขอลบข้อมูลตาม PDPA — ทั้งจากในแชท ("ลบข้อมูลของฉัน")
 * และจาก Facebook Data Deletion Callback (signed_request)
 *
 * @property int $id
 * @property string $confirmation_code รหัสยืนยัน (ใช้เปิดหน้าเช็คสถานะ)
 * @property string $platform line | facebook
 * @property string|null $platform_user_id LINE userId / FB PSID
 * @property string $source in_chat | facebook_callback
 * @property string $status pending | processing | completed | failed
 * @property array|null $summary สรุป counts ต่อ table
 * @property string|null $error ข้อความ error (ไม่มี PII)
 * @property \Carbon\Carbon|null $requested_at
 * @property \Carbon\Carbon|null $completed_at
 */
class FortuneDataDeletionRequest extends Model
{
    /** สถานะคำขอ */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** ที่มาของคำขอ */
    public const SOURCE_IN_CHAT = 'in_chat';

    public const SOURCE_FACEBOOK_CALLBACK = 'facebook_callback';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fortune_data_deletion_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'confirmation_code',
        'platform',
        'platform_user_id',
        'source',
        'status',
        'summary',
        'error',
        'requested_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'summary' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
