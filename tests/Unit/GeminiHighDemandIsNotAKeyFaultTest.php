<?php

namespace Tests\Unit;

use App\Services\FortuneAIService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 🌙 (2026-11-04) Gemini ล่มทั้งรุ่น ("high demand") ต้องไม่นับเป็นความผิดของคีย์
 *
 * เดิมนับ strike → 3 ครั้งติด critical → prod คีย์ฟรี 25/26/29 ถูกปิดในชั่วโมงเดียว
 * ทั้งที่คีย์ดี — เลยดูเหมือน "Gemini ฟรีหลุดบ่อย"
 */
class GeminiHighDemandIsNotAKeyFaultTest extends TestCase
{
    private function detect(string $message): array
    {
        $ai = (new \ReflectionClass(FortuneAIService::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(FortuneAIService::class, 'detectRateLimit');

        return $method->invoke($ai, $message);
    }

    public static function temporary(): array
    {
        return [
            'gemini high demand' => ['Gemini Chat (history) Error: This model is currently experiencing high demand. Spikes in demand are usually temporary. Please try again later.'],
            'overloaded' => ['The model is overloaded. Please try again later.'],
            'service unavailable' => ['503 Service Unavailable'],
            'rate limit เดิม' => ['429 RESOURCE_EXHAUSTED'],
        ];
    }

    #[DataProvider('temporary')]
    public function test_temporary_errors_do_not_count_as_strikes(string $message): void
    {
        [$isRateLimit] = $this->detect($message);

        $this->assertTrue($isRateLimit);
    }

    public function test_a_real_key_fault_still_counts(): void
    {
        [$isRateLimit] = $this->detect('Your project has been denied access. Please contact support.');

        $this->assertFalse($isRateLimit, 'คีย์โดนแบนจริงยังต้องนับ strike');
    }
}
