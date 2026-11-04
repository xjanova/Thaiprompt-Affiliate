<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * 🔈 (2026-11-04) การใช้บริการเสียงของเว็บจันทราหนึ่งครั้ง (งานที่เสียเงินจริงเท่านั้น)
 *
 * @property int $id
 * @property string $kind tts | stt | narration | greeting
 * @property string|null $purpose chat | greeting | narration | sample | transcribe
 * @property string $provider elevenlabs | scribe | google_tts | gtts | ai
 * @property string|null $persona
 * @property string|null $user_ref id ลูกค้าฝั่งเว็บ
 * @property int $chars
 * @property float $seconds
 * @property bool $success
 * @property array|null $meta
 * @property \Carbon\Carbon $created_at
 */
class FortuneVoiceUsage extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'fortune_voice_usages';

    protected $fillable = [
        'kind', 'purpose', 'provider', 'persona', 'user_ref', 'chars', 'seconds', 'success', 'meta',
    ];

    protected $casts = [
        'chars' => 'integer',
        'seconds' => 'float',
        'success' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * บันทึกการใช้ — ล้ม (ตารางยังไม่มี/DB สะดุด) ต้องไม่ทำให้คำขอของลูกค้าล้มตาม
     */
    public static function record(array $attributes): void
    {
        try {
            self::create($attributes);
        } catch (\Throwable $e) {
            Log::warning('🔈 บันทึกการใช้เสียงไม่สำเร็จ', ['kind' => $attributes['kind'] ?? null, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        }
    }
}
