<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Services\FortuneVoiceStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * 🌥️ (2026-05-18) Fortune Voice Storage Controller
 *
 * จัดการการตั้งค่า cloud storage สำหรับไฟล์เสียง TTS
 *
 * Endpoints:
 *   POST /admin/fortune/voice-storage/save       → บันทึก driver + config
 *   POST /admin/fortune/voice-storage/test       → ทดสอบ upload/delete กับ driver ปัจจุบัน
 *   POST /admin/fortune/voice-storage/migrate    → ย้ายไฟล์ local → cloud ที่เลือก
 *   POST /admin/fortune/voice-storage/fix-symlink → รัน storage:link ผ่าน Artisan
 *   GET  /admin/fortune/voice-storage/stats      → สถิติไฟล์เสียง
 */
class FortuneVoiceStorageController extends Controller
{
    /**
     * บันทึก driver + config
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver' => 'required|in:local,r2,s3,gcs,firebase',
            'config' => 'nullable|array',
            'config.r2' => 'nullable|array',
            'config.r2.account_id' => 'nullable|string|max:64',
            'config.r2.access_key_id' => 'nullable|string|max:128',
            'config.r2.secret_access_key' => 'nullable|string|max:256',
            'config.r2.bucket' => 'nullable|string|max:128',
            'config.r2.public_url' => 'nullable|string|max:512',
            'config.s3' => 'nullable|array',
            'config.s3.access_key_id' => 'nullable|string|max:128',
            'config.s3.secret_access_key' => 'nullable|string|max:256',
            'config.s3.region' => 'nullable|string|max:32',
            'config.s3.bucket' => 'nullable|string|max:128',
            'config.s3.endpoint' => 'nullable|string|max:512',
            'config.s3.public_url' => 'nullable|string|max:512',
            'config.gcs' => 'nullable|array',
            'config.gcs.credentials_path' => 'nullable|string|max:512',
            'config.gcs.bucket' => 'nullable|string|max:128',
            'config.gcs.public_url' => 'nullable|string|max:512',
            'config.firebase' => 'nullable|array',
            'config.firebase.credentials_path' => 'nullable|string|max:512',
            'config.firebase.bucket' => 'nullable|string|max:128',
        ]);

        $settings = FortuneTellingSetting::getSettings();

        // Merge config — secrets ที่ admin เว้นว่าง (empty string) → ใช้ค่าเดิม (ไม่ทับ)
        $existing = is_array($settings->voice_storage_config) ? $settings->voice_storage_config : [];
        $incoming = $validated['config'] ?? [];

        $secretKeys = [
            'r2' => ['secret_access_key', 'access_key_id'],
            's3' => ['secret_access_key', 'access_key_id'],
            'gcs' => [],
            'firebase' => [],
        ];

        $merged = $existing;
        foreach ($incoming as $driver => $cfg) {
            $merged[$driver] ??= [];
            foreach ($cfg as $k => $v) {
                // Empty string secret → ใช้ค่าเดิม
                if (in_array($k, $secretKeys[$driver] ?? [], true) && ($v === '' || $v === null)) {
                    continue;
                }
                $merged[$driver][$k] = $v;
            }
        }

        $settings->update([
            'voice_storage_driver' => $validated['driver'],
            'voice_storage_config' => $merged,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'บันทึก storage config สำเร็จ — driver: '.$validated['driver'],
            'driver' => $validated['driver'],
        ]);
    }

    /**
     * ทดสอบ upload/read/delete กับ driver
     *
     * ถ้าส่ง override_driver + override_config มา → ทดสอบกับค่าใหม่ก่อนเซฟ
     * ถ้าไม่ส่ง → ทดสอบกับ driver+config ปัจจุบันใน DB
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'override_driver' => 'nullable|in:local,r2,s3,gcs,firebase',
            'override_config' => 'nullable|array',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $storage = new FortuneVoiceStorageService($settings);

        $driver = $request->input('override_driver') ?: $storage->driver();
        $overrideCfg = $request->input('override_config');

        try {
            $result = $storage->testConnection($driver, $overrideCfg);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result['details'],
                'driver' => $driver,
                'driver_name' => $storage->driverName($driver),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FortuneVoiceStorage: test exception', [
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Exception: '.$e->getMessage(),
                'driver' => $driver,
            ], 422);
        }
    }

    /**
     * รัน storage:link ผ่าน Artisan (สำหรับ admin กดปุ่ม fix)
     */
    public function fixSymlink(): JsonResponse
    {
        try {
            $exitCode = Artisan::call('storage:link', ['--force' => true]);
            $output = Artisan::output();

            $linkPath = public_path('storage');
            $exists = file_exists($linkPath) || is_link($linkPath);

            return response()->json([
                'success' => $exists,
                'message' => $exists
                    ? '✅ สร้าง symlink สำเร็จ — local driver พร้อมใช้แล้ว'
                    : '⚠️ รัน storage:link แล้วแต่ symlink ยังไม่เจอ — ลองรันใน terminal: php artisan storage:link',
                'output' => trim($output),
                'exit_code' => $exitCode,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ รัน storage:link ไม่ได้: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger migration command — ย้ายไฟล์ local → cloud
     */
    public function migrate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|in:local,r2,s3,gcs,firebase',
            'to' => 'required|in:local,r2,s3,gcs,firebase|different:from',
            'dry_run' => 'boolean',
        ]);

        try {
            $exitCode = Artisan::call('fortune:migrate-voice-storage', [
                '--from' => $validated['from'],
                '--to' => $validated['to'],
                '--dry-run' => $validated['dry_run'] ?? false,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? '✅ Migrate สำเร็จ — '.$validated['from'].' → '.$validated['to']
                    : '⚠️ Migrate มี error — ดู output',
                'output' => $output,
                'exit_code' => $exitCode,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ Migrate exception: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * สถิติไฟล์ voice (จำนวน + ขนาด — เฉพาะ local)
     */
    public function stats(): JsonResponse
    {
        $settings = FortuneTellingSetting::getSettings();
        $storage = new FortuneVoiceStorageService($settings);

        $stats = $storage->stats();
        $availability = $storage->driverAvailability();

        $countByDisk = \App\Models\FortuneReading::whereNotNull('voice_audio_path')
            ->selectRaw('COALESCE(voice_audio_disk, ?) as disk, COUNT(*) as cnt', ['local'])
            ->groupBy('disk')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->disk => (int) $r->cnt])
            ->toArray();

        return response()->json([
            'success' => true,
            'driver' => $storage->driver(),
            'driver_name' => $storage->driverName($storage->driver()),
            'availability' => $availability,
            'local_stats' => $stats,
            'count_by_disk' => $countByDisk,
        ]);
    }
}
