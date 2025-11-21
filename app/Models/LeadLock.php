<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LeadLock Model
 *
 * ระบบล็อคผู้มุ่งหวัง (Lead Lock) 48 ชั่วโมง
 * เมื่อผู้มุ่งหวังเข้าลิงก์แม่ทีมคนใด จะถูกล็อคไว้ 48 ชม.
 * ถ้ามีคนอื่นส่งลิงก์ซ้อน จะให้สิทธิ์คนแรกที่ล็อคไว้
 *
 * @property int $id
 * @property string $visitor_identifier IP + Browser fingerprint hash
 * @property string|null $ip_address IP Address
 * @property string|null $user_agent Browser User Agent
 * @property int $team_leader_id แม่ทีมที่ล็อคไว้
 * @property int|null $mlm_member_id MLM Member ถ้าสมัครแล้ว
 * @property \Carbon\Carbon $locked_at เวลาที่ล็อค
 * @property \Carbon\Carbon $expires_at เวลาหมดอายุ
 * @property \Carbon\Carbon|null $converted_at เวลาที่สมัครสำเร็จ
 * @property string $status สถานะ (locked/expired/converted)
 * @property string|null $source_url URL ที่เข้ามา
 * @property string|null $referrer_url URL ที่อ้างอิง
 * @property array|null $utm_params UTM Parameters
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class LeadLock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lead_locks';

    /**
     * สถานะของ Lead Lock
     */
    const STATUS_LOCKED = 'locked';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CONVERTED = 'converted';

    /**
     * ระยะเวลาล็อค (48 ชั่วโมง)
     */
    const LOCK_DURATION_HOURS = 48;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'visitor_identifier',
        'ip_address',
        'user_agent',
        'team_leader_id',
        'mlm_member_id',
        'locked_at',
        'expires_at',
        'converted_at',
        'status',
        'source_url',
        'referrer_url',
        'utm_params',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'expires_at' => 'datetime',
            'converted_at' => 'datetime',
            'utm_params' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * ความสัมพันธ์กับ User (แม่ทีม)
     *
     * @return BelongsTo
     */
    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /**
     * ความสัมพันธ์กับ MlmMember (ถ้าสมัครแล้ว)
     *
     * @return BelongsTo
     */
    public function mlmMember(): BelongsTo
    {
        return $this->belongsTo(MlmMember::class, 'mlm_member_id');
    }

    /**
     * Scope: เฉพาะที่ล็อคอยู่
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocked($query)
    {
        return $query->where('status', self::STATUS_LOCKED)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope: เฉพาะที่หมดอายุ
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_LOCKED)
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope: เฉพาะที่แปลงแล้ว (converted)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * สร้าง Lead Lock ใหม่
     *
     * @param string $visitorIdentifier
     * @param int $teamLeaderId
     * @param array $additionalData
     * @return LeadLock
     */
    public static function createLock(
        string $visitorIdentifier,
        int $teamLeaderId,
        array $additionalData = []
    ): LeadLock {
        $lockedAt = now();
        $expiresAt = $lockedAt->copy()->addHours(self::LOCK_DURATION_HOURS);

        return self::create(array_merge([
            'visitor_identifier' => $visitorIdentifier,
            'team_leader_id' => $teamLeaderId,
            'locked_at' => $lockedAt,
            'expires_at' => $expiresAt,
            'status' => self::STATUS_LOCKED,
        ], $additionalData));
    }

    /**
     * ตรวจสอบว่ามี Lead Lock ที่ active อยู่หรือไม่
     *
     * @param string $visitorIdentifier
     * @return LeadLock|null
     */
    public static function getActiveLock(string $visitorIdentifier): ?LeadLock
    {
        return self::where('visitor_identifier', $visitorIdentifier)
            ->locked()
            ->orderBy('locked_at', 'desc')
            ->first();
    }

    /**
     * ทำเครื่องหมายว่าสมัครสำเร็จ (converted)
     *
     * @param int $mlmMemberId
     * @return bool
     */
    public function markAsConverted(int $mlmMemberId): bool
    {
        $this->status = self::STATUS_CONVERTED;
        $this->mlm_member_id = $mlmMemberId;
        $this->converted_at = now();

        // อัพเดทสถิติของ customization
        $customization = RecruitCustomization::where('user_id', $this->team_leader_id)->first();
        if ($customization) {
            $customization->incrementConversions();
        }

        return $this->save();
    }

    /**
     * ทำเครื่องหมายว่าหมดอายุ
     *
     * @return bool
     */
    public function markAsExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    /**
     * ตรวจสอบว่าหมดอายุหรือยัง
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * ตรวจสอบว่า active อยู่หรือไม่
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_LOCKED && !$this->isExpired();
    }

    /**
     * ดึงเวลาที่เหลือ (ในรูปแบบมนุษย์อ่าน)
     *
     * @return string
     */
    public function getTimeRemaining(): string
    {
        if ($this->isExpired()) {
            return 'หมดอายุแล้ว';
        }

        return $this->expires_at->diffForHumans(null, true);
    }
}
