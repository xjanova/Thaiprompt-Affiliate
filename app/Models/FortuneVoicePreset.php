<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 🎙️ Fortune Voice Preset — Library ของ voice configs
 *
 * @property int $id
 * @property string $name
 * @property string $provider
 * @property string $voice_id
 * @property string|null $model
 * @property string $language
 * @property bool $is_default
 * @property int $sort_order
 * @property string|null $sample_text
 * @property string|null $sample_audio_path
 * @property \Carbon\Carbon|null $sample_generated_at
 * @property string|null $notes
 */
class FortuneVoicePreset extends Model
{
    protected $table = 'fortune_voice_presets';

    protected $fillable = [
        'name',
        'provider',
        'voice_id',
        'model',
        'language',
        'is_default',
        'sort_order',
        'sample_text',
        'sample_audio_path',
        'sample_generated_at',
        'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'sample_generated_at' => 'datetime',
    ];

    /**
     * URL ของ sample audio (สำหรับ player ใน admin UI)
     *
     * 🌥️ (2026-05-18) ใช้ FortuneVoiceStorageService เพื่อรองรับ cloud driver
     *   - admin เปลี่ยน driver → sample URL ต้องชี้ไปที่ cloud ที่ถูก
     */
    public function getSampleAudioUrlAttribute(): ?string
    {
        if (empty($this->sample_audio_path)) {
            return null;
        }

        try {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $storage = new \App\Services\FortuneVoiceStorageService($settings);
            $url = $storage->audioUrl($this->sample_audio_path);
            if (! empty($url)) {
                return $url;
            }
        } catch (\Throwable $e) {
            // fallback ลง local — รอ admin migrate
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->sample_audio_path);
    }

    /**
     * ตั้งเป็น preset ปัจจุบัน + เคลียร์ตัวเดิม
     */
    public function setAsDefault(): void
    {
        // เคลียร์ทุกตัว
        static::query()->update(['is_default' => false]);
        // ตั้ง row นี้
        $this->update(['is_default' => true]);
    }
}
