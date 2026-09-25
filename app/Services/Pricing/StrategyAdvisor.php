<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Services\DeliveryFeeCalculator;
use App\Services\ShippingService;
use InvalidArgumentException;

/**
 * ที่ปรึกษากลยุทธ์ราคาสำหรับร้านค้า
 *
 * รับต้นทุน + เป้ากำไร (+ ราคาคู่แข่ง / ยอดขายต่อเดือน / วิธีส่ง) แล้วเสนอราคา 3 แบบ
 * (เจาะตลาด / สมดุล / พรีเมียม) พร้อมการแบ่งเงินครบทุกบรรทัดจาก PricingEngine,
 * จุดคุ้มทุน และคำแนะนำภาษาไทยที่ทำตามได้ทันที
 *
 * ทุกตัวเลขมาจาก PricingEngine::breakdown() ชุดเดียวกับตอนตัดเงินจริง จึงไม่มีทางไม่ตรงกัน
 * "กำไร %" ในคลาสนี้ = กำไรสุทธิ ÷ ราคาขาย × 100 (margin บนราคาขาย ไม่ใช่ markup บนต้นทุน)
 */
class StrategyAdvisor
{
    public const STRATEGY_PENETRATION = 'penetration';

    public const STRATEGY_BALANCED = 'balanced';

    public const STRATEGY_PREMIUM = 'premium';

    public const DEFAULT_TARGET_MARGIN = 20.0;

    /** กำไรขั้นต่ำของราคาเจาะตลาด (%) */
    public const MIN_PENETRATION_MARGIN = 5.0;

    public const DEFAULT_AVG_DISTANCE_KM = 3.0;

    /** เกณฑ์รายได้ต่อปีที่ต้องจดทะเบียน VAT (บาท) */
    public const VAT_REGISTRATION_THRESHOLD = 1800000;

    /** สมมติฐานยอดขายเทียบกับราคาสมดุล (ใช้ประมาณการรายเดือนเท่านั้น) */
    private const VOLUME_FACTOR = [
        self::STRATEGY_PENETRATION => 1.3,
        self::STRATEGY_BALANCED => 1.0,
        self::STRATEGY_PREMIUM => 0.7,
    ];

    private PricingEngine $engine;

    /** @var array<string, mixed> ตัวเลือกที่ส่งให้ engine ของแผนที่กำลังคำนวณ */
    private array $opts = [];

    private float $cost = 0.0;

    public function __construct(?PricingEngine $engine = null)
    {
        $this->engine = $engine ?? new PricingEngine;
    }

