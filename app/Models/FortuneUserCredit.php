<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FortuneUserCredit Model
 *
 * จัดการเครดิตดูดวงฟรีรายคน
 * แอดมินสามารถเพิ่มเครดิต / รีเซ็ตสิทธิ์ฟรี / ให้ไม่จำกัดเป็นรายคนได้
 *
 * @property int $id
 * @property string $facebook_user_id
 * @property string|null $facebook_user_name
 * @property string $platform
 * @property int $bonus_credits เครดิตฟรีที่ได้รับเพิ่ม
 * @property int $credits_used เครดิตที่ใช้ไปแล้ว
 * @property bool $is_unlimited ดูดวงฟรีไม่จำกัด
 * @property \Carbon\Carbon|null $unlimited_until ดูฟรีจนถึงวันที่
 * @property bool $is_daily_reset รีเซ็ตสิทธิ์ฟรีวันนี้
 * @property \Carbon\Carbon|null $daily_reset_date วันที่รีเซ็ตล่าสุด
 * @property string|null $note หมายเหตุ
 * @property int|null $updated_by แอดมินที่แก้ไข
 */
class FortuneUserCredit extends Model
{
    /**
     * ชื่อตาราง
     */
    protected $table = 'fortune_user_credits';

    /**
     * คอลัมน์ที่สามารถ mass assign ได้
     */
    protected $fillable = [
        'facebook_user_id',
        'facebook_user_name',
        'platform',
        'bonus_credits',
        'credits_used',
        'is_unlimited',
        'unlimited_until',
        'is_daily_reset',
        'daily_reset_date',
        'note',
        'updated_by',
        // 🎯 Phase B.1 — DM tracking
        'first_dm_at',
        'last_dm_at',
        'dm_count',
        'last_warmup_sent_at',
        // 💬 (2026-06-06) Weekly image dedup — เวลาส่งรูปแบนเนอร์ใน DM ล่าสุด
        'last_dm_image_at',
        // 👁️ Follow-page tracking (2026-04-28)
        'facebook_follow_prompted_at',
        'facebook_followed_confirmed_at',
        // 👥 Group invite tracking (2026-05-05)
        'facebook_group_clicked_at',
    ];

    /**
     * การ cast ประเภทข้อมูล
     */
    protected $casts = [
        'bonus_credits' => 'integer',
        'credits_used' => 'integer',
        'is_unlimited' => 'boolean',
        'unlimited_until' => 'date',
        'is_daily_reset' => 'boolean',
        'daily_reset_date' => 'date',
        // 🎯 Phase B.1 — DM tracking
        'first_dm_at' => 'datetime',
        'last_dm_at' => 'datetime',
        'dm_count' => 'integer',
        'last_warmup_sent_at' => 'datetime',
        // 💬 (2026-06-06) Weekly image dedup
        'last_dm_image_at' => 'datetime',
        // 👁️ Follow-page tracking
        'facebook_follow_prompted_at' => 'datetime',
        'facebook_followed_confirmed_at' => 'datetime',
        // 👥 Group invite tracking
        'facebook_group_clicked_at' => 'datetime',
    ];

    /**
     * ค่าเริ่มต้น
     */
    protected $attributes = [
        'platform' => 'facebook',
        'bonus_credits' => 0,
        'credits_used' => 0,
        'is_unlimited' => false,
        'is_daily_reset' => false,
    ];

    // ============================================================
    // Relationships
    // ============================================================

    /**
     * แอดมินที่แก้ไขล่าสุด
     */
    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * Scope: ค้นหาตาม facebook_user_id
     */
    public function scopeByUser($query, string $facebookUserId, string $platform = 'facebook')
    {
        return $query->where('facebook_user_id', $facebookUserId)
            ->where('platform', $platform);
    }

    /**
     * Scope: เฉพาะผู้ใช้ที่มีเครดิตเหลือ
     */
    public function scopeHasCredits($query)
    {
        return $query->whereColumn('bonus_credits', '>', 'credits_used');
    }

