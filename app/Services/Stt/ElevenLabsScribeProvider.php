<?php

namespace App\Services\Stt;

use App\Models\FortuneTellingSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🎙️ (2026-11-04) ElevenLabs Scribe — ถอดเสียงไมค์ของลูกค้า (ตัวสำรองเมื่อเบราว์เซอร์ถอดเองไม่ได้)
 *
 *   POST {base}/v1/speech-to-text  multipart: file, model_id, language_code=th, timestamps_granularity=word
 *        → {text, language_code, words:[{text,start,end,type:word|spacing|audio_event}]}
 *
 * ใช้ key ElevenLabs ตัวเดียวกับเสียงตัวละคร (ตาราง settings เข้ารหัส) · คิดเงินตามความยาวเสียง (~$0.22/ชม.)
 * ไม่ retry เอง — เว็บมีทางถอย (ให้ลูกค้าพิมพ์) และการส่งซ้ำ = จ่ายซ้ำ
 * 🔒 key ห้ามโผล่ใน log / error
 */
class ElevenLabsScribeProvider implements SttProviderInterface
{
    public const BASE_URL = 'https://api.elevenlabs.io';

    public const DEFAULT_MODEL = 'scribe_v2';

    /** แอดมินเปลี่ยนรุ่นได้ที่ตาราง settings (เช่น scribe_v1 ถ้ารุ่นใหม่มีปัญหากับภาษาไทย) */
    public const MODEL_SETTING = 'fortune_elevenlabs_stt_model';

    protected string $baseUrl;

    public function __construct(
        protected FortuneTellingSetting $settings,
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?: (string) config('services.elevenlabs.base_url', self::BASE_URL), '/');
    }

    public function name(): string
    {
        return 'scribe';
    }

    public function isAvailable(): bool
    {
        return $this->settings->getElevenlabsApiKey() !== null;
    }

    public function model(): string
    {
        try {
            $model = trim((string) Setting::get(self::MODEL_SETTING, ''));
        } catch (\Throwable) {
            $model = '';
        }

        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    public function transcribe(string $path, array $options = []): array
    {
        $key = $this->settings->getElevenlabsApiKey();
        if ($key === null) {
            return $this->fail('ยังไม่ได้ตั้ง ElevenLabs API key');
        }
        if (! is_file($path) || filesize($path) === 0) {
            return $this->fail('ไม่มีไฟล์เสียง');
        }

        $handle = fopen($path, 'rb');
        try {
            $response = Http::withHeaders(['xi-api-key' => $key])
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->attach('file', $handle, 'speech.'.$this->extension($options['mime'] ?? null))
                ->post($this->baseUrl.'/v1/speech-to-text', [
                    'model_id' => $this->model(),
                    'language_code' => $options['language'] ?? 'th',
                    'timestamps_granularity' => 'word',
                    'tag_audio_events' => 'false',
                    'diarize' => 'false',
                ]);
        } catch (\Throwable $e) {
            return $this->fail('เชื่อมต่อ ElevenLabs ไม่ได้: '.$this->scrub($e->getMessage(), $key));
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if (! $response->successful()) {
            $detail = $response->json('detail');
            $status = is_array($detail) ? ($detail['status'] ?? null) : null;
            $error = match (true) {
                $status === 'invalid_api_key' || $response->status() === 401 => 'ElevenLabs ไม่รับ API key นี้',
                $status === 'missing_permissions' => 'key นี้ไม่มีสิทธิ์ Speech to Text — เปิดสิทธิ์ให้ key ในหน้า ElevenLabs',
                $status === 'quota_exceeded' => 'โควต้า ElevenLabs หมดแล้ว',
                $response->status() === 429 => 'เรียก ElevenLabs ถี่เกินไป',
                default => 'ElevenLabs ตอบ HTTP '.$response->status(),
            };
            Log::warning('🎙️ Scribe: HTTP fail', ['status' => $response->status(), 'error' => $error]);

            return $this->fail($error);
        }

        $words = [];
        $duration = 0.0;
        foreach ((array) $response->json('words', []) as $w) {
            if (! is_array($w)) {
                continue;
            }
            $end = (float) ($w['end'] ?? 0);
            $duration = max($duration, $end);
            if (($w['type'] ?? 'word') === 'word' && trim((string) ($w['text'] ?? '')) !== '') {
                $words[] = ['text' => (string) $w['text'], 'start' => round((float) ($w['start'] ?? 0), 3), 'end' => round($end, 3)];
            }
        }

        return [
            'success' => true,
            'text' => trim((string) $response->json('text', '')),
            'words' => $words,
            'language' => is_string($response->json('language_code')) ? $response->json('language_code') : null,
            'duration' => round($duration, 2),
            'error' => null,
        ];
    }

    /** นามสกุลไฟล์ตามชนิดเสียง — Scribe ดูชนิดจากเนื้อไฟล์ แต่ชื่อไฟล์ที่ตรงช่วยให้ไม่งง */
    protected function extension(?string $mime): string
    {
        return match (true) {
            $mime === null => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'mp4'), str_contains($mime, 'm4a'), str_contains($mime, 'aac') => 'm4a',
            str_contains($mime, 'mpeg'), str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => 'webm',
        };
    }

    protected function scrub(string $message, string $key): string
    {
        return (string) preg_replace('/sk_[A-Za-z0-9]{8,}/', 'sk_[key]', str_replace($key, '[key]', $message));
    }

    /** @return array{success:false,text:string,words:array,language:null,duration:float,error:string} */
    protected function fail(string $error): array
    {
        return ['success' => false, 'text' => '', 'words' => [], 'language' => null, 'duration' => 0.0, 'error' => $error];
    }
}