    /**
     * วางแผนราคา 3 กลยุทธ์
     *
     * input:
     *  - cost (float, บังคับ)            ต้นทุนต่อชิ้น (0 ได้ถ้าระบุ competitor_price)
     *  - target_margin_percent (float)   เป้ากำไร % ของราคาขาย (ค่าเริ่มต้น 20)
     *  - competitor_price (float)        ราคาคู่แข่ง/ราคากลางตลาด
     *  - monthly_volume (int)            ยอดขายที่คาดต่อเดือน (ชิ้น) ที่ราคาสมดุล
     *  - fixed_monthly_cost (float)      ค่าใช้จ่ายคงที่ต่อเดือน (ค่าแพ็กเกจ ค่าเช่า ค่าโฆษณา)
     *  - gp_rate (float %)               อัตรา GP (ค่าเริ่มต้น = อัตรามาตรฐาน)
     *  - pv (float)                      PV ต่อชิ้น
     *  - mlm_enabled (bool)              ระบบแนะนำเปิดอยู่หรือไม่ (ค่าเริ่มต้นตามระบบ)
     *  - commission_per_pv (float)       บาทต่อ PV
     *  - vat_registered (bool)           ร้านจด VAT หรือไม่ (ค่าเริ่มต้น false)
     *  - payment_fee_rate (float %)      ค่าธรรมเนียมรับชำระเงิน
     *  - delivery ('none'|'rider'|'parcel') วิธีส่ง
     *  - delivery_fee (float)            ค่าส่งต่อเที่ยว (ไม่ระบุ = ประมาณจากค่าตั้งระบบ)
     *  - avg_distance_km (float)         ระยะส่งเฉลี่ยของไรเดอร์ (ค่าเริ่มต้น 3 กม.)
     *  - current_price (float)           ราคาปัจจุบัน (ถ้ามี จะประเมินให้ด้วย)
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException เมื่อข้อมูลไม่พอหรือผิดรูปแบบ (ข้อความภาษาไทย)
     */
    public function plan(array $input): array
    {
        $in = $this->normalizeInput($input);
        $this->cost = $in['cost'];
        $this->opts = [
            'gp_rate' => $in['gp_rate'],
            'pv' => $in['pv'],
            'mlm_enabled' => $in['mlm_enabled'],
            'commission_per_pv' => $in['commission_per_pv'],
            'referral_pool_percent' => $this->engine->referralPoolPercent(),
            'referral_pool_cap_percent' => $this->engine->referralPoolCapPercent(),
            'vat_registered' => $in['vat_registered'],
            'vat_rate' => $this->engine->vatRate(),
            'payment_fee_rate' => $in['payment_fee_rate'],
            'cost_per_unit' => $in['cost'],
        ];

        $warnings = [];
        $recommendations = [];
        $competitor = $in['competitor_price'];
        $hasCost = $in['cost'] > 0;

        // เพดานกำไรที่เป็นไปได้ (ราคาสูงมากๆ ต้นทุนแทบไม่มีผล) = 100% − GP − VAT − ค่าแนะนำ% − ค่าธรรมเนียม
        $maxMargin = $this->marginAt(10000000.0);

        if ($maxMargin <= 1.0) {
            $warnings[] = $this->warning('NO_PROFITABLE_PRICE', 'ค่า GP + VAT + ค่าแนะนำ + ค่าธรรมเนียม รวมกันสูงจนไม่มีราคาใดทำกำไรได้ กรุณาติดต่อแอดมินเพื่อตรวจสอบอัตรา GP ของร้าน');

            return $this->result($in, null, null, $maxMargin, $this->emptyStrategies(), self::STRATEGY_BALANCED,
                'ยังไม่มีราคาที่ทำกำไรได้ด้วยอัตราค่าธรรมเนียมปัจจุบัน', null, $this->deliveryPlan($in, null, null),
                null, $recommendations, $warnings);
        }

        // ----- เป้ากำไรของแต่ละกลยุทธ์ -----
        $target = $in['target_margin_percent'];
        $reachable = floor(($maxMargin - 1.0) * 100) / 100;
        if ($target > $reachable) {
            $warnings[] = $this->warning('TARGET_MARGIN_UNREACHABLE', 'เป้ากำไร '.$this->fmt($target).'% สูงเกินกว่าที่ทำได้ (สูงสุดราว '.$this->fmt($reachable).'% หลังหัก GP/VAT/ค่าแนะนำ) ระบบปรับเป้าให้เป็น '.$this->fmt($reachable).'%');
            $target = max(0.0, $reachable);
        }
        $penMargin = min($target, max(self::MIN_PENETRATION_MARGIN, $target * 0.5));
        $premMargin = min($reachable, $target + max(10.0, $target * 0.5));

        // ----- ราคาตามสูตร (ก่อนปรับลงท้ายเลขสวย) -----
        $breakevenPrice = $hasCost ? $this->priceForMargin(0.0) : null;
        $floorPrice = $hasCost ? $this->priceForMargin($penMargin) : null;
        $balancedRaw = $hasCost ? $this->priceForMargin($target) : null;
        $premiumRaw = $hasCost ? $this->priceForMargin($premMargin) : null;

        if (! $hasCost) {
            $warnings[] = $this->warning('COST_MISSING', 'ยังไม่ได้กรอกต้นทุน ราคาที่แนะนำอิงราคาคู่แข่งอย่างเดียว กำไรที่แสดงยังไม่หักต้นทุน');
        }

        // ----- ตั้งราคา 3 กลยุทธ์ -----
        if ($competitor !== null) {
            $floorCharm = $floorPrice !== null ? $this->charmUp($floorPrice) : 0.0;
            $penPrice = max($this->charmDown($competitor * 0.95), $floorCharm);
            $balPrice = $balancedRaw !== null && $balancedRaw > $competitor
                ? $this->charmUp($balancedRaw)
                : max($this->charmDown($competitor), $this->charmUp($balancedRaw ?? 0.0));
            $premPrice = max($this->charmUp($premiumRaw ?? 0.0), $this->charmUp($competitor * 1.15));
        } else {
            // มีต้นทุนแน่นอน (ไม่มีทั้งต้นทุนและคู่แข่ง ถูกปฏิเสธตั้งแต่ normalizeInput)
            $penPrice = $this->charmUp((float) ($floorPrice ?? $breakevenPrice ?? $this->cost));
            $balPrice = $this->charmUp((float) ($balancedRaw ?? $floorPrice ?? $this->cost));
            $premPrice = $this->charmUp((float) ($premiumRaw ?? $balancedRaw ?? $this->cost));
        }

        // บังคับให้เรียงราคา เจาะตลาด < สมดุล < พรีเมียม
        if ($balPrice <= $penPrice) {
            $balPrice = $this->charmUp($penPrice + 0.01);
        }
        if ($premPrice <= $balPrice) {
            $premPrice = $this->charmUp($balPrice * 1.1);
        }

        $strategies = [
            $this->strategy(self::STRATEGY_PENETRATION, $penPrice, $floorPrice, $penMargin, $in),
            $this->strategy(self::STRATEGY_BALANCED, $balPrice, $balancedRaw, $target, $in),
            $this->strategy(self::STRATEGY_PREMIUM, $premPrice, $premiumRaw, $premMargin, $in),
        ];
        [$pen, $bal, $prem] = $strategies;

        // ----- เลือกกลยุทธ์ที่แนะนำ -----
        $recommended = self::STRATEGY_BALANCED;
        $reason = 'ราคาสมดุลได้กำไรตามเป้า '.$this->fmt($target).'% และยังแข่งขันได้';
        if ($competitor !== null && $breakevenPrice !== null && $breakevenPrice > $competitor) {
            $recommended = self::STRATEGY_PREMIUM;
            $reason = 'ต้นทุนของคุณทำให้แข่งราคากับคู่แข่งไม่ได้ ควรขายด้วยจุดเด่นที่ต่าง (คุณภาพ/บริการ/ส่งไว) ในราคาพรีเมียม';
        } elseif ($competitor !== null && $balancedRaw !== null && $balancedRaw > $competitor * 1.05) {
            $recommended = self::STRATEGY_PREMIUM;
            $reason = 'ราคาที่ได้กำไรตามเป้าสูงกว่าตลาด ควรวางตำแหน่งเป็นสินค้าคุณภาพสูงกว่า แทนการแข่งราคา';
        } elseif ($in['monthly_volume'] !== null && $in['fixed_monthly_cost'] > 0
            && $bal['breakeven_units'] !== null && $in['monthly_volume'] < $bal['breakeven_units']
            && $pen['monthly'] !== null && ($pen['monthly']['profit'] ?? 0) > ($bal['monthly']['profit'] ?? 0)) {
            $recommended = self::STRATEGY_PENETRATION;
            $reason = 'ยอดขายตอนนี้ยังไม่ถึงจุดคุ้มทุนของค่าใช้จ่ายคงที่ ราคาเจาะตลาดช่วยเร่งยอดได้มากกว่า';
        }

        // ----- ชุดสินค้า (bundle) -----
        $bundle = $this->bundlePlan($bal['price'], $penMargin);

        // ----- การส่ง -----
        $delivery = $this->deliveryPlan($in, $bal['price'], $bal['margin_percent']);

        // ----- ประเมินราคาปัจจุบัน -----
        $current = null;
        if ($in['current_price'] !== null) {
            $b = $this->quote($in['current_price']);
            $current = [
                'price' => $in['current_price'],
                'profit_per_unit' => $b->profit,
                'margin_percent' => $b->margin_percent,
                'breakdown' => $b->toArray(),
            ];
            if ($b->isLoss()) {
                $warnings[] = $this->warning('CURRENT_PRICE_LOSS', 'ราคาปัจจุบัน '.$this->money($in['current_price']).' บาท ขาดทุนชิ้นละ '.$this->money(abs((float) ($b->profit ?? $b->seller_net))).' บาท หลังหักค่าธรรมเนียม ควรปรับราคาขึ้นอย่างน้อยเป็น '.$this->money((float) ($breakevenPrice !== null ? $this->charmUp($breakevenPrice) : 0)).' บาท');
            } elseif ($hasCost && $b->margin_percent !== null && $b->margin_percent < $target) {
                $recommendations[] = 'ราคาปัจจุบัน '.$this->money($in['current_price']).' บาท ได้กำไร '.$this->fmt($b->margin_percent).'% ต่ำกว่าเป้า '.$this->fmt($target).'% ลองขยับเป็น '.$this->money($bal['price']).' บาท (ราคาสมดุล)';
            }
        }

        // ----- เทียบ GP ตามแพ็กเกจ (ที่ราคาสมดุล) -----
        $gpComparison = $this->gpComparison($in['compare_gp_rates'], (float) $bal['price'], (float) $bal['profit_per_unit']);
        foreach ($gpComparison as $row) {
            if ($row['extra_profit_per_unit'] > 0 && $in['monthly_volume'] !== null && $in['monthly_volume'] > 0) {
                $recommendations[] = 'ถ้าใช้'.$row['label'].' (GP '.$this->fmt($row['gp_rate']).'%) กำไรเพิ่มชิ้นละ '.$this->money($row['extra_profit_per_unit']).' บาท หรือราว '.$this->money(round($row['extra_profit_per_unit'] * $in['monthly_volume'], 2)).' บาท/เดือน ที่ยอดขาย '.$in['monthly_volume'].' ชิ้น — เทียบกับค่าแพ็กเกจก่อนตัดสินใจ';
                break;
            }
        }

        // ----- คำแนะนำ -----
        $recommendations = array_merge(
            $this->buildRecommendations($in, $target, $breakevenPrice, $pen, $bal, $prem, $bundle, $delivery, $warnings),
            $recommendations
        );

        $result = $this->result($in, $breakevenPrice, $balancedRaw,
            $maxMargin, $strategies, $recommended, $reason, $bundle, $delivery, $current, $recommendations, $warnings);
        $result['gp_comparison'] = $gpComparison;

        return $result;
    }

