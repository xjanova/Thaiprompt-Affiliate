<?php

namespace App\Services\Fortune;

use Illuminate\Support\Facades\Cache;

/**
 * 📦 (2026-05-20 Phase 4) Message Buffer — รวมข้อความที่ลูกค้าพิมพ์ติด ๆ กัน
 *
 * User spec 2026-05-20: "ลูกค้าอาจพิมพ์ยังไม่ครบ ให้ใช้ช่วงเวลาดีเลย์นี้คอยดูว่า
 *   เขาพิมพ์อะไรมา แล้วค่อยนำมารวมแล้วคุยต่อ ให้มีผลทั้งหมด"
 *
 * Pattern: Debounce
 *   - ลูกค้าพิมพ์ "ขอบคุณ"          (t=0)   → append + dispatch job delay N sec
 *   - ลูกค้าพิมพ์ "อยากถามต่อ"     (t=2s)  → append + dispatch job delay N sec
 *   - ลูกค้าพิมพ์ "เรื่องคนรัก"     (t=5s)  → append + dispatch job delay N sec
 *   - (silence)                     (t=8s)  → job แรก fire — เห็นว่า last_at < N → skip
 *   - (silence)                     (t=10s) → job ที่สอง fire — เห็นว่า last_at ≥ N → flush + AI
 *
 * Storage: Laravel Cache (Redis/file) per scope+userId
 * TTL: 5 นาที — กัน buffer ค้างถ้า job หาย
 *
 * Scope strings:
 *   • 'celtic_q'       — Celtic Q2+ chat (Phase 4a)
 *   • 'deep_qa'        — Deep 39 Q&A    (Phase 4b — future)
 *   • 'chat'           — Chat ปกติ       (Phase 4c — future)
 */
class MessageBuffer
{
    protected const KEY_PREFIX = 'msgbuf';

    protected const TTL_SECONDS = 300; // 5 นาที

    /**
     * Append message → return buffer stats
     *
     * @return array{count: int, first_at: float, last_at: float}
     */
    public function append(string $scope, string $userId, string $text): array
    {
        $key = $this->key($scope, $userId);
        $now = microtime(true);

        $buf = Cache::get($key, []);
        $buf[] = [
            'text' => $text,
            'at' => $now,
        ];

        Cache::put($key, $buf, self::TTL_SECONDS);

        return [
            'count' => count($buf),
            'first_at' => $buf[0]['at'],
            'last_at' => $now,
        ];
    }

    /**
     * Peek buffer (no flush)
     *
     * @return array  รายการ messages [{text, at}, ...]
     */
    public function peek(string $scope, string $userId): array
    {
        return Cache::get($this->key($scope, $userId), []);
    }

    /**
     * Flush buffer → return combined messages + clear cache
     *
     * @return array{messages: array, count: int, combined: string, first_at: ?float, last_at: ?float}
     */
    public function flush(string $scope, string $userId): array
    {
        $key = $this->key($scope, $userId);
        $buf = Cache::get($key, []);

        Cache::forget($key);

        if (empty($buf)) {
            return [
                'messages' => [],
                'count' => 0,
                'combined' => '',
                'first_at' => null,
                'last_at' => null,
            ];
        }

        $texts = array_map(fn ($m) => $m['text'] ?? '', $buf);
        // รวมด้วย newline — ให้ AI เห็นเป็น message แยกบรรทัด
        $combined = implode("\n", array_filter($texts, fn ($t) => trim($t) !== ''));

        return [
            'messages' => $buf,
            'count' => count($buf),
            'combined' => $combined,
            'first_at' => $buf[0]['at'],
            'last_at' => end($buf)['at'],
        ];
    }

    /**
     * Manual clear (admin override / error recovery)
     */
    public function clear(string $scope, string $userId): void
    {
        Cache::forget($this->key($scope, $userId));
    }

    /**
     * เช็คว่า buffer "พร้อม flush" หรือยัง — ใช้ใน Job เพื่อตัดสินใจ
     *
     * @param  int  $windowSeconds  ระยะเวลา debounce
     * @param  bool  $fromFirstMessage  โหมดการนับเวลา:
     *                                   • true  = นับจาก "ข้อความแรก" (fixed window / เพดาน) —
     *                                             เก็บได้สูงสุด windowSeconds นับจากข้อความแรกแล้ว
     *                                             ตอบเสมอ. การพิมพ์ใหม่ "ไม่ reset" นาฬิกา → กันไม่ให้
     *                                             รวมเลยเกินค่าที่ตั้ง (owner spec 2026-06-22 "เก็บแค่ N วิ")
     *                                   • false = นับจาก "ข้อความล่าสุด" (silence-based debounce, legacy)
     * @return bool  true ถ้าพร้อม flush
     */
    public function isReadyToFlush(string $scope, string $userId, int $windowSeconds, bool $fromFirstMessage = false): bool
    {
        $buf = $this->peek($scope, $userId);
        if (empty($buf)) {
            return false;
        }

        $now = microtime(true);

        // ✅ (2026-06-22) Fixed-window mode — วัดจาก "ข้อความแรก"
        //   เดิม (silence-based) วัดจากข้อความล่าสุด → ลูกค้าพิมพ์เรื่อย ๆ ห่าง < window
        //   = นาฬิกา reset ทุกครั้ง → รอรวมนานเกิน window จริง (เคส 60s บานเป็น 2-3 นาที)
        //   ใหม่: เก็บครบ window จากข้อความแรกแล้วตอบทีเดียว — เพดานตายตัว ไม่ reset
        if ($fromFirstMessage) {
            $firstAt = $buf[0]['at'] ?? $now;

            return ($now - $firstAt) >= $windowSeconds;
        }

        $lastAt = end($buf)['at'] ?? 0;

        return ($now - $lastAt) >= $windowSeconds;
    }

    protected function key(string $scope, string $userId): string
    {
        return self::KEY_PREFIX.':'.$scope.':'.$userId;
    }
}
