<?php

namespace App\Services\RiderPay;

use App\Models\FreshMarketSetting;
use App\Services\AiApiKeyPoolService;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ให้ AI "เรียบเรียง" คำแนะนำค่าตอบแทนไรเดอร์เป็นภาษาไทยเป็นกันเอง (ไม่เกิน 3 ประโยค)
 *
 * กติกาเหล็ก: AI ห้ามเปลี่ยนตัวเลข — ตัวเลขทุกตัวในคำตอบต้องอยู่ในชุดตัวเลขที่ระบบส่งไป ไม่งั้นทิ้งแล้วใช้ข้อความจากกฎ
 *
 * - ใช้ AI pool + ผู้ให้บริการเดียวกับตลาดสด (FreshMarketSetting ai_provider/ai_model, AiApiKeyPoolService) ไม่เพิ่ม SDK ใหม่
 * - cache ต่อร้าน + ต่อชุดข้อมูล 6 ชม. (ข้อมูลเปลี่ยน = ข้อความใหม่) · AI ล้ม = จำไว้ 15 นาทีไม่ถามซ้ำ
 * - จำกัด 6 ครั้ง/ร้าน/6 ชม. (กันร้านกดบันทึกรัวจนเผาโควต้า AI) · ล็อกกันยิงซ้อนจากคำขอพร้อมกัน
 * - หมดเวลาเร็ว (services.rider_pay_ai.timeout ≈ 5 วินาที) · ทดลองค่า (preview) ไม่เรียก AI เลย
 */
class RiderPayAdviceWriter
{
    public const CACHE_TTL_SECONDS = 21600;

    public const FAILURE_TTL_SECONDS = 900;

    public const MAX_AI_CALLS_PER_WINDOW = 6;

    public const LOCK_SECONDS = 20;

    public const MAX_HEADLINE_CHARS = 60;

    public const MAX_TEXT_CHARS = 320;

    private const PREFIX = 'rider_pay:advice:';

    /**
     * @param  string  $storeKey  เช่น shop:12 / fresh-market:7
     * @param  array<string, mixed>  $facts  ตัวเลขที่ระบบคำนวณแล้ว (เป็น int/สตริงทศนิยมคงที่/bool เท่านั้น)
     * @param  array{headline: string, text: string}  $rules  ข้อความจากกฎ (ทางสำรอง + ต้นฉบับให้ AI เรียบเรียง)
     * @param  bool  $allowAi  false = ห้ามเรียก AI สด (ใช้ของใน cache ได้)
     * @return array{headline: string, text: string, source: string, generated_at: string}
     */
    public function phrase(string $storeKey, array $facts, array $rules, bool $allowAi): array
    {
        $fallback = [
            'headline' => $rules['headline'],
            'text' => $rules['text'],
            'source' => 'rules',
            'generated_at' => now()->toIso8601String(),
        ];

        // hash จาก JSON ของค่าที่จัดรูปแบบแล้ว (ไม่มี float ดิบ) → ข้อมูลเท่าเดิม = key เดิมทุกเครื่อง
        $hash = md5((string) json_encode([$facts, $rules], JSON_UNESCAPED_UNICODE));
        $cacheKey = self::PREFIX.$storeKey.':'.$hash;

        $cached = $this->cacheGet($cacheKey);
        if (is_array($cached) && isset($cached['headline'], $cached['text'], $cached['generated_at'])) {
            return [
                'headline' => (string) $cached['headline'],
                'text' => (string) $cached['text'],
                'source' => 'ai',
                'generated_at' => (string) $cached['generated_at'],
            ];
        }

        if (! $allowAi || ! (bool) config('services.rider_pay_ai.enabled', true)) {
            return $fallback;
        }

        $failKey = self::PREFIX.'fail:'.$storeKey.':'.$hash;
        $countKey = self::PREFIX.'count:'.$storeKey;
        $lockKey = self::PREFIX.'lock:'.$storeKey;

        try {
            if (Cache::has($failKey) || (int) Cache::get($countKey, 0) >= self::MAX_AI_CALLS_PER_WINDOW) {
                return $fallback;
            }

            // คำขอพร้อมกันของร้านเดียวกัน → ตัวแรกถาม AI ตัวอื่นใช้ข้อความจากกฎไปก่อน
            if (! Cache::add($lockKey, 1, self::LOCK_SECONDS)) {
                return $fallback;
            }

            Cache::add($countKey, 0, self::CACHE_TTL_SECONDS);
            Cache::increment($countKey);
        } catch (\Throwable) {
            return $fallback;
        }

        try {
            $content = $this->ask($facts, $rules);
            $clean = $content !== null ? $this->validate($content, $this->allowedNumbers($facts, $rules)) : null;

            if ($clean === null) {
                $this->cachePut($failKey, 1, self::FAILURE_TTL_SECONDS);

                return $fallback;
            }

            $result = $clean + ['generated_at' => now()->toIso8601String()];
            $this->cachePut($cacheKey, $result, self::CACHE_TTL_SECONDS);

            return $result + ['source' => 'ai'];
        } catch (\Throwable $e) {
            Log::warning('RiderPay: AI เรียบเรียงคำแนะนำไม่สำเร็จ ใช้ข้อความจากกฎ', ['error' => SafeLog::exceptionMessage($e)]);
            $this->cachePut($failKey, 1, self::FAILURE_TTL_SECONDS);

            return $fallback;
        } finally {
            try {
                Cache::forget($lockKey);
            } catch (\Throwable) {
                // ข้าม
            }
        }
    }

