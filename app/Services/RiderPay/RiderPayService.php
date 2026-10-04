<?php

namespace App\Services\RiderPay;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\Order;
use App\Models\RiderJob;
use App\Models\VendorStore;
use App\Services\DeliveryFeeCalculator;
use App\Support\SafeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🛵 ผู้ช่วยตั้ง "ค่าตอบแทนไรเดอร์" ของร้าน (ไรเดอร์รอบ 2 — GET/PUT /seller/rider-pay, /fresh-market/seller/rider-pay)
 *
 * ตัวเลขเงินทั้งหมดมาจากสูตร (DeliveryFeeCalculator) — AI แค่เรียบเรียงคำแนะนำ ห้ามแตะตัวเลข (RiderPayAdviceWriter)
 *
 * ===== แบบจำลองโอกาส "มีไรเดอร์รับงานภายใน 5 นาที" ต่อช่วงระยะ (0-2, 2-5, 5-8, 8+ กม.) =====
 *
 * 1) basis = 'data' — มีงานไรเดอร์ในช่วงระยะนั้น ≥ 20 งานใน 30 วันล่าสุด (ใช้งานใกล้ร้านในรัศมี ~5 กม. ก่อน ถ้าพอ ไม่พอใช้ทั้งระบบ)
 *    accept_rate_5min = สัดส่วนงานที่ accepted_at − created_at ≤ 5 นาที
 *      (ไม่นับงานที่ผู้ซื้อ/ร้าน/แอดมินยกเลิกเองก่อนครบ 5 นาทีโดยยังไม่มีคนรับ — ยังไม่ได้ให้โอกาสไรเดอร์ครบ)
 *    accept_rate_with_bonus = เลื่อนโอกาสบนเส้นโค้ง logistic ตาม "รายได้ไรเดอร์ต่อกม." (EPK) ที่เปลี่ยนไป:
 *      logit(p₁) = logit(p₀) + (EPK_ใหม่ − EPK_ที่เห็นจริง) / PAY_SCALE
 *      EPK_ใหม่ = รายได้จากค่าส่งต่อกม. (เฉลี่ยของตัวอย่าง) + โบนัส ÷ ระยะตัวแทนของช่วง
 *      → เพิ่มโบนัสแล้วโอกาสเพิ่มเสมอ (monotonic) และไม่ทะลุ 0–1
 *
 * 2) basis = 'estimate' — ข้อมูลยังไม่พอ (prod เพิ่งเปิด งานแทบเป็น 0) ใช้สูตรประมาณที่อธิบายได้:
 *      p = P_MAX × f_pay(EPK) × f_dist(d)
 *      f_pay(EPK) = 1 / (1 + e^(−(EPK − PAY_MID_EPK) / PAY_SCALE))   — ไรเดอร์ได้ 8 บาท/กม. = ครึ่งทาง, 12 บาท/กม. ≈ 0.88
 *      f_dist(d)  = 1 / (1 + (d / DIST_HALF_KM)²)                   — งานไกลรับยากขึ้น (ไม่ได้กลับมารับงานใกล้ร้านอีกนาน)
 *      d = ระยะตัวแทนของช่วง (1.5 / 3.5 / 6.5 กม. / กึ่งกลาง 8 กม.–ระยะสูงสุด), EPK = (ส่วนแบ่งไรเดอร์จากค่าส่ง + โบนัส) ÷ d
 *      ค่าเริ่มต้นของระบบ (30 บาท + 10 บาท/กม. หลัง 2 กม., ไรเดอร์ได้ 80%) ได้ประมาณ 93% / 64% / 41% / 22%
 *
 *    ชั่วโมงเร่งด่วน (11–13, 17–19) ไรเดอร์ว่างน้อย: คูณ PEAK_SUPPLY_FACTOR (0.85) ทั้งสองแบบ
 *
 * ===== คำแนะนำ (กฎตายตัว ตัวเลขเดียวกันทุกครั้งที่ข้อมูลเท่าเดิม) =====
 *   ช่วงระยะหลัก = ช่วงที่ครอบ p90 ระยะลูกค้าของร้าน (ไม่มีข้อมูล = 2–5 กม. ที่พบบ่อยที่สุด)
 *   suggested_bonus      = โบนัสน้อยที่สุดใน 0,5,…,30 บาท ที่ทำให้ช่วงหลักมีโอกาส ≥ 70% (ไม่ถึง = 30)
 *   suggested_bonus_peak = แบบเดียวกันแต่คิดชั่วโมงเร่งด่วน (ไม่น้อยกว่า suggested_bonus)
 *   suggest_free_delivery = ลูกค้าครึ่งหนึ่งอยู่ในระยะที่รวมในค่าส่งเริ่มต้น (p50 ≤ free_km) และยอดเฉลี่ยต่อบิล ≥ 10 เท่าของค่าส่งขั้นต่ำ
 */
