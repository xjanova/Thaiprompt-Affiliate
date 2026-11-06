<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * 🎭 (2026-11-04) บทพูดในคลังเสียงของตัวละคร — คำรับ/คำเติม/คำปิดที่อัดไว้ใช้ซ้ำ (ฟรีตอนเล่น)
 *
 * line_key = "{หมวด}_{เลข}" (ack_1, listen_2 …) — โหมดคุยด้วยเสียงอ้างอิงด้วย id นี้
 * voice_id / model ว่าง = ใช้ของตัวละคร · clip_id = ไฟล์ที่สร้างล่าสุด (fortune_character_voice_clips)
 *
 * @property int $id
 * @property string $persona
 * @property string $line_key
 * @property string $category
 * @property string $script
 * @property string|null $voice_id
 * @property string|null $model
 * @property int|null $clip_id
 * @property bool $is_active
 * @property int $sort_order
 * @property-read FortuneCharacterVoiceClip|null $clip
 */
class FortuneVoiceLine extends Model
{
    /** หมวดใหม่ที่แอดมินตั้งเองได้ — ตัวพิมพ์เล็ก a-z และ _ (ไม่มีตัวเลข จะได้แยกเลขลำดับท้าย id ได้) */
    public const CATEGORY_PATTERN = '/^[a-z][a-z_]{1,19}$/';

    public const CACHE_PREFIX = 'fortune:voice_lines:';

    protected $table = 'fortune_voice_lines';

    protected $fillable = ['persona', 'line_key', 'category', 'script', 'voice_id', 'model', 'clip_id', 'is_active', 'sort_order'];

    protected $casts = [
        'clip_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $line) => Cache::forget(self::CACHE_PREFIX.$line->persona));
        static::deleted(fn (self $line) => Cache::forget(self::CACHE_PREFIX.$line->persona));
    }

    public function clip(): BelongsTo
    {
        return $this->belongsTo(FortuneCharacterVoiceClip::class, 'clip_id');
    }

    /** id ถัดไปของหมวดนี้ในตัวละครนี้ (ack_1, ack_2 … ไม่ใช้เลขซ้ำแม้ลบไปแล้ว) */
    public static function nextKey(string $persona, string $category): string
    {
        $max = self::where('persona', $persona)->where('category', $category)->pluck('line_key')
            ->map(fn ($k) => (int) substr((string) strrchr($k, '_'), 1))
            ->max() ?? 0;

        return $category.'_'.($max + 1);
    }
}
