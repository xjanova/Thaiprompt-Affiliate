<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneVoiceStorageService;
use App\Services\Tts\GoogleCloudTtsProvider;
use App\Services\Tts\GttsProvider;
use App\Services\Tts\MiniMaxTtsProvider;
use App\Services\Tts\OpenAITtsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 🩺 (2026-05-18) Fortune Voice Diagnostic Controller
 *
 * หน้าตรวจระบบ TTS ทั้งหมด — ดูได้ว่าอะไรพัง อะไรไม่พัง
 *
 *   - Voice toggle status (enabled / tier scope)
 *   - Storage driver health (symlink / credentials / package)
 *   - Provider availability (key configured, can synth test)
 *   - Last 20 failed readings ที่ voice ไม่ส่ง
 *   - Test synth ด้วย provider ที่เลือก (ฟังเสียงตัวอย่าง 30 char)
 */
class FortuneVoiceDiagnosticController extends Controller
{
    public function index(): View
    {
        $settings = FortuneTellingSetting::getSettings();
        $storage = new FortuneVoiceStorageService($settings);

        // Provider availability + last error reason (ถ้ามี)
        $providers = $this->checkAllProviders($settings);

        // Storage availability
        $storageStatus = $storage->driverAvailability();

        // Stats: นับ readings ที่ voice generated vs failed
        $stats = [
            'total_celtic' => FortuneReading::where('reading_type', 'celtic_cross')->count(),
            'voice_generated' => FortuneReading::whereNotNull('voice_audio_path')->count(),
            'voice_failed_state' => FortuneReading::where('reading_type', 'celtic_cross')
                ->where('conversation_state', 'like', '%voice_summary_status%')
                ->whereRaw("conversation_state LIKE '%\"voice_summary_status\":\"failed%'")
                ->count(),
        ];

        // Last 20 readings with voice issues
        $recentFails = FortuneReading::where('reading_type', 'celtic_cross')
            ->whereNotNull('conversation_state')
            ->whereRaw("conversation_state LIKE '%voice_summary_error%'")
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'platform_user_id', 'platform', 'conversation_state', 'created_at'])
            ->map(function ($r) {
                $state = is_array($r->conversation_state) ? $r->conversation_state : (json_decode($r->conversation_state, true) ?: []);

                return [
                    'id' => $r->id,
                    'platform' => $r->platform,
                    'user_id' => $r->platform_user_id,
                    'created_at' => $r->created_at?->diffForHumans(),
                    'status' => $state['voice_summary_status'] ?? null,
                    'error' => $state['voice_summary_error'] ?? null,
                ];
            });

        return view('admin.fortune.voice-diagnostic', [
            'pageTitle' => '🩺 TTS Voice Diagnostic',
            'settings' => $settings,
            'providers' => $providers,
            'storage' => $storage,
            'storageStatus' => $storageStatus,
            'stats' => $stats,
            'recentFails' => $recentFails,
        ]);
    }

    /**
     * AJAX — ทดสอบสังเคราะห์เสียงสั้นๆ กับ provider เดียว
     */
    public function testProvider(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'nullable|string|max:500',
        ]);

        $text = $validated['text'] ?? 'สวัสดีค่ะ ดิฉันแม่หมอจันทรา ทดสอบระบบเสียงนะคะ';
        $settings = FortuneTellingSetting::getSettings();

        $instance = $this->resolveProvider($provider, $settings);
        if (! $instance) {
            return response()->json([
                'success' => false,
                'error' => 'Unknown provider: '.$provider,
            ], 422);
        }

        if (! $instance->isAvailable()) {
            return response()->json([
                'success' => false,
                'error' => "Provider '{$provider}' ไม่พร้อมใช้ — เช็ค API key/credentials/package ก่อน",
            ], 422);
        }

        $tempDir = storage_path('app/tmp-voice');
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        $token = Str::random(16);
        $tempPath = $tempDir.'/diag-'.$token.'.mp3';

        try {
            $result = $instance->synthesize($text, [
                'output_path' => $tempPath,
                'language' => 'th',
            ]);

            if (! $result['success']) {
                @unlink($tempPath);

                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Synthesis failed',
                    'voice_used' => $result['voice_used'] ?? null,
                ], 422);
            }

            // อัปโหลดผ่าน storage service
            $storage = new FortuneVoiceStorageService($settings);
            $relativePath = 'fortune-voice-diagnostic/'.$provider.'-'.$token.'.mp3';
            $put = $storage->putAudio($tempPath, $relativePath);

            if (! $put['success']) {
                @unlink($tempPath);

                return response()->json([
                    'success' => false,
                    'error' => 'Synth สำเร็จ แต่ upload ล้มเหลว: '.$put['error'],
                    'synth_ok' => true,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'audio_url' => $put['url'],
                'voice_used' => $result['voice_used'] ?? null,
                'duration_ms' => $result['duration_ms'] ?? null,
                'audio_length_ms' => $result['audio_length_ms'] ?? null,
                'storage_driver' => $put['disk'],
                'message' => '✅ '.$provider.' ใช้ได้ — เซฟที่ '.$storage->driverName($put['disk']),
            ]);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            Log::warning('Diagnostic testProvider exception', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * AJAX — กดสร้าง voice ใหม่สำหรับ reading ที่ค้าง
     */
    public function regenerateReading(Request $request, int $readingId): JsonResponse
    {
        $reading = FortuneReading::find($readingId);
        if (! $reading) {
            return response()->json(['success' => false, 'error' => 'ไม่พบ reading'], 404);
        }

        try {
            \App\Jobs\ProcessVoiceSummaryJob::dispatchSmart(
                $reading->id,
                $reading->platform,
                $reading->platform_user_id
            );

            return response()->json([
                'success' => true,
                'message' => '🚀 dispatched voice job ใหม่สำหรับ reading #'.$reading->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Dispatch exception: '.$e->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * เช็คทุก provider — return array พร้อม status สำหรับแสดงในหน้า
     *
     * @return array<int, array{name: string, label: string, available: bool, hint: string, ...}>
     */
    protected function checkAllProviders(FortuneTellingSetting $settings): array
    {
        $list = [
            ['name' => 'minimax', 'label' => '🎙️ MiniMax (premium, primary)'],
            ['name' => 'openai_tts', 'label' => '🎙️ OpenAI TTS (paid)'],
            ['name' => 'google_tts', 'label' => '🎙️ Google Cloud TTS (free 1M chars/เดือน)'],
            ['name' => 'gtts', 'label' => '🆓 gTTS (Google Translate, ไม่ต้องตั้ง key)'],
        ];

        $checks = [];
        foreach ($list as $row) {
            $instance = $this->resolveProvider($row['name'], $settings);
            $available = $instance && $instance->isAvailable();

            $hint = match ($row['name']) {
                'minimax' => $available
                    ? '✅ MiniMax key ตั้งแล้ว'
                    : '❗ ตั้ง MiniMax API key ที่ Voice section หรือ /admin/ai-api-keys (provider=minimax, purpose=tts)',
                'openai_tts' => $available
                    ? '✅ OpenAI key พร้อม'
                    : '❗ ต้องมี key ใน pool: /admin/ai-api-keys (provider=openai, purpose=tts หรือ any)',
                'google_tts' => $available
                    ? '✅ Google credentials พร้อม'
                    : '❗ ต้องตั้ง GOOGLE_TTS_API_KEY หรือ GOOGLE_TRANSLATE_API_KEY ใน .env (หรือ service account JSON)',
                'gtts' => '✅ ไม่ต้องตั้งค่า (ใช้เป็น fallback สุดท้าย)',
                default => '',
            };

            $checks[] = array_merge($row, [
                'available' => $available,
                'hint' => $hint,
                'is_primary' => ($settings->voice_summary_primary_provider ?? 'minimax') === $row['name'],
                'in_fallback' => is_array($settings->voice_summary_fallback_providers)
                    && in_array($row['name'], $settings->voice_summary_fallback_providers, true),
            ]);
        }

        return $checks;
    }

    protected function resolveProvider(string $name, FortuneTellingSetting $settings)
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