    /**
     * Scope: เฉพาะผู้ใช้ไม่จำกัด
     */
    public function scopeUnlimited($query)
    {
        return $query->where('is_unlimited', true);
    }

    // ============================================================
    // Static Methods
    // ============================================================

    /**
     * ดึงเครดิตของผู้ใช้ (สร้างใหม่ถ้ายังไม่มี)
     */
    public static function getOrCreate(string $facebookUserId, string $platform = 'facebook', ?string $userName = null): self
    {
        return self::firstOrCreate(
            ['facebook_user_id' => $facebookUserId, 'platform' => $platform],
            ['facebook_user_name' => $userName]
        );
    }

    /**
     * ดึงเครดิตของผู้ใช้ (ไม่สร้างใหม่)
     */
    public static function findByUser(string $facebookUserId, string $platform = 'facebook'): ?self
    {
        return self::byUser($facebookUserId, $platform)->first();
    }

    /**
     * 👤 จดจำชื่อจริงของลูกค้าจาก source ที่ trust ได้ (เช่น Facebook comment payload from.name)
     *
     * Behavior:
     *   - ไม่มี record → สร้างใหม่พร้อมชื่อ (ถ้าชื่อ valid)
     *   - มี record + ชื่อเก่าไม่ใช่ชื่อจริง → update เป็น $name
     *   - มี record + ชื่อเก่าเป็นชื่อจริง → ไม่ทับ (รักษาชื่อที่ user แก้เอง / ใหม่กว่า)
     *
     * เรียกจาก: comment engagement, DM webhook, postback handler ใด ๆ ที่มี name จริง
     *
     * ⚠️ กัน code-pattern ("FACEBOOK-XXXXXX") + raw IDs ไม่ให้ persist ลง DB
     */
    public static function rememberName(string $facebookUserId, string $platform, ?string $name): ?self
    {
        if (! self::isHumanLikeName($name)) {
            return self::findByUser($facebookUserId, $platform);
        }

        $record = self::firstOrCreate(
            ['facebook_user_id' => $facebookUserId, 'platform' => $platform],
            ['facebook_user_name' => $name]
        );

        // ถ้า record มีอยู่แล้วแต่ name เก่าไม่ใช่ชื่อจริง → update
        if (! self::isHumanLikeName($record->facebook_user_name)) {
            $record->update(['facebook_user_name' => $name]);
        }

        return $record;
    }

    /**
     * ตรวจว่าค่าที่ได้ "ดูเป็นชื่อคนจริง" หรือเปล่า
     * (sync logic เดียวกับ FortuneReading::isHumanLikeName + FortuneChannelManager::isHumanLikeName)
     */
    public static function isHumanLikeName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $name = trim($name);
        if ($name === '' || $name === 'คุณ' || $name === 'ลูกค้า' || $name === 'เจ้าชะตา') {
            return false;
        }
        if (preg_match('/^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$/i', $name)) {
            return false;
        }
        if (preg_match('/^U[0-9a-f]{32}$/i', $name)) {
            return false;
        }
        if (preg_match('/^\d{15,}$/', $name)) {
            return false;
        }

