<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneVoicePreset;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\MiniMaxTtsProvider;
use App\Services\Tts\OpenAITtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🎙️ Fortune Voice Preset Controller
 *
 * จัดการ Voice Library — ตั้งค่า, ทดสอบ, อิมพอตโค๊ดเสียง
 *
 * Endpoints:
 *   GET    /admin/fortune/voice-presets          → list (JSON)
 *   POST   /admin/fortune/voice-presets          → create
 *   PUT    /admin/fortune/voice-presets/{id}     → update
 *   DELETE /admin/fortune/voice-presets/{id}     → delete
 *   POST   /admin/fortune/voice-presets/{id}/apply  → set default + sync to settings
 *   POST   /admin/fortune/voice-presets/{id}/test   → generate sample audio
 *   POST   /admin/fortune/voice-presets/import   → bulk paste import
 *   POST   /admin/fortune/voice-presets/seed-thai → seed Thai voices ที่นิยม
 */
class FortuneVoicePresetController extends Controller
{
    /**
     * GET — รายการ presets ทั้งหมด
     */
    public function index(): JsonResponse
    {
        $presets = FortuneVoicePreset::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => $this->serialize($p));

        return response()->json([
            'success' => true,
            'data' => $presets,
        ]);
    }

    /**
     * POST — สร้าง preset ใหม่
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|in:minimax,openai_tts,google_tts,gtts',
            'voice_id' => 'required|string|max:100',
            'model' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'notes' => 'nullable|string|max:500',
        ]);

        $preset = FortuneVoicePreset::create($validated);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($preset),
            'message' => 'เพิ่ม preset สำเร็จ',
        ]);
    }

    /**
     * PUT — แก้ไข preset
     */
    public function update(Request $request, FortuneVoicePreset $preset): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'provider' => 'sometimes|in:minimax,openai_tts,google_tts,gtts',
            'voice_id' => 'sometimes|string|max:100',
            'model' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'notes' => 'nullable|string|max:500',
        ]);

        $preset->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($preset->fresh()),
            'message' => 'อัปเดตสำเร็จ',
        ]);
    }

    /**
     * DELETE — ลบ preset
     */
    public function destroy(FortuneVoicePreset $preset): JsonResponse
    {
        // ลบ sample audio file ด้วย — ใช้ storage service เผื่อ admin ตั้ง cloud
        if ($preset->sample_audio_path) {
            $settings = \App\Models\FortuneTellingSetting::getSettings();
            $storage = new FortuneVoiceStorageService($settings);
            // sample ของ preset เก่าอาจอยู่ local — fallback ลบทั้ง 2 ที่
            $storage->deleteAudio($preset->sample_audio_path);
            Storage::disk('public')->delete($preset->sample_audio_path);
        }

        $preset->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบ preset สำเร็จ',
        ]);
    }

    /**
     * POST /apply — ตั้งเป็น default + sync ไปที่ FortuneTellingSetting
     */
    public function apply(FortuneVoicePreset $preset): JsonResponse
    {
        $preset->setAsDefault();

        // Sync ค่าไปที่ FortuneTellingSetting ตาม provider
        $settings = \App\Models\FortuneTellingSetting::getSettings();

        $update = [
            'voice_summary_primary_provider' => $preset->provider,
        ];

        switch ($preset->provider) {
            case 'minimax':
                $update['minimax_voice_id'] = $preset->voice_id;
                if ($preset->model) {
                    $update['minimax_model'] = $preset->model;
                }
                break;
            case 'openai_tts':
                $update['openai_tts_voice'] = $preset->voice_id;
                if ($preset->model) {
                    $update['openai_tts_model'] = $preset->model;
                }
                break;
            case 'google_tts':
                $update['google_tts_voice'] = $preset->voice_id;
                break;
        }

        $settings->update($update);

        return response()->json([
            'success' => true,
            'message' => "ตั้ง '{$preset->name}' เป็นเสียงปัจจุบันแล้ว",
            'applied_settings' => $update,
        ]);
    }

    /**
     * POST /test — ทดสอบเสียง — generate sample audio
     */
    public function test(Request $request, FortuneVoicePreset $preset): JsonResponse
    {
        $sampleText = $request->input('text')
            ?? $preset->sample_text
            ?? 'สวัสดีค่ะ ดิฉันแม่หมอจันทรา วันนี้เจ้าชะตาดวงดีนะคะ ใจเย็น ๆ คิดให้ดีก่อนตัดสินใจค่ะ';

        // จำกัด 500 chars สำหรับ test
        $sampleText = mb_substr($sampleText, 0, 500);

        // ใช้ provider จริงสังเคราะห์
        $settings = \App\Models\FortuneTellingSetting::getSettings();

        // Override ค่า provider ใน settings ชั่วคราว เพื่อ test ตรง preset
        $originalValues = $this->overrideSettingsForTest($settings, $preset);

        try {
            $provider = $this->resolveProvider($preset->provider, $settings);
            if (! $provider) {
                throw new \Exception('Unknown provider: '.$preset->provider);
            }

            if (! $provider->isAvailable()) {
                throw new \Exception("Provider '{$preset->provider}' ไม่พร้อมใช้ — เช็ค API key/credentials ก่อน (ดู /admin/fortune/voice-diagnostic)");
            }

            // ลบ sample เก่า ถ้ามี — ใช้ storage service เผื่อ cloud
            $storage = new FortuneVoiceStorageService($settings);
            if ($preset->sample_audio_path) {
                $storage->deleteAudio($preset->sample_audio_path);
                Storage::disk('public')->delete($preset->sample_audio_path);
            }

            $token = Str::random(20);
            $relativePath = 'fortune-voice-samples/preset-'.$preset->id.'-'.$token.'.mp3';

            // TTS provider เขียนลง temp file ก่อน → upload ผ่าน storage service
            $tempDir = storage_path('app/tmp-voice');
            if (! is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }
            $tempPath = $tempDir.'/sample-'.$token.'.mp3';

            $result = $provider->synthesize($sampleText, [
                'voice' => $preset->voice_id,
                'output_path' => $tempPath,
                'language' => $preset->language ?? 'th',
            ]);

            if (! $result['success']) {
                @unlink($tempPath);
                throw new \Exception($result['error'] ?? 'TTS synthesis failed');
            }

            // อัปโหลด temp → storage driver ที่ admin เลือก
            $put = $storage->putAudio($tempPath, $relativePath);
            if (! $put['success']) {
                @unlink($tempPath);
                throw new \Exception('Upload sample ล้มเหลว: '.$put['error']);
            }

            $preset->update([
                'sample_text' => $sampleText,
                'sample_audio_path' => $relativePath,
                'sample_generated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'audio_url' => $put['url'],
                'duration_ms' => $result['audio_length_ms'] ?? null,
                'voice_used' => $result['voice_used'] ?? $preset->voice_id,
                'storage_driver' => $put['disk'],
                'message' => 'สร้าง sample audio สำเร็จ (เซฟที่ '.$storage->driverName($put['disk']).')',
            ]);
        } catch (\Throwable $e) {
            Log::warning('FortuneVoicePreset: test fail', [
                'preset_id' => $preset->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } finally {
            // คืนค่า settings เดิม (ไม่ persist override)
            foreach ($originalValues as $k => $v) {
                $settings->{$k} = $v;
            }
        }
    }

    /**
     * POST /import — bulk import จาก textarea/JSON
     *
     * รองรับ 2 format:
     *   1. Plain text — 1 voice_id ต่อบรรทัด
     *      voice_id|name|model
     *      Thai_warmFemaleHost|แม่หมอใจดี|speech-2.8-hd
     *      Thai_femaleSerene|เสียงสงบ
     *   2. JSON array
     *      [{"name":"...","provider":"minimax","voice_id":"...","model":"..."}]
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:minimax,openai_tts,google_tts,gtts',
            'raw' => 'required|string|max:50000',
            'replace' => 'boolean',
        ]);

        $provider = $validated['provider'];
        $raw = trim($validated['raw']);
        $replace = (bool) ($validated['replace'] ?? false);

        $entries = $this->parseImportData($raw, $provider);

        if (empty($entries)) {
            return response()->json([
                'success' => false,
                'error' => 'ไม่พบข้อมูลที่ parse ได้ — ตรวจ format อีกครั้ง',
            ], 422);
        }

        // Replace mode: ลบ presets ของ provider นี้ก่อน
        if ($replace) {
            FortuneVoicePreset::where('provider', $provider)->delete();
        }

        $created = 0;
        $skipped = 0;
        $maxOrder = (int) FortuneVoicePreset::max('sort_order');

        foreach ($entries as $entry) {
            // Skip duplicate (provider + voice_id เดิม)
            $exists = FortuneVoicePreset::where('provider', $provider)
                ->where('voice_id', $entry['voice_id'])
                ->exists();
            if ($exists && ! $replace) {
                $skipped++;

                continue;
            }

            FortuneVoicePreset::create([
                'name' => $entry['name'] ?: $entry['voice_id'],
                'provider' => $provider,
                'voice_id' => $entry['voice_id'],
                'model' => $entry['model'] ?? null,
                'language' => $entry['language'] ?? 'th',
                'sort_order' => ++$maxOrder,
                'notes' => $entry['notes'] ?? null,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => "อิมพอต {$created} voice presets สำเร็จ"
                .($skipped > 0 ? " (ข้าม {$skipped} ตัวที่มีอยู่แล้ว)" : ''),
        ]);
    }

    /**
     * POST /seed-thai — เพิ่ม Thai voices ที่นิยมแบบ default (ไม่ต้อง paste เอง)
     */
    public function seedThai(): JsonResponse
    {
        $defaults = [
            // MiniMax — Thai voices ที่นิยม (ตาม community)
            ['provider' => 'minimax', 'voice_id' => 'Thai_warmFemaleHost', 'name' => '🌙 แม่หมอใจดี (MiniMax)', 'model' => 'speech-2.8-hd'],
            ['provider' => 'minimax', 'voice_id' => 'Thai_femaleSerene', 'name' => '✨ เสียงสงบนุ่มนวล (MiniMax)', 'model' => 'speech-2.8-hd'],
            ['provider' => 'minimax', 'voice_id' => 'female-tianmei', 'name' => '🍯 เสียงหวาน (MiniMax)', 'model' => 'speech-2.8-hd'],
            ['provider' => 'minimax', 'voice_id' => 'Thai_warmFemaleHost', 'name' => '⚡ แม่หมอ Turbo (เร็ว+ถูก)', 'model' => 'speech-2.8-turbo'],

            // Google Cloud TTS — Thai voices
            ['provider' => 'google_tts', 'voice_id' => 'th-TH-Neural2-C', 'name' => '🌟 Google Neural2-C (premium)'],
            ['provider' => 'google_tts', 'voice_id' => 'th-TH-Neural2-A', 'name' => '✨ Google Neural2-A'],
            ['provider' => 'google_tts', 'voice_id' => 'th-TH-Standard-A', 'name' => '🆓 Google Standard-A (4M chars/mo free)'],
            ['provider' => 'google_tts', 'voice_id' => 'th-TH-Wavenet-C', 'name' => '🎵 Google WaveNet-C'],

            // OpenAI TTS — language-agnostic voices (รองรับไทย)
            ['provider' => 'openai_tts', 'voice_id' => 'shimmer', 'name' => '✨ OpenAI Shimmer (female warm)', 'model' => 'gpt-4o-mini-tts'],
            ['provider' => 'openai_tts', 'voice_id' => 'nova', 'name' => '🌟 OpenAI Nova (female bright)', 'model' => 'gpt-4o-mini-tts'],

            // gTTS — Thai default (no voice_id customization)
            ['provider' => 'gtts', 'voice_id' => 'th', 'name' => '🆓 gTTS Thai (Google Translate, ฟรี)'],
        ];

        $created = 0;
        $skipped = 0;
        $maxOrder = (int) FortuneVoicePreset::max('sort_order');

        foreach ($defaults as $entry) {
            $exists = FortuneVoicePreset::where('provider', $entry['provider'])
                ->where('voice_id', $entry['voice_id'])
                ->where('model', $entry['model'] ?? null)
                ->exists();
            if ($exists) {
                $skipped++;

                continue;
            }

            FortuneVoicePreset::create([
                'name' => $entry['name'],
                'provider' => $entry['provider'],
                'voice_id' => $entry['voice_id'],
                'model' => $entry['model'] ?? null,
                'language' => 'th',
                'sort_order' => ++$maxOrder,
            ]);
            $created++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'skipped' => $skipped,
            'message' => "Seed Thai voices สำเร็จ ({$created} ใหม่, ข้าม {$skipped})",
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    protected function serialize(FortuneVoicePreset $preset): array
    {
        return [
            'id' => $preset->id,
            'name' => $preset->name,
            'provider' => $preset->provider,
            'voice_id' => $preset->voice_id,
            'model' => $preset->model,
            'language' => $preset->language,
            'is_default' => (bool) $preset->is_default,
            'sort_order' => $preset->sort_order,
            'sample_text' => $preset->sample_text,
            'sample_audio_url' => $preset->sample_audio_url,
            'sample_generated_at' => $preset->sample_generated_at?->diffForHumans(),
            'notes' => $preset->notes,
        ];
    }

    /**
     * Parse import data — รองรับ JSON หรือ pipe-delimited
     */
    protected function parseImportData(string $raw, string $provider): array
    {
        // ลอง JSON ก่อน
        $trimmed = trim($raw);
        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $data = json_decode($trimmed, true);
            if (is_array($data)) {
                $items = isset($data['voice_id']) ? [$data] : $data;
                $entries = [];
                foreach ($items as $item) {
                    if (! is_array($item) || empty($item['voice_id'])) {
                        continue;
                    }
                    $entries[] = [
                        'voice_id' => (string) $item['voice_id'],
                        'name' => (string) ($item['name'] ?? $item['voice_id']),
                        'model' => $item['model'] ?? null,
                        'language' => $item['language'] ?? 'th',
                        'notes' => $item['notes'] ?? null,
                    ];
                }

                return $entries;
            }
        }

        // Pipe-delimited: voice_id|name|model
        $entries = [];
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            $voiceId = $parts[0] ?? '';
            if ($voiceId === '') {
                continue;
            }
            $entries[] = [
                'voice_id' => $voiceId,
                'name' => $parts[1] ?? $voiceId,
                'model' => $parts[2] ?? null,
                'language' => 'th',
            ];
        }

        return $entries;
    }

    /**
     * Override settings ชั่วคราว (in-memory) สำหรับ test mode
     * คืน array ของค่าเดิมเพื่อ rollback
     */
    protected function overrideSettingsForTest(\App\Models\FortuneTellingSetting $settings, FortuneVoicePreset $preset): array
    {
        $original = [];

        switch ($preset->provider) {
            case 'minimax':
                $original['minimax_voice_id'] = $settings->minimax_voice_id;
                $original['minimax_model'] = $settings->minimax_model;
                $settings->minimax_voice_id = $preset->voice_id;
                if ($preset->model) {
                    $settings->minimax_model = $preset->model;
                }
                break;
            case 'openai_tts':
                $original['openai_tts_voice'] = $settings->openai_tts_voice;
                $original['openai_tts_model'] = $settings->openai_tts_model;
                $settings->openai_tts_voice = $preset->voice_id;
                if ($preset->model) {
                    $settings->openai_tts_model = $preset->model;
                }
                break;
            case 'google_tts':
                $original['google_tts_voice'] = $settings->google_tts_voice;
                $settings->google_tts_voice = $preset->voice_id;
                break;
        }

        return $original;
    }

    protected function resolveProvider(string $name, \App\Models\FortuneTellingSetting $settings)
    {
        return match ($name) {
            'minimax' => new MiniMaxTtsProvider($settings),
            'openai_tts' => new OpenAITtsProvider($settings, app(\App\Services\AiApiKeyPoolService::class)),
            'google_tts' => new GoogleCloudTtsProvider($settings),
            'gtts' => new GttsProvider,
            default => null,
        };
    }
}
