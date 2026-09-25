<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * ตัวช่วยแสดงผลหน้าเว็บไรเดอร์ (/user/rider/*)
 *
 * - วันที่ภาษาไทย ปี พ.ศ. — ไม่ใช้ macro thaidate() เพราะ macro นั้นแทนที่เลขปี 2 หลัก ("26")
 *   ทับวัน/นาทีที่บังเอิญเป็นเลขเดียวกัน (วันที่ 26 ปี 2026 จะกลายเป็น 69)
 * - จำนวนเงินบาท, โทนสีตามสถานะงาน, ไอคอน, ลิงก์แผนที่, ขั้นตอนงาน, กราฟแท่งรายได้รายวัน
 *
 * ฟังก์ชันล้วน ไม่แตะฐานข้อมูล — เรียกจาก Blade ได้ตรง ๆ
 */
final class RiderWebUi
{
    /**
     * ชื่อเดือนย่อภาษาไทย
     */
    private const MONTHS = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    /**
     * ชื่อวันย่อ (0 = อาทิตย์ ตาม Carbon::dayOfWeek)
     */
    private const DAYS = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

    /**
     * ขั้นตอนงานที่ไรเดอร์เห็น (ใช้วาดแถบความคืบหน้า)
     */
    private const STEPS = [
        'accepted' => 'รับงาน',
        'picking_up' => 'ไปรับของ',
        'picked_up' => 'รับของแล้ว',
        'delivering' => 'กำลังนำส่ง',
        'completed' => 'ส่งสำเร็จ',
    ];

    /**
     * แปลงค่าเวลา (Carbon / ISO-8601 / null) เป็น Carbon ตามเขตเวลาของแอป
     */
    public static function carbon(CarbonInterface|string|null $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = $value instanceof CarbonInterface ? Carbon::instance($value) : Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $date->copy()->setTimezone(config('app.timezone') ?: 'Asia/Bangkok');
    }

    /**
     * วันที่ไทย เช่น "26 ก.ย. 2569 14:05" (ไม่มีค่า → "—")
     */
    public static function date(CarbonInterface|string|null $value, bool $withTime = true): string
    {
        $date = self::carbon($value);

        if (! $date) {
            return '—';
        }

        $text = $date->day.' '.self::MONTHS[$date->month].' '.($date->year + 543);

        return $withTime ? $text.' '.$date->format('H:i') : $text;
    }

    /**
     * วันที่แบบสั้นไม่มีปี เช่น "26 ก.ย. 14:05" — ใช้ในรายการงาน
     */
    public static function shortDate(CarbonInterface|string|null $value, bool $withTime = true): string
    {
        $date = self::carbon($value);

        if (! $date) {
            return '—';
        }

        $text = $date->day.' '.self::MONTHS[$date->month];

        return $withTime ? $text.' '.$date->format('H:i') : $text;
    }

    /**
     * เวลาอย่างเดียว เช่น "14:05"
     */
    public static function time(CarbonInterface|string|null $value): string
    {
        $date = self::carbon($value);

        return $date ? $date->format('H:i') : '—';
    }

    /**
     * จำนวนเงิน (ไม่มีเศษสตางค์ → ไม่แสดงทศนิยม) เช่น 1,250 / 42.50
     */
    public static function money(float|int|string|null $amount, bool $forceDecimals = false): string
    {
        $value = round((float) $amount, 2);
        $decimals = ($forceDecimals || abs($value - round($value)) >= 0.005) ? 2 : 0;

        return number_format($value, $decimals);
    }

    /**
     * ระยะทาง เช่น "3.4 กม." / "800 ม."
     */
    public static function distance(float|int|string|null $km): string
    {
        if ($km === null || $km === '') {
            return '—';
        }

        $value = (float) $km;

        return $value < 1
            ? number_format(max(0, $value * 1000), 0).' ม.'
            : number_format($value, 1).' กม.';
    }

    /**
     * โทนสีของสถานะงาน → ใช้กับคลาส rd-tone-{tone}
     */
    public static function jobTone(?string $status): string
    {
        return match ($status) {
            'completed', 'delivered' => 'ok',
            'failed' => 'bad',
            'cancelled' => 'muted',
            'pending' => 'warn',
            default => 'info',
        };
    }

    /**
     * ไอคอน Font Awesome ตามประเภทงาน
     */
    public static function jobIcon(?string $jobType): string
    {
        return match ($jobType) {
            'fresh_market' => 'fa-basket-shopping',
            'food' => 'fa-bowl-food',
            'shop_delivery' => 'fa-bag-shopping',
            'document' => 'fa-file-lines',
            'service' => 'fa-screwdriver-wrench',
            default => 'fa-box',
        };
    }

    /**
     * ไอคอนยานพาหนะ
     */
    public static function vehicleIcon(?string $vehicleType): string
    {
        return match ($vehicleType) {
            'car' => 'fa-car-side',
            'bicycle' => 'fa-bicycle',
            'walk' => 'fa-person-walking',
            default => 'fa-motorcycle',
        };
    }

    /**
     * ไอคอนเอกสาร
     */
    public static function documentIcon(?string $type): string
    {
        return match ($type) {
            'id_card' => 'fa-id-card',
            'driver_license' => 'fa-id-badge',
            'vehicle_registration' => 'fa-file-contract',
            'profile' => 'fa-user',
            default => 'fa-file',
        };
    }

    /**
     * ลิงก์เปิดตำแหน่งใน Google Maps (พิกัดก่อน ถ้าไม่มีใช้ข้อความที่อยู่)
     */
    public static function mapUrl(float|int|string|null $lat, float|int|string|null $lng, ?string $query = null): ?string
    {
        if (self::validPoint($lat, $lng)) {
            return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(((float) $lat).','.((float) $lng));
        }

        $query = trim((string) $query);

        return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null;
    }

    /**
     * ลิงก์นำทางไปยังจุดหมายใน Google Maps
     */
    public static function directionsUrl(float|int|string|null $lat, float|int|string|null $lng, ?string $query = null): ?string
    {
        if (self::validPoint($lat, $lng)) {
            return 'https://www.google.com/maps/dir/?api=1&travelmode=driving&destination='.rawurlencode(((float) $lat).','.((float) $lng));
        }

        $query = trim((string) $query);

        return $query !== '' ? 'https://www.google.com/maps/dir/?api=1&travelmode=driving&destination='.rawurlencode($query) : null;
    }

    /**
     * พิกัดใช้ได้จริง (ไม่ใช่ 0,0 และอยู่ในช่วง)
     */
    public static function validPoint(float|int|string|null $lat, float|int|string|null $lng): bool
    {
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        return abs($lat) <= 90 && abs($lng) <= 180 && ! (abs($lat) < 0.000001 && abs($lng) < 0.000001);
    }

    /**
     * ขั้นตอนงานพร้อมสถานะ done|current|todo (งานยกเลิก/ไม่สำเร็จ = ขั้นที่ค้างเป็น stopped)
     *
     * @param  array<string, mixed>  $timeline  timeline จาก RiderJob::toApiDetail()
     * @return array<int, array{key: string, label: string, state: string, at: ?string}>
     */
    public static function progressSteps(?string $status, array $timeline = []): array
    {
        $order = array_keys(self::STEPS);
        $stopped = in_array($status, ['cancelled', 'failed'], true);
        $position = match ($status) {
            'delivered', 'completed' => count($order) - 1,
            'pending' => -1,
            default => array_search($status, $order, true),
        };

        // งานยกเลิก/ส่งไม่สำเร็จ: ประเมินขั้นที่ทำถึงจาก timeline
        if ($stopped) {
            $position = ! empty($timeline['picked_up_at']) ? 2 : (! empty($timeline['accepted_at']) ? 0 : -1);
        }

        $times = [
            'accepted' => $timeline['accepted_at'] ?? null,
            'picking_up' => null,
            'picked_up' => $timeline['picked_up_at'] ?? null,
            'delivering' => null,
            'completed' => $timeline['completed_at'] ?? ($timeline['delivered_at'] ?? null),
        ];

        $steps = [];
        foreach ($order as $index => $key) {
            $state = 'todo';
            if ($position !== false && $index <= $position) {
                $state = ($index === $position && ! in_array($status, ['completed', 'delivered'], true) && ! $stopped) ? 'current' : 'done';
            } elseif ($stopped && $position !== false && $index === $position + 1) {
                $state = 'stopped';
            }

            $steps[] = [
                'key' => $key,
                'label' => self::STEPS[$key],
                'state' => $state,
                'at' => $times[$key],
            ];
        }

        return $steps;
    }

    /**
     * กราฟแท่งรายได้รายวัน (สัปดาห์นี้ = 7 วัน, เดือนนี้ = วันที่ 1 ถึงวันนี้) — วันที่ไม่มีงานเป็น 0
     *
     * @param  array<int, array{date: string, jobs: int, earnings: float}>  $daily  จาก RiderEarningService::summary()['daily']
     * @param  string|null  $from  summary['from'] (ISO-8601) — จุดเริ่มของช่วง
     * @return array<int, array{date: string, label: string, sub: string, jobs: int, earnings: float, pct: float, is_today: bool}>
     */
    public static function dailyBars(array $daily, string $period, ?string $from): array
    {
        if (! in_array($period, ['week', 'month'], true)) {
            return [];
        }

        $start = self::carbon($from)?->startOfDay() ?? ($period === 'week' ? now()->startOfWeek() : now()->startOfMonth());
        $end = $period === 'week' ? $start->copy()->addDays(6) : now()->startOfDay();

        $byDate = [];
        foreach ($daily as $row) {
            if (isset($row['date'])) {
                $byDate[(string) $row['date']] = $row;
            }
        }

        $bars = [];
        $today = now()->toDateString();
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $row = $byDate[$key] ?? null;
            $bars[] = [
                'date' => $key,
                'label' => $period === 'week' ? self::DAYS[$day->dayOfWeek] : (string) $day->day,
                'sub' => $day->day.' '.self::MONTHS[$day->month],
                'jobs' => (int) ($row['jobs'] ?? 0),
                'earnings' => round((float) ($row['earnings'] ?? 0), 2),
                'pct' => 0.0,
                'is_today' => $key === $today,
            ];

            if (count($bars) > 31) {
                break;
            }
        }

        $max = max([0.0, ...array_column($bars, 'earnings')]);
        foreach ($bars as &$bar) {
            // แท่งที่มีรายได้สูงอย่างน้อย 6% ให้ยังมองเห็น / ไม่มีรายได้ = แท่งเตี้ยสุด 2%
            $bar['pct'] = $max > 0 && $bar['earnings'] > 0 ? max(6.0, round($bar['earnings'] / $max * 100, 1)) : 2.0;
        }
        unset($bar);

        return $bars;
    }
}
