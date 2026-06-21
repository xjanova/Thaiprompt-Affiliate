<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FortuneSystemVoiceClip — คลังเสียง "ระบบ" (ข้อความกลาง)
 *
 * เสียงข้อความตายตัวที่สร้างไฟล์เก็บไว้ล่วงหน้า แล้ว reuse ส่งให้ลูกค้าทุกคน
 * (ไม่ต้องสร้าง TTS ใหม่ทุกครั้ง) — เก็บไฟล์ที่ public disk (local server) เท่านั้น
 *
 * @property int $id
 * @property string $clip_key slug เรียกใช้ในโค้ด
 * @property string $step_group กลุ่มขั้นตอน
 * @property string $title ชื่อแสดง
 * @property string|null $description คำอธิบาย
 * @property string $script_text ข้อความที่จะสร้างเป็นเสียง
 * @property bool $enabled เปิด/ปิดการส่งคลิปนี้
 * @property int $sort_order
 * @property string|null $audio_path
 * @property string|null $audio_url
 * @property int|null $audio_duration_ms
 * @property string|null $audio_provider
 * @property string|null $audio_voice_id
 * @property int|null $audio_chars
 * @property \Carbon\Carbon|null $generated_at
 * @property array|null $voice_config
 */
class FortuneSystemVoiceClip extends Model
{
    /**
     * @var string
     */
    protected $table = 'fortune_system_voice_clips';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'clip_key',
        'step_group',
        'title',
        'description',
        'script_text',
        'enabled',
        'sort_order',
        'audio_path',
        'audio_url',
        'audio_duration_ms',
        'audio_provider',
        'audio_source',
        'audio_original_name',
        'audio_voice_id',
        'audio_chars',
        'generated_at',
        'voice_config',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
        'audio_duration_ms' => 'integer',
        'audio_chars' => 'integer',
        'generated_at' => 'datetime',
        'voice_config' => 'array',
    ];

    /**
     * คลิปนี้มีไฟล์เสียงที่สร้างไว้แล้วหรือยัง
     */
    public function hasAudio(): bool
    {
        return ! empty($this->audio_path) && ! empty($this->audio_url);
    }

    /**
     * คลิปพร้อมส่งจริงไหม (เปิดอยู่ + มีไฟล์เสียง)
     */
    public function isDeliverable(): bool
    {
        return $this->enabled && $this->hasAudio();
    }

    /**
     * ไฟล์เสียงนี้มาจากการอัปโหลดเองหรือไม่ (ไม่ใช่ TTS)
     */
    public function isUploaded(): bool
    {
        return $this->audio_source === 'upload';
    }
}
