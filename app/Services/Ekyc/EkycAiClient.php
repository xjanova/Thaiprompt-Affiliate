<?php

namespace App\Services\Ekyc;

use App\Support\SafeLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ตัวเรียกบริการ AI eKYC ของเราเอง (docker `ekyc-ai` ที่ config ekyc.ai_url)
 *
 * - ทุกคำขอแนบ header X-Ekyc-Key = config ekyc.ai_key · หมดเวลาตาม config ekyc.timeout
 * - ห้าม log รูปภาพหรือข้อความจากบัตร (OCR) เด็ดขาด — log ได้แค่เวลา · สถานะ HTTP · คะแนน · รหัสเหตุผล
 * - เรียกไม่ติด / หมดเวลา / ตอบผิดรูป → คืน available = false (ผู้เรียกส่งเคสให้แอดมินตรวจ ไม่ใช่ error 500)
 *
 * ชื่อฟิลด์ multipart ของ /v1/face/verify ตามสัญญา: card_image · frames[] · labels[]
 *
 * @example
 * $result = app(EkycAiClient::class)->idCard($jpegBytes);
 * if ($result['available']) { $fields = $result['data']['fields']; }
 */
class EkycAiClient
{
    /** ชื่อฟิลด์รูปเฟรมใบหน้า (ซ้ำได้หลายครั้ง) */
    public const FRAME_FIELD = 'frames[]';

    /** ชื่อฟิลด์ป้ายคำสั่งของแต่ละเฟรม (ซ้ำได้หลายครั้ง เรียงตรงกับ frames[]) */
    public const LABEL_FIELD = 'labels[]';

    /**
     * ตั้งค่าครบพอจะเรียกบริการ AI ได้หรือไม่ (มีทั้ง url และกุญแจ)
     */
    public function configured(): bool
    {
        return trim((string) config('ekyc.ai_url', '')) !== '' && trim((string) config('ekyc.ai_key', '')) !== '';
    }

    /**
     * อ่านบัตรประชาชน: POST /v1/id-card (multipart image)
     *
     * @param  string  $jpeg  ไบต์รูปบัตร (JPEG ที่ normalize แล้ว)
     * @return array{available: bool, data: array<string, mixed>, error: string|null, ms: int}
     */
    public function idCard(string $jpeg): array
    {
        return $this->send('/v1/id-card', function (PendingRequest $request, string $url) use ($jpeg) {
            return $request
                ->attach('image', $jpeg, 'card.jpg', ['Content-Type' => 'image/jpeg'])
                ->post($url);
        });
    }

    /**
     * ตรวจใบหน้า + liveness + เทียบกับรูปบนบัตร: POST /v1/face/verify
     *
     * @param  string  $cardJpeg  รูปบัตรใบเดียวกับที่อ่าน OCR
     * @param  array<int, string>  $frames  ไบต์ JPEG ของแต่ละเฟรม
     * @param  array<int, string>  $labels  ป้ายของแต่ละเฟรม (neutral, blink, turn_left, turn_right, smile, nod)
     * @return array{available: bool, data: array<string, mixed>, error: string|null, ms: int}
     */
    public function verifyFace(string $cardJpeg, array $frames, array $labels): array
    {
        return $this->send('/v1/face/verify', function (PendingRequest $request, string $url) use ($cardJpeg, $frames, $labels) {
            $request->attach('card_image', $cardJpeg, 'card.jpg', ['Content-Type' => 'image/jpeg']);

            foreach (array_values($frames) as $i => $bytes) {
                $request->attach(self::FRAME_FIELD, $bytes, 'frame'.$i.'.jpg', ['Content-Type' => 'image/jpeg']);
            }

            // ฟิลด์ชื่อซ้ำต้องส่งเป็นรายการ part ทีละตัว (ส่งเป็น key => value จะทับกันเหลือตัวเดียว)
            $parts = [];
            foreach (array_values($labels) as $label) {
                $parts[] = ['name' => self::LABEL_FIELD, 'contents' => (string) $label];
            }

            return $request->post($url, $parts);
        });
    }