class RiderPayService
{
    /** ช่วงระยะ (กม.) — to = null คือไม่มีเพดาน (ถึงระยะส่งสูงสุดของระบบ) */
    public const BANDS = [
        ['key' => '0-2', 'label' => '0–2 กม.', 'from' => 0.0, 'to' => 2.0],
        ['key' => '2-5', 'label' => '2–5 กม.', 'from' => 2.0, 'to' => 5.0],
        ['key' => '5-8', 'label' => '5–8 กม.', 'from' => 5.0, 'to' => 8.0],
        ['key' => '8+', 'label' => 'เกิน 8 กม.', 'from' => 8.0, 'to' => null],
    ];

    /** ระยะตัวแทนของช่วง (กม.) ใช้คิดรายได้ต่อกม. — 8+ คิดกึ่งกลาง 8 กม. ถึงระยะส่งสูงสุด */
    public const REPRESENTATIVE_KM = ['0-2' => 1.5, '2-5' => 3.5, '5-8' => 6.5];

    /** ต้องมีงานในช่วงระยะอย่างน้อยเท่านี้ (30 วัน) จึงใช้ข้อมูลจริง */
    public const MIN_SAMPLE = 20;

    public const DATA_WINDOW_DAYS = 30;

    /** รัศมี "ใกล้ร้าน" สำหรับเลือกงานตัวอย่าง (กม.) */
    public const NEAR_STORE_KM = 5.0;

    /** วินาทีที่นับว่า "มีคนรับเร็ว" */
    public const ACCEPT_WITHIN_SECONDS = 300;

    /** cache งานตัวอย่างทั้งระบบ */
    public const SAMPLES_CACHE_KEY = 'rider_pay:job_samples:v1';

    public const SAMPLES_CACHE_SECONDS = 300;

    // ===== เส้นโค้งประมาณ (อธิบายในหัวคลาส) =====
    public const P_MAX = 0.97;

    public const PAY_MID_EPK = 8.0;

    public const PAY_SCALE = 2.0;

    public const DIST_HALF_KM = 9.0;

    public const PEAK_SUPPLY_FACTOR = 0.85;

    public const RATE_FLOOR = 0.02;

    public const RATE_CEIL = 0.98;

    // ===== คำแนะนำ =====
    public const TARGET_RATE = 0.70;

    /** @var array<int, int> */
    public const BONUS_STEPS = [0, 5, 10, 15, 20, 25, 30];

    /** โบนัสสูงสุดที่ผู้ช่วยจะแนะนำ (ขั้นสุดท้ายของ BONUS_STEPS) */
    public const MAX_SUGGESTED_BONUS = 30;

    public const DEFAULT_FOCUS_BAND = '2-5';

    /** ระยะลูกค้า: ต้องมีอย่างน้อยกี่ออเดอร์ · ย้อนหลังกี่วัน */
    public const CUSTOMER_MIN_SAMPLE = 5;

    public const CUSTOMER_WINDOW_DAYS = 180;

    public function __construct(
        private readonly DeliveryFeeCalculator $fees,
        private readonly RiderPayAdviceWriter $writer,
    ) {}

    /**
     * ข้อมูลหน้า "ค่าตอบแทนไรเดอร์" (สัญญากลาง RiderPay)
     *
     * @param  VendorStore|FreshMarketSeller  $store
     * @param  array{rider_bonus?: float, rider_bonus_peak?: float, rider_free_delivery?: bool}|null  $preview  ค่าทดลอง (ไม่บันทึก)
     * @return array<string, mixed>
     */
    public function build(Model $store, ?array $preview = null): array
    {
        $settings = $this->settingsOf($store, $preview);
        $base = $this->base();
        $samples = $this->jobSamples($store);
        $customer = $this->customerDistance($store);

        $bandContexts = [];
        $bandsOut = [];
        foreach (self::BANDS as $band) {
            $ctx = $this->bandContext($band, $samples, $base['max_distance_km']);
            $bandContexts[$band['key']] = $ctx;
            $bandsOut[] = $this->presentBand($band, $ctx, $settings);
        }

        $advice = $this->advice($store, $bandContexts, $settings, $customer, $base, $preview !== null);

        return [
            'settings' => $settings,
            'base' => $base,
            'bands' => $bandsOut,
            'advice' => $advice,
            'customer_distance' => $customer === null ? null : [
                'p50_km' => $customer['p50_km'],
                'p90_km' => $customer['p90_km'],
                'sample_size' => $customer['sample_size'],
            ],
        ];
    }

