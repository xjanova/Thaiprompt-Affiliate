<?php

namespace App\Services\Pricing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * คำแนะนำการตั้งส่วนแบ่งรายได้ของ "แพลตฟอร์ม" (หน้าแอดมิน ส่วนแบ่งรายได้ & GP)
 *
 * แนวคิดเดียวกับ StrategyAdvisor ของผู้ขาย แต่มองจากฝั่งแพลตฟอร์ม:
 *  - ช่วงเปิดตัวคง GP 0% เพื่อดึงร้านค้า → เริ่มเก็บ 8–15% เมื่อมีออเดอร์สำเร็จถึงเป้า
 *  - ไรเดอร์ควรได้ ≥ 80% ของค่าส่งเพื่อดึงไรเดอร์เข้าระบบ
 *  - ค่าแนะนำ (เฉพาะเว็บ) ควรจ่ายจากส่วน GP ของแพลตฟอร์ม ไม่ใช่หักจากราคาผู้ขาย
 *
 * คลาสนี้ไม่แตะเงินจริง — คืนข้อความภาษาไทยให้หน้าแอดมินแสดงเท่านั้น
 */
class PlatformShareAdvisor
{
    /** ออเดอร์สำเร็จ (อีคอมเมิร์ซ + ตลาดสด) ที่ควรมีก่อนเริ่มเก็บ GP */
    public const GP_START_ORDER_MILESTONE = 300;

    /** ช่วง GP ที่แนะนำหลังหมดโปรฯ เปิดตัว (%) */
    public const RECOMMENDED_GP_MIN = 8.0;

    public const RECOMMENDED_GP_MAX = 15.0;

    /** ส่วนแบ่งไรเดอร์ขั้นต่ำที่แนะนำ (% ของค่าส่ง) */
    public const RECOMMENDED_RIDER_SHARE = 80.0;

    /** GP ตลาดสดสูงสุดที่แนะนำ (%) — ของสดกำไรต่อชิ้นต่ำ */
    public const RECOMMENDED_FRESH_GP_MAX = 5.0;

    /** อัตรา VAT ปัจจุบันของไทย (%) */
    public const THAI_VAT_RATE = 7.0;

    /**
     * ตัวเลขประกอบการแนะนำจากฐานข้อมูล (อ่านไม่ได้ = 0)
     *
     * @return array{completed_orders: int, shop_orders: int, fresh_orders: int, shops: int, fresh_sellers: int, riders: int}
     */
    public function stats(): array
    {
        $count = function (callable $query): int {
            try {
                return (int) $query();
            } catch (\Throwable $e) {
                Log::warning('PlatformShareAdvisor: stats query failed', ['error' => $e->getMessage()]);

                return 0;
            }
        };

        $shopOrders = $count(fn () => DB::table('orders')->whereNull('deleted_at')->whereIn('status', ['delivered', 'completed'])->count());
        $freshOrders = $count(fn () => DB::table('fresh_market_orders')->whereNull('deleted_at')->where('order_status', 'completed')->count());

        return [
            'completed_orders' => $shopOrders + $freshOrders,
            'shop_orders' => $shopOrders,
            'fresh_orders' => $freshOrders,
            'shops' => $count(fn () => DB::table('vendor_stores')->whereNull('deleted_at')->where('status', 'active')->where('is_active', true)->count()),
            'fresh_sellers' => $count(fn () => DB::table('fresh_market_sellers')->whereNull('deleted_at')->where('is_active', true)->count()),
            'riders' => $count(fn () => DB::table('riders')->whereNull('deleted_at')->where('status', 'approved')->count()),
        ];
    }

