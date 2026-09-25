<?php

namespace App\Services;

use App\Models\MobileBanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * แบนเนอร์แคมเปญในแอป (ตาราง mobile_banners)
 *
 * - รายการสำหรับแอป: cache 5 นาทีต่อ placement (เก็บ "ผู้สมัคร" ที่เปิดอยู่และยังไม่หมดเวลา)
 *   แล้วกรองช่วงเวลา/กลุ่มผู้ชมทุก request → แบนเนอร์ที่เริ่ม/หมดเวลาระหว่าง cache ก็ถูกต้อง
 * - แอดมินแก้ไขเมื่อไหร่ → bumpVersion() เปลี่ยน key cache ทั้งหมดทันที
 * - นับคลิก/การแสดงผล กันยิงซ้ำด้วย cache ต่อ IP (ไม่ให้ตัวเลขพองจากการกดรัว)
 */
class AppBannerService
{
    public const CACHE_TTL_SECONDS = 300;

    public const VERSION_KEY = 'app_banners:version';

    /** นับคลิกซ้ำจาก IP เดิมต่อแบนเนอร์ได้ครั้งเดียวในช่วงนี้ (วินาที) */
    public const CLICK_DEDUPE_SECONDS = 60;

    /** นับการแสดงผลซ้ำจาก IP เดิมต่อแบนเนอร์ได้ครั้งเดียวในช่วงนี้ (วินาที) */
    public const IMPRESSION_DEDUPE_SECONDS = 600;

    /**
     * แบนเนอร์ที่แสดงอยู่ตอนนี้ของตำแหน่งหนึ่ง
     *
     * @param  string|null  $audience  null = ไม่กรองกลุ่มผู้ชม / มีค่า = เฉพาะ all + กลุ่มนั้น
     * @return array<int, array<string, mixed>> รูปแบบ MobileBanner::toAppApi()
     */
    public function activeFor(string $placement, ?string $audience = null, ?\DateTimeInterface $at = null): array
    {
        $now = $at !== null ? Carbon::instance($at) : now();
        $nowTs = $now->getTimestamp();

        $candidates = $this->candidates($placement);

        $result = [];
        foreach ($candidates as $row) {
            if ($row['_starts_ts'] !== null && $row['_starts_ts'] > $nowTs) {
                continue;
            }
            if ($row['_ends_ts'] !== null && $row['_ends_ts'] < $nowTs) {
                continue;
            }
            if ($audience !== null && $row['audience'] !== 'all' && $row['audience'] !== $audience) {
                continue;
            }

            unset($row['_starts_ts'], $row['_ends_ts']);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * แบนเนอร์ที่เปิดอยู่และยังไม่หมดเวลา (รวมที่ตั้งเวลาเริ่มไว้ล่วงหน้า) — cache 5 นาที
     *
     * @return array<int, array<string, mixed>>
     */
    public function candidates(string $placement): array
    {
        $key = 'app_banners:v'.$this->version().':'.$placement;

        try {
            return Cache::remember($key, self::CACHE_TTL_SECONDS, fn () => $this->loadCandidates($placement));
        } catch (\Throwable $e) {
            Log::warning('AppBannerService: cache unavailable, reading database directly', ['error' => $e->getMessage()]);

            return $this->loadCandidates($placement);
        }
    }

    /**
     * เวอร์ชันของ cache (เปลี่ยนทุกครั้งที่แอดมินแก้แบนเนอร์)
     */
    public function version(): string
    {
        try {
            return (string) Cache::rememberForever(self::VERSION_KEY, fn () => '1');
        } catch (\Throwable) {
            return '1';
        }
    }

    /**
     * ล้าง cache แบนเนอร์ทั้งหมด (เปลี่ยน key)
     */
    public function bumpVersion(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, (string) now()->getTimestampMs().random_int(10, 99));
        } catch (\Throwable $e) {
            Log::warning('AppBannerService: bump cache version failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * นับคลิก 1 ครั้ง (IP เดิมกดซ้ำภายใน 60 วินาทีไม่นับ)
     *
     * @return bool นับเพิ่มหรือไม่
     */
    public function recordClick(MobileBanner $banner, ?string $ip): bool
    {
        if (! $this->firstHit('click', (int) $banner->id, $ip, self::CLICK_DEDUPE_SECONDS)) {
            return false;
        }

        MobileBanner::whereKey($banner->id)->increment('click_count');

        return true;
    }

    /**
     * นับการแสดงผลของหลายแบนเนอร์ (IP เดิมซ้ำภายใน 10 นาทีไม่นับ)
     *
     * @param  array<int, mixed>  $ids
     * @return int จำนวนแบนเนอร์ที่นับเพิ่ม
     */
    public function recordImpressions(array $ids, ?string $ip): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

        if ($ids === []) {
            return 0;
        }

        $existing = MobileBanner::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $counted = [];
        foreach ($existing as $id) {
            if ($this->firstHit('view', $id, $ip, self::IMPRESSION_DEDUPE_SECONDS)) {
                $counted[] = $id;
            }
        }

        if ($counted !== []) {
            MobileBanner::whereIn('id', $counted)->increment('view_count');
        }

        return count($counted);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCandidates(string $placement): array
    {
        return MobileBanner::query()
            ->where('position', $placement)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(function (MobileBanner $banner) {
                $row = $banner->toAppApi();
                $row['_starts_ts'] = $banner->start_date?->getTimestamp();
                $row['_ends_ts'] = $banner->end_date?->getTimestamp();

                return $row;
            })
            ->values()
            ->all();
    }

    /**
     * ครั้งแรกของ IP นี้กับแบนเนอร์นี้ในช่วงเวลาที่กำหนดหรือไม่
     */
    private function firstHit(string $kind, int $bannerId, ?string $ip, int $seconds): bool
    {
        try {
            return Cache::add('app_banners:'.$kind.':'.$bannerId.':'.sha1((string) $ip), 1, $seconds);
        } catch (\Throwable) {
            // cache ใช้ไม่ได้ → นับไปเลย (ตัวเลขสถิติ ไม่ใช่เงิน)
            return true;
        }
    }
}
