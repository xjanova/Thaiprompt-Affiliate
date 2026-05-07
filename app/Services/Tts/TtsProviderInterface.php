<?php

namespace App\Services\Tts;

/**
 * 🎙️ (2026-05-08) Interface สำหรับ TTS provider
 *
 * Implementations:
 *   - MiniMaxTtsProvider     (paid, primary, premium HD voice)
 *   - OpenAITtsProvider      (paid, fallback ผ่าน ai_api_keys pool)
 *   - GoogleCloudTtsProvider (free 1M chars/month, ใช้ Google credentials เดิม)
 *   - GttsProvider           (Google Translate unofficial — ไม่ต้องมี key, fallback สุดท้าย)
 *
 * Contract:
 *   synthesize($text) → ['success' => bool, 'audio_path' => ?string, 'error' => ?string]
 *   audio_path = absolute filesystem path ของ mp3 file
 *   caller รับผิดชอบ move file ไปที่ public storage + คืน URL
 */
interface TtsProviderInterface
{
    /**
     * ชื่อ provider (สำหรับ log)
     */
    public function name(): string;

    /**
     * Provider พร้อมใช้งานหรือไม่ (เช็ค key/credentials ก่อนเรียก synthesize)
     */
    public function isAvailable(): bool;

    /**
     * สังเคราะห์ข้อความเป็นไฟล์เสียง mp3
     *
     * @param  string  $text  ข้อความที่จะอ่าน (ไม่ควรเกิน 2000 chars)
     * @param  array  $options  [
     *                          'voice' => string|null,  // override voice id
     *                          'language' => string,    // 'th' / 'en'
     *                          'output_path' => string, // absolute path ไฟล์ output
     *                          ]
     * @return array{success: bool, audio_path: ?string, error: ?string, duration_ms: ?int, voice_used: ?string}
     */
    public function synthesize(string $text, array $options = []): array;
}
