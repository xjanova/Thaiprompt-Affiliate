<?php

namespace App\Models;

use App\Services\Fortune\FortuneCharacterVoiceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * 🎭 (2026-11-04) ตัวละครเสียงพากย์ — แม่หมอ/บุษบา/คุณไสย (ติดมากับระบบ) + ที่แอดมินเพิ่มเองสำหรับห้องพยากรณ์ใหม่
 *
 * key = persona ที่ห้องแชท/คลิปเสียงใช้อ้างอิง (a-z ตัวเลข _ ยาวไม่เกิน 20)
 *
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string|null $brief
 * @property string $sample_text
 * @property string|null $voice_id เสียง ElevenLabs (null = เสียงสำรองของระบบ)
 * @property string|null $model โมเดลเฉพาะตัว (null = โมเดลกลาง)
 * @property bool $is_builtin
 * @property bool $is_active
 * @property int $sort_order
 */
class FortuneVoiceCharacter extends Model
{
    /** รูปแบบ key ที่รับ — ตรงกับคอลัมน์ persona (ยาว 20) และใช้เป็นชื่อโฟลเดอร์ไฟล์เสียงได้ */
    public const KEY_PATTERN = '/^[a-z][a-z0-9_]{1,19}$/';

    protected const CACHE_KEY = 'fortune:voice_characters';

    protected $table = 'fortune_voice_characters';

    protected $fillable = ['key', 'label', 'brief', 'sample_text', 'voice_id', 'model', 'is_builtin', 'is_active', 'sort_order'];

    protected $casts = [
        'is_builtin' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCatalog());
        static::deleted(fn () => self::flushCatalog());
    }

    public static function flushCatalog(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * ตัวละครทั้งหมด (รวมที่ปิดไว้) เรียงตามลำดับ — ยังไม่มีตาราง = 3 ตัวเดิมในโค้ด
     *
     * @return array<string, array{label: string, brief: string, default_voice: string, sample_text: string, voice_id: ?string, model: ?string, builtin: bool, active: bool}>
     */
    public static function catalog(): array
    {
        $catalog = Cache::rememberForever(self::CACHE_KEY, function () {
            if (! Schema::hasTable('fortune_voice_characters')) {
                return null;
            }
            $rows = self::orderBy('sort_order')->orderBy('id')->get();

            return $rows->isEmpty() ? null : $rows->mapWithKeys(fn (self $c) => [$c->key => [
                'label' => $c->label,
                'brief' => (string) $c->brief,
                'default_voice' => FortuneCharacterVoiceService::PERSONAS[$c->key]['default_voice'] ?? self::fallbackVoice(),
                'sample_text' => $c->sample_text,
                'voice_id' => $c->voice_id ?: null,
                'model' => $c->model ?: null,
                'builtin' => $c->is_builtin,
                'active' => $c->is_active,
            ]])->all();
        });

        if (is_array($catalog)) {
            return $catalog;
        }

        // ยังไม่ได้ migrate (หรือเทสต์ที่ไม่ได้สร้างตารางนี้) — ใช้ 3 ตัวเดิมเหมือนก่อนมีตาราง
        return collect(FortuneCharacterVoiceService::PERSONAS)->map(fn ($p) => $p + [
            'voice_id' => null, 'model' => null, 'builtin' => true, 'active' => true,
        ])->all();
    }

    /** ตัวละครที่เปิดใช้อยู่ */
    public static function active(): array
    {
        return array_filter(self::catalog(), fn ($c) => $c['active']);
    }

    /** เสียงสำรองของตัวละครที่แอดมินเพิ่มแต่ยังไม่เลือกเสียง */
    public static function fallbackVoice(): string
    {
        return FortuneCharacterVoiceService::PERSONAS['maemor']['default_voice'];
    }
}
