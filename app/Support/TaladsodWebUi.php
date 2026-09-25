<?php

namespace App\Support;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

/**
 * ตัวช่วยแสดงผลหน้าเว็บตลาดสด (Theme V4) — ป้ายสถานะ, โทนสี, ไอคอน, ขั้นตอนออเดอร์, ลิงก์แบนเนอร์
 *
 * ทุกเมธอดเป็นฟังก์ชันบริสุทธิ์ (ไม่แตะฐานข้อมูล) เพื่อให้ view ใช้ซ้ำได้และทดสอบได้ง่าย
 * โทนสีคืนค่าเป็นชื่อ (ok|warn|bad|info|gold|muted) → ใช้กับคลาส ts-tone-{tone}
 */
class TaladsodWebUi
{
    /** ชื่อเดือนย่อภาษาไทย */
    private const MONTHS = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    /** ชื่อวันย่อภาษาไทย (0 = อาทิตย์) */
    private const WEEKDAYS = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

    // ===== ตัวเลข / วันที่ =====

    public static function money(float|int|string|null $amount, bool $forceDecimals = false): string
    {
        return RiderWebUi::money($amount, $forceDecimals);
    }

    public static function date(CarbonInterface|string|null $value, bool $withTime = true): string
    {
        return RiderWebUi::date($value, $withTime);
    }

    public static function shortDate(CarbonInterface|string|null $value, bool $withTime = true): string
    {
        return RiderWebUi::shortDate($value, $withTime);
    }

    public static function time(CarbonInterface|string|null $value): string
    {
        return RiderWebUi::time($value);
    }

    public static function distance(float|int|string|null $km): string
    {
        return RiderWebUi::distance($km);
    }

    /**
     * ป้ายวันแบบสั้นสำหรับกราฟ เช่น "จ. 26"
     */
    public static function shortDay(CarbonInterface|string|null $value): string
    {
        $date = RiderWebUi::carbon($value);

        return $date ? self::WEEKDAYS[$date->dayOfWeek].' '.$date->day : '—';
    }

    /**
     * วันที่ไทยแบบไม่มีปี + ชื่อเดือนย่อ เช่น "26 ก.ย."
     */
    public static function dayMonth(CarbonInterface|string|null $value): string
    {
        $date = RiderWebUi::carbon($value);

        return $date ? $date->day.' '.self::MONTHS[$date->month] : '—';
    }

    /**
     * อักษรแรกของชื่อ (ใช้ในวงกลมแทนรูป)
     */
    public static function initial(?string $name, string $fallback = 'ร'): string
    {
        $name = trim((string) $name);

        return $name !== '' ? mb_substr($name, 0, 1) : $fallback;
    }

    // ===== ออเดอร์ =====

    /**
     * โทนสีของสถานะออเดอร์
     */
    public static function orderTone(?string $status): string
    {
        return match ($status) {
            FreshMarketOrder::STATUS_PENDING => 'warn',
            FreshMarketOrder::STATUS_ACCEPTED, FreshMarketOrder::STATUS_PREPARING => 'info',
            FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERING => 'gold',
            FreshMarketOrder::STATUS_DELIVERED, FreshMarketOrder::STATUS_COMPLETED => 'ok',
            FreshMarketOrder::STATUS_CANCELLED, FreshMarketOrder::STATUS_DELIVERY_FAILED => 'bad',
            default => 'muted',
        };
    }

    /**
     * ไอคอน Font Awesome ของสถานะออเดอร์
     */
    public static function orderIcon(?string $status): string
    {
        return match ($status) {
            FreshMarketOrder::STATUS_PENDING => 'fa-hourglass-half',
            FreshMarketOrder::STATUS_ACCEPTED => 'fa-circle-check',
            FreshMarketOrder::STATUS_PREPARING => 'fa-fire-burner',
            FreshMarketOrder::STATUS_READY => 'fa-box',
            FreshMarketOrder::STATUS_DELIVERING => 'fa-motorcycle',
            FreshMarketOrder::STATUS_DELIVERED => 'fa-house-circle-check',
            FreshMarketOrder::STATUS_COMPLETED => 'fa-face-smile',
            FreshMarketOrder::STATUS_CANCELLED => 'fa-ban',
            FreshMarketOrder::STATUS_DELIVERY_FAILED => 'fa-triangle-exclamation',
            default => 'fa-receipt',
        };
    }

