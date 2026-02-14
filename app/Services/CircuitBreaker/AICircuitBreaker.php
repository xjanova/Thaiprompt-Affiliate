<?php

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Circuit Breaker
 *
 * ป้องกันระบบล่มเมื่อ AI Provider มีปัญหา
 * ใช้ Circuit Breaker Pattern เพื่อ:
 * - ตรวจจับความล้มเหลวของ AI Provider
 * - Auto-fallback ไป provider อื่น
 * - Self-healing เมื่อ provider กลับมาทำงานได้
 *
 * States:
 * - CLOSED (ปกติ): อนุญาตให้ส่ง requests ไปยัง provider
 * - OPEN (ล่ม): บล็อกไม่ให้ส่ง requests (ใช้ fallback)
 * - HALF_OPEN (ทดสอบ): ทดสอบว่า provider กลับมาหรือยัง
 *
 * @example
 * $breaker = new AICircuitBreaker('gemini');
 * if ($breaker->isAvailable()) {
 *     try {
 *         $result = $aiService->call();
 *         $breaker->recordSuccess();
 *     } catch (Exception $e) {
 *         $breaker->recordFailure();
 *         throw $e;
 *     }
 * } else {
 *     // Use fallback provider
 * }
 */
class AICircuitBreaker
{
    /**
     * States ของ Circuit Breaker
     */
    public const STATE_CLOSED = 'closed';

    public const STATE_OPEN = 'open';

    public const STATE_HALF_OPEN = 'half_open';

    /**
     * ชื่อ AI Provider
     */
    protected string $provider;

    /**
     * จำนวนครั้งที่ล้มเหลวก่อนจะเปิด circuit
     */
    protected int $failureThreshold;

    /**
     * เวลา (วินาที) ที่ circuit จะเปิดก่อนเข้าสู่ half-open
     */
    protected int $timeout;

    /**
     * จำนวนครั้งที่จะทดสอบใน half-open state
     */
    protected int $successThreshold;

    /**
     * Cache key prefix
     */
    protected string $cacheKeyPrefix = 'circuit_breaker:ai:';

    /**
     * สร้าง Circuit Breaker instance
     *
     * @param  string  $provider  ชื่อ AI provider (gemini, groq, qwen, openrouter)
     * @param  int  $failureThreshold  จำนวนครั้งที่ล้มเหลวก่อนเปิด circuit (default: 5)
     * @param  int  $timeout  เวลา (วินาที) ที่เปิด circuit (default: 60)
     * @param  int  $successThreshold  จำนวนความสำเร็จที่ต้องการใน half-open (default: 2)
     */
    public function __construct(
        string $provider,
        int $failureThreshold = 5,
        int $timeout = 60,
        int $successThreshold = 2
    ) {
        $this->provider = $provider;
        $this->failureThreshold = $failureThreshold;
        $this->timeout = $timeout;
        $this->successThreshold = $successThreshold;
    }

    /**
     * ตรวจสอบว่า provider พร้อมใช้งานหรือไม่
     */
    public function isAvailable(): bool
    {
        $state = $this->getState();

        switch ($state) {
            case self::STATE_CLOSED:
                // ปกติ - อนุญาตให้ใช้งาน
                return true;

            case self::STATE_OPEN:
                // ล่ม - ตรวจสอบว่าถึงเวลาทดสอบหรือยัง
                if ($this->shouldAttemptReset()) {
                    $this->setState(self::STATE_HALF_OPEN);
                    Log::info("Circuit Breaker: {$this->provider} เข้าสู่ HALF-OPEN state");

                    return true;
                }

                return false;

            case self::STATE_HALF_OPEN:
                // กำลังทดสอบ - อนุญาตให้ลอง
                return true;

            default:
                return true;
        }
    }