    /**
     * คำแนะนำจากค่าตั้ง (ค่าที่บันทึกแล้วหรือค่าร่างในฟอร์ม)
     *
     * @param  array{gp_free: bool, gp_free_until: ?string, default_gp_rate: float, min_gp_rate: float, fresh_market_gp_rate: float, referral_pool_percent: float, vat_rate: float, rider_share_percent: float, mlm_enabled?: bool}  $s
     * @param  array<string, int>  $stats  จาก stats()
     * @return array{items: array<int, array{level: string, icon: string, title: string, message: string}>, milestone: array{target: int, completed: int, percent: float, reached: bool}, promo_active: bool, promo_ends_at: ?string}
     */
    public function advise(array $s, array $stats, ?\DateTimeInterface $at = null): array
    {
        $now = $at !== null ? Carbon::instance($at) : now();
        $items = [];

        $defaultGp = (float) $s['default_gp_rate'];
        $minGp = (float) $s['min_gp_rate'];
        $effectiveAfterPromo = max($defaultGp, $minGp);
        $freshGp = (float) $s['fresh_market_gp_rate'];
        $pool = (float) $s['referral_pool_percent'];
        $share = (float) $s['rider_share_percent'];
        $vat = (float) $s['vat_rate'];
        $mlmEnabled = (bool) ($s['mlm_enabled'] ?? false);

        $promoEnd = $this->parseEnd($s['gp_free_until'] ?? null);
        $promoOn = (bool) $s['gp_free'];
        $promoActive = $promoOn && ($promoEnd === null || $now->lessThanOrEqualTo($promoEnd));

        $completed = (int) ($stats['completed_orders'] ?? 0);
        $target = self::GP_START_ORDER_MILESTONE;
        $milestone = [
            'target' => $target,
            'completed' => $completed,
            'percent' => round(min(100, $completed / max(1, $target) * 100), 1),
            'reached' => $completed >= $target,
        ];

        $shopsTotal = (int) ($stats['shops'] ?? 0) + (int) ($stats['fresh_sellers'] ?? 0);

        // ----- GP อีคอมเมิร์ซ / โปรฯ เปิดตัว -----
        if ($promoActive) {
            $items[] = $this->item('good', 'fa-gift', 'คง GP 0% ช่วงเปิดตัว',
                'ตอนนี้ไม่เก็บค่า GP ทุกร้าน ช่วยดึงร้านเล็ก รถเข็น ตลาดนัดเข้าระบบ (มีร้านค้าแล้ว '.number_format($shopsTotal).' ร้าน)');

            if ($milestone['reached']) {
                $items[] = $this->item('info', 'fa-flag-checkered', 'ถึงเป้าออเดอร์แล้ว เริ่มวางแผนเก็บ GP ได้',
                    'มีออเดอร์สำเร็จ '.number_format($completed).' ออเดอร์ (เป้า '.number_format($target).') — แนะนำเริ่มเก็บ GP '
                    .$this->fmt(self::RECOMMENDED_GP_MIN).'–'.$this->fmt(self::RECOMMENDED_GP_MAX).'% โดยตั้งวันสิ้นสุดโปรฯ และแจ้งร้านค้าล่วงหน้าอย่างน้อย 30 วัน');
            } else {
                $items[] = $this->item('info', 'fa-route', 'แผนเริ่มเก็บ GP',
                    'เมื่อมีออเดอร์สำเร็จครบ '.number_format($target).' ออเดอร์ (ตอนนี้ '.number_format($completed).') ค่อยเริ่มเก็บ GP '
                    .$this->fmt(self::RECOMMENDED_GP_MIN).'–'.$this->fmt(self::RECOMMENDED_GP_MAX).'% และแจ้งร้านค้าล่วงหน้าอย่างน้อย 30 วัน');
            }

            if ($promoEnd === null) {
                $items[] = $this->item('info', 'fa-calendar', 'ยังไม่ได้กำหนดวันสิ้นสุดโปรฯ',
                    'โปรฯ จะฟรีไปเรื่อยๆ จนกว่าจะปิดเอง ถ้าจะเริ่มเก็บ GP ให้ตั้งวันสิ้นสุดล่วงหน้า ระบบจะเปลี่ยนเป็นอัตรามาตรฐานเองเมื่อพ้นวันนั้น');
            } else {
                $daysLeft = (int) ceil($now->diffInDays($promoEnd, false));
                if ($daysLeft <= 14) {
                    $items[] = $this->item('warn', 'fa-hourglass-half', 'โปรฯ GP ฟรีใกล้หมดแล้ว',
                        'เหลืออีก '.max(0, $daysLeft).' วัน (ถึง '.$promoEnd->format('d/m/Y').') หลังจากนั้นเก็บ GP '
                        .$this->fmt($effectiveAfterPromo).'% — แจ้งร้านค้าให้ทราบล่วงหน้า');
                }
            }

            if ($effectiveAfterPromo <= 0) {
                $items[] = $this->item('warn', 'fa-triangle-exclamation', 'อัตรา GP หลังหมดโปรฯ ยังเป็น 0%',
                    'ตั้ง "อัตรา GP มาตรฐาน" ที่จะใช้หลังเปิดตัวไว้ก่อน (แนะนำ '
                    .$this->fmt(self::RECOMMENDED_GP_MIN).'–'.$this->fmt(self::RECOMMENDED_GP_MAX).'%) ไม่งั้นเมื่อปิดโปรฯ แพลตฟอร์มจะยังไม่มีรายได้จาก GP');
            }
        } else {
            if ($promoOn && $promoEnd !== null && $now->greaterThan($promoEnd)) {
                $items[] = $this->item('warn', 'fa-calendar-xmark', 'โปรฯ GP ฟรีหมดอายุแล้ว',
                    'สิ้นสุดเมื่อ '.$promoEnd->format('d/m/Y').' ตอนนี้ระบบเก็บ GP '.$this->fmt($effectiveAfterPromo).'% ตามอัตรามาตรฐาน/แพ็กเกจร้าน');
            }

            if ($effectiveAfterPromo < self::RECOMMENDED_GP_MIN) {
                $items[] = $this->item('info', 'fa-arrow-trend-down', 'GP ต่ำกว่าช่วงที่แนะนำ',
                    'GP '.$this->fmt($effectiveAfterPromo).'% ต่ำกว่า '.$this->fmt(self::RECOMMENDED_GP_MIN).'% รายได้แพลตฟอร์มอาจไม่พอค่าระบบ ค่าการตลาด และค่าชำระเงิน');
            } elseif ($effectiveAfterPromo > self::RECOMMENDED_GP_MAX) {
                $items[] = $this->item('warn', 'fa-arrow-trend-up', 'GP สูงกว่าช่วงที่แนะนำ',
                    'GP '.$this->fmt($effectiveAfterPromo).'% สูงกว่า '.$this->fmt(self::RECOMMENDED_GP_MAX).'% ร้านเล็กอาจขึ้นราคาหรือย้ายไปขายช่องทางอื่น');
            } else {
                $items[] = $this->item('good', 'fa-circle-check', 'GP อยู่ในช่วงที่แนะนำ',
                    'GP '.$this->fmt($effectiveAfterPromo).'% อยู่ในช่วง '.$this->fmt(self::RECOMMENDED_GP_MIN).'–'.$this->fmt(self::RECOMMENDED_GP_MAX).'% ร้านค้ายังมีกำไรและแพลตฟอร์มมีรายได้');
            }
        }

        // ----- GP ตลาดสด -----
        if (! $promoActive && $freshGp > self::RECOMMENDED_FRESH_GP_MAX) {
            $items[] = $this->item('warn', 'fa-carrot', 'GP ตลาดสดค่อนข้างสูง',
                'ของสด/อาหารกำไรต่อชิ้นต่ำ แนะนำ GP ตลาดสด 0–'.$this->fmt(self::RECOMMENDED_FRESH_GP_MAX).'% (ตอนนี้ '.$this->fmt($freshGp).'%)');
        }

        // ----- ส่วนแบ่งไรเดอร์ -----
        $riders = (int) ($stats['riders'] ?? 0);
        if ($share < self::RECOMMENDED_RIDER_SHARE) {
            $items[] = $this->item('warn', 'fa-motorcycle', 'ส่วนแบ่งไรเดอร์ต่ำเกินไปสำหรับช่วงหาไรเดอร์',
                'ควรให้ไรเดอร์ได้อย่างน้อย '.$this->fmt(self::RECOMMENDED_RIDER_SHARE).'% ของค่าส่ง (ตอนนี้ '.$this->fmt($share).'%, ไรเดอร์ที่อนุมัติแล้ว '.number_format($riders).' คน)');
        } else {
            $items[] = $this->item('good', 'fa-motorcycle', 'ส่วนแบ่งไรเดอร์จูงใจดี',
                'ไรเดอร์ได้ '.$this->fmt($share).'% ของค่าส่ง เงินเข้ากระเป๋าทันทีที่ส่งสำเร็จ (ไรเดอร์ที่อนุมัติแล้ว '.number_format($riders).' คน)');
        }

        // ----- ค่าแนะนำ (เฉพาะเว็บ) -----
        $gpNow = $promoActive ? 0.0 : $effectiveAfterPromo;
        if ($pool > 0) {
            if (! $mlmEnabled) {
                $items[] = $this->item('info', 'fa-people-arrows', 'ระบบแนะนำปิดอยู่',
                    'ตั้งค่าแนะนำไว้ '.$this->fmt($pool).'% แต่ระบบแนะนำ (เว็บ) ยังปิดอยู่ จึงยังไม่หักจากออเดอร์');
            } elseif ($pool > $gpNow) {
                $items[] = $this->item('warn', 'fa-people-arrows', 'ค่าแนะนำเกินส่วน GP ของแพลตฟอร์ม',
                    'ค่าแนะนำควรจ่ายจาก GP ไม่ใช่หักจากราคาผู้ขาย — ตอนนี้หัก '.$this->fmt($pool).'% แต่ GP มีแค่ '.$this->fmt($gpNow).'% ร้านค้าจึงรับภาระส่วนต่าง');
            } else {
                $items[] = $this->item('info', 'fa-people-arrows', 'ค่าแนะนำอยู่ภายใน GP',
                    'ค่าแนะนำ '.$this->fmt($pool).'% ไม่เกิน GP '.$this->fmt($gpNow).'% — ควรจ่ายจากส่วน GP ของแพลตฟอร์ม ไม่ใช่เพิ่มภาระให้ผู้ขาย');
            }
        } else {
            $items[] = $this->item('good', 'fa-people-arrows', 'ไม่หักค่าแนะนำจากร้านค้า',
                'ถ้าเปิดค่าแนะนำในอนาคต ให้กำหนดไม่เกินส่วน GP เพื่อให้ผู้ขายไม่เสียเพิ่ม');
        }

        // ----- VAT -----
        if (abs($vat - self::THAI_VAT_RATE) > 0.001) {
            $items[] = $this->item('warn', 'fa-receipt', 'อัตรา VAT ไม่ตรงกับอัตราปัจจุบัน',
                'อัตรา VAT ของไทยตอนนี้คือ '.$this->fmt(self::THAI_VAT_RATE).'% (ตั้งไว้ '.$this->fmt($vat).'%) — ตรวจสอบประกาศกรมสรรพากรก่อนเปลี่ยน');
        }

        return [
            'items' => $items,
            'milestone' => $milestone,
            'promo_active' => $promoActive,
            'promo_ends_at' => $promoEnd?->toIso8601String(),
        ];
    }

    /**
     * วันสิ้นสุดโปรฯ ("YYYY-MM-DD" = ฟรีถึงสิ้นวันนั้น) — อ่านไม่ออก = null
     */
    public function parseEnd(?string $raw): ?Carbon
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        try {
            $end = Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $end->endOfDay() : $end;
    }

    /**
     * @return array{level: string, icon: string, title: string, message: string}
     */
    private function item(string $level, string $icon, string $title, string $message): array
    {
        return ['level' => $level, 'icon' => $icon, 'title' => $title, 'message' => $message];
    }

    private function fmt(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $text === '' || $text === '-0' ? '0' : $text;
    }
}
