<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * VideoMissionCompletion Model
 *
 * บันทึกการทำภารกิจของผู้ใช้พร้อมข้อมูลป้องกันการโกง
 * เก็บ timestamps และสถิติต่างๆ เพื่อตรวจสอบความถูกต้อง
 *
 * @property int $id
 * @property int $user_id ID ของผู้ใช้
 * @property int $mission_id ID ของภารกิจ
 * @property string $session_token Token สำหรับ session นี้
 * @property string $status สถานะ (started, watching, completed, verified, failed)
 * @property int $watched_seconds เวลาที่ดูไป (วินาที)
 * @property bool $reward_given ให้รางวัลแล้ว
 * @property bool $is_suspicious น่าสงสัย
 */
class VideoMissionCompletion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * ชื่อตาราง
     *
     * @var string
     */
    protected $table = 'video_mission_completions';

    /**
     * คอลัมน์ที่สามารถกรอกข้อมูลได้
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'mission_id',
        'session_token',
        'device_fingerprint',
        'status',
        'watched_seconds',
        'required_seconds',
        'watch_percentage',
        'video_position_seconds',
        'playback_speed',
        'started_at',
        'last_heartbeat_at',
        'completed_at',
        'verified_at',
        'reward_claimed_at',
        'heartbeat_count',
        'tab_switch_count',
        'pause_count',
        'seek_count',
        'total_seek_seconds',
        'focus_lost_count',
        'interaction_count',
        'interaction_timestamps',
        'watch_segments',
        'suspicious_activities',
        'is_suspicious',
        'passed_verification',
        'verification_notes',
        'verification_answers',
        'verification_score',
        'reward_given',
        'reward_money',
        'reward_coins',
        'reward_points',
        'reward_tpix',
        'reward_exp',
        'bonus_multiplier_applied',
        'ip_address',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'country_code',
        'city',
        'completion_date',
        'completion_week',
        'completion_month',
        'completion_year',
        'verified_by',
        'rejected_by',
        'admin_notes',
    ];

    /**
     * การแปลงประเภทข้อมูล
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Integer
        'watched_seconds' => 'integer',
        'required_seconds' => 'integer',
        'video_position_seconds' => 'integer',
        'heartbeat_count' => 'integer',
        'tab_switch_count' => 'integer',
        'pause_count' => 'integer',
        'seek_count' => 'integer',
        'total_seek_seconds' => 'integer',
        'focus_lost_count' => 'integer',
        'interaction_count' => 'integer',
        'verification_score' => 'integer',
        'reward_points' => 'integer',
        'reward_exp' => 'integer',
        'completion_week' => 'integer',
        'completion_month' => 'integer',
        'completion_year' => 'integer',

        // Decimal
        'watch_percentage' => 'decimal:2',
        'playback_speed' => 'decimal:2',
        'reward_money' => 'decimal:2',
        'reward_coins' => 'decimal:2',
        'reward_tpix' => 'decimal:8',
        'bonus_multiplier_applied' => 'decimal:2',

        // Boolean
        'is_suspicious' => 'boolean',
        'passed_verification' => 'boolean',
        'reward_given' => 'boolean',

        // DateTime
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'reward_claimed_at' => 'datetime',

        // Date
        'completion_date' => 'date',

        // JSON
        'interaction_timestamps' => 'array',
        'watch_segments' => 'array',
        'suspicious_activities' => 'array',
        'verification_answers' => 'array',
    ];

    /**
     * ค่าเริ่มต้น
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'started',
        'watched_seconds' => 0,
        'watch_percentage' => 0,
        'video_position_seconds' => 0,
        'playback_speed' => 1.00,
        'heartbeat_count' => 0,
        'tab_switch_count' => 0,
        'pause_count' => 0,
        'seek_count' => 0,
        'total_seek_seconds' => 0,
        'focus_lost_count' => 0,
        'interaction_count' => 0,
        'is_suspicious' => false,
        'reward_given' => false,
        'bonus_multiplier_applied' => 1.00,
    ];

    // ==================== BOOT ====================

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // สร้าง session token อัตโนมัติ
        static::creating(function ($model) {
            if (!$model->session_token) {
                $model->session_token = static::generateSessionToken();
            }

            // ตั้งค่า completion date/week/month/year
            $now = now();
            $model->completion_date = $now->toDateString();
            $model->completion_week = $now->weekOfYear;
            $model->completion_month = $now->month;
            $model->completion_year = $now->year;

            // ตั้งค่า started_at
            if (!$model->started_at) {
                $model->started_at = $now;
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * ความสัมพันธ์กับ User
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ความสัมพันธ์กับ VideoMission
     *
     * @return BelongsTo
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(VideoMission::class, 'mission_id');
    }

    /**
     * ความสัมพันธ์กับ Admin ที่ verify
     *
     * @return BelongsTo
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * ความสัมพันธ์กับ Admin ที่ reject
     *
     * @return BelongsTo
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // ==================== SCOPES ====================

    /**
     * เฉพาะที่ทำสำเร็จ
     */
    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    /**
     * เฉพาะที่รอตรวจสอบ
     */
    public function scopePendingVerification($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * เฉพาะที่น่าสงสัย
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * เฉพาะที่ได้รางวัลแล้ว
     */
    public function scopeRewarded($query)
    {
        return $query->where('reward_given', true);
    }

    /**
     * เฉพาะวันนี้
     */
    public function scopeToday($query)
    {
        return $query->where('completion_date', now()->toDateString());
    }

    /**
     * เฉพาะสัปดาห์นี้
     */
    public function scopeThisWeek($query)
    {
        return $query->where('completion_week', now()->weekOfYear)
            ->where('completion_year', now()->year);
    }

    /**
     * เฉพาะเดือนนี้
     */
    public function scopeThisMonth($query)
    {
        return $query->where('completion_month', now()->month)
            ->where('completion_year', now()->year);
    }

    /**
     * กรองตาม user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * กรองตาม mission
     */
    public function scopeForMission($query, int $missionId)
    {
        return $query->where('mission_id', $missionId);
    }

    // ==================== ACCESSORS ====================

    /**
     * ดึงสถานะภาษาไทย
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'started' => 'เริ่มดู',
            'watching' => 'กำลังดู',
            'paused' => 'หยุดชั่วคราว',
            'completed' => 'ดูจบแล้ว (รอตรวจ)',
            'verified' => 'ยืนยันแล้ว',
            'failed' => 'ไม่ผ่าน',
            'expired' => 'หมดเวลา',
            'cancelled' => 'ยกเลิก',
            default => 'ไม่ทราบ',
        };
    }

    /**
     * ดึงสี status
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'started' => 'blue',
            'watching' => 'indigo',
            'paused' => 'yellow',
            'completed' => 'orange',
            'verified' => 'green',
            'failed' => 'red',
            'expired' => 'gray',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * ดึง icon status
     *
     * @return string
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'started' => '▶️',
            'watching' => '👁️',
            'paused' => '⏸️',
            'completed' => '⏳',
            'verified' => '✅',
            'failed' => '❌',
            'expired' => '⏰',
            'cancelled' => '🚫',
            default => '❓',
        };
    }

    /**
     * ดึงรางวัลรวม
     *
     * @return string
     */
    public function getRewardSummaryAttribute(): string
    {
        $rewards = [];

        if ($this->reward_money > 0) {
            $rewards[] = '฿' . number_format($this->reward_money, 2);
        }
        if ($this->reward_coins > 0) {
            $rewards[] = number_format($this->reward_coins) . ' Coins';
        }
        if ($this->reward_points > 0) {
            $rewards[] = number_format($this->reward_points) . ' แต้ม';
        }
        if ($this->reward_tpix > 0) {
            $rewards[] = number_format($this->reward_tpix, 4) . ' TPIX';
        }
        if ($this->reward_exp > 0) {
            $rewards[] = number_format($this->reward_exp) . ' EXP';
        }

        return implode(' + ', $rewards) ?: '-';
    }

    /**
     * ดึงเวลาที่ดูในรูปแบบอ่านง่าย
     *
     * @return string
     */
    public function getWatchedTimeFormattedAttribute(): string
    {
        $seconds = $this->watched_seconds;

        if ($seconds < 60) {
            return $seconds . ' วินาที';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds > 0) {
            return $minutes . ':' . str_pad($remainingSeconds, 2, '0', STR_PAD_LEFT);
        }

        return $minutes . ' นาที';
    }

    /**
     * ดึงระยะเวลาที่ใช้ทำภารกิจ
     *
     * @return string|null
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $diff = $this->started_at->diff($this->completed_at);

        if ($diff->h > 0) {
            return $diff->h . ' ชม. ' . $diff->i . ' นาที';
        }

        if ($diff->i > 0) {
            return $diff->i . ' นาที ' . $diff->s . ' วินาที';
        }

        return $diff->s . ' วินาที';
    }

    // ==================== METHODS ====================

    /**
     * สร้าง session token
     *
     * @return string
     */
    public static function generateSessionToken(): string
    {
        return Str::random(64);
    }

    /**
     * อัพเดท heartbeat
     *
     * @param int $currentPosition ตำแหน่งปัจจุบันในวิดีโอ (วินาที)
     * @param float $playbackSpeed ความเร็วในการเล่น
     * @return void
     */
    public function recordHeartbeat(int $currentPosition, float $playbackSpeed = 1.0): void
    {
        $this->update([
            'last_heartbeat_at' => now(),
            'heartbeat_count' => $this->heartbeat_count + 1,
            'video_position_seconds' => $currentPosition,
            'playback_speed' => $playbackSpeed,
            'status' => 'watching',
        ]);

        // อัพเดท watched_seconds
        $this->updateWatchedSeconds($currentPosition);
    }

    /**
     * อัพเดทเวลาที่ดูไป
     *
     * @param int $currentPosition
     * @return void
     */
    public function updateWatchedSeconds(int $currentPosition): void
    {
        // เก็บ segment ที่ดู
        $segments = $this->watch_segments ?? [];
        $lastSegmentEnd = 0;

        if (!empty($segments)) {
            $lastSegment = end($segments);
            $lastSegmentEnd = $lastSegment['end'] ?? 0;
        }

        // ถ้าตำแหน่งใหม่ต่อเนื่องจากที่เดิม
        if ($currentPosition >= $lastSegmentEnd) {
            if (!empty($segments)) {
                $segments[count($segments) - 1]['end'] = $currentPosition;
            } else {
                $segments[] = ['start' => 0, 'end' => $currentPosition];
            }
        } else {
            // กระโดดไปตำแหน่งอื่น
            $segments[] = ['start' => $currentPosition, 'end' => $currentPosition];
        }

        // คำนวณเวลาที่ดูจริงจาก segments
        $totalWatched = 0;
        foreach ($segments as $segment) {
            $totalWatched += ($segment['end'] - $segment['start']);
        }

        // คำนวณ percentage
        $percentage = $this->required_seconds > 0
            ? min(100, round(($totalWatched / $this->required_seconds) * 100, 2))
            : 0;

        $this->update([
            'watched_seconds' => $totalWatched,
            'watch_percentage' => $percentage,
            'watch_segments' => $segments,
        ]);
    }

    /**
     * บันทึกการสลับ tab
     *
     * @return void
     */
    public function recordTabSwitch(): void
    {
        $this->increment('tab_switch_count');

        // บันทึกใน suspicious activities
        $activities = $this->suspicious_activities ?? [];
        $activities[] = [
            'type' => 'tab_switch',
            'at' => now()->toDateTimeString(),
            'position' => $this->video_position_seconds,
        ];
        $this->update(['suspicious_activities' => $activities]);
    }

    /**
     * บันทึกการหยุดวิดีโอ
     *
     * @return void
     */
    public function recordPause(): void
    {
        $this->increment('pause_count');
        $this->update(['status' => 'paused']);
    }

    /**
     * บันทึกการเล่นต่อ
     *
     * @return void
     */
    public function recordResume(): void
    {
        $this->update(['status' => 'watching']);
    }

    /**
     * บันทึกการกระโดดข้าม (seek)
     *
     * @param int $fromPosition
     * @param int $toPosition
     * @return void
     */
    public function recordSeek(int $fromPosition, int $toPosition): void
    {
        $seekAmount = abs($toPosition - $fromPosition);

        $this->update([
            'seek_count' => $this->seek_count + 1,
            'total_seek_seconds' => $this->total_seek_seconds + $seekAmount,
        ]);

        // บันทึกใน suspicious activities ถ้าเป็นการกระโดดไปข้างหน้ามาก
        if ($toPosition > $fromPosition && $seekAmount > 10) {
            $activities = $this->suspicious_activities ?? [];
            $activities[] = [
                'type' => 'seek_forward',
                'at' => now()->toDateTimeString(),
                'from' => $fromPosition,
                'to' => $toPosition,
                'amount' => $seekAmount,
            ];
            $this->update(['suspicious_activities' => $activities]);
        }
    }

    /**
     * บันทึกการหลุด focus
     *
     * @return void
     */
    public function recordFocusLost(): void
    {
        $this->increment('focus_lost_count');

        $activities = $this->suspicious_activities ?? [];
        $activities[] = [
            'type' => 'focus_lost',
            'at' => now()->toDateTimeString(),
            'position' => $this->video_position_seconds,
        ];
        $this->update(['suspicious_activities' => $activities]);
    }

    /**
     * บันทึก interaction (click, mouse move, etc.)
     *
     * @return void
     */
    public function recordInteraction(): void
    {
        $timestamps = $this->interaction_timestamps ?? [];
        $timestamps[] = now()->toDateTimeString();

        $this->update([
            'interaction_count' => $this->interaction_count + 1,
            'interaction_timestamps' => $timestamps,
        ]);
    }

    /**
     * บันทึกการพยายามโกง (DevTools, speed change, etc.)
     *
     * @param string $type ประเภทการโกง
     * @param array $details รายละเอียดเพิ่มเติม
     * @return void
     */
    public function recordCheatAttempt(string $type, array $details = []): void
    {
        $activities = $this->suspicious_activities ?? [];
        $activities[] = [
            'type' => $type,
            'at' => now()->toDateTimeString(),
            'details' => $details,
            'position' => $this->video_position_seconds,
            'ip' => request()->ip(),
        ];

        $this->update([
            'suspicious_activities' => $activities,
        ]);

        // เพิ่ม cheat_count ถ้ามีคอลัมน์นี้
        if (in_array('cheat_attempt_count', $this->fillable)) {
            $this->increment('cheat_attempt_count');
        }
    }

    /**
     * ทำเครื่องหมายว่าดูจบ
     *
     * @return void
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // อัพเดท mission completion count
        $this->mission?->incrementCompletionCount();
    }

    /**
     * ตรวจสอบและคำนวณความน่าสงสัย
     *
     * @return int คะแนนความน่าสงสัย (0-100)
     */
    public function calculateSuspiciousScore(): int
    {
        $score = 0;

        // Tab switch มากเกินไป
        $mission = $this->mission;
        $maxTabSwitches = $mission?->max_tab_switches ?? 3;
        if ($this->tab_switch_count > $maxTabSwitches) {
            $score += min(30, ($this->tab_switch_count - $maxTabSwitches) * 10);
        }

        // Seek มากเกินไป
        if ($this->seek_count > 5) {
            $score += min(20, ($this->seek_count - 5) * 5);
        }

        // ดูเร็วเกินไป (ถ้าใช้เวลาน้อยกว่า 80% ของเวลาที่กำหนด)
        if ($this->started_at && $this->completed_at) {
            $actualDuration = $this->started_at->diffInSeconds($this->completed_at);
            $expectedDuration = $this->required_seconds;

            if ($actualDuration < $expectedDuration * 0.8) {
                $score += 25;
            }
        }

        // Focus lost มากเกินไป
        if ($this->focus_lost_count > 3) {
            $score += min(15, ($this->focus_lost_count - 3) * 5);
        }

        // Heartbeat ขาดหาย
        if ($this->started_at && $this->completed_at) {
            $duration = $this->started_at->diffInSeconds($this->completed_at);
            $expectedHeartbeats = floor($duration / 5); // 1 heartbeat ทุก 5 วินาที

            if ($expectedHeartbeats > 0 && $this->heartbeat_count < $expectedHeartbeats * 0.7) {
                $score += 15;
            }
        }

        // ไม่มี interaction เลย (ถ้า require_interaction)
        if ($mission?->require_interaction && $this->interaction_count === 0) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * ยืนยันการทำภารกิจ
     *
     * @param int|null $verifierId ID ของ Admin ที่ verify
     * @param string|null $notes หมายเหตุ
     * @return bool
     */
    public function verify(?int $verifierId = null, ?string $notes = null): bool
    {
        // คำนวณความน่าสงสัย
        $suspiciousScore = $this->calculateSuspiciousScore();

        // ถ้าน่าสงสัยมาก ไม่ให้ผ่าน auto
        if ($suspiciousScore >= 70 && !$verifierId) {
            $this->update([
                'is_suspicious' => true,
                'verification_score' => $suspiciousScore,
                'verification_notes' => 'ต้องรอ Admin ตรวจสอบ - คะแนนน่าสงสัยสูง',
            ]);
            return false;
        }

        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
            'passed_verification' => true,
            'verified_by' => $verifierId,
            'verification_score' => $suspiciousScore,
            'verification_notes' => $notes,
            'is_suspicious' => $suspiciousScore >= 50,
        ]);

        return true;
    }

    /**
     * ปฏิเสธการทำภารกิจ
     *
     * @param int|null $rejecterId ID ของ Admin ที่ reject
     * @param string|null $reason เหตุผล
     * @return void
     */
    public function reject(?int $rejecterId = null, ?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'passed_verification' => false,
            'rejected_by' => $rejecterId,
            'verification_notes' => $reason ?? 'ถูกปฏิเสธโดย Admin',
        ]);
    }

    /**
     * ให้รางวัล
     *
     * @param float $multiplier ตัวคูณรางวัล
     * @return bool
     */
    public function giveRewards(float $multiplier = 1.0): bool
    {
        if ($this->reward_given) {
            return false;
        }

        if ($this->status !== 'verified') {
            return false;
        }

        $mission = $this->mission;
        if (!$mission) {
            return false;
        }

        // คำนวณรางวัล
        $rewards = $mission->calculateRewards($multiplier);

        $this->update([
            'reward_given' => true,
            'reward_claimed_at' => now(),
            'reward_money' => $rewards['money'],
            'reward_coins' => $rewards['coins'],
            'reward_points' => $rewards['points'],
            'reward_tpix' => $rewards['tpix'],
            'reward_exp' => $rewards['exp'],
            'bonus_multiplier_applied' => $multiplier,
        ]);

        // อัพเดทรางวัลที่แจกไปใน mission
        $totalRewardValue = $rewards['money'] + ($rewards['coins'] * 0.01);
        $mission->addRewardsGiven($totalRewardValue);

        return true;
    }

    /**
     * ตรวจสอบว่า session ยังใช้ได้หรือไม่ (ไม่ expired)
     *
     * @param int $timeoutMinutes timeout (นาที)
     * @return bool
     */
    public function isSessionValid(int $timeoutMinutes = 30): bool
    {
        if (in_array($this->status, ['verified', 'failed', 'expired', 'cancelled'])) {
            return false;
        }

        if (!$this->last_heartbeat_at) {
            // ถ้ายังไม่มี heartbeat ใช้ started_at
            $lastActivity = $this->started_at ?? $this->created_at;
        } else {
            $lastActivity = $this->last_heartbeat_at;
        }

        return $lastActivity->diffInMinutes(now()) < $timeoutMinutes;
    }

    /**
     * ทำให้ session หมดอายุ
     *
     * @return void
     */
    public function expire(): void
    {
        $this->update([
            'status' => 'expired',
            'verification_notes' => 'หมดเวลา - ไม่มีการตอบสนอง',
        ]);
    }
}
