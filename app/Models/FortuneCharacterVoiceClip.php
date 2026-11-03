<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🎭 (2026-11-03) ไฟล์เสียงพากย์ตัวละคร (แม่หมอจันทรา / น้องบุษบา) — ทำหน้าที่เป็นแคช
 *
 * ข้อความ+เสียง+โมเดลเดิม = text_hash เดิม = ใช้ไฟล์เดิม ไม่สั่งสร้างใหม่ (คุมต้นทุน)
 *
 * @property int $id
 * @property string $persona maemor | busaba | kunsai
 * @property string $purpose sample | chat
 * @property string $provider
 * @property string $model
 * @property string $voice_id
 * @property string $text_hash
 * @property string $text
 * @property int $chars
 * @property string $status pending | completed | failed
 * @property int|null $remote_generation_id
 * @property string|null $audio_path
 * @property string|null $audio_disk
 * @property string|null $audio_url
 * @property string|null $error
 * @property int $hits
 * @property \Carbon\Carbon|null $completed_at
 */
class FortuneCharacterVoiceClip extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'fortune_character_voice_clips';

    protected $fillable = [
        'persona',
        'purpose',
        'provider',
        'model',
        'voice_id',
        'text_hash',
        'text',
        'chars',
        'status',
        'remote_generation_id',
        'audio_path',
        'audio_disk',
        'audio_url',
        'error',
        'hits',
        'completed_at',
    ];

    protected $casts = [
        'chars' => 'integer',
        'hits' => 'integer',
        'remote_generation_id' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED && ! empty($this->audio_url);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * รูปแบบที่ส่งให้หน้าแอดมิน/เว็บจันทรา — ไม่มีข้อมูลลับ
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'clip_id' => $this->id,
            'persona' => $this->persona,
            'voice_id' => $this->voice_id,
            'status' => $this->status,
            'url' => $this->isCompleted() ? $this->audio_url : null,
            'error' => $this->status === self::STATUS_FAILED ? $this->error : null,
            'chars' => $this->chars,
        ];
    }
}
