<?php

namespace App\Services;

use App\Models\AiApiKey;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🩺 AI API Key Health Probe Service (2026-05-07)
 *
 * จุดประสงค์:
 *   - Probe key ที่ถูก mark critical → ถ้ายังใช้ได้ → unban อัตโนมัติ
 *   - แต่ละ provider ใช้ minimal API call ที่ถูกที่สุด (ไม่กิน tokens)
 *   - exponential backoff ป้องกัน probe ถี่เกินไปกับ key พังจริง
 *
 * Backoff schedule (recheck_failure_count → next attempt):
 *   0 fails → 1 hour
 *   1 fail  → 4 hours
 *   2 fails → 12 hours
 *   3 fails → 24 hours
 *   4+ fails → 72 hours (cap)
 *
 * เรียกผ่าน:
 *   - Scheduled command: ai:recheck-critical (ทุกชั่วโมง)
 *   - Admin manual: POST /admin/ai-api-keys/{id}/recheck-now
 */
class AiApiKeyHealthProbeService
{
    /**
     * Backoff hours by failure count
     *
     * @var array<int, int>
     */
    protected const BACKOFF_HOURS = [1, 4, 12, 24, 72];

    /**
     * 🩺 Probe key — ส่ง minimal API call เพื่อตรวจว่ายังใช้ได้
     *
     * @return bool true = key ยังใช้ได้ / false = ยัง fail
     */
    public function probe(AiApiKey $key): bool
    {
        $request = $this->buildProbeRequest($key);

        if (! $request) {
            Log::warning('AI Health Probe: ไม่รองรับ provider นี้', [
                'key_id' => $key->id,
                'provider' => $key->provider,
            ]);

            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($request['headers'])
                ->get($request['url']);

            // 200-299 = ok, 401/403 = key invalid, 429 = rate-limited (still alive!), 5xx = provider down
            $status = $response->status();

            if ($status >= 200 && $status < 300) {
                Log::info('AI Health Probe: key OK', [
                    'key_id' => $key->id,
                    'provider' => $key->provider,
                    'status' => $status,
                ]);

                return true;
            }

            // 429 = rate-limited but key valid → ถือว่ายังใช้ได้ (จะ retry ใน production normally)
            if ($status === 429) {
                Log::info('AI Health Probe: key rate-limited (still alive)', [
                    'key_id' => $key->id,
                    'provider' => $key->provider,
                ]);

                return true;
            }

            Log::info('AI Health Probe: key still failing', [
                'key_id' => $key->id,
                'provider' => $key->provider,
                'status' => $status,
                'body' => mb_substr($response->body(), 0, 200),
            ]);

            return false;
        } catch (Exception $e) {
            Log::warning('AI Health Probe: exception ระหว่าง probe', [
                'key_id' => $key->id,
                'provider' => $key->provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🩺 Recheck critical key — probe + unban หรือ schedule next attempt
     *
     * @return bool true = recovered (unbanned) / false = still bad
     */
    public function recheckCritical(AiApiKey $key): bool
    {
        if (! $key->is_critical) {
            // ไม่ใช่ critical — ไม่ต้องทำ
            return true;
        }

        $success = $this->probe($key);

        $key->update(['last_recheck_at' => now()]);

        if ($success) {
            // 🟢 Recover! — unban + log + reset counters
            $key->update([
                'is_critical' => false,
                'is_active' => true,
                'consecutive_errors' => 0,
                'error_check_attempts' => 0,
                'recheck_failure_count' => 0,
                'next_recheck_at' => null,
                'auto_recovered_at' => now(),
                'last_error' => null,
                'last_error_at' => null,
                'disabled_until' => null,
            ]);

            $key->usageLogs()->create([
                'model' => $key->model,
                'is_success' => true,
                'error_message' => '[AUTO_RECOVERED] probe success after critical lock',
            ]);

            Log::info('🟢 AI Health Probe: key auto-recovered', [
                'key_id' => $key->id,
                'key_name' => $key->name,
                'provider' => $key->provider,
            ]);

            // 🔔 ส่ง LINE alert ให้ admin (best-effort)
            $this->notifyAdminOfRecovery($key);

            return true;
        }

        // ❌ ยัง fail — เพิ่ม failure count + schedule next attempt
        $newFailCount = ($key->recheck_failure_count ?? 0) + 1;
        $backoffHours = self::BACKOFF_HOURS[min($newFailCount - 1, count(self::BACKOFF_HOURS) - 1)];

        $key->update([
            'recheck_failure_count' => $newFailCount,
            'next_recheck_at' => now()->addHours($backoffHours),
        ]);

        Log::info('AI Health Probe: schedule next recheck', [
            'key_id' => $key->id,
            'failure_count' => $newFailCount,
            'next_recheck_in_hours' => $backoffHours,
        ]);

        return false;
    }

    /**
     * สร้าง probe request payload ตาม provider
     *
     * @return array{url: string, headers: array}|null
     */
    protected function buildProbeRequest(AiApiKey $key): ?array
    {
        $apiKey = $key->api_key;
        $baseUrl = rtrim($key->resolveBaseUrl() ?? '', '/');

        if (empty($apiKey) || empty($baseUrl)) {
            return null;
        }

        // 🎯 ใช้ /models endpoint สำหรับ OpenAI-compatible providers
        //    Gemini มี endpoint แตกต่าง: /models?key=...
        //    Anthropic ไม่มี GET /models — ต้อง POST /messages (ข้าม — admin ตรวจเอง)
        return match ($key->provider) {
            'openai', 'grok', 'groq', 'qwen', 'openrouter', 'deepseek', 'typhoon', 'xiaomi' => [
                'url' => "{$baseUrl}/models",
                'headers' => [
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ],
            ],
            'gemini' => [
                'url' => "{$baseUrl}/models?key={$apiKey}",
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
            default => null,
        };
    }

    /**
     * 🔔 แจ้ง admin ผ่าน LINE alert เมื่อ key recover
     */
    protected function notifyAdminOfRecovery(AiApiKey $key): void
    {
        try {
            // 🛡️ ใช้ FQCN ชัดเจน — ไม่พึ่ง use statement
            $alertClass = LineAlertService::class;
            if (! class_exists($alertClass)) {
                return;
            }

            $alertService = app($alertClass);
            if (! method_exists($alertService, 'alertSystemError')) {
                return;
            }

            // 🛡️ (2026-05-07 review) alertSystemError signature: (string $error, array $context = [])
            //   เดิม: pass string เป็น 2nd arg → TypeError → catch swallow → admin ไม่ได้รับแจ้ง
            //   ใหม่: pass array ตามถูก signature
            $alertService->alertSystemError('🟢 AI Key Auto-Recovered', [
                'key_id' => $key->id,
                'name' => $key->name,
                'provider' => $key->provider,
                'recovered_after_attempts' => $key->recheck_failure_count ?? 0,
                'note' => 'Key กลับมาใช้งานปกติแล้ว ระบบ enable ให้อัตโนมัติ',
            ]);
        } catch (\Throwable $e) {
            // best-effort — ไม่ throw
            Log::debug('Notify admin of recovery failed: '.$e->getMessage());
        }
    }
}