    /**
     * สถานะบริการ AI: GET /health (ใช้ตอนตรวจหลัง deploy — คำสั่ง ekyc:health)
     *
     * @return array{available: bool, data: array<string, mixed>, error: string|null, ms: int}
     */
    public function health(): array
    {
        return $this->send('/health', fn (PendingRequest $request, string $url) => $request->get($url), 5);
    }

    /**
     * ส่งคำขอ + แปลงผลเป็นรูปแบบเดียว (ไม่โยน exception)
     *
     * @param  callable(PendingRequest, string): Response  $build
     * @return array{available: bool, data: array<string, mixed>, error: string|null, ms: int}
     */
    private function send(string $path, callable $build, ?int $timeout = null): array
    {
        $started = microtime(true);

        if (! $this->configured()) {
            Log::warning('eKYC AI: not configured', ['endpoint' => $path]);

            return $this->unavailable('NOT_CONFIGURED', $started);
        }

        $url = rtrim((string) config('ekyc.ai_url'), '/').$path;
        $seconds = max(1, $timeout ?? (int) config('ekyc.timeout', 25));

        try {
            $request = Http::withHeaders([
                'X-Ekyc-Key' => (string) config('ekyc.ai_key'),
                'Accept' => 'application/json',
            ])->timeout($seconds)->connectTimeout(min(5, $seconds));

            /** @var Response $response */
            $response = $build($request, $url);
        } catch (\Throwable $e) {
            Log::warning('eKYC AI: request failed', [
                'endpoint' => $path,
                'ms' => $this->elapsed($started),
                'error' => class_basename($e),
                'message' => mb_substr(SafeLog::exceptionMessage($e), 0, 200),
            ]);

            return $this->unavailable('UNREACHABLE', $started);
        }

        if (! $response->successful()) {
            Log::warning('eKYC AI: bad status', [
                'endpoint' => $path,
                'status' => $response->status(),
                'ms' => $this->elapsed($started),
            ]);

            return $this->unavailable('HTTP_'.$response->status(), $started);
        }

        $json = $response->json();
        if (! is_array($json)) {
            Log::warning('eKYC AI: response is not JSON', ['endpoint' => $path, 'ms' => $this->elapsed($started)]);

            return $this->unavailable('BAD_RESPONSE', $started);
        }

        // log เฉพาะตัวเลข/รหัส — ไม่มีชื่อ เลขบัตร วันเกิด หรือรูป
        Log::info('eKYC AI: ok', array_filter([
            'endpoint' => $path,
            'ms' => $this->elapsed($started),
            'ok' => $json['ok'] ?? null,
            'reasons' => array_values(array_filter((array) ($json['reasons'] ?? []), 'is_string')),
            'ocr_confidence' => is_numeric($json['ocr_confidence'] ?? null) ? (float) $json['ocr_confidence'] : null,
            'card_real' => is_numeric($json['card_real_score'] ?? null) ? (float) $json['card_real_score'] : null,
            'cosine' => is_numeric($json['match']['cosine'] ?? null) ? (float) $json['match']['cosine'] : null,
            'liveness' => is_numeric($json['liveness']['score'] ?? null) ? (float) $json['liveness']['score'] : null,
            'real' => is_numeric($json['anti_spoof']['real_score'] ?? null) ? (float) $json['anti_spoof']['real_score'] : null,
            'model_version' => is_string($json['model_version'] ?? null) ? mb_substr($json['model_version'], 0, 60) : null,
        ], fn ($v) => $v !== null && $v !== []));

        return ['available' => true, 'data' => $json, 'error' => null, 'ms' => $this->elapsed($started)];
    }

    /**
     * @return array{available: bool, data: array<string, mixed>, error: string, ms: int}
     */
    private function unavailable(string $error, float $started): array
    {
        return ['available' => false, 'data' => [], 'error' => $error, 'ms' => $this->elapsed($started)];
    }

    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