    /**
     * ตรวจคำตอบของ AI: JSON {headline, text}, ภาษาไทย, ไม่มีลิงก์/อีโมจิ, ยาวไม่เกินกำหนด, ตัวเลขต้องมาจากระบบเท่านั้น
     *
     * @param  array<string, true>  $allowed  ตัวเลขที่อนุญาต (รูปแบบ normalize แล้ว)
     * @return array{headline: string, text: string}|null null = ใช้ไม่ได้
     */
    public function validate(string $content, array $allowed): ?array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($data) || ! is_string($data['headline'] ?? null) || ! is_string($data['text'] ?? null)) {
            return null;
        }

        $headline = $this->clean($data['headline']);
        $text = $this->clean($data['text']);

        if ($headline === '' || $text === '' || mb_strlen($headline) > self::MAX_HEADLINE_CHARS) {
            return null;
        }

        // ตัดให้เหลือไม่เกิน 3 ประโยค (แยกด้วยจุด/ขึ้นบรรทัด) แล้วจำกัดความยาว
        $parts = preg_split('/(?<=[.!?])\s+|\n+/u', $text) ?: [$text];
        $text = trim(implode(' ', array_slice(array_filter(array_map('trim', $parts)), 0, 3)));
        if (mb_strlen($text) > self::MAX_TEXT_CHARS || $text === '') {
            return null;
        }

        // ต้องเป็นภาษาไทย ไม่มีลิงก์
        if (! preg_match('/\p{Thai}/u', $headline.$text) || preg_match('/https?:|www\.|<|>/i', $headline.$text)) {
            return null;
        }

        // ตัวเลขทุกตัวต้องมาจากระบบ (AI ห้ามคิดเลขเอง)
        preg_match_all('/\d+(?:[.,]\d+)*/', $headline.' '.$text, $matches);
        foreach ($matches[0] as $number) {
            if (! isset($allowed[self::normalizeNumber($number)])) {
                return null;
            }
        }

        return ['headline' => $headline, 'text' => $text];
    }

    /**
     * ชุดตัวเลขที่ AI ใช้ได้ = ทุกตัวเลขในข้อมูล + ข้อความจากกฎ
     *
     * @param  array<string, mixed>  $facts
     * @param  array{headline: string, text: string}  $rules
     * @return array<string, true>
     */
    public function allowedNumbers(array $facts, array $rules): array
    {
        $source = json_encode($facts, JSON_UNESCAPED_UNICODE).' '.$rules['headline'].' '.$rules['text'];
        preg_match_all('/\d+(?:[.,]\d+)*/', (string) $source, $matches);

        $allowed = [];
        foreach ($matches[0] as $number) {
            $allowed[self::normalizeNumber($number)] = true;
        }

        return $allowed;
    }

    /**
     * รูปแบบเดียวของตัวเลข: ตัดคอมมาหลักพัน, ตัดศูนย์ท้ายทศนิยม, ตัดศูนย์นำหน้า ("10.50" → "10.5", "00" → "0")
     */
    public static function normalizeNumber(string $number): string
    {
        // คอมมาตามด้วย 3 หลัก = คั่นหลักพัน
        $number = preg_replace('/,(?=\d{3}(\D|$))/', '', $number) ?? $number;
        $number = str_replace(',', '.', $number);

        if (str_contains($number, '.')) {
            $number = rtrim(rtrim($number, '0'), '.');
        }

        $number = ltrim($number, '0');
        if ($number === '' || str_starts_with($number, '.')) {
            $number = '0'.$number;
        }

        return $number;
    }

    /**
     * ถาม AI ผ่าน pool ของตลาดสด (null = ไม่มีคีย์/ตอบว่าง)
     *
     * @param  array<string, mixed>  $facts
     * @param  array{headline: string, text: string}  $rules
     */
    private function ask(array $facts, array $rules): ?string
    {
        $settings = FreshMarketSetting::getSettings();
        $provider = (string) ($settings->ai_provider ?: 'groq');
        $pool = app(AiApiKeyPoolService::class);
        $key = $pool->acquireKey($provider);

        if (! $key || trim((string) $key->api_key) === '') {
            return null;
        }

        $model = (string) ($key->model ?: ($settings->ai_model ?: 'llama-3.3-70b-versatile'));
        $timeout = max(1.0, (float) config('services.rider_pay_ai.timeout', 5));

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$key->api_key,
                'Content-Type' => 'application/json',
            ])
                ->connectTimeout(min(2.0, $timeout))
                ->timeout($timeout)
                ->post($this->endpoint($provider), [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => "ข้อมูล (JSON):\n".json_encode($facts, JSON_UNESCAPED_UNICODE)
                            ."\n\nคำแนะนำต้นฉบับ:\nหัวข้อ: {$rules['headline']}\nข้อความ: {$rules['text']}"],
                    ],
                    'max_tokens' => 300,
                    'temperature' => 0.4,
                ]);
        } finally {
            $pool->releaseKey($provider, $key->id);
        }

        if (! $response->successful()) {
            // ไม่แนบ body/URL (อาจมีข้อมูลคีย์) — แค่สถานะ
            throw new \RuntimeException('AI HTTP '.$response->status());
        }

        $content = $response->json('choices.0.message.content');

        return is_string($content) && trim($content) !== '' ? $content : null;
    }

    private function systemPrompt(): string
    {
        return 'คุณคือผู้ช่วยเจ้าของร้านบนแพลตฟอร์ม Thai Prompt หน้าที่คือเรียบเรียงคำแนะนำเรื่องโบนัสไรเดอร์ให้อ่านง่าย เป็นกันเอง สุภาพ เป็นภาษาไทย'
            ."\nกติกา:"
            ."\n1) ใช้เฉพาะตัวเลขที่อยู่ในข้อมูลหรือคำแนะนำต้นฉบับ ห้ามคำนวณใหม่ ห้ามปัดเศษ ห้ามเพิ่มตัวเลขอื่น"
            ."\n2) ห้ามเปลี่ยนคำแนะนำ (จำนวนโบนัส โบนัสช่วงเร่งด่วน การส่งฟรี)"
            ."\n3) headline ไม่เกิน 40 ตัวอักษร · text ไม่เกิน 3 ประโยค"
            ."\n4) ห้ามใช้อีโมจิ ห้ามใส่ลิงก์"
            ."\n5) ตอบเป็น JSON อย่างเดียว: {\"headline\":\"...\",\"text\":\"...\"}";
    }

    private function endpoint(string $provider): string
    {
        return match ($provider) {
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'openai' => 'https://api.openai.com/v1/chat/completions',
            default => 'https://api.groq.com/openai/v1/chat/completions',
        };
    }

    /**
     * ตัดแท็ก อีโมจิ และช่องว่างซ้ำ
     */
    private function clean(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u', '', $value) ?? $value;
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable) {
            return null;
        }
    }

    private function cachePut(string $key, mixed $value, int $seconds): void
    {
        try {
            Cache::put($key, $value, $seconds);
        } catch (\Throwable) {
            // ข้าม
        }
    }
}