        return true;
    }

    // ============================================================
    // Instance Methods
    // ============================================================

    /**
     * ตรวจสอบว่าผู้ใช้มีสิทธิ์ดูฟรีเพิ่มเติมหรือไม่
     *
     * เช็คตามลำดับ:
     * 1. is_unlimited + unlimited_until ยังไม่หมดอายุ → true
     * 2. bonus_credits > credits_used → มีเครดิตเหลือ → true
     * 3. is_daily_reset + daily_reset_date = วันนี้ → รีเซ็ตวันนี้ → true
     */
    public function hasExtraFreeAccess(): bool
    {
        // 1. ดูฟรีไม่จำกัด
        if ($this->isCurrentlyUnlimited()) {
            return true;
        }

        // 2. มีเครดิตเหลือ
        if ($this->getRemainingCredits() > 0) {
            return true;
        }

        // 3. รีเซ็ตสิทธิ์ฟรีวันนี้
        if ($this->isDailyResetActive()) {
            return true;
        }

        return false;
    }

    /**
     * ตรวจสอบว่าดูฟรีไม่จำกัดยังมีผลอยู่หรือไม่
     */
    public function isCurrentlyUnlimited(): bool
    {
        if (! $this->is_unlimited) {
            return false;
        }

        // ถ้ามีกำหนดวันหมดอายุ ต้องยังไม่หมด
        if ($this->unlimited_until) {
            return Carbon::today()->lte($this->unlimited_until);
        }

        // ไม่มีวันหมดอายุ = ไม่จำกัดตลอด
        return true;
    }

    /**
     * คำนวณเครดิตคงเหลือ
     */
    public function getRemainingCredits(): int
    {
        return max(0, $this->bonus_credits - $this->credits_used);
    }

    /**
     * ตรวจสอบว่ารีเซ็ตสิทธิ์ฟรีวันนี้หรือไม่
     */
    public function isDailyResetActive(): bool
    {
        return $this->is_daily_reset
            && $this->daily_reset_date
            && Carbon::today()->eq($this->daily_reset_date);
    }

    /**
     * ใช้เครดิตฟรี 1 ครั้ง
     *
     * @return bool สำเร็จหรือไม่
     */
    public function useCredit(): bool
    {
        // ดูฟรีไม่จำกัด → ไม่ต้องหักเครดิต
        if ($this->isCurrentlyUnlimited()) {
            return true;
        }

        // รีเซ็ตสิทธิ์ฟรีวันนี้ → ไม่ต้องหักเครดิต
        if ($this->isDailyResetActive()) {
            return true;
        }

        // มีเครดิตเหลือ → หักเครดิต
        if ($this->getRemainingCredits() > 0) {
            $this->increment('credits_used');

            return true;
        }

        return false;
    }

    /**
     * เพิ่มเครดิต
     */
    public function addCredits(int $amount, ?int $adminId = null, ?string $note = null): self
    {
        $this->increment('bonus_credits', $amount);

        $updateData = [];
        if ($adminId) {
            $updateData['updated_by'] = $adminId;
        }
        if ($note) {
            $updateData['note'] = $note;
        }
        if (! empty($updateData)) {
            $this->update($updateData);
        }

        return $this->fresh();
    }

    /**
     * รีเซ็ตสิทธิ์ฟรีประจำวัน
     */
    public function resetDaily(?int $adminId = null, ?string $note = null): self
    {
        $this->update([
            'is_daily_reset' => true,
            'daily_reset_date' => Carbon::today(),
            'updated_by' => $adminId,
            'note' => $note ?? 'รีเซ็ตสิทธิ์ฟรีวันนี้',
        ]);

        return $this->fresh();
    }

    /**
     * ตั้งค่าดูฟรีไม่จำกัด
     */
    public function setUnlimited(bool $unlimited, ?string $untilDate = null, ?int $adminId = null, ?string $note = null): self
    {
        $this->update([
            'is_unlimited' => $unlimited,
            'unlimited_until' => $untilDate ? Carbon::parse($untilDate) : null,
            'updated_by' => $adminId,
            'note' => $note,
        ]);

        return $this->fresh();
    }

    /**
     * รีเซ็ตเครดิตทั้งหมด (กลับเป็น 0)
     */
    public function resetCredits(?int $adminId = null, ?string $note = null): self
    {
        $this->update([
            'bonus_credits' => 0,
            'credits_used' => 0,
            'is_unlimited' => false,
            'unlimited_until' => null,
            'is_daily_reset' => false,
            'daily_reset_date' => null,
            'updated_by' => $adminId,
            'note' => $note ?? 'รีเซ็ตเครดิตทั้งหมด',
        ]);

        return $this->fresh();
    }

    // ============================================================
    // 🎯 Phase B.1 — DM Tracking (24-hour warm-up memory)
    // ============================================================

    /**
     * บันทึกว่าลูกค้า DM เข้ามาตอนนี้
     *
     * - ถ้ายังไม่เคย DM → ตั้ง first_dm_at
     * - อัปเดต last_dm_at = ปัจจุบัน
     * - เพิ่ม dm_count (atomic — กัน race condition จาก concurrent DMs)
     *
     * เรียกจาก FortuneConversationService::processMessage() ทุกครั้งที่มี DM เข้ามา
     */
    public function recordDm(): self
    {
        // ✅ Atomic increment ผ่าน SQL — ปลอดภัยกับ concurrent DMs
        //   ใช้ $this->increment() แทน $this->update(['dm_count' => ...+1]) ที่ race ได้
        $this->increment('dm_count', 1, [
            'last_dm_at' => now(),
            // ตั้ง first_dm_at เฉพาะครั้งแรก (ใช้ COALESCE ผ่าน PHP — ถ้า null ตอนนี้ ให้ใส่ now)
            'first_dm_at' => $this->first_dm_at ?? now(),
        ]);

        return $this->fresh();
    }

    /**
     * ตรวจสอบว่าลูกค้ามีการ DM ภายใน 24 ชั่วโมงล่าสุดหรือไม่
     *
     * ใช้ตัดสินว่าจะใช้โหมด "AI warm-up" (หว่านล้อมเนียนๆ) หรือ "pattern เดิม" (ทักทาย+ปุ่ม)
     *
     * @return bool true = คือลูกค้าเก่าที่กลับมาในหน้าต่าง 24 ชม.
     */
    public function isWithin24hDmWindow(): bool
    {
        if (empty($this->last_dm_at)) {
            return false;
        }

        return $this->last_dm_at->greaterThanOrEqualTo(now()->subHours(24));
    }

    /**
     * ตรวจสอบว่าเคย DM มาก่อนหน้านี้หรือเปล่า (ไม่ใช่ first contact)
     */
    public function hasPriorDmInteraction(): bool
    {
        return ! empty($this->first_dm_at) && ($this->dm_count ?? 0) > 1;
    }

    /**
     * ตรวจว่าสามารถส่ง AI warm-up ตอนนี้ได้ไหม
     *
     * กันไม่ให้ส่ง warm-up ถี่เกินไปในช่วงสั้น
     * (เช่น ลูกค้า DM 10 ข้อความต่อเนื่อง → ไม่ควร warm-up ทุกข้อความ)
     *
     * @param  int  $cooldownMinutes  นาทีระหว่าง warm-up ครั้งล่าสุด (default 5)
     */
    public function canSendWarmup(int $cooldownMinutes = 5): bool
    {
        if (empty($this->last_warmup_sent_at)) {
            return true;
        }

        return $this->last_warmup_sent_at->lessThan(now()->subMinutes($cooldownMinutes));
    }

    /**
     * บันทึกว่าส่ง warm-up ไปแล้ว (ใช้กัน spam)
     */
    public function markWarmupSent(): self
    {
        $this->update(['last_warmup_sent_at' => now()]);

        return $this->fresh();
    }

    // ============================================================
    // 💬 (2026-06-06) Weekly DM Image Dedup
    //   "ใครได้รูปแบนเนอร์ในสัปดาห์นี้แล้ว → DM กลับครั้งถัดไปส่งข้อความชวนแทนรูป"
    //   เก็บใน DB (ไม่ใช่ Cache) เพราะ auto-deploy รัน cache:clear ทุกครั้ง
    // ============================================================

    /**
     * 💬 ตรวจว่า user คนนี้ได้รับรูปแบนเนอร์ใน DM มาแล้วในสัปดาห์นี้หรือยัง
     *
     * "สัปดาห์นี้" = ตั้งแต่วันจันทร์ 00:00 (ISO week, Asia/Bangkok) ถึงปัจจุบัน
     *
     * @param  string  $userId  facebook_user_id / platform_user_id
     * @param  string  $platform  'facebook' | 'line'
     * @return bool true = เคยได้รูปสัปดาห์นี้แล้ว (caller จะส่งข้อความชวนแทน)
     */
    public static function hasReceivedImageThisWeek(string $userId, string $platform = 'facebook'): bool
    {
        $record = self::findByUser($userId, $platform);

        if (! $record || empty($record->last_dm_image_at)) {
            return false;
        }

        return $record->last_dm_image_at->greaterThanOrEqualTo(now()->startOfWeek());
    }

    /**
     * 💬 บันทึกว่าเพิ่งส่งรูปแบนเนอร์ใน DM ให้ user คนนี้ (สร้าง record ถ้ายังไม่มี)
     *
     * @param  string  $userId  facebook_user_id / platform_user_id
     * @param  string  $platform  'facebook' | 'line'
     */
    public static function markImageSent(string $userId, string $platform = 'facebook'): void
    {
        self::getOrCreate($userId, $platform)
            ->forceFill(['last_dm_image_at' => now()])
            ->save();
    }

    // ============================================================
    // 👁️ Follow-page tracking (2026-04-28)
    // ============================================================

    /**
     * ตรวจว่า user ยืนยันแล้วว่ากดติดตามเพจ
     * (เช็คผ่าน postback "✅ ติดตามแล้ว" — เพราะ FB API ไม่เปิด GET /{user}/likes)
     */
    public function hasFacebookFollowed(): bool
    {
        return ! empty($this->facebook_followed_confirmed_at);
    }

    /**
     * ควรส่งกล่อง "ติดตามเพจ" ให้ user หรือไม่
     *
     * Rules:
     * 1. ถ้ายืนยันติดตามแล้ว → false (ไม่ต้องส่งอีก)
     * 2. ถ้ายังไม่เคยส่ง prompt → true
     * 3. ถ้าส่งไปแล้วเกิน cooldown → true (re-prompt)
     *
     * @param  int  $cooldownDays  จำนวนวันก่อน prompt ซ้ำ (default: 7)
     */
    public function shouldPromptFollow(int $cooldownDays = 7): bool
    {
        if ($this->hasFacebookFollowed()) {
            return false;
        }

        if ($this->facebook_follow_prompted_at === null) {
            return true;
        }

        return $this->facebook_follow_prompted_at->lessThan(now()->subDays($cooldownDays));
    }

    /**
     * 🆕 (2026-05-02) ควรส่งกล่อง "ติดตามเพจ" วันนี้หรือไม่ — daily cooldown
     *
     * User feedback: "ในการทักแชทครั้งแรกของวันนั้น ถ้ายังไม่ติดตาม ให้ปรากฏ"
     *
     * Rules:
     * 1. ถ้ายืนยันติดตามแล้ว → false
     * 2. ถ้ายังไม่เคยส่ง → true
     * 3. ถ้าส่งวันนี้ไปแล้ว → false (ไม่สแปม ทักหลายครั้งใน 1 วัน)
     * 4. วันใหม่ + ยังไม่ติดตาม → true (re-prompt ครั้งแรกของวัน)
     */
    public function shouldPromptFollowToday(): bool
    {
        if ($this->hasFacebookFollowed()) {
            return false;
        }

        if ($this->facebook_follow_prompted_at === null) {
            return true;
        }

        return ! $this->facebook_follow_prompted_at->isToday();
    }

    /**
     * บันทึกว่าส่งกล่อง "ติดตามเพจ" ไปแล้ว
     */
    public function markFollowPrompted(): self
    {
        $this->update(['facebook_follow_prompted_at' => now()]);

        return $this->fresh();
    }

    /**
     * บันทึกว่า user ยืนยันแล้วว่ากดติดตามเพจ (ผ่าน postback)
     */
    public function markFacebookFollowed(): self
    {
        $this->update(['facebook_followed_confirmed_at' => now()]);

        return $this->fresh();
    }
}