    /**
     * โทน + ไอคอนของปุ่ม action ฝั่งร้าน/ผู้ซื้อ
     *
     * @return array{tone: string, icon: string}
     */
    public static function actionStyle(string $action): array
    {
        return match ($action) {
            'accept' => ['tone' => 'ok', 'icon' => 'fa-check'],
            'prepare' => ['tone' => 'info', 'icon' => 'fa-fire-burner'],
            'ready' => ['tone' => 'gold', 'icon' => 'fa-box'],
            'handover' => ['tone' => 'ok', 'icon' => 'fa-hand-holding-heart'],
            'confirm' => ['tone' => 'ok', 'icon' => 'fa-circle-check'],
            'cancel' => ['tone' => 'bad', 'icon' => 'fa-xmark'],
            default => ['tone' => 'muted', 'icon' => 'fa-circle'],
        };
    }

    /**
     * ป้ายวิธีรับสินค้า
     */
    public static function deliveryLabel(?string $type): string
    {
        return $type === 'rider' ? 'ไรเดอร์ส่งถึงบ้าน' : 'รับเองที่ร้าน';
    }

    /**
     * ป้ายวิธีชำระเงินแบบสั้น
     */
    public static function paymentShortLabel(?string $method): string
    {
        return match ($method) {
            'wallet', 'escrow' => 'จ่ายด้วยกระเป๋าเงิน',
            'cod' => 'เก็บเงินปลายทาง',
            'transfer' => 'โอนเงิน',
            default => (string) $method,
        };
    }

    /**
     * ขั้นตอนออเดอร์สำหรับไทม์ไลน์ (ส่งด้วยไรเดอร์มีขั้น "กำลังจัดส่ง") — state = done | now | todo | stopped
     *
     * @return array<int, array{key: string, label: string, icon: string, state: string, at: ?string}>
     */
    public static function orderSteps(FreshMarketOrder $order): array
    {
        $rider = $order->delivery_type === 'rider';

        $steps = [
            ['key' => FreshMarketOrder::STATUS_PENDING, 'label' => 'สั่งซื้อแล้ว', 'icon' => 'fa-receipt', 'at' => $order->created_at],
            ['key' => FreshMarketOrder::STATUS_ACCEPTED, 'label' => 'ร้านรับออเดอร์', 'icon' => 'fa-circle-check', 'at' => $order->accepted_at],
            ['key' => FreshMarketOrder::STATUS_PREPARING, 'label' => 'กำลังจัดเตรียม', 'icon' => 'fa-fire-burner', 'at' => $order->getAttribute('preparing_at')],
            ['key' => FreshMarketOrder::STATUS_READY, 'label' => $rider ? 'พร้อมส่ง รอไรเดอร์' : 'พร้อมให้มารับ', 'icon' => 'fa-box', 'at' => $order->getAttribute('ready_at')],
        ];

        if ($rider) {
            $steps[] = ['key' => FreshMarketOrder::STATUS_DELIVERING, 'label' => 'ไรเดอร์กำลังไปส่ง', 'icon' => 'fa-motorcycle', 'at' => $order->rider_picked_up_at];
        }

        $steps[] = ['key' => FreshMarketOrder::STATUS_DELIVERED, 'label' => $rider ? 'ส่งถึงแล้ว' : 'รับสินค้าแล้ว', 'icon' => 'fa-house-circle-check', 'at' => $order->delivered_at];
        $steps[] = ['key' => FreshMarketOrder::STATUS_COMPLETED, 'label' => 'เสร็จสิ้น', 'icon' => 'fa-face-smile', 'at' => $order->completed_at];

        $keys = array_column($steps, 'key');
        $status = (string) $order->order_status;
        $stopped = in_array($status, [FreshMarketOrder::STATUS_CANCELLED, FreshMarketOrder::STATUS_DELIVERY_FAILED], true);

        // จุดที่หยุด: ยกเลิก = ขั้นล่าสุดที่มีเวลา / จัดส่งไม่สำเร็จ = ขั้นส่งของ
        if ($status === FreshMarketOrder::STATUS_DELIVERY_FAILED) {
            $currentIndex = array_search($rider ? FreshMarketOrder::STATUS_DELIVERING : FreshMarketOrder::STATUS_READY, $keys, true);
        } elseif ($stopped) {
            $currentIndex = 0;
            foreach ($steps as $i => $step) {
                if ($step['at']) {
                    $currentIndex = $i;
                }
            }
        } else {
            $currentIndex = array_search($status, $keys, true);
        }

        $currentIndex = $currentIndex === false ? 0 : (int) $currentIndex;

        foreach ($steps as $i => &$step) {
            $state = match (true) {
                $i < $currentIndex => 'done',
                $i === $currentIndex && $stopped => 'stopped',
                $i === $currentIndex && $status === FreshMarketOrder::STATUS_COMPLETED => 'done',
                $i === $currentIndex => 'now',
                default => 'todo',
            };
            $step['state'] = $state;
            $step['at'] = $step['at'] ? RiderWebUi::shortDate($step['at']) : null;
        }
        unset($step);

        return $steps;
    }