    /**
     * บันทึกความสำเร็จ
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successes = $this->incrementSuccessCount();

            if ($successes >= $this->successThreshold) {
                // กลับมาทำงานได้แล้ว - ปิด circuit
                $this->setState(self::STATE_CLOSED);
                $this->resetCounters();
                Log::info("Circuit Breaker: {$this->provider} กลับสู่ CLOSED state");
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count เมื่อสำเร็จ
            $this->resetFailureCount();
        }
    }

    /**
     * บันทึกความล้มเหลว
     */
    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            // ยังไม่กลับมา - เปิด circuit อีกครั้ง
            $this->setState(self::STATE_OPEN);
            $this->setLastFailureTime();
            Log::warning("Circuit Breaker: {$this->provider} กลับสู่ OPEN state");
        } elseif ($state === self::STATE_CLOSED) {
            $failures = $this->incrementFailureCount();

            if ($failures >= $this->failureThreshold) {
                // ล้มเหลวเกินกำหนด - เปิด circuit
                $this->setState(self::STATE_OPEN);
                $this->setLastFailureTime();
                Log::error("Circuit Breaker: {$this->provider} เปิด circuit (failures: {$failures})");
            }
        }
    }

    /**
     * ดึง state ปัจจุบัน
     */
    public function getState(): string
    {
        return Cache::get($this->cacheKey('state'), self::STATE_CLOSED);
    }

    /**
     * ตั้งค่า state
     */
    protected function setState(string $state): void
    {
        Cache::put($this->cacheKey('state'), $state, now()->addMinutes(60));
    }

    /**
     * ตรวจสอบว่าควรทดสอบรีเซ็ตหรือไม่
     */
    protected function shouldAttemptReset(): bool
    {
        $lastFailureTime = Cache::get($this->cacheKey('last_failure_time'), 0);

        return (time() - $lastFailureTime) >= $this->timeout;
    }

    /**
     * เพิ่มจำนวนครั้งที่ล้มเหลว
     */
    protected function incrementFailureCount(): int
    {
        $key = $this->cacheKey('failures');
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes(10));

        return $count;
    }

    /**
     * รีเซ็ตจำนวนครั้งที่ล้มเหลว
     */
    protected function resetFailureCount(): void
    {
        Cache::forget($this->cacheKey('failures'));
    }

    /**
     * เพิ่มจำนวนความสำเร็จ (ใน half-open state)
     */
    protected function incrementSuccessCount(): int
    {
        $key = $this->cacheKey('successes');
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes(5));

        return $count;
    }

    /**
     * บันทึกเวลาที่ล้มเหลวล่าสุด
     */
    protected function setLastFailureTime(): void
    {
        Cache::put($this->cacheKey('last_failure_time'), time(), now()->addMinutes(60));
    }

    /**
     * รีเซ็ต counters ทั้งหมด
     */
    protected function resetCounters(): void
    {
        Cache::forget($this->cacheKey('failures'));
        Cache::forget($this->cacheKey('successes'));
        Cache::forget($this->cacheKey('last_failure_time'));
    }

    /**
     * สร้าง cache key
     */
    protected function cacheKey(string $suffix): string
    {
        return $this->cacheKeyPrefix.$this->provider.':'.$suffix;
    }

    /**
     * ดึงสถิติของ circuit breaker
     */
    public function getStats(): array
    {
        return [
            'provider' => $this->provider,
            'state' => $this->getState(),
            'failures' => Cache::get($this->cacheKey('failures'), 0),
            'successes' => Cache::get($this->cacheKey('successes'), 0),
            'last_failure_time' => Cache::get($this->cacheKey('last_failure_time'), 0),
            'threshold' => $this->failureThreshold,
            'timeout' => $this->timeout,
        ];
    }

    /**
     * รีเซ็ต circuit breaker (สำหรับ testing หรือ manual intervention)
     */
    public function reset(): void
    {
        $this->setState(self::STATE_CLOSED);
        $this->resetCounters();
        Log::info("Circuit Breaker: {$this->provider} ถูกรีเซ็ตแบบ manual");
    }

    /**
     * ดึง fallback providers
     */
    public static function getFallbackProviders(string $currentProvider): array
    {
        $providers = ['gemini', 'groq', 'qwen', 'openrouter'];

        // ลบ provider ปัจจุบันออก
        $fallbacks = array_filter($providers, fn ($p) => $p !== $currentProvider);

        return array_values($fallbacks);
    }
}