    /**
     * รายการแพ็กเกจร้านที่เปิดขายพร้อมอัตรา GP (ส่งเข้า plan() เป็น compare_gp_rates ได้เลย)
     *
     * @return array<int, array{key: string, label: string, gp_rate: float, monthly_price: float}>
     */
    public function packageGpOptions(): array
    {
        return \App\Models\VendorPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'package_slug', 'display_name', 'commission_rate', 'price'])
            ->map(fn ($package) => [
                'key' => (string) $package->package_slug,
                'label' => 'แพ็กเกจ '.$package->display_name,
                'gp_rate' => max($this->engine->minGpRate(), (float) $package->commission_rate),
                'monthly_price' => (float) $package->price,
            ])
            ->values()
            ->all();
    }

    /**
     * กำไรต่อชิ้นที่ราคาสมดุล ถ้าเปลี่ยนอัตรา GP เป็นของแต่ละแพ็กเกจ
     *
     * @param  array<int, array{key?: string, label?: string, gp_rate?: float}>  $rates
     * @return array<int, array{key: string, label: string, gp_rate: float, profit_per_unit: float, margin_percent: float, extra_profit_per_unit: float}>
     */
    private function gpComparison(array $rates, float $price, float $currentProfit): array
    {
        if ($price <= 0 || $rates === []) {
            return [];
        }

        $rows = [];
        foreach ($rates as $rate) {
            if (! is_array($rate) || ! isset($rate['gp_rate']) || ! is_numeric($rate['gp_rate'])) {
                continue;
            }

            $opts = $this->opts;
            $opts['gp_rate'] = max(0.0, min(100.0, (float) $rate['gp_rate']));
            $b = $this->engine->breakdown($price, 1, $opts);
            $profit = (float) ($b->profit ?? $b->seller_net);

            $rows[] = [
                'key' => (string) ($rate['key'] ?? ''),
                'label' => (string) ($rate['label'] ?? ('GP '.$this->fmt($opts['gp_rate']).'%')),
                'gp_rate' => $b->gp_rate,
                'profit_per_unit' => round($profit, 2),
                'margin_percent' => $b->gross > 0 ? round($profit / $b->gross * 100, 2) : 0.0,
                'extra_profit_per_unit' => round($profit - $currentProfit, 2),
            ];
        }

        // กำไรมากสุดขึ้นก่อน
        usort($rows, fn ($a, $b) => $b['profit_per_unit'] <=> $a['profit_per_unit']);

        return $rows;
    }

    /**
     * สรุปราคาตลาดจากรายการราคา (ใช้ได้ทั้ง e-commerce และตลาดสด)
     *
     * @param  array<int, float|int|string>  $prices
     * @return array{count: int, min: ?float, p25: ?float, median: ?float, p75: ?float, max: ?float}
     */
    public static function summarizeMarketPrices(array $prices): array
    {
        $clean = array_values(array_filter(
            array_map(fn ($p) => is_numeric($p) ? (float) $p : null, $prices),
            fn ($p) => $p !== null && $p > 0
        ));
        sort($clean);
        $count = count($clean);

        if ($count === 0) {
            return ['count' => 0, 'min' => null, 'p25' => null, 'median' => null, 'p75' => null, 'max' => null];
        }

        return [
            'count' => $count,
            'min' => round($clean[0], 2),
            'p25' => round(self::percentile($clean, 25), 2),
            'median' => round(self::percentile($clean, 50), 2),
            'p75' => round(self::percentile($clean, 75), 2),
            'max' => round($clean[$count - 1], 2),
        ];
    }

    /**
     * ราคากลางของหมวดสินค้า จากสินค้าที่ขายอยู่ของร้านอื่น (ใช้เป็น competitor_price ได้)
     *
     * @return array{count: int, min: ?float, p25: ?float, median: ?float, p75: ?float, max: ?float}
     */
    public function marketReferenceForCategory(int $categoryId, ?int $excludeSellerId = null, int $limit = 500): array
    {
        $query = Product::query()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->where('is_blocked', false)
            ->where('is_hidden', false)
            ->where('price', '>', 0);

        if ($excludeSellerId !== null) {
            $query->where('seller_id', '!=', $excludeSellerId);
        }

        $prices = $query->latest('id')->limit(max(1, min($limit, 2000)))->pluck('price')->all();

        return self::summarizeMarketPrices($prices);
    }

    /**
     * ปัดราคาขึ้นให้ลงท้ายเลขที่ลูกค้ารับรู้ว่าถูก (charm pricing)
     * <20 → บาทเต็ม, 20-99 → ลงท้าย 5 หรือ 9, 100-1,999 → ลงท้าย 9, ≥2,000 → ลงท้าย 90
     */
    public function charmUp(float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        $eps = 0.000001;
        if ($price < 20) {
            return (float) ceil($price - $eps);
        }

        if ($price < 100) {
            // เลื่อนขึ้นทีละบาทจนลงท้าย 5 หรือ 9 (ไม่เกิน 10 รอบ)
            $candidate = (int) ceil($price - $eps);
            while ($candidate % 10 !== 5 && $candidate % 10 !== 9) {
                $candidate++;
            }

            return $candidate >= 100 ? $this->charmUp(100.0) : (float) $candidate;
        }

        if ($price < 2000) {
            $candidate = ceil(($price + 1 - $eps) / 10) * 10 - 1;

            return $candidate >= 2000 ? $this->charmUp(2000.0) : (float) $candidate;
        }

        return (float) (ceil(($price + 10 - $eps) / 100) * 100 - 10);
    }

    /**
     * ปัดราคาลงให้ลงท้ายเลขสวย (ไม่เกินราคาที่ให้มา)
     */
    public function charmDown(float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        $eps = 0.000001;
        if ($price < 20) {
            return max(0.0, (float) floor($price + $eps));
        }

        if ($price < 100) {
            // เลื่อนลงทีละบาทจนลงท้าย 5 หรือ 9 (ต่ำกว่า 20 ใช้บาทเต็มได้เลย)
            $candidate = (int) floor($price + $eps);
            while ($candidate >= 20 && $candidate % 10 !== 5 && $candidate % 10 !== 9) {
                $candidate--;
            }

            return (float) $candidate;
        }

        if ($price < 2000) {
            return (float) (floor(($price + 1 + $eps) / 10) * 10 - 1);
        }

        return (float) (floor(($price + 10 + $eps) / 100) * 100 - 10);
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        if (! isset($input['cost']) || ! is_numeric($input['cost']) || (float) $input['cost'] < 0) {
            throw new InvalidArgumentException('กรุณาระบุต้นทุนต่อชิ้นเป็นตัวเลขที่ไม่ติดลบ');
        }

        $competitor = isset($input['competitor_price']) && is_numeric($input['competitor_price']) && (float) $input['competitor_price'] > 0
            ? round((float) $input['competitor_price'], 2)
            : null;

        $cost = round((float) $input['cost'], 2);
        if ($cost <= 0 && $competitor === null) {
            throw new InvalidArgumentException('กรุณาระบุต้นทุนต่อชิ้น หรือราคาคู่แข่งอย่างน้อยหนึ่งอย่าง');
        }

        $delivery = in_array($input['delivery'] ?? 'none', ['none', 'rider', 'parcel'], true) ? ($input['delivery'] ?? 'none') : 'none';

        $num = fn (string $key, float $default, float $min = 0.0, float $max = PHP_FLOAT_MAX): float => isset($input[$key]) && is_numeric($input[$key])
            ? max($min, min($max, (float) $input[$key]))
            : $default;

        return [
            'cost' => $cost,
            'target_margin_percent' => round($num('target_margin_percent', self::DEFAULT_TARGET_MARGIN, 0.0, 90.0), 2),
            'competitor_price' => $competitor,
            'monthly_volume' => isset($input['monthly_volume']) && is_numeric($input['monthly_volume']) && (int) $input['monthly_volume'] >= 0
                ? (int) $input['monthly_volume']
                : null,
            'fixed_monthly_cost' => round($num('fixed_monthly_cost', 0.0), 2),
            'gp_rate' => round($num('gp_rate', $this->engine->defaultGpRate(), 0.0, 100.0), 2),
            'pv' => $num('pv', 0.0),
            'mlm_enabled' => array_key_exists('mlm_enabled', $input) ? (bool) $input['mlm_enabled'] : $this->engine->mlmEnabled(),
            'commission_per_pv' => $num('commission_per_pv', $this->engine->commissionPerPv()),
            'vat_registered' => (bool) ($input['vat_registered'] ?? false),
            'payment_fee_rate' => $num('payment_fee_rate', 0.0, 0.0, 100.0),
            'delivery' => $delivery,
            'delivery_fee' => isset($input['delivery_fee']) && is_numeric($input['delivery_fee']) && (float) $input['delivery_fee'] >= 0
                ? round((float) $input['delivery_fee'], 2)
                : null,
            'avg_distance_km' => $num('avg_distance_km', self::DEFAULT_AVG_DISTANCE_KM, 0.0, 100.0),
            'current_price' => isset($input['current_price']) && is_numeric($input['current_price']) && (float) $input['current_price'] > 0
                ? round((float) $input['current_price'], 2)
                : null,
            'compare_gp_rates' => isset($input['compare_gp_rates']) && is_array($input['compare_gp_rates'])
                ? array_slice(array_values($input['compare_gp_rates']), 0, 10)
                : [],
        ];
    }

    private function quote(float $price): PriceBreakdown
    {
        return $this->engine->breakdown(max(0.0, $price), 1, $this->opts);
    }

    /**
     * กำไร % ที่ราคานี้ (ไม่ปัดเศษ เพื่อให้ค้นหาราคาได้แม่น)
     */
    private function marginAt(float $price): float
    {
        if ($price <= 0) {
            return -INF;
        }

        $b = $this->quote($price);

        return $b->gross > 0 ? (float) ($b->profit ?? $b->seller_net) / $b->gross * 100 : -INF;
    }

    /**
     * หาราคาต่ำสุดที่ได้กำไร ≥ margin% (ค้นหาแบบแบ่งครึ่ง บนสูตรจริงของ engine ทั้งเพดาน/การปัดเศษ)
     */
    private function priceForMargin(float $margin): ?float
    {
        $hi = max(1.0, $this->cost * 2);
        $guard = 0;
        while ($this->marginAt($hi) < $margin && $guard < 40) {
            $hi *= 2;
            $guard++;
        }
        if ($this->marginAt($hi) < $margin) {
            return null;
        }

        $lo = 0.0;
        for ($i = 0; $i < 80 && ($hi - $lo) > 0.001; $i++) {
            $mid = ($lo + $hi) / 2;
            if ($this->marginAt($mid) >= $margin) {
                $hi = $mid;
            } else {
                $lo = $mid;
            }
        }

        $price = ceil($hi * 100) / 100;
        // กันการปัดเศษสตางค์ทำให้ต่ำกว่าเป้าเล็กน้อย
        while ($this->marginAt($price) < $margin && $price < $hi + 1) {
            $price = round($price + 0.01, 2);
        }

        return $price;
    }

    /**
     * @return array<string, mixed>
     */
    private function strategy(string $key, float $price, ?float $rawPrice, float $targetMargin, array $in): array
    {
        $meta = [
            self::STRATEGY_PENETRATION => [
                'name_th' => 'เจาะตลาด',
                'description_th' => 'ราคาต่ำกว่าตลาดเล็กน้อย เร่งยอดขายและรีวิวช่วงแรก กำไรต่อชิ้นน้อย',
                'fit_th' => 'เหมาะกับสินค้าใหม่ที่ยังไม่มีรีวิว สินค้าที่มีคู่แข่งเยอะ หรือช่วงเปิดร้าน',
            ],
            self::STRATEGY_BALANCED => [
                'name_th' => 'สมดุล',
                'description_th' => 'ราคาใกล้ตลาด ได้กำไรตามเป้าและยังขายได้ต่อเนื่อง',
                'fit_th' => 'เหมาะกับสินค้าขายประจำที่มีฐานลูกค้าแล้ว',
            ],
            self::STRATEGY_PREMIUM => [
                'name_th' => 'พรีเมียม',
                'description_th' => 'ราคาสูงกว่าตลาด กำไรต่อชิ้นสูง ต้องมีจุดเด่นชัดเจน',
                'fit_th' => 'เหมาะกับสินค้าคุณภาพสูง แพ็กเกจสวย มีรีวิวดี หรือส่งไวกว่าคู่แข่ง',
            ],
        ][$key];

        $b = $this->quote($price);
        $profit = (float) ($b->profit ?? $b->seller_net);
        $competitor = $in['competitor_price'];

        $breakevenUnits = null;
        if ($profit > 0) {
            $breakevenUnits = $in['fixed_monthly_cost'] > 0 ? (int) ceil($in['fixed_monthly_cost'] / $profit) : 0;
        }

        $monthly = null;
        if ($in['monthly_volume'] !== null) {
            $volume = (int) round($in['monthly_volume'] * self::VOLUME_FACTOR[$key]);
            $monthly = [
                'volume' => $volume,
                'revenue' => round($b->gross * $volume, 2),
                'seller_net' => round($b->seller_net * $volume, 2),
                'profit' => round($profit * $volume - $in['fixed_monthly_cost'], 2),
                'volume_assumption_th' => match ($key) {
                    self::STRATEGY_PENETRATION => 'สมมติขายได้มากกว่าราคาสมดุลราว 30% (ประมาณการคร่าวๆ)',
                    self::STRATEGY_PREMIUM => 'สมมติขายได้น้อยกว่าราคาสมดุลราว 30% (ประมาณการคร่าวๆ)',
                    default => 'ใช้ยอดขายต่อเดือนที่คุณกรอก',
                },
            ];
        }

        return [
            'key' => $key,
            'name_th' => $meta['name_th'],
            'description_th' => $meta['description_th'],
            'fit_th' => $meta['fit_th'],
            'price' => $price,
            'formula_price' => $rawPrice,
            'target_margin_percent' => round($targetMargin, 2),
            'profit_per_unit' => round($profit, 2),
            'margin_percent' => $b->gross > 0 ? round($profit / $b->gross * 100, 2) : 0.0,
            'markup_percent' => $this->cost > 0 ? round($profit / $this->cost * 100, 2) : null,
            'seller_net_per_unit' => $b->seller_net,
            'breakeven_units' => $breakevenUnits,
            'vs_competitor_percent' => $competitor !== null ? round(($price - $competitor) / $competitor * 100, 2) : null,
            'monthly' => $monthly,
            'breakdown' => $b->toArray(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function emptyStrategies(): array
    {
        $names = [
            self::STRATEGY_PENETRATION => 'เจาะตลาด',
            self::STRATEGY_BALANCED => 'สมดุล',
            self::STRATEGY_PREMIUM => 'พรีเมียม',
        ];

        $list = [];
        foreach ($names as $key => $name) {
            $list[] = [
                'key' => $key,
                'name_th' => $name,
                'description_th' => 'ไม่มีราคาที่ทำกำไรได้ด้วยอัตราค่าธรรมเนียมปัจจุบัน',
                'fit_th' => '',
                'price' => null,
                'formula_price' => null,
                'target_margin_percent' => null,
                'profit_per_unit' => null,
                'margin_percent' => null,
                'markup_percent' => null,
                'seller_net_per_unit' => null,
                'breakeven_units' => null,
                'vs_competitor_percent' => null,
                'monthly' => null,
                'breakdown' => null,
            ];
        }

        return $list;
    }

    /**
     * ข้อเสนอขายเป็นชุด 3 ชิ้น ลดราว 5% — เสนอเฉพาะเมื่อยังได้กำไร ≥ กำไรขั้นต่ำ
     *
     * @return array<string, mixed>|null
     */
    private function bundlePlan(?float $balancedPrice, float $minMargin): ?array
    {
        if ($balancedPrice === null || $balancedPrice <= 0) {
            return null;
        }

        $qty = 3;
        $bundlePrice = $this->charmDown($balancedPrice * $qty * 0.95);
        if ($bundlePrice <= 0) {
            return null;
        }

        // ชุด 3 ชิ้นคิดเป็น 1 รายการ: ต้นทุนและ PV ต้องคูณ 3 ด้วย
        $opts = $this->opts;
        $opts['cost_per_unit'] = $this->cost * $qty;
        $opts['pv'] = (float) ($opts['pv'] ?? 0) * $qty;
        $b = $this->engine->breakdown($bundlePrice, 1, $opts);
        $profit = (float) ($b->profit ?? $b->seller_net);
        $margin = $b->gross > 0 ? $profit / $b->gross * 100 : 0.0;

        if ($margin < $minMargin) {
            return null;
        }

        return [
            'quantity' => $qty,
            'price' => $bundlePrice,
            'price_per_unit' => round($bundlePrice / $qty, 2),
            'discount_percent' => round((1 - $bundlePrice / ($balancedPrice * $qty)) * 100, 2),
            'profit' => round($profit, 2),
            'margin_percent' => round($margin, 2),
            'breakdown' => $b->toArray(),
        ];
    }

    /**
     * ประมาณค่าส่งและเกณฑ์ส่งฟรีที่ร้านยังมีกำไร
     *
     * @return array<string, mixed>
     */
    private function deliveryPlan(array $in, ?float $balancedPrice, ?float $balancedMargin): array
    {
        $mode = $in['delivery'];
        if ($mode === 'none') {
            return ['mode' => 'none', 'estimated_fee' => null, 'fee_basis_th' => null, 'free_delivery_threshold' => null, 'units_for_free_delivery' => null];
        }

        if ($mode === 'rider') {
            $base = $this->numericSetting('rider.base_fee', (float) DeliveryFeeCalculator::DEFAULTS['rider.base_fee']);
            $perKm = $this->numericSetting('rider.per_km_fee', (float) DeliveryFeeCalculator::DEFAULTS['rider.per_km_fee']);
            $freeKm = $this->numericSetting('rider.free_km', (float) DeliveryFeeCalculator::DEFAULTS['rider.free_km']);
            $minFee = $this->numericSetting('rider.min_fee', (float) DeliveryFeeCalculator::DEFAULTS['rider.min_fee']);
            $km = $in['avg_distance_km'];
            // ใช้สูตรค่าส่งไรเดอร์ชุดเดียวกับตอนสร้างงานจริง (DeliveryFeeCalculator) — ส่งค่าตั้งเข้าไปตรงๆ
            $estimated = (new DeliveryFeeCalculator([
                'rider.base_fee' => $base,
                'rider.per_km_fee' => $perKm,
                'rider.free_km' => $freeKm,
                'rider.min_fee' => $minFee,
            ]))->quoteForDistance($km)['total_fee'];
            $fee = $in['delivery_fee'] ?? (float) $estimated;
            $basis = $in['delivery_fee'] !== null
                ? 'ค่าส่งที่คุณระบุ'
                : 'ค่าไรเดอร์โดยประมาณ: เริ่มต้น '.$this->fmt($base).' บาท ('.$this->fmt($freeKm).' กม.แรก) + '.$this->fmt($perKm).' บาท/กม. ระยะเฉลี่ย '.$this->fmt($km).' กม.';
        } else {
            $fee = $in['delivery_fee'] ?? (float) ShippingService::DEFAULT_SHIPPING_FEE;
            $basis = $in['delivery_fee'] !== null ? 'ค่าส่งที่คุณระบุ' : 'ค่าส่งพัสดุมาตรฐานของระบบ';
        }

        // ส่งฟรีเมื่อ: ค่าส่งไม่เกินครึ่งหนึ่งของกำไรในบิลนั้น → ยอดบิล ≥ 2 × ค่าส่ง ÷ กำไร%
        $threshold = null;
        $units = null;
        if ($balancedPrice !== null && $balancedPrice > 0 && $balancedMargin !== null && $balancedMargin > 0 && $fee > 0) {
            $raw = max($balancedPrice, 2 * $fee / ($balancedMargin / 100));
            $step = $raw < 1000 ? 50 : 100;
            $threshold = (float) (ceil($raw / $step) * $step);
            $units = (int) ceil($threshold / $balancedPrice);
        }

        return [
            'mode' => $mode,
            'estimated_fee' => round($fee, 2),
            'fee_basis_th' => $basis,
            'free_delivery_threshold' => $threshold,
            'units_for_free_delivery' => $units,
        ];
    }

    /**
     * คำแนะนำภาษาไทยที่ทำตามได้จริง
     *
     * @param  array<int, array{code: string, message: string}>  $warnings  (เพิ่มคำเตือนเข้าไปได้)
     * @return array<int, string>
     */
    private function buildRecommendations(array $in, float $target, ?float $breakevenPrice, array $pen, array $bal, array $prem, ?array $bundle, array $delivery, array &$warnings): array
    {
        $rec = [];
        $competitor = $in['competitor_price'];
        $balPrice = (float) $bal['price'];

        // 1) ลงท้ายเลขสวย
        $plain = $balPrice >= 100 ? ceil(($balPrice + 1) / 10) * 10 : ceil($balPrice + 1);
        $rec[] = 'ตั้งราคาลงท้ายด้วยเลข 9 หรือ 5 เช่น '.$this->money($balPrice).' บาท แทน '.$this->money((float) $plain).' บาท ลูกค้ารับรู้ว่าถูกกว่าแม้ต่างกันนิดเดียว (ราคาในทั้ง 3 แผนปรับให้แล้ว)';

        // 2) จุดคุ้มทุน
        if ($breakevenPrice !== null) {
            $rec[] = 'ราคาคุ้มทุน (กำไร 0 บาท) คือ '.$this->money($breakevenPrice).' บาทต่อชิ้น ห้ามตั้งราคาหรือจัดโปรจนต่ำกว่านี้';
        }

        // 3) GP กินกำไรมากไหม
        $grossMargin = $balPrice - $in['cost'];
        $gpAmount = (float) ($bal['breakdown']['gp_amount'] ?? 0);
        if ($in['cost'] > 0 && $grossMargin > 0 && $gpAmount / $grossMargin >= 0.5) {
            $rec[] = 'ค่า GP '.$this->fmt($in['gp_rate']).'% กินส่วนต่างราคา-ต้นทุนไปถึง '.$this->fmt(round($gpAmount / $grossMargin * 100)).'% ลองลดต้นทุน (ซื้อล็อตใหญ่ขึ้น/หาซัพพลายเออร์ใหม่) หรืออัปเกรดแพ็กเกจร้านเพื่อลดอัตรา GP';
        }

        // 4) ตำแหน่งเทียบคู่แข่ง
        if ($competitor !== null) {
            if ($breakevenPrice !== null && $breakevenPrice > $competitor) {
                $warnings[] = $this->warning('COMPETITOR_BELOW_BREAKEVEN', 'ราคาคู่แข่ง '.$this->money($competitor).' บาท ต่ำกว่าราคาคุ้มทุนของคุณ ('.$this->money($breakevenPrice).' บาท) ด้วยต้นทุนและ GP ปัจจุบัน');
                $rec[] = 'ด้วยต้นทุน '.$this->money($in['cost']).' บาทและ GP '.$this->fmt($in['gp_rate']).'% คุณแข่งราคากับคู่แข่ง ('.$this->money($competitor).' บาท) ไม่ได้ — เลือกทางใดทางหนึ่ง: ลดต้นทุน, ขายเป็นชุดเพื่อลดต้นทุนต่อบิล, หรือขายแบบพรีเมียมที่ '.$this->money((float) $prem['price']).' บาทพร้อมจุดเด่นที่ชัดเจน';
            } elseif ($bal['formula_price'] !== null && $bal['formula_price'] > $competitor) {
                $warnings[] = $this->warning('COMPETITOR_BELOW_TARGET', 'ราคาที่ได้กำไรตามเป้า ('.$this->money($this->charmUp((float) $bal['formula_price'])).' บาท) สูงกว่าราคาคู่แข่ง '.$this->money($competitor).' บาท');
                $rec[] = 'ขายที่ราคาคู่แข่งได้แต่กำไรต่ำกว่าเป้า ถ้าจะตั้ง '.$this->money($balPrice).' บาท ให้เน้นจุดต่าง เช่น ของสดกว่า แพ็กดีกว่า ส่งไวกว่า หรือแถมของเล็กๆ';
            } elseif ((float) $pen['vs_competitor_percent'] < 0) {
                $rec[] = 'ราคาเจาะตลาด '.$this->money((float) $pen['price']).' บาท ถูกกว่าคู่แข่ง '.$this->fmt(abs((float) $pen['vs_competitor_percent'])).'% และยังมีกำไร '.$this->fmt((float) $pen['margin_percent']).'% — ใช้เป็นราคาเปิดตัวได้';
            } else {
                $rec[] = 'ราคาต่ำสุดที่ยังมีกำไร ('.$this->money((float) $pen['price']).' บาท) ใกล้เคียงราคาคู่แข่งแล้ว อย่าลดต่ำกว่านี้ ให้แข่งด้วยบริการ รีวิว และความสดของสินค้าแทน';
            }
        }

        // 5) ราคาเปิดตัวแบบซื่อตรง
        $rec[] = 'ใช้ราคาเจาะตลาดเป็นช่วงสั้นๆ (2-4 สัปดาห์) เพื่อเก็บรีวิวแรก แล้วค่อยขยับสู่ราคาสมดุล และบอกลูกค้าตรงๆ ว่าเป็น "ราคาเปิดตัว"';

        // 6) ราคาก่อนลด / โปร ภายใต้ขอบเขตที่ซื่อตรง
        if ($breakevenPrice !== null && $balPrice > 0) {
            $maxPromo = floor(max(0.0, ($balPrice - $this->charmUp($breakevenPrice)) / $balPrice * 100));
            // ส่วนลดที่ยังเหลือกำไรอย่างน้อยเท่ากำไรของราคาเจาะตลาด
            $safeFloor = $pen['formula_price'] !== null ? (float) $pen['formula_price'] : $breakevenPrice;
            $safePromo = floor(max(0.0, ($balPrice - $safeFloor) / $balPrice * 100));
            if ($maxPromo >= 1) {
                $rec[] = 'จัดโปรลดจากราคาสมดุลได้ไม่เกินราว '.$this->fmt($safePromo).'% เพื่อยังได้กำไรอย่างน้อย '.$this->fmt((float) $pen['target_margin_percent']).'% (ลดเกิน '.$this->fmt($maxPromo).'% = ขาดทุน) ส่วน "ราคาก่อนลด" ที่โชว์ ต้องเป็นราคาที่เคยขายจริง ห้ามตั้งราคาสูงเกินจริงเพื่อให้ดูลดเยอะ (ผิดกฎหมายคุ้มครองผู้บริโภค)';
            } else {
                $rec[] = 'ราคาสมดุลใกล้จุดคุ้มทุนมาก ไม่ควรจัดโปรลดราคา ส่วน "ราคาก่อนลด" ถ้าจะโชว์ ต้องเป็นราคาที่เคยขายจริงเท่านั้น';
            }
        } else {
            $rec[] = 'ถ้าจะโชว์ "ราคาก่อนลด" ต้องเป็นราคาที่เคยขายจริงเท่านั้น ห้ามตั้งราคาสูงเกินจริงเพื่อให้ดูลดเยอะ';
        }

        // 7) ขายเป็นชุด / ส่วนลดตามจำนวน
        if ($bundle !== null) {
            $rec[] = 'จัดชุด '.$bundle['quantity'].' ชิ้น ราคา '.$this->money((float) $bundle['price']).' บาท (เฉลี่ยชิ้นละ '.$this->money((float) $bundle['price_per_unit']).' บาท ลด '.$this->fmt((float) $bundle['discount_percent']).'%) ยังได้กำไร '.$this->fmt((float) $bundle['margin_percent']).'% ช่วยเพิ่มยอดต่อบิลและคุ้มค่าส่งกว่า';
        } elseif ($bal['price'] !== null && $in['cost'] > 0) {
            $rec[] = 'กำไรต่อชิ้นยังบางเกินกว่าจะลดราคาชุดได้ ลองขายคู่กับสินค้าอื่นที่กำไรสูงกว่า (ขายพ่วง) แทนการลดราคา';
        }

        // 8) ส่งฟรี
        if ($delivery['mode'] !== 'none' && $delivery['free_delivery_threshold'] !== null) {
            $label = $delivery['mode'] === 'rider' ? 'ค่าไรเดอร์' : 'ค่าส่ง';
            $rec[] = 'ตั้ง "ส่งฟรีเมื่อซื้อครบ '.$this->money((float) $delivery['free_delivery_threshold']).' บาท" (ประมาณ '.$delivery['units_for_free_delivery'].' ชิ้น) ต่ำกว่านั้นให้ลูกค้าจ่าย'.$label.'ราว '.$this->money((float) $delivery['estimated_fee']).' บาท — ร้านออกค่าส่งไม่เกินครึ่งหนึ่งของกำไรในบิล';
        } elseif ($delivery['mode'] === 'rider') {
            $rec[] = 'ส่งด้วยไรเดอร์ให้ลูกค้าจ่ายค่าส่งเอง ราว '.$this->money((float) $delivery['estimated_fee']).' บาท เพราะกำไรต่อชิ้นยังไม่พอให้ส่งฟรี';
        }

        // 9) ค่าแนะนำ (PV)
        $pool = (float) ($bal['breakdown']['referral_pool_amount'] ?? 0);
        if ($pool > 0 && $balPrice > 0) {
            $share = $pool / $balPrice * 100;
            if ($share > 15) {
                $warnings[] = $this->warning('HIGH_REFERRAL_SHARE', 'ค่าแนะนำ '.$this->money($pool).' บาทต่อชิ้น คิดเป็น '.$this->fmt(round($share, 1)).'% ของราคา');
                $rec[] = 'ค่าแนะนำ (PV) ที่ตั้งไว้คิดเป็น '.$this->fmt(round($share, 1)).'% ของราคา สูงเกินไป ลองลด PV ลงเพื่อให้กำไรเหลือมากขึ้นหรือราคาแข่งขันได้';
            } else {
                $rec[] = 'ค่าแนะนำ (PV) '.$this->money($pool).' บาทต่อชิ้น ('.$this->fmt(round($share, 1)).'% ของราคา) ช่วยให้มีคนช่วยแนะนำสินค้า อยู่ในระดับที่เหมาะสม';
            }
        }

        // 10) ค่าใช้จ่ายคงที่ / ยอดขายต่อเดือน
        if ($in['fixed_monthly_cost'] > 0 && $bal['breakeven_units'] !== null) {
            $rec[] = 'ต้องขายให้ได้อย่างน้อย '.$bal['breakeven_units'].' ชิ้นต่อเดือนที่ราคาสมดุล จึงจะคุ้มค่าใช้จ่ายคงที่ '.$this->money($in['fixed_monthly_cost']).' บาท/เดือน';
            if ($in['monthly_volume'] !== null && $in['monthly_volume'] < $bal['breakeven_units']) {
                $warnings[] = $this->warning('MONTHLY_VOLUME_BELOW_BREAKEVEN', 'ยอดขายที่คาด '.$in['monthly_volume'].' ชิ้น/เดือน ยังไม่ถึงจุดคุ้มทุน '.$bal['breakeven_units'].' ชิ้น');
            }
        }

        // 11) VAT
        if (! $in['vat_registered']) {
            $rec[] = 'ร้านยังไม่ได้จดทะเบียน VAT จึงไม่ถูกหัก VAT — ถ้ารายได้ทั้งปีเกิน '.number_format(self::VAT_REGISTRATION_THRESHOLD).' บาท ต้องจด VAT และระบบจะถอด VAT 7/107 ออกจากราคา ควรเผื่อราคาไว้ล่วงหน้า';
        }

        return $rec;
    }

    /**
     * @return array<string, mixed>
     */
    private function result(array $in, ?float $breakevenPrice, ?float $minPriceForTarget, float $maxMargin, array $strategies,
        string $recommended, string $reason, ?array $bundle, array $delivery, ?array $current, array $recommendations, array $warnings): array
    {
        return [
            'input' => $in,
            'breakeven_price' => $breakevenPrice,
            'min_price_for_target' => $minPriceForTarget,
            'max_margin_percent' => is_finite($maxMargin) ? round($maxMargin, 2) : 0.0,
            'strategies' => $strategies,
            'recommended' => $recommended,
            'recommended_reason_th' => $reason,
            'bundle' => $bundle,
            'delivery' => $delivery,
            'current' => $current,
            'gp_comparison' => [],
            'recommendations' => array_values($recommendations),
            'warnings' => array_values($warnings),
            'formula_steps' => PricingEngine::formulaStepsTh(),
        ];
    }

    private static function percentile(array $sorted, float $p): float
    {
        $count = count($sorted);
        if ($count === 1) {
            return (float) $sorted[0];
        }

        $rank = ($p / 100) * ($count - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);
        $weight = $rank - $low;

        return (float) ($sorted[$low] + ($sorted[$high] - $sorted[$low]) * $weight);
    }

    private function numericSetting(string $key, float $default): float
    {
        $value = $this->engine->setting($key, $default);

        return is_numeric($value) ? max(0.0, (float) $value) : $default;
    }

    /**
     * @return array{code: string, message: string}
     */
    private function warning(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }

    private function fmt(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $text === '' || $text === '-0' ? '0' : $text;
    }

    private function money(float $value): string
    {
        return floor($value) == $value ? number_format($value) : number_format($value, 2);
    }
}