    /**
     * บันทึกค่าตอบแทนไรเดอร์ของร้าน (เฉพาะช่องที่ส่งมา)
     *
     * @param  VendorStore|FreshMarketSeller  $store
     * @param  array{rider_bonus?: mixed, rider_bonus_peak?: mixed, rider_free_delivery?: mixed}  $data  ผ่าน validation แล้ว
     */
    public function save(Model $store, array $data): Model
    {
        $changes = [];
        if (array_key_exists('rider_bonus', $data)) {
            $changes['rider_bonus'] = DeliveryFeeCalculator::clampBonus($data['rider_bonus']);
        }
        if (array_key_exists('rider_bonus_peak', $data)) {
            $changes['rider_bonus_peak'] = DeliveryFeeCalculator::clampBonus($data['rider_bonus_peak']);
        }
        if (array_key_exists('rider_free_delivery', $data)) {
            $changes['rider_free_delivery'] = filter_var($data['rider_free_delivery'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($changes !== []) {
            $store->fill($changes)->save();
        }

        return $store->fresh() ?? $store;
    }

    /**
     * ค่าที่ร้านตั้ง (หรือค่าทดลอง)
     *
     * @param  array<string, mixed>|null  $preview
     * @return array{rider_bonus: float, rider_bonus_peak: float, rider_free_delivery: bool}
     */
    public function settingsOf(Model $store, ?array $preview = null): array
    {
        $preview ??= [];

        return [
            'rider_bonus' => DeliveryFeeCalculator::clampBonus($preview['rider_bonus'] ?? $store->getAttribute('rider_bonus')),
            'rider_bonus_peak' => DeliveryFeeCalculator::clampBonus($preview['rider_bonus_peak'] ?? $store->getAttribute('rider_bonus_peak')),
            'rider_free_delivery' => array_key_exists('rider_free_delivery', $preview)
                ? (bool) $preview['rider_free_delivery']
                : DeliveryFeeCalculator::storeFreeDelivery($store),
        ];
    }

    /**
     * สูตรค่าส่งของระบบที่ร้านต้องรู้
     *
     * @return array<string, mixed>
     */
    public function base(): array
    {
        return [
            'base_fee' => round($this->fees->floatSetting('rider.base_fee'), 2),
            'per_km_fee' => round($this->fees->floatSetting('rider.per_km_fee'), 2),
            'free_km' => round($this->fees->floatSetting('rider.free_km'), 2),
            'min_fee' => round($this->fees->floatSetting('rider.min_fee'), 2),
            'rider_share_percent' => round($this->fees->floatSetting('rider.rider_share_percent'), 2),
            'max_distance_km' => round($this->fees->maxDistanceKm(), 2),
            'night_surcharge' => round(max(0.0, $this->fees->floatSetting('rider.night_surcharge')), 2),
            'peak_surcharge' => round(max(0.0, $this->fees->floatSetting('rider.peak_surcharge')), 2),
            'peak_hours' => DeliveryFeeCalculator::PEAK_HOURS,
        ];
    }

    // =====================================================
    // ช่วงระยะ + แบบจำลองโอกาสรับงาน
    // =====================================================

    /**
     * บริบทของช่วงระยะ: ระยะตัวแทน, ค่าส่ง, รายได้ไรเดอร์, และข้อมูลจริง (ถ้าพอ)
     *
     * @param  array{key: string, label: string, from: float, to: ?float}  $band
     * @param  array{near: Collection<int, object>, all: Collection<int, object>}  $samples
     * @return array<string, mixed>
     */
    private function bandContext(array $band, array $samples, float $maxKm): array
    {
        $available = $maxKm > $band['from'] + 0.0001;
        $to = $band['to'] === null ? max($band['from'], $maxKm) : min($band['to'], max($band['from'], $maxKm));
        $rep = self::REPRESENTATIVE_KM[$band['key']] ?? round(($band['from'] + max($maxKm, $band['from'] + 2.0)) / 2, 2);
        $rep = max(0.5, min($rep, max($band['from'] + 0.5, $maxKm)));

        $repQuote = $this->fees->quoteForDistance($rep);
        $peakSurcharge = max(0.0, $this->fees->floatSetting('rider.peak_surcharge'));
        $repPeakEarn = $this->fees->split((float) $repQuote['total_fee'] + $peakSurcharge)['rider_earnings'];

        $ctx = [
            'key' => $band['key'],
            'label' => $band['label'],
            'available' => $available,
            'from' => $band['from'],
            'to' => $to,
            'rep_km' => $rep,
            'fee_min' => (float) $this->fees->quoteForDistance($band['from'])['total_fee'],
            'fee_max' => (float) $this->fees->quoteForDistance($to)['total_fee'],
            'rep_fee' => (float) $repQuote['total_fee'],
            'earn' => (float) $repQuote['rider_earnings'],
            'earn_peak' => (float) $repPeakEarn,
            'basis' => 'estimate',
            'sample_size' => 0,
            'p0' => null,
            'e_obs' => null,
            'e_sys' => null,
        ];

        $inBand = fn (Collection $rows) => $rows->filter(function ($row) use ($band) {
            $d = (float) $row->distance_km;

            return $d >= $band['from'] && ($band['to'] === null || $d < $band['to']);
        })->values();

        $near = $inBand($samples['near']);
        $all = $inBand($samples['all']);
        $used = $near->count() >= self::MIN_SAMPLE ? $near : ($all->count() >= self::MIN_SAMPLE ? $all : null);

        $ctx['sample_size'] = $used ? $used->count() : $all->count();

        if ($used !== null && $available) {
            $accepted = $used->filter(fn ($row) => $row->accepted_within_5 === true)->count();
            $ctx['basis'] = 'data';
            $ctx['p0'] = $accepted / max(1, $used->count());
            $ctx['e_obs'] = $used->avg(fn ($row) => ((float) $row->rider_earnings + (float) $row->shop_bonus) / max(0.5, (float) $row->distance_km));
            $ctx['e_sys'] = $used->avg(fn ($row) => (float) $row->rider_earnings / max(0.5, (float) $row->distance_km));
        }

        return $ctx;
    }

    /**
     * โอกาสมีคนรับใน 5 นาที (0–1) เมื่อร้านเติมโบนัส $bonus บาท
     *
     * @param  array<string, mixed>  $ctx
     */
    public function rateFor(array $ctx, float $bonus, bool $peak = false): ?float
    {
        if (! $ctx['available']) {
            return null;
        }

        $rep = max(0.5, (float) $ctx['rep_km']);
        $bonus = max(0.0, $bonus);

        if ($ctx['basis'] === 'data') {
            $p0 = min(self::RATE_CEIL, max(self::RATE_FLOOR, (float) $ctx['p0']));
            $deltaEpk = ((float) $ctx['e_sys'] + $bonus / $rep) - (float) $ctx['e_obs'];
            $rate = self::sigmoid(self::logit($p0) + $deltaEpk / self::PAY_SCALE);
        } else {
            $earn = $peak ? (float) $ctx['earn_peak'] : (float) $ctx['earn'];
            $rate = self::estimateRate($earn + $bonus, $rep);
        }

        if ($peak) {
            $rate *= self::PEAK_SUPPLY_FACTOR;
        }

        return min(self::RATE_CEIL, max(self::RATE_FLOOR, $rate));
    }

    /**
     * สูตรประมาณ (basis = estimate): P_MAX × f_pay(EPK) × f_dist(d)
     *
     * @param  float  $riderEarn  รายได้ไรเดอร์ทั้งงาน (ส่วนแบ่งค่าส่ง + โบนัส)
     * @param  float  $km  ระยะตัวแทน
     */
    public static function estimateRate(float $riderEarn, float $km): float
    {
        $km = max(0.5, $km);
        $epk = $riderEarn / $km;
        $fPay = 1 / (1 + exp(-($epk - self::PAY_MID_EPK) / self::PAY_SCALE));
        $fDist = 1 / (1 + ($km / self::DIST_HALF_KM) ** 2);

        return self::P_MAX * $fPay * $fDist;
    }

    /**
     * @param  array{key: string, label: string, from: float, to: ?float}  $band
     * @param  array<string, mixed>  $ctx
     * @param  array{rider_bonus: float, rider_bonus_peak: float, rider_free_delivery: bool}  $settings
     * @return array<string, mixed>
     */
    private function presentBand(array $band, array $ctx, array $settings): array
    {
        $bonus = (float) $settings['rider_bonus'];
        $base = $ctx['basis'] === 'data' ? (float) $ctx['p0'] : $this->rateFor($ctx, 0.0);
        $withBonus = $this->rateFor($ctx, $bonus);

        return [
            'key' => $band['key'],
            'label' => $band['label'],
            'from_km' => round($band['from'], 2),
            'to_km' => $band['to'] === null ? null : round($band['to'], 2),
            'fee_min' => round($ctx['fee_min'], 2),
            'fee_max' => round($ctx['fee_max'], 2),
            // ไรเดอร์ได้ = ส่วนแบ่งจากค่าส่ง + โบนัสปกติของร้าน (ส่งฟรีไม่กระทบ — ร้านออกค่าส่งแทนผู้ซื้อ ไรเดอร์ได้เท่าเดิม)
            'rider_earn_min' => round($this->fees->split($ctx['fee_min'])['rider_earnings'] + $bonus, 2),
            'rider_earn_max' => round($this->fees->split($ctx['fee_max'])['rider_earnings'] + $bonus, 2),
            'accept_rate_5min' => $ctx['available'] && $base !== null ? round($base, 2) : null,
            'accept_rate_with_bonus' => $withBonus !== null ? round($withBonus, 2) : null,
            'sample_size' => (int) $ctx['sample_size'],
            'basis' => $ctx['basis'],
        ];
    }

    // =====================================================
    // คำแนะนำ
    // =====================================================

    /**
     * @param  array<string, array<string, mixed>>  $bands
     * @param  array{rider_bonus: float, rider_bonus_peak: float, rider_free_delivery: bool}  $settings
     * @param  array<string, mixed>|null  $customer
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function advice(Model $store, array $bands, array $settings, ?array $customer, array $base, bool $isPreview): array
    {
        $focusKey = $this->focusBandKey($bands, $customer);
        $focus = $bands[$focusKey];

        $suggested = $this->smallestBonusReaching($focus, false);
        $suggestedPeak = max($suggested, $this->smallestBonusReaching($focus, true));

        $avgOrder = $customer['avg_order_value'] ?? null;
        $suggestFree = $customer !== null
            && $customer['p50_km'] <= (float) $base['free_km']
            && $avgOrder !== null
            && (float) $base['min_fee'] > 0
            && $avgOrder >= (float) $base['min_fee'] * 10;

        $rateNow = (int) round(100 * (float) ($focus['basis'] === 'data' ? $focus['p0'] : $this->rateFor($focus, 0.0)));
        $rateSuggested = (int) round(100 * (float) $this->rateFor($focus, (float) $suggested));
        $ratePeakSuggested = (int) round(100 * (float) $this->rateFor($focus, (float) $suggestedPeak, true));
        $reachesTarget = ($this->rateFor($focus, (float) self::MAX_SUGGESTED_BONUS) ?? 0) >= self::TARGET_RATE;

        $facts = [
            'band' => $focus['label'],
            'basis' => $focus['basis'],
            'rate_now_percent' => $rateNow,
            'suggested_bonus' => $suggested,
            'rate_with_suggested_percent' => $rateSuggested,
            'suggested_bonus_peak' => $suggestedPeak,
            'rate_peak_with_suggested_percent' => $ratePeakSuggested,
            'peak_hours' => '11:00–13:00 และ 17:00–19:00',
            'reaches_target' => $reachesTarget,
            'target_percent' => (int) round(self::TARGET_RATE * 100),
            'suggest_free_delivery' => $suggestFree,
            'customer_p50_km' => $customer !== null ? number_format((float) $customer['p50_km'], 1, '.', '') : null,
            'customer_p90_km' => $customer !== null ? number_format((float) $customer['p90_km'], 1, '.', '') : null,
            'typical_fee' => $customer !== null ? (int) round((float) $this->fees->quoteForDistance((float) $customer['p50_km'])['total_fee']) : null,
            'current_bonus' => number_format((float) $settings['rider_bonus'], 2, '.', ''),
            'current_bonus_peak' => number_format((float) $settings['rider_bonus_peak'], 2, '.', ''),
            'current_free_delivery' => (bool) $settings['rider_free_delivery'],
        ];

        $rules = $this->rulesText($facts);
        $phrased = $this->writer->phrase($this->storeKey($store), $facts, $rules, ! $isPreview);

        return [
            'headline' => $phrased['headline'],
            'text' => $phrased['text'],
            'suggested_bonus' => (float) $suggested,
            'suggested_bonus_peak' => (float) $suggestedPeak,
            'suggest_free_delivery' => $suggestFree,
            'source' => $phrased['source'],
            'generated_at' => $phrased['generated_at'],
        ];
    }

    /**
     * ช่วงระยะหลักของร้าน = ช่วงที่ครอบ p90 ระยะลูกค้า (ไม่มีข้อมูล = 2–5 กม.)
     *
     * @param  array<string, array<string, mixed>>  $bands
     * @param  array<string, mixed>|null  $customer
     */
    private function focusBandKey(array $bands, ?array $customer): string
    {
        $key = self::DEFAULT_FOCUS_BAND;

        if ($customer !== null) {
            $p90 = (float) $customer['p90_km'];
            foreach (self::BANDS as $band) {
                if ($p90 >= $band['from'] && ($band['to'] === null || $p90 < $band['to'])) {
                    $key = $band['key'];
                    break;
                }
            }
        }

        // ช่วงที่เกินระยะส่งสูงสุด → ถอยไปช่วงไกลสุดที่ยังส่งได้
        if (! ($bands[$key]['available'] ?? false)) {
            foreach (array_reverse(self::BANDS) as $band) {
                if ($bands[$band['key']]['available'] ?? false) {
                    return $band['key'];
                }
            }

            return '0-2';
        }

        return $key;
    }

    /**
     * โบนัสน้อยที่สุดใน BONUS_STEPS ที่ทำให้โอกาสถึงเป้า (ไม่ถึงเลย = ขั้นสูงสุด)
     *
     * @param  array<string, mixed>  $ctx
     */
    private function smallestBonusReaching(array $ctx, bool $peak): int
    {
        foreach (self::BONUS_STEPS as $bonus) {
            if (($this->rateFor($ctx, (float) $bonus, $peak) ?? 0) >= self::TARGET_RATE) {
                return $bonus;
            }
        }

        return self::MAX_SUGGESTED_BONUS;
    }

    /**
     * ข้อความคำแนะนำจากกฎ (ใช้เมื่อ AI ไม่พร้อม และเป็นต้นฉบับให้ AI เรียบเรียง) — ไม่เกิน 3 ประโยค
     *
     * @param  array<string, mixed>  $f
     * @return array{headline: string, text: string}
     */
    public function rulesText(array $f): array
    {
        $b = (int) $f['suggested_bonus'];
        $bp = (int) $f['suggested_bonus_peak'];

        if ($b === 0 && $bp === 0) {
            $headline = 'ค่าส่งตอนนี้ไรเดอร์รับงานได้ดีแล้ว';
        } elseif ($bp > $b && $b === 0) {
            $headline = "แนะนำเติมโบนัส {$bp} บาท เฉพาะช่วงเร่งด่วน";
        } elseif ($bp > $b) {
            $headline = "แนะนำโบนัส {$b} บาท ช่วงเร่งด่วน {$bp} บาท";
        } else {
            $headline = "แนะนำเติมโบนัส {$b} บาทต่อออเดอร์";
        }

        $sentences = [];
        $sentences[] = "งานระยะ {$f['band']} มีโอกาสมีไรเดอร์รับภายใน 5 นาทีประมาณ {$f['rate_now_percent']}%"
            .($f['basis'] === 'data' ? ' (จากงานจริง 30 วันล่าสุด)' : ' (ประเมินจากสูตร เพราะงานจริงยังไม่พอ)');

        if (! $f['reaches_target']) {
            $sentences[] = "แม้เติม {$b} บาทก็ได้ประมาณ {$f['rate_with_suggested_percent']}% งานระยะนี้อาจต้องรอไรเดอร์นานกว่าปกติ";
        } elseif ($b > 0) {
            $sentences[] = "เติมโบนัส {$b} บาทต่อออเดอร์ จะเพิ่มเป็นประมาณ {$f['rate_with_suggested_percent']}%";
        } else {
            $sentences[] = 'ไม่ต้องเติมโบนัสก็ได้ ไรเดอร์ได้ส่วนแบ่งค่าส่งตามสูตรอยู่แล้ว';
        }

        if ($bp > $b) {
            $sentences[] = "ช่วง {$f['peak_hours']} ไรเดอร์ว่างน้อย ตั้งโบนัสช่วงเร่งด่วน {$bp} บาทจะได้ประมาณ {$f['rate_peak_with_suggested_percent']}%";
        } elseif ($f['suggest_free_delivery'] && $f['typical_fee'] !== null) {
            $sentences[] = "ลูกค้าครึ่งหนึ่งอยู่ไม่เกิน {$f['customer_p50_km']} กม. ลองเปิดส่งฟรีโดยร้านออกค่าส่งราว {$f['typical_fee']} บาทต่อออเดอร์ ช่วยปิดการขายได้ง่ายขึ้น";
        }

        return [
            'headline' => $headline,
            'text' => implode(' ', array_slice($sentences, 0, 3)),
        ];
    }

    // =====================================================
    // ข้อมูลจริง
    // =====================================================

    /**
     * งานไรเดอร์ 30 วันล่าสุด (ทั้งระบบ + เฉพาะใกล้ร้าน) พร้อมธง "มีคนรับภายใน 5 นาที"
     *
     * @return array{near: Collection<int, object>, all: Collection<int, object>}
     */
    private function jobSamples(Model $store): array
    {
        try {
            // งานทั้งระบบใช้ร่วมกันทุกร้าน → cache 5 นาที (กันร้านเปิดหน้าซ้ำแล้วดึงงานหลายพันแถวทุกครั้ง)
            $rows = Cache::remember(self::SAMPLES_CACHE_KEY, self::SAMPLES_CACHE_SECONDS, fn () => $this->platformSamples());
        } catch (\Throwable $e) {
            Log::warning('RiderPay: อ่านงานไรเดอร์ไม่ได้ ใช้สูตรประมาณแทน', ['error' => SafeLog::exceptionMessage($e)]);
            $rows = [];
        }

        $all = collect($rows)->map(fn (array $row) => (object) $row)->values();

        $point = $this->storePoint($store);
        $near = $point === null ? collect() : $all->filter(function ($row) use ($point) {
            return $row->lat !== null && $row->lng !== null
                && DeliveryFeeCalculator::haversineKm($point[0], $point[1], $row->lat, $row->lng) <= self::NEAR_STORE_KM;
        })->values();

        return ['near' => $near, 'all' => $all];
    }

    /**
     * งานไรเดอร์ 30 วันล่าสุดทั้งระบบ (แถวละ array ล้วน — เก็บลง cache ได้) ตัดงานที่ผู้ซื้อ/ร้าน/แอดมินยกเลิกเองก่อนครบ 5 นาที
     *
     * @return array<int, array{distance_km: float, accepted_within_5: bool, rider_earnings: float, shop_bonus: float, lat: ?float, lng: ?float}>
     */
    private function platformSamples(): array
    {
        $rows = RiderJob::query()
            ->where('created_at', '>=', now()->subDays(self::DATA_WINDOW_DAYS))
            ->where('created_at', '<=', now()->subSeconds(self::ACCEPT_WITHIN_SECONDS))
            ->whereNotNull('distance_km')
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['id', 'distance_km', 'created_at', 'accepted_at', 'status', 'cancelled_at', 'cancelled_by', 'rider_earnings', 'shop_bonus', 'pickup_latitude', 'pickup_longitude']);

        return $rows->map(function (RiderJob $job) {
            $created = $job->created_at;
            $acceptedWithin = $created !== null && $job->accepted_at !== null
                && $job->accepted_at->getTimestamp() - $created->getTimestamp() <= self::ACCEPT_WITHIN_SECONDS;

            // ผู้ซื้อ/ร้าน/แอดมินยกเลิกเองก่อนครบ 5 นาทีโดยยังไม่มีคนรับ → ไรเดอร์ยังไม่ได้โอกาสครบ ไม่นับ
            $cancelledEarly = $job->accepted_at === null && $job->cancelled_at !== null && $created !== null
                && $job->cancelled_at->getTimestamp() - $created->getTimestamp() < self::ACCEPT_WITHIN_SECONDS
                && $job->cancelled_by !== 'system';

            return $cancelledEarly ? null : [
                'distance_km' => (float) $job->distance_km,
                'accepted_within_5' => $acceptedWithin,
                'rider_earnings' => (float) $job->rider_earnings,
                'shop_bonus' => (float) ($job->shop_bonus ?? 0),
                'lat' => $job->pickup_latitude !== null ? (float) $job->pickup_latitude : null,
                'lng' => $job->pickup_longitude !== null ? (float) $job->pickup_longitude : null,
            ];
        })->filter()->values()->all();
    }

    /**
     * ระยะลูกค้าของร้านจากออเดอร์ส่งไรเดอร์ในอดีต (น้อยกว่า 5 ออเดอร์ = null)
     *
     * @return array{p50_km: float, p90_km: float, sample_size: int, avg_order_value: ?float}|null
     */
    private function customerDistance(Model $store): ?array
    {
        try {
            $since = now()->subDays(self::CUSTOMER_WINDOW_DAYS);

            if ($store instanceof VendorStore) {
                // ระยะจากงานไรเดอร์ของออเดอร์ร้านนี้ (1 ออเดอร์อาจมีหลายงานเมื่อเรียกไรเดอร์ใหม่ → ใช้งานล่าสุด)
                $rows = DB::table('rider_jobs')
                    ->join('orders', 'orders.id', '=', 'rider_jobs.source_id')
                    ->where('rider_jobs.source_type', (new Order)->getMorphClass())
                    ->where('orders.store_id', $store->id)
                    ->whereNull('rider_jobs.deleted_at')
                    ->whereNull('orders.deleted_at')
                    ->whereNotNull('rider_jobs.distance_km')
                    ->where('rider_jobs.created_at', '>=', $since)
                    ->orderByDesc('rider_jobs.id')
                    ->limit(1000)
                    ->get(['orders.id as order_id', 'rider_jobs.distance_km', 'orders.subtotal as order_value']);
            } elseif ($store instanceof FreshMarketSeller) {
                $rows = FreshMarketOrder::query()
                    ->where('seller_id', $store->id)
                    ->where('delivery_type', 'rider')
                    ->whereNotNull('delivery_distance_km')
                    ->where('order_status', '!=', 'cancelled')
                    ->where('created_at', '>=', $since)
                    ->orderByDesc('id')
                    ->limit(1000)
                    ->get(['id as order_id', 'delivery_distance_km as distance_km', 'total_amount as order_value']);
            } else {
                return null;
            }
        } catch (\Throwable $e) {
            Log::warning('RiderPay: อ่านระยะลูกค้าไม่ได้', ['error' => SafeLog::exceptionMessage($e)]);

            return null;
        }

        $rows = collect($rows)->unique('order_id')->values();
        if ($rows->count() < self::CUSTOMER_MIN_SAMPLE) {
            return null;
        }

        $distances = $rows->map(fn ($r) => (float) $r->distance_km)->sort()->values()->all();

        return [
            'p50_km' => round(self::percentile($distances, 50), 2),
            'p90_km' => round(self::percentile($distances, 90), 2),
            'sample_size' => count($distances),
            'avg_order_value' => round((float) $rows->avg(fn ($r) => (float) $r->order_value), 2),
        ];
    }

    /**
     * percentile แบบ nearest-rank (รายการเรียงแล้ว)
     *
     * @param  array<int, float>  $sorted
     */
    public static function percentile(array $sorted, int $p): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0.0;
        }

        $rank = (int) ceil($p / 100 * $n);

        return (float) $sorted[max(0, min($n - 1, $rank - 1))];
    }

    /**
     * พิกัดจุดรับของร้าน [lat, lng] (ไม่มี = null)
     *
     * @return array{0: float, 1: float}|null
     */
    private function storePoint(Model $store): ?array
    {
        [$lat, $lng] = $store instanceof VendorStore
            ? [$store->pickup_latitude, $store->pickup_longitude]
            : [$store->getAttribute('latitude'), $store->getAttribute('longitude')];

        return DeliveryFeeCalculator::isValidCoordinate($lat, $lng) ? [(float) $lat, (float) $lng] : null;
    }

    /**
     * key ของร้านสำหรับ cache คำแนะนำ
     */
    public function storeKey(Model $store): string
    {
        return ($store instanceof FreshMarketSeller ? 'fresh-market' : 'shop').':'.$store->getKey();
    }

    private static function logit(float $p): float
    {
        return log($p / (1 - $p));
    }

    private static function sigmoid(float $x): float
    {
        return 1 / (1 + exp(-$x));
    }
}