    // ===== สินค้า =====

    /**
     * ป้ายสถานะสินค้าฝั่งร้าน
     *
     * @return array{label: string, tone: string}
     */
    public static function listingStatus(FreshMarketListing $listing): array
    {
        return match (true) {
            $listing->status === 'suspended' => ['label' => 'ถูกระงับโดยแอดมิน', 'tone' => 'bad'],
            $listing->status === 'sold_out' => ['label' => 'ของหมด', 'tone' => 'warn'],
            $listing->status === 'active' && $listing->is_available => ['label' => 'เปิดขาย', 'tone' => 'ok'],
            $listing->status === 'active' => ['label' => 'ซ่อนไว้', 'tone' => 'muted'],
            $listing->status === 'draft' => ['label' => 'ฉบับร่าง', 'tone' => 'muted'],
            default => ['label' => (string) $listing->status, 'tone' => 'muted'],
        };
    }

    // ===== แบนเนอร์ =====

    /**
     * ลิงก์ปลายทางของแบนเนอร์บนเว็บ (แบนเนอร์เดียวกับแอป)
     *
     * cta_type url → ลิงก์นั้น (เฉพาะ http(s) หรือ path ในเว็บเรา) · screen → หน้าเว็บที่ตรงกับหน้าจอในแอป
     *
     * @param  array<string, mixed>  $banner  MobileBanner::toAppApi()
     */
    public static function bannerHref(array $banner): string
    {
        $fallback = self::route('taladsod.home', url('/taladsod'));
        $type = $banner['cta_type'] ?? null;
        $value = trim((string) ($banner['cta_value'] ?? ''));

        if ($type === 'url') {
            $url = trim((string) ($banner['cta_url'] ?? $value));

            if (preg_match('#^https?://#i', $url) === 1) {
                return $url;
            }

            return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? url($url) : $fallback;
        }

        if ($type !== 'screen' || $value === '') {
            return $fallback;
        }

        $screen = strtolower(trim($value, '/'));
        $user = auth()->user();

        return match (true) {
            $screen === 'taladsod' || str_starts_with($screen, 'taladsod/') => url('/'.$screen),
            $screen === 'rider' || str_starts_with($screen, 'rider') => $user
                ? self::route('user.rider.dashboard', $fallback)
                : self::route('taladsod.landing.rider', $fallback),
            $screen === 'merchant' || str_starts_with($screen, 'merchant') => self::route('taladsod.landing.seller', $fallback),
            $screen === 'shopping' || $screen === 'shop' || $screen === '(tabs)/shop' => self::route('storefront.index', $fallback),
            default => $fallback,
        };
    }

    private static function route(string $name, string $fallback): string
    {
        return Route::has($name) ? route($name) : $fallback;
    }
}
