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
 * @property string|null $upload_audio_path
 * @property string|null $upload_audio_url
 * @property int|null $upload_audio_duration_ms
 * @property string|null $upload_original_name
 * @property \Carbon\Carbon|null $upload_audio_at
 * @property string $active_audio_source tts|upload — เลือกว่าจะส่งสล็อตไหนให้ลูกค้า
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
        // สล็อตอัปโหลด (แยกจาก TTS)
        'upload_audio_path',
        'upload_audio_url',
        'upload_audio_duration_ms',
        'upload_original_name',
        'upload_audio_at',
        // ตัวเลือก: tts | upload
        'active_audio_source',
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
        'upload_audio_duration_ms' => 'integer',
        'upload_audio_at' => 'datetime',
        'voice_config' => 'array',
    ];

    /**
     * สล็อตที่เลือกใช้จริง (tts | upload) — fallback 'tts'
     */
    public function activeSource(): string
    {
        return in_array($this->active_audio_source, ['tts', 'upload'], true)
            ? $this->active_audio_source
            : 'tts';
    }

    /**
     * มีไฟล์เสียง TTS (สร้างจากระบบ) ในสล็อตหรือยัง
     */
    public function hasTtsAudio(): bool
    {
        return ! empty($this->audio_path) && ! empty($this->audio_url);
    }

    /**
     * มีไฟล์เสียงที่อัปโหลดเองในสล็อตหรือยัง
     */
    public function hasUploadAudio(): bool
    {
        return ! empty($this->upload_audio_path) && ! empty($this->upload_audio_url);
    }

    /**
     * มีไฟล์เสียงในสล็อตใดสล็อตหนึ่งไหม
     */
    public function hasAnyAudio(): bool
    {
        return $this->hasTtsAudio() || $this->hasUploadAudio();
    }

    /**
     * path ของไฟล์เสียงที่ "ใช้จริง" (ตาม active source)
     */
    public function activeAudioPath(): ?string
    {
        return $this->activeSource() === 'upload' ? $this->upload_audio_path : $this->audio_path;
    }

    /**
     * URL ของไฟล์เสียงที่ "ใช้จริง" (ตาม active source)
     */
    public function activeAudioUrl(): ?string
    {
        return $this->activeSource() === 'upload' ? $this->upload_audio_url : $this->audio_url;
    }

    /**
     * ความยาวเสียง ms ของไฟล์ที่ "ใช้จริง"
     */
    public function activeAudioDurationMs(): ?int
    {
        return $this->activeSource() === 'upload' ? $this->upload_audio_duration_ms : $this->audio_duration_ms;
    }

    /**
     * เวอร์ชันไฟล์เสียงสล็อตที่ใช้จริง (epoch วินาที) — สำหรับ cache-bust URL
     *
     * อิงเวลาสร้าง/อัปโหลดล่าสุด → เปลี่ยนทุกครั้งที่ "เจนเสียงใหม่"
     * (tts = generated_at / upload = upload_audio_at)
     */
    public function activeAudioVersion(): ?int
    {
        $ts = $this->activeSource() === 'upload' ? $this->upload_audio_at : $this->generated_at;

        return $ts?->getTimestamp();
    }

    /**
     * 🔊 URL เสียงสล็อตที่ใช้จริง + cache-bust (?v=version) — "URL ที่ใช้ส่งลูกค้า"
     *
     * ⚠️ ทำไมต้องมี: ไฟล์เสียงเก็บที่ path คงที่ ({key}.mp3) → URL เดิมทุกครั้งที่เจนใหม่
     *   Facebook cache สื่อตาม URL (is_reusable=true) + เซิร์ฟเวอร์ตั้ง Cache-Control:max-age
     *   → เจนเสียงใหม่แล้ว แต่ FB ยังส่ง "เสียงเก่า" ให้ลูกค้า เพราะ URL ไม่เปลี่ยน
     *   เติม ?v={timestamp การเจน} → เจนใหม่ = URL ใหม่ → FB โหลดไฟล์ใหม่
     *   (ใช้ timestamp ไม่ใช่ random → ส่งเวอร์ชันเดิมซ้ำ FB ยัง reuse cache ได้ ไม่เปลืองโหลด)
     */
    public function deliveryAudioUrl(): ?string
    {
        $url = $this->activeAudioUrl();
        if (empty($url)) {
            return null;
        }

        $version = $this->activeAudioVersion();
        if (! $version) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
    }

    /**
     * คลิปนี้มีไฟล์เสียง "ในสล็อตที่เลือกใช้" แล้วหรือยัง
     *
     * ⚠️ หมายถึงสล็อต active เท่านั้น (ใช้ตัดสินใจส่งจริง) — ไม่ใช่ "มีไฟล์ใดๆ"
     */
    public function hasAudio(): bool
    {
        return $this->activeSource() === 'upload' ? $this->hasUploadAudio() : $this->hasTtsAudio();
    }

    /**
     * คลิปพร้อมส่งจริงไหม (เปิดอยู่ + มีไฟล์เสียงในสล็อตที่เลือก)
     */
    public function isDeliverable(): bool
    {
        return $this->enabled && $this->hasAudio();
    }

    /**
     * ตอนนี้คลิปตั้งให้ใช้ไฟล์ "อัปโหลดเอง" หรือไม่ (ไม่ใช่ TTS)
     */
    public function isUploaded(): bool
    {
        return $this->activeSource() === 'upload';
    }
}
