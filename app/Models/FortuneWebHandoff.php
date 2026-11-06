<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * FortuneWebHandoff — การส่งต่อลูกค้าจากแชท FB ไปห้องแม่หมอบนเว็บจันทรา (FB web-only, 2026-11-03)
 *
 * - nonce (uuid) อยู่ใน token v2 (h) และใน path /chat/continue/{nonce}
 * - ใช้ครั้งเดียว: consume() เป็น atomic update (consumed_at IS NULL) — ยิงซ้อนกันได้แค่ครั้งเดียว
 * - prune แถวที่เกิน PRUNE_AFTER_DAYS วัน (MassPrunable → php artisan model:prune)
 * - ต้องอยู่ใน PDPA deletion (ลบตาม platform_user_id / thaiprompt_user_id)
 *
 * @property int $id
 * @property string $nonce
 * @property string $platform
 * @property string $platform_user_id
 * @property int|null $fortune_page_id
 * @property int|null $thaiprompt_user_id
 * @property string|null $intent_text ข้อความที่ทำให้เกิดการย้าย (≤300 ตัวอักษร)
 * @property string|null $intent_topic love|career|money|health|kunsai|general
 * @property string|null $source จุดที่สร้าง handoff
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $consumed_at
 * @property string|null $consumed_juntra_user_id
 */
class FortuneWebHandoff extends Model
{
    use MassPrunable;

    protected $table = 'fortune_web_handoffs';

    /** อายุ handoff (ชั่วโมง) — ตรงกับหน้าต่าง "แถวล่าสุดที่ยังไม่ consume ใน 72 ชม." */
    public const TTL_HOURS = 72;

    /** ลบทิ้งเมื่อเก่ากว่ากี่วัน */
    public const PRUNE_AFTER_DAYS = 7;

    /** ความยาวสูงสุดของ intent_text */
    public const INTENT_MAX_LENGTH = 300;

    /** หัวข้อที่ยอมรับ — อย่างอื่นตกเป็น general */
    public const TOPICS = ['love', 'career', 'money', 'health', 'kunsai', 'general'];

    protected $fillable = [
        'nonce',
        'platform',
        'platform_user_id',
        'fortune_page_id',
        'thaiprompt_user_id',
        'intent_text',
        'intent_topic',
        'source',
        'expires_at',
        'consumed_at',
        'consumed_juntra_user_id',
    ];

    protected $casts = [
        'fortune_page_id' => 'integer',
        'thaiprompt_user_id' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    /**
     * สร้าง handoff ใหม่พร้อม nonce และวันหมดอายุ
     *
     * @param  array  $attributes  platform_user_id (บังคับ), platform, fortune_page_id, intent_text, intent_topic, source ...
     *
     * @example
     * $h = FortuneWebHandoff::issue(['platform_user_id' => $psid, 'fortune_page_id' => 2, 'intent_text' => $text, 'source' => 'web_only_gate']);
     * $url = $svc->generateChatLink('facebook', $psid, '/chat/continue/'.$h->nonce, $h->nonce, $h->fortune_page_id);
     */
    public static function issue(array $attributes): self
    {
        $attributes['nonce'] = (string) Str::uuid();
        $attributes['platform'] = $attributes['platform'] ?? 'facebook';
        $attributes['expires_at'] = $attributes['expires_at'] ?? now()->addHours(self::TTL_HOURS);
        unset($attributes['consumed_at'], $attributes['consumed_juntra_user_id']);

        return static::create($attributes);
    }

    /**
     * ตัด intent_text ให้ไม่เกิน 300 ตัวอักษร (กันคอลัมน์ล้น — การ sanitize เนื้อหาทำก่อนส่งเข้ามา)
     */
    public function setIntentTextAttribute($value): void
    {
        $value = $value === null ? null : trim((string) $value);
        $this->attributes['intent_text'] = ($value === null || $value === '')
            ? null
            : mb_substr($value, 0, self::INTENT_MAX_LENGTH);
    }

    /**
     * หัวข้อที่ไม่รู้จัก → general
     */
    public function setIntentTopicAttribute($value): void
    {
        $this->attributes['intent_topic'] = $value === null
            ? null
            : (in_array($value, self::TOPICS, true) ? $value : 'general');
    }

    /**
     * เฉพาะแถวที่ยังใช้ได้ (ยังไม่ consume และยังไม่หมดอายุ)
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * ยังใช้ได้หรือไม่
     */
    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * หา handoff ที่ยังใช้ได้จาก nonce (nonce ไม่ใช่ uuid = null ทันที)
     */
    public static function findUsableByNonce(?string $nonce): ?self
    {
        if (! is_string($nonce) || ! Str::isUuid($nonce)) {
            return null;
        }

        return static::query()->usable()->where('nonce', strtolower($nonce))->first();
    }

    /**
     * ใช้ handoff นี้ (ครั้งเดียว) — atomic: คืน true เฉพาะคนแรกที่ consume ได้
     */
    public function consume(?string $juntraUserId = null): bool
    {
        $now = now();
        $affected = static::query()
            ->whereKey($this->getKey())
            ->whereNull('consumed_at')
            ->update([
                'consumed_at' => $now,
                'consumed_juntra_user_id' => $juntraUserId,
                'updated_at' => $now,
            ]);

        if ($affected === 1) {
            $this->consumed_at = $now;
            $this->consumed_juntra_user_id = $juntraUserId;
            $this->syncOriginalAttributes(['consumed_at', 'consumed_juntra_user_id']);

            return true;
        }

        return false;
    }

    /**
     * แถวที่ต้อง prune — เก่ากว่า PRUNE_AFTER_DAYS วัน
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(self::PRUNE_AFTER_DAYS));
    }

    /**
     * ความสัมพันธ์กับ User ฝั่ง Thaiprompt
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'thaiprompt_user_id');
    }

    /**
     * ความสัมพันธ์กับสาขา (เพจ FB)
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FortunePage::class, 'fortune_page_id');
    }
}
