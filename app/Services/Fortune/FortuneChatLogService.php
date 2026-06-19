<?php

namespace App\Services\Fortune;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Realtime, ephemeral conversation log — the closest-to-Messenger transcript.
 *
 * The Fortune bot doesn't persist a raw message log, so the warroom transcript
 * could only reconstruct structured bits (cards / Q&A). This captures EVERY
 * inbound + outbound message into Redis as it happens, so the operator sees the
 * live conversation verbatim.
 *
 * Ephemeral by design (saves memory): keys are stamped with the Bangkok date and
 * TTL'd to expire just after the next midnight, so each day's log is auto-cleared
 * at ~00:30. `fortune:chatlog:purge` runs at 00:05 as an explicit backstop.
 *
 * Fail-safe: every Redis touch is guarded. If redis-server is unreachable the
 * service silently no-ops (one debug log) and the transcript falls back to the
 * structured reconstruction — the bot is NEVER affected.
 */
class FortuneChatLogService
{
    public const TZ = 'Asia/Bangkok';
    private const KEY_PREFIX = 'fortune:chatlog';
    private const MAX_PER_CONV = 400;   // cap memory per customer/day
    private const MAX_TEXT = 4000;      // clamp very long messages

    /** Availability memo so a down redis doesn't get hammered. Re-checked every
     *  RECHECK_SECONDS so a long-lived worker (queue/Octane) recovers when redis
     *  comes back, instead of pinning 'down' until restart. */
    private static ?bool $available = null;
    private static int $checkedAt = 0;
    private const RECHECK_SECONDS = 60;

    /**
     * Append one message to a customer's running conversation log.
     *
     * @param string $platform 'facebook' | 'line'
     * @param string $userId   platform user id (FB PSID / LINE userId)
     * @param string $role     'user' | 'bot' | 'admin'
     */
    public function record(string $platform, string $userId, string $role, ?string $text, array $meta = []): void
    {
        $text = trim((string) $text);
        if ($userId === '' || $text === '' || ! $this->available()) {
            return;
        }

        try {
            $key = $this->key($platform, $userId);
            $entry = json_encode(array_filter([
                'role' => in_array($role, ['user', 'bot', 'admin'], true) ? $role : 'user',
                'text' => mb_substr($text, 0, self::MAX_TEXT),
                'ts' => Carbon::now(self::TZ)->toIso8601String(),
                'ai' => $meta['ai'] ?? null,
                'by' => $meta['by'] ?? null,
                'image_url' => $meta['image_url'] ?? null,
            ], static fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE);

            $ttl = $this->ttlSeconds();
            $conn = Redis::connection();
            $conn->rpush($key, $entry);
            $conn->ltrim($key, -self::MAX_PER_CONV, -1);
            $conn->expire($key, $ttl);

            // Day index → lets the purge command delete a day's keys without SCAN.
            $idx = $this->indexKey(Carbon::now(self::TZ)->toDateString());
            $conn->sadd($idx, $key);
            $conn->expire($idx, $ttl);
        } catch (\Throwable $e) {
            self::$available = false; // stop retrying for the rest of this request
            Log::debug('FortuneChatLog: record skipped', ['err' => $e->getMessage()]);
        }
    }

    /**
     * Today's full conversation for a customer, oldest-first.
     *
     * @return array<int,array{role:string,text:string,ts:?string,ai:?string,by:?string,image_url:?string}>
     */
    public function getForCustomer(string $platform, string $userId): array
    {
        if ($userId === '' || ! $this->available()) {
            return [];
        }
        try {
            $raw = Redis::connection()->lrange($this->key($platform, $userId), 0, -1);
            $out = [];
            foreach ($raw as $r) {
                $d = json_decode((string) $r, true);
                if (is_array($d) && isset($d['text'])) {
                    $out[] = $d;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Delete every conversation key for the given Bangkok date (default:
     * yesterday). Returns the number of keys removed. Used by the midnight
     * purge command; TTL is the safety net if this never runs.
     */
    public function purgeDate(?string $date = null): int
    {
        $date ??= Carbon::yesterday(self::TZ)->toDateString();
        if (! $this->available()) {
            return 0;
        }
        try {
            $conn = Redis::connection();
            $idx = $this->indexKey($date);
            $keys = $conn->smembers($idx);
            $deleted = 0;
            foreach ($keys as $k) {
                $deleted += (int) $conn->del($k);
            }
            $conn->del($idx);

            return $deleted;
        } catch (\Throwable $e) {
            Log::warning('FortuneChatLog: purge failed', ['date' => $date, 'err' => $e->getMessage()]);

            return 0;
        }
    }

    private function available(): bool
    {
        $now = time();
        if (self::$available !== null && ($now - self::$checkedAt) < self::RECHECK_SECONDS) {
            return self::$available;
        }
        self::$checkedAt = $now;
        try {
            Redis::connection()->ping();

            return self::$available = true;
        } catch (\Throwable $e) {
            Log::debug('FortuneChatLog: redis unavailable — feature inactive', ['err' => $e->getMessage()]);

            return self::$available = false;
        }
    }

    private function key(string $platform, string $userId): string
    {
        $p = $platform === 'line' ? 'line' : 'facebook';
        $date = Carbon::now(self::TZ)->toDateString();

        return self::KEY_PREFIX . ":{$date}:{$p}:{$userId}";
    }

    private function indexKey(string $date): string
    {
        return self::KEY_PREFIX . ":index:{$date}";
    }

    /** Seconds until ~00:30 tomorrow (Bangkok) → each day's log clears at midnight. */
    private function ttlSeconds(): int
    {
        $expireAt = Carbon::tomorrow(self::TZ)->addMinutes(30);

        return max(60, $expireAt->diffInSeconds(Carbon::now(self::TZ)));
    }
}
