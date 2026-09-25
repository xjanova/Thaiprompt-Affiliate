<?php

namespace App\Services\Pricing;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketSetting;
use App\Models\MlmGlobalSetting;
use App\Models\MlmProductPv;
use App\Models\Product;
use App\Models\Setting;
use App\Models\VendorStore;
use Closure;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * เครื่องคำนวณราคากลางของระบบ (แหล่งเดียวของสูตร GP / VAT / ค่าแนะนำ / เงินสุทธิผู้ขาย)
 *
 * เดิมมีสูตร 4 ชุดที่ไม่ตรงกัน (ตัวจำลองในฟอร์มสินค้า, CheckoutController, OrderDistributionService,
 * CalculatesEarnings) ทำให้ผู้ขายเห็นรายได้ไม่ตรงกับที่ได้จริง — ทุกจุดต้องเรียกคลาสนี้แทน
 *
 * สูตร (ต่อ 1 รายการสินค้า):
 *  1. ยอดขาย = ราคาต่อหน่วย × จำนวน (ราคาที่ลูกค้าเห็น รวม VAT แล้ว)
 *  2. GP = ยอดขาย × อัตรา GP%
 *  3. VAT = ยอดขาย × 7/107 เฉพาะร้านที่จดทะเบียน VAT (ร้านที่ไม่ได้จด = 0)
 *  4. ค่าแนะนำ = เฉพาะเมื่อเปิดระบบแนะนำ: PV ต่อชิ้น × จำนวน × บาทต่อ PV
 *     (ไม่มี PV → ใช้ pricing.referral_pool_percent % ของยอดขาย ซึ่งค่าเริ่มต้น = 0)
 *     และไม่เกิน max_commission_percentage % ของยอดขาย และไม่เกินยอดที่เหลือหลังหัก GP+VAT
 *  5. ค่าธรรมเนียมรับชำระ = ยอดขาย × อัตรา% (ถ้ามี)
 *  6. ผู้ขายได้สุทธิ = ยอดขาย − GP − VAT − ค่าแนะนำ − ค่าธรรมเนียม − เงินคืนที่ร้านออก − ค่าส่งที่ร้านออก
 *  7. แพลตฟอร์มได้สุทธิ = GP − เงินคืนที่แพลตฟอร์มออก
 *
 * ใช้งาน: app(PricingEngine::class) — ไม่ผูกเป็น singleton เพื่อให้ค่าตั้งใหม่มีผลทันที
 * (ค่าตั้งถูก memo ไว้ต่อ instance เท่านั้น)
 */
class PricingEngine
{
    // ===== ค่าเริ่มต้น (ใช้เมื่อยังไม่มีค่าใน settings) =====
    public const DEFAULT_GP_RATE = 15.0;

    public const DEFAULT_MIN_GP_RATE = 5.0;

    public const DEFAULT_VAT_RATE = 7.0;

    public const DEFAULT_REFERRAL_POOL_PERCENT = 0.0;

    public const DEFAULT_REFERRAL_POOL_CAP_PERCENT = 50.0;

    public const DEFAULT_FRESH_MARKET_GP_RATE = 3.0;

    public const MAX_RATE = 100.0;

    // ===== คีย์ settings (ตาราง settings, group 'pricing') =====
    public const KEY_DEFAULT_GP_RATE = 'pricing.default_gp_rate';

    public const KEY_MIN_GP_RATE = 'pricing.min_gp_rate';

    public const KEY_VAT_RATE = 'pricing.vat_rate';

    public const KEY_REFERRAL_POOL_PERCENT = 'pricing.referral_pool_percent';

    public const KEY_FRESH_MARKET_GP_RATE = 'pricing.fresh_market_gp_rate';

    public const KEY_OFFICIAL_SHOP_VAT_REGISTERED = 'pricing.official_shop_vat_registered';

    /** โปรฯ "GP ฟรีช่วงเปิดตัว" (boolean) — เปิดอยู่ = GP 0% ทุกสินค้า ยกเว้นสินค้าที่แอดมินตั้ง admin_gp_rate ไว้ */
    public const KEY_GP_FREE = 'pricing.gp_free';

    /** วันสิ้นสุดโปรฯ GP ฟรี (ว่าง = ไม่มีกำหนด / "YYYY-MM-DD" = ฟรีถึงสิ้นวันนั้น) */
    public const KEY_GP_FREE_UNTIL = 'pricing.gp_free_until';

    // ===== คีย์ที่อ่านจากแหล่งอื่น (prefix บอกตาราง) =====
    public const KEY_MLM_ENABLED = 'mlm:mlm_enabled';

    public const KEY_COMMISSION_PER_PV = 'mlm:commission_per_pv';

    public const KEY_MAX_COMMISSION_PERCENT = 'mlm:max_commission_percentage';

    public const KEY_DEFAULT_MLM_PLAN_ID = 'mlm:default_mlm_plan_id';

    public const KEY_FRESH_PLATFORM_FEE = 'fresh:platform_fee_percentage';

    public const KEY_FRESH_FEE_MODE = 'fresh:fee_mode';

    // ===== แหล่งที่มาของอัตรา GP =====
    public const GP_SOURCE_OFFICIAL = 'official_shop';

    public const GP_SOURCE_ADMIN = 'admin_override';

    public const GP_SOURCE_PACKAGE = 'package';

    public const GP_SOURCE_STORE = 'store';

    public const GP_SOURCE_DEFAULT = 'default';

    public const GP_SOURCE_PROMO = 'launch_promo';

    /** ตัวอ่านค่าตั้ง fn(string $key, mixed $default): mixed — ฉีดได้ในเทสต์ */
    private ?Closure $settingResolver;

    /** ตัวหา user_id ของร้านทางการ fn(): ?int — ฉีดได้ในเทสต์ */
    private ?Closure $officialSellerIdResolver;

    /** @var array<string, mixed> memo ค่าตั้งภายใน instance */
    private array $settingCache = [];

    private bool $officialSellerResolved = false;

    private ?int $officialSellerId = null;

    /** @var array<int, VendorStore|null> memo ร้านตาม seller_id */
    private array $storeBySeller = [];

    public function __construct(?Closure $settingResolver = null, ?Closure $officialSellerIdResolver = null)
    {
        $this->settingResolver = $settingResolver;
        $this->officialSellerIdResolver = $officialSellerIdResolver;
    }

    // =====================================================================
    // อัตรา GP
    // =====================================================================

    /**
     * อัตรา GP (%) ของสินค้า e-commerce
     *
     * ลำดับอำนาจ: ร้านทางการ (0) → admin_gp_rate ที่แอดมินตั้ง → อัตราตามแพ็กเกจร้าน
     * → vendor_stores.commission_rate (ร้านที่ไม่มีแพ็กเกจ, แอดมินตั้ง) → pricing.default_gp_rate
     * แล้วบีบไม่ให้ต่ำกว่า pricing.min_gp_rate — ค่า products.commission_rate ที่ผู้ขายส่งมาไม่ถูกใช้
     */
    public function gpRateForProduct(Product $product): float
    {
        return $this->gpRateInfoForProduct($product)['rate'];
    }

    /**
     * อัตรา GP พร้อมที่มา (ไว้แสดงให้ผู้ขาย/แอดมินเห็นว่าทำไมได้อัตรานี้)
     *
     * @return array{rate: float, source: string, label_th: string, clamped: bool}
     */
    public function gpRateInfoForProduct(Product $product): array
    {
        if ($this->isOfficialProduct($product)) {
            return [
                'rate' => 0.0,
                'source' => self::GP_SOURCE_OFFICIAL,
                'label_th' => 'สินค้าร้านทางการของแพลตฟอร์ม ไม่มีค่า GP',
                'clamped' => false,
            ];
        }

        $adminRate = $product->getAttribute('admin_gp_rate');
        if ($adminRate !== null && $adminRate !== '' && is_numeric($adminRate)) {
            return $this->clampGp((float) $adminRate, self::GP_SOURCE_ADMIN, 'อัตรา GP ที่แอดมินกำหนดให้สินค้านี้');
        }

        // โปรฯ GP ฟรีช่วงเปิดตัว → ทับอัตราตามแพ็กเกจ/ร้าน/ค่ากลาง (ไม่บีบขึ้นเป็นขั้นต่ำ)
        if ($this->gpPromoActive()) {
            return [
                'rate' => 0.0,
                'source' => self::GP_SOURCE_PROMO,
                'label_th' => $this->gpPromoLabel(),
                'clamped' => false,
            ];
        }

        $store = $this->storeForProduct($product);
        if ($store !== null) {
            $package = $this->packageForStore($store);
            if ($package !== null && is_numeric($package->getAttribute('commission_rate'))) {
                $packageName = $package->getAttribute('display_name') ?: $package->getAttribute('package_name');

                return $this->clampGp(
                    (float) $package->getAttribute('commission_rate'),
                    self::GP_SOURCE_PACKAGE,
                    $packageName ? "อัตรา GP ตามแพ็กเกจร้าน {$packageName}" : 'อัตรา GP ตามแพ็กเกจร้าน'
                );
            }

            if (is_numeric($store->getAttribute('commission_rate'))) {
                return $this->clampGp(
                    (float) $store->getAttribute('commission_rate'),
                    self::GP_SOURCE_STORE,
                    'อัตรา GP ของร้าน (กำหนดโดยแอดมิน)'
                );
            }
        }

        return $this->clampGp($this->defaultGpRate(), self::GP_SOURCE_DEFAULT, 'อัตรา GP มาตรฐานของแพลตฟอร์ม');
    }

    /**
     * อัตรา GP (%) ของสินค้าตลาดสด
     *
     * pricing.fresh_market_gp_rate (ถ้าแอดมินตั้งไว้) → ถ้า fee_mode = subscription (เก็บแต่ค่าสมาชิก) = 0
     * → fresh_market_settings.platform_fee_percentage — ค่า commission_rate บน listing ไม่ถูกใช้
     */
    public function gpRateForFreshListing(FreshMarketListing $listing): float
    {
        // โปรฯ GP ฟรีช่วงเปิดตัว ใช้กับตลาดสดด้วย
        if ($this->gpPromoActive()) {
            return 0.0;
        }

        $explicit = $this->setting(self::KEY_FRESH_MARKET_GP_RATE, null);
        if ($explicit !== null && $explicit !== '' && is_numeric($explicit)) {
            return $this->pct((float) $explicit);
        }

        if ((string) $this->setting(self::KEY_FRESH_FEE_MODE, 'both') === 'subscription') {
            return 0.0;
        }

        return $this->pct($this->numericSetting(self::KEY_FRESH_PLATFORM_FEE, self::DEFAULT_FRESH_MARKET_GP_RATE));
    }

    // =====================================================================
    // คำนวณราคา
    // =====================================================================

    /**
     * คำนวณการแบ่งเงินของ 1 รายการ
     *
     * opts (ทุกตัวไม่บังคับ — ไม่ส่งมาจะใช้ค่าตั้งของระบบ):
     *  - gp_rate (float %)                 อัตรา GP
     *  - pv (float)                        PV ต่อชิ้น
     *  - mlm_enabled (bool)                เปิดระบบแนะนำ/MLM หรือไม่ (ปิด = ค่าแนะนำ 0)
     *  - commission_per_pv (float)         บาทต่อ 1 PV
     *  - referral_pool_percent (float %)   ค่าแนะนำ % ของยอดขาย ใช้เมื่อไม่มี PV เท่านั้น
     *  - referral_pool_cap_percent (float %) เพดานค่าแนะนำ % ของยอดขาย
     *  - vat_registered (bool)             ร้านจดทะเบียน VAT หรือไม่ (ค่าเริ่มต้น false)
     *  - vat_rate (float %)                อัตรา VAT
     *  - cashback_rate (float %)           เงินคืนลูกค้า % ของยอดขาย
     *  - cashback_funded_by ('platform'|'seller') ใครออกเงินคืน (ค่าเริ่มต้น platform)
     *  - shipping_fee (float)              ค่าส่งที่ลูกค้าจ่าย (ส่งผ่าน ไม่กระทบเงินผู้ขาย)
     *  - seller_shipping_subsidy (float)   ค่าส่งที่ร้านออกให้ (หักจากเงินผู้ขาย)
     *  - payment_fee_rate (float %)        ค่าธรรมเนียมรับชำระเงินที่หักจากผู้ขาย
     *  - cost_per_unit (float|null)        ต้นทุนต่อชิ้น → ได้ profit / margin_percent
     *
     * @throws InvalidArgumentException เมื่อราคาติดลบหรือจำนวน < 1
     */
    public function breakdown(float $unitPrice, int $qty, array $opts = []): PriceBreakdown
    {
        if (! is_finite($unitPrice) || $unitPrice < 0) {
            throw new InvalidArgumentException('ราคาต่อหน่วยต้องเป็นตัวเลขที่ไม่ติดลบ');
        }
        if ($qty < 1) {
            throw new InvalidArgumentException('จำนวนสินค้าต้องมีอย่างน้อย 1 ชิ้น');
        }

        // ----- อ่าน/ทำความสะอาดตัวเลือก -----
        $gpRate = $this->pct($opts['gp_rate'] ?? $this->defaultGpRate());
        $mlmEnabled = array_key_exists('mlm_enabled', $opts)
            ? (bool) $opts['mlm_enabled']
            : $this->mlmEnabled();
        $pvPerUnit = $this->nonNegative($opts['pv'] ?? 0);
        $commissionPerPv = $this->nonNegative($opts['commission_per_pv'] ?? $this->commissionPerPv());
        $poolPercent = $this->pct($opts['referral_pool_percent'] ?? $this->referralPoolPercent());
        $poolCapPercent = $this->pct($opts['referral_pool_cap_percent'] ?? $this->referralPoolCapPercent());
        $vatRegistered = (bool) ($opts['vat_registered'] ?? false);
        $vatRate = $vatRegistered ? $this->pct($opts['vat_rate'] ?? $this->vatRate()) : 0.0;
        $cashbackRate = $this->pct($opts['cashback_rate'] ?? 0);
        $cashbackFundedBy = ($opts['cashback_funded_by'] ?? 'platform') === 'seller' ? 'seller' : 'platform';
        $shippingFee = $this->money($this->nonNegative($opts['shipping_fee'] ?? 0));
        $subsidy = $this->money($this->nonNegative($opts['seller_shipping_subsidy'] ?? 0));
        $paymentFeeRate = $this->pct($opts['payment_fee_rate'] ?? 0);
        $costPerUnit = isset($opts['cost_per_unit']) && is_numeric($opts['cost_per_unit'])
            ? $this->nonNegative($opts['cost_per_unit'])
            : null;

        $warnings = [];

        // 1. ยอดขาย
        $unitPrice = $this->money($unitPrice);
        $gross = $this->money($unitPrice * $qty);

        // 2. GP
        $gpAmount = $this->money($gross * $gpRate / 100);

        // 3. VAT (ราคารวม VAT แล้ว → ถอดออกด้วย r/(100+r)) เฉพาะร้านจด VAT
        $vatAmount = $vatRate > 0 ? $this->money($gross * $vatRate / (100 + $vatRate)) : 0.0;

        // 4. ค่าแนะนำ — ปิดระบบ = 0, ไม่มี PV = % ที่แอดมินตั้ง (ค่าเริ่มต้น 0) ไม่ใช่ 100% แบบเดิม
        $pvTotal = 0.0;
        $pool = 0.0;
        $poolBasis = '';
        if ($mlmEnabled) {
            if ($pvPerUnit > 0) {
                $pvTotal = $this->money($pvPerUnit * $qty);
                $pool = $pvTotal * $commissionPerPv;
                $poolBasis = $this->fmt($pvTotal).' PV';
            } elseif ($poolPercent > 0) {
                $pool = $gross * $poolPercent / 100;
                $poolBasis = $this->fmt($poolPercent).'% ของยอดขาย';
            }

            $cap = min($gross * $poolCapPercent / 100, max(0.0, $gross - $gpAmount - $vatAmount));
            if ($pool > $cap + 0.000001) {
                $pool = $cap;
                $warnings[] = [
                    'code' => 'REFERRAL_POOL_CAPPED',
                    'message' => 'ค่าแนะนำถูกจำกัดไว้ไม่เกิน '.$this->fmt($poolCapPercent).'% ของยอดขาย และไม่เกินยอดที่เหลือหลังหัก GP และ VAT',
                ];
            }
            $pool = $this->money($pool);
        }

        // 5. เงินคืนลูกค้า / ค่าธรรมเนียมรับชำระ
        $cashback = $this->money($gross * $cashbackRate / 100);
        $paymentFee = $this->money($gross * $paymentFeeRate / 100);
        $sellerCashback = $cashbackFundedBy === 'seller' ? $cashback : 0.0;
        $platformCashback = $cashbackFundedBy === 'platform' ? $cashback : 0.0;

        // 6-7. สุทธิ
        $sellerNet = $this->money($gross - $gpAmount - $vatAmount - $pool - $paymentFee - $sellerCashback - $subsidy);
        $platformNet = $this->money($gpAmount - $platformCashback);
        $buyerTotal = $this->money($gross + $shippingFee);

        $costTotal = $costPerUnit !== null ? $this->money($costPerUnit * $qty) : null;
        $profit = $costTotal !== null ? $this->money($sellerNet - $costTotal) : null;
        $marginPercent = ($profit !== null && $gross > 0) ? round($profit / $gross * 100, 2) : null;

        // ----- คำเตือน -----
        if ($gross <= 0) {
            $warnings[] = ['code' => 'ZERO_PRICE', 'message' => 'ราคาสินค้าเป็น 0 บาท ร้านจะไม่มีรายได้จากรายการนี้'];
        }
        if ($sellerNet < 0) {
            $warnings[] = [
                'code' => 'SELLER_NET_NEGATIVE',
                'message' => 'ราคานี้ต่ำเกินไป หลังหักค่าต่างๆ แล้วร้านได้ติดลบ '.$this->fmtMoney(abs($sellerNet)).' บาท',
            ];
        }
        if ($profit !== null && $profit < 0) {
            $warnings[] = [
                'code' => 'BELOW_COST',
                'message' => 'ราคานี้ขาดทุน ร้านได้สุทธิ '.$this->fmtMoney($sellerNet).' บาท แต่ต้นทุน '.$this->fmtMoney((float) $costTotal).' บาท',
            ];
        }
        if ($platformNet < 0) {
            $warnings[] = [
                'code' => 'PLATFORM_NET_NEGATIVE',
                'message' => 'เงินคืนลูกค้าสูงกว่าค่า GP แพลตฟอร์มขาดทุนในรายการนี้',
            ];
        }

        // ----- บรรทัดแสดงผล (ค่าหักเป็นจำนวนติดลบ) -----
        $lines = [
            $this->line('gross', 'ยอดขาย ('.$this->fmtMoney($unitPrice).' บาท × '.$qty.' ชิ้น)', $gross, 'income'),
            $this->line('gp', 'ค่า GP แพลตฟอร์ม '.$this->fmt($gpRate).'%', -$gpAmount, 'deduction'),
        ];
        if ($vatAmount > 0) {
            $lines[] = $this->line('vat', 'ภาษีมูลค่าเพิ่ม '.$this->fmt($vatRate).'% (ถอดจากราคาที่รวม VAT แล้ว)', -$vatAmount, 'deduction');
        }
        if ($pool > 0) {
            $lines[] = $this->line('referral_pool', 'ค่าแนะนำสินค้า ('.$poolBasis.')', -$pool, 'deduction');
        }
        if ($paymentFee > 0) {
            $lines[] = $this->line('payment_fee', 'ค่าธรรมเนียมรับชำระเงิน '.$this->fmt($paymentFeeRate).'%', -$paymentFee, 'deduction');
        }
        if ($sellerCashback > 0) {
            $lines[] = $this->line('cashback', 'เงินคืนลูกค้า '.$this->fmt($cashbackRate).'% (ร้านออกเอง)', -$sellerCashback, 'deduction');
        }
        if ($subsidy > 0) {
            $lines[] = $this->line('shipping_subsidy', 'ค่าส่งที่ร้านออกให้ลูกค้า', -$subsidy, 'deduction');
        }
        $lines[] = $this->line('seller_net', 'ร้านได้รับสุทธิ', $sellerNet, 'result');
        if ($costTotal !== null) {
            $lines[] = $this->line('cost', 'ต้นทุนสินค้า', -$costTotal, 'deduction');
            $lines[] = $this->line('profit', 'กำไรสุทธิ', (float) $profit, 'result');
        }
        if ($platformCashback > 0) {
            $lines[] = $this->line('cashback_platform', 'เงินคืนลูกค้า '.$this->fmt($cashbackRate).'% (แพลตฟอร์มออกให้ ไม่หักจากร้าน)', $platformCashback, 'info');
        }
        if ($shippingFee > 0) {
            $lines[] = $this->line('shipping_fee', 'ค่าส่งที่ลูกค้าจ่าย (ส่งต่อให้ผู้ส่ง ไม่ใช่รายได้ร้าน)', $shippingFee, 'info');
        }

        return new PriceBreakdown(
            unit_price: $unitPrice,
            quantity: $qty,
            gross: $gross,
            gp_rate: $gpRate,
            gp_amount: $gpAmount,
            vat_rate: $vatRate,
            vat_amount: $vatAmount,
            pv_total: $pvTotal,
            referral_pool_amount: $pool,
            cashback_amount: $cashback,
            cashback_funded_by: $cashbackFundedBy,
            payment_fee: $paymentFee,
            shipping_fee: $shippingFee,
            seller_shipping_subsidy: $subsidy,
            seller_net: $sellerNet,
            platform_net: $platformNet,
            buyer_total: $buyerTotal,
            cost_total: $costTotal,
            profit: $profit,
            margin_percent: $marginPercent,
            lines: $lines,
            warnings: $warnings,
        );
    }

    /**
     * ตัวเลือกคำนวณของสินค้า e-commerce จากข้อมูลจริงในระบบ (GP, PV, VAT ของร้าน, ต้นทุน)
     *
     * @param  array<string, mixed>  $overrides  ค่าที่ต้องการทับ เช่น ['shipping_fee' => 40]
     * @return array<string, mixed>
     */
    public function optionsForProduct(Product $product, array $overrides = []): array
    {
        $official = $this->isOfficialProduct($product);
        $store = $official ? null : $this->storeForProduct($product);
        $pvInfo = $this->pvInfoForProduct($product);
        $cost = $product->getAttribute('cost_price');

        $options = [
            'gp_rate' => $this->gpRateForProduct($product),
            'pv' => $pvInfo['pv'],
            'mlm_enabled' => $this->mlmEnabled(),
            'commission_per_pv' => $pvInfo['commission_per_pv'] ?? $this->commissionPerPv(),
            'referral_pool_percent' => $this->referralPoolPercent(),
            'referral_pool_cap_percent' => $this->referralPoolCapPercent(),
            'vat_registered' => $official ? $this->officialShopVatRegistered() : $this->storeVatRegistered($store),
            'vat_rate' => $this->vatRate(),
            'cost_per_unit' => (is_numeric($cost) && (float) $cost > 0) ? (float) $cost : null,
        ];

        return array_merge($options, $overrides);
    }

    /**
     * คำนวณราคาของสินค้า e-commerce ด้วยค่าจริงในระบบ
     *
     * @param  float|null  $unitPrice  null = ใช้ราคาปัจจุบันของสินค้า
     */
    public function quoteProduct(Product $product, int $qty = 1, ?float $unitPrice = null, array $overrides = []): PriceBreakdown
    {
        $price = $unitPrice ?? (float) $product->getAttribute('price');

        return $this->breakdown($price, $qty, $this->optionsForProduct($product, $overrides));
    }

    /**
     * ค่าที่ต้องบันทึกลง order_items ตอนสร้างออเดอร์ (snapshot ณ เวลาซื้อ)
     *
     * คีย์ตรงกับคอลัมน์ order_items: unit_price, quantity, subtotal, discount_amount, total,
     * commission_rate (= อัตรา GP), commission_amount (= ค่า GP), seller_earning (= ผู้ขายได้สุทธิ ไม่ต่ำกว่า 0)
     *
     * @return array{unit_price: float, quantity: int, subtotal: float, discount_amount: float, total: float, commission_rate: float, commission_amount: float, seller_earning: float}
     */
    public function itemSnapshot(Product $product, int $qty, float $unitPrice): array
    {
        $b = $this->quoteProduct($product, $qty, $unitPrice);

        return [
            'unit_price' => $b->unit_price,
            'quantity' => $b->quantity,
            'subtotal' => $b->gross,
            'discount_amount' => 0.0,
            'total' => $b->gross,
            'commission_rate' => $b->gp_rate,
            'commission_amount' => $b->gp_amount,
            'seller_earning' => max(0.0, $b->seller_net),
        ];
    }

    /**
     * ตัวเลือกคำนวณของสินค้าตลาดสด: GP ตามตลาดสด, ไม่มี VAT (ผัก/ผลไม้/ของสดได้รับยกเว้น VAT),
     * ไม่มีค่าแนะนำ — ผู้ขายได้ ยอดขาย − GP
     *
     * @return array<string, mixed>
     */
    public function optionsForFreshListing(FreshMarketListing $listing, array $overrides = []): array
    {
        return array_merge([
            'gp_rate' => $this->gpRateForFreshListing($listing),
            'mlm_enabled' => false,
            'pv' => 0,
            'vat_registered' => false,
        ], $overrides);
    }

    /**
     * คำนวณการแบ่งเงินของออเดอร์ตลาดสด (ยอดรวมสินค้าของออเดอร์ ไม่รวมค่าส่ง)
     * ตลาดสดขายเป็นกิโล/หน่วยทศนิยมได้ จึงคิดจากยอดรวมเป็น 1 รายการ
     */
    public function quoteFreshListing(FreshMarketListing $listing, float $itemsTotal, array $overrides = []): PriceBreakdown
    {
        return $this->breakdown($itemsTotal, 1, $this->optionsForFreshListing($listing, $overrides));
    }

    // =====================================================================
    // ข้อมูลประกอบ (PV, ร้าน, ค่าตั้ง)
    // =====================================================================

    /**
     * PV ต่อชิ้นของสินค้า: แถว mlm_product_pv (ถ้ามี — ค่าในแถวคือคำตอบ แม้เป็น 0)
     * → products.pv_value → 0  (ไม่มี fallback คิดจากราคาแบบเดิมที่ทำให้หัก 100%)
     *
     * @return array{pv: float, commission_per_pv: ?float, source: string}
     */
    public function pvInfoForProduct(Product $product): array
    {
        $productId = (int) $product->getKey();
        if ($productId > 0 && $product->exists) {
            $row = $this->resolveProductPvRow($productId);
            if ($row !== null) {
                $custom = $row->getAttribute('custom_commission_per_pv');
                $useGlobal = (bool) $row->getAttribute('use_global_rate');

                return [
                    'pv' => $this->nonNegative($row->getAttribute('pv_value') ?? 0),
                    'commission_per_pv' => (! $useGlobal && is_numeric($custom) && (float) $custom > 0) ? (float) $custom : null,
                    'source' => 'mlm_product_pv',
                ];
            }
        }

        $pv = $this->nonNegative($product->getAttribute('pv_value') ?? 0);

        return ['pv' => $pv, 'commission_per_pv' => null, 'source' => $pv > 0 ? 'product' : 'none'];
    }

    /**
     * ร้านของสินค้า: products.store_id → ร้านของ seller_id
     */
    public function storeForProduct(Product $product): ?VendorStore
    {
        if ($product->relationLoaded('store')) {
            $store = $product->getRelation('store');
            if ($store instanceof VendorStore) {
                return $store;
            }
        } elseif ($product->getAttribute('store_id')) {
            $store = $product->store;
            if ($store instanceof VendorStore) {
                return $store;
            }
        }

        $sellerId = (int) $product->getAttribute('seller_id');
        if ($sellerId <= 0) {
            return null;
        }

        if (! array_key_exists($sellerId, $this->storeBySeller)) {
            $this->storeBySeller[$sellerId] = VendorStore::where('user_id', $sellerId)->orderBy('id')->first();
        }

        return $this->storeBySeller[$sellerId];
    }

    /**
     * สินค้าของร้านทางการ (แพลตฟอร์มขายเอง) หรือไม่
     */
    public function isOfficialProduct(Product $product): bool
    {
        $sellerId = (int) $product->getAttribute('seller_id');
        if ($sellerId <= 0) {
            return false;
        }

        $officialId = $this->officialSellerId();

        return $officialId !== null && $officialId === $sellerId;
    }

    /**
     * ร้านจดทะเบียน VAT หรือไม่ (vendor_stores.vat_registered — ไม่มีคอลัมน์/ไม่มีร้าน = ไม่จด)
     */
    public function storeVatRegistered(?VendorStore $store): bool
    {
        if ($store === null) {
            return false;
        }

        return filter_var($store->getAttribute('vat_registered'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * โปรฯ "GP ฟรีช่วงเปิดตัว" ยังมีผลอยู่หรือไม่
     *
     * pricing.gp_free = true และ (ไม่ได้ตั้ง pricing.gp_free_until หรือยังไม่ถึงวันสิ้นสุด)
     * วันที่แบบ "YYYY-MM-DD" = ฟรีถึงสิ้นวันนั้น · วันที่อ่านไม่ออก = ถือว่ายังฟรี (ไม่เก็บเงินร้านโดยไม่ตั้งใจ)
     */
    public function gpPromoActive(?\DateTimeInterface $at = null): bool
    {
        if (! filter_var($this->setting(self::KEY_GP_FREE, false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $end = $this->gpPromoEndsAt();

        if ($end === null) {
            return true;
        }

        $now = $at !== null ? \Illuminate\Support\Carbon::instance($at) : \Illuminate\Support\Carbon::now();

        return $now->lessThanOrEqualTo($end);
    }

    /**
     * วันเวลาสิ้นสุดโปรฯ GP ฟรี (null = ไม่มีกำหนด/อ่านไม่ออก)
     */
    public function gpPromoEndsAt(): ?\Illuminate\Support\Carbon
    {
        $raw = trim((string) ($this->setting(self::KEY_GP_FREE_UNTIL, '') ?? ''));

        if ($raw === '') {
            return null;
        }

        try {
            $end = \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }

        // ระบุแค่วันที่ → ฟรีถึงสิ้นวัน
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1 ? $end->endOfDay() : $end;
    }

    /**
     * ข้อความอธิบายโปรฯ GP ฟรี (แสดงให้ผู้ขายเห็นว่าทำไม GP เป็น 0)
     */
    private function gpPromoLabel(): string
    {
        $end = $this->gpPromoEndsAt();

        return $end !== null
            ? 'โปรโมชันเปิดตัว: ไม่เก็บค่า GP ถึงวันที่ '.$end->format('d/m/Y')
            : 'โปรโมชันเปิดตัว: ไม่เก็บค่า GP';
    }

    public function defaultGpRate(): float
    {
        return $this->pct($this->numericSetting(self::KEY_DEFAULT_GP_RATE, self::DEFAULT_GP_RATE));
    }

    public function minGpRate(): float
    {
        return $this->pct($this->numericSetting(self::KEY_MIN_GP_RATE, self::DEFAULT_MIN_GP_RATE));
    }

    public function vatRate(): float
    {
        return $this->pct($this->numericSetting(self::KEY_VAT_RATE, self::DEFAULT_VAT_RATE));
    }

    public function referralPoolPercent(): float
    {
        return $this->pct($this->numericSetting(self::KEY_REFERRAL_POOL_PERCENT, self::DEFAULT_REFERRAL_POOL_PERCENT));
    }

    public function referralPoolCapPercent(): float
    {
        return $this->pct($this->numericSetting(self::KEY_MAX_COMMISSION_PERCENT, self::DEFAULT_REFERRAL_POOL_CAP_PERCENT));
    }

    public function mlmEnabled(): bool
    {
        return filter_var($this->setting(self::KEY_MLM_ENABLED, false), FILTER_VALIDATE_BOOLEAN);
    }

    public function commissionPerPv(): float
    {
        return $this->nonNegative($this->numericSetting(self::KEY_COMMISSION_PER_PV, 1.0));
    }

    public function officialShopVatRegistered(): bool
    {
        return filter_var($this->setting(self::KEY_OFFICIAL_SHOP_VAT_REGISTERED, true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * อ่านค่าตั้ง (memo ต่อ instance)
     *
     * คีย์ปกติ → ตาราง settings, 'mlm:xxx' → MlmGlobalSetting, 'fresh:xxx' → fresh_market_settings
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->settingCache)) {
            return $this->settingCache[$key] ?? $default;
        }

        $value = $this->settingResolver !== null
            ? ($this->settingResolver)($key, $default)
            : $this->resolveSettingFromDatabase($key, $default);

        $this->settingCache[$key] = $value;

        return $value ?? $default;
    }

    /**
     * ล้าง memo (เช่น หลังแอดมินเปลี่ยนค่าตั้งใน request เดียวกัน)
     */
    public function flush(): void
    {
        $this->settingCache = [];
        $this->storeBySeller = [];
        $this->officialSellerResolved = false;
        $this->officialSellerId = null;
    }

    /**
     * ขั้นตอนสูตรเป็นภาษาไทย สำหรับแสดงในหน้าตัวจำลองราคา/หน้าแอดมิน
     *
     * @return array<int, string>
     */
    public static function formulaStepsTh(): array
    {
        return [
            '1) ยอดขาย = ราคาต่อชิ้น × จำนวน (ราคาที่ลูกค้าเห็น รวม VAT แล้ว)',
            '2) ค่า GP = ยอดขาย × อัตรา GP% (ลำดับ: อัตราที่แอดมินตั้งให้สินค้า → อัตราตามแพ็กเกจร้าน → อัตรามาตรฐาน และไม่ต่ำกว่าอัตราขั้นต่ำ)',
            '3) VAT = ยอดขาย × 7 ÷ 107 เฉพาะร้านที่จดทะเบียน VAT (ร้านที่ไม่ได้จด ไม่หัก VAT)',
            '4) ค่าแนะนำ = PV ต่อชิ้น × จำนวน × บาทต่อ PV เฉพาะเมื่อเปิดระบบแนะนำ (ไม่ได้ตั้ง PV = ไม่หัก) และไม่เกินเพดาน % ของยอดขาย',
            '5) ค่าธรรมเนียมรับชำระเงิน = ยอดขาย × อัตรา% (ถ้ามี)',
            '6) ร้านได้รับสุทธิ = ยอดขาย − GP − VAT − ค่าแนะนำ − ค่าธรรมเนียม − เงินคืน/ค่าส่งที่ร้านออกเอง',
            '7) กำไร = ร้านได้รับสุทธิ − ต้นทุน และ % กำไร = กำไร ÷ ยอดขาย × 100',
            'หมายเหตุ: ค่าส่งที่ลูกค้าจ่ายส่งต่อให้ผู้ส่ง ไม่นับเป็นรายได้ร้าน / เงินคืนลูกค้าตามโปรของแพลตฟอร์ม แพลตฟอร์มเป็นผู้ออก',
        ];
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * อ่านแถว PV ของสินค้า (แยกเป็นเมธอดเพื่อให้เทสต์แทนที่ได้)
     */
    protected function resolveProductPvRow(int $productId): ?MlmProductPv
    {
        try {
            $planId = (int) $this->numericSetting(self::KEY_DEFAULT_MLM_PLAN_ID, 0);

            return MlmProductPv::resolveForProduct($productId, $planId > 0 ? $planId : null);
        } catch (\Throwable $e) {
            Log::warning('PricingEngine: read product PV failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * อ่านค่าตั้งจากฐานข้อมูล — อ่านไม่ได้ให้ใช้ค่าเริ่มต้น (และ log ไว้)
     */
    protected function resolveSettingFromDatabase(string $key, mixed $default): mixed
    {
        try {
            if (str_starts_with($key, 'mlm:')) {
                return MlmGlobalSetting::get(substr($key, 4), $default);
            }

            if (str_starts_with($key, 'fresh:')) {
                $value = FreshMarketSetting::getSettings()->getAttribute(substr($key, 6));

                return $value ?? $default;
            }

            return Setting::get($key, $default);
        } catch (\Throwable $e) {
            Log::warning('PricingEngine: read setting failed, using default', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    private function officialSellerId(): ?int
    {
        if ($this->officialSellerResolved) {
            return $this->officialSellerId;
        }

        try {
            $id = $this->officialSellerIdResolver !== null
                ? ($this->officialSellerIdResolver)()
                : Product::getOfficialSellerId();
            $this->officialSellerId = is_numeric($id) && (int) $id > 0 ? (int) $id : null;
        } catch (\Throwable $e) {
            Log::warning('PricingEngine: resolve official seller failed', ['error' => $e->getMessage()]);
            $this->officialSellerId = null;
        }

        $this->officialSellerResolved = true;

        return $this->officialSellerId;
    }

    private function packageForStore(VendorStore $store): ?\App\Models\VendorPackage
    {
        if ($store->relationLoaded('package')) {
            $package = $store->getRelation('package');

            return $package instanceof \App\Models\VendorPackage ? $package : null;
        }

        if (! $store->getAttribute('package_id')) {
            return null;
        }

        $package = $store->package;

        return $package instanceof \App\Models\VendorPackage ? $package : null;
    }

    /**
     * บีบอัตรา GP ให้อยู่ในช่วง [ขั้นต่ำ, 100]
     *
     * @return array{rate: float, source: string, label_th: string, clamped: bool}
     */
    private function clampGp(float $rate, string $source, string $label): array
    {
        $min = $this->minGpRate();
        $clean = $this->pct($rate);
        $clamped = false;

        if ($clean < $min) {
            $clean = $min;
            $clamped = true;
            $label .= ' (ปรับขึ้นเป็นอัตราขั้นต่ำ '.$this->fmt($min).'%)';
        }

        return ['rate' => round($clean, 2), 'source' => $source, 'label_th' => $label, 'clamped' => $clamped];
    }

    private function numericSetting(string $key, float $default): float
    {
        $value = $this->setting($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * บีบเปอร์เซ็นต์ให้อยู่ในช่วง 0-100
     */
    private function pct(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        if (! is_finite($number)) {
            return 0.0;
        }

        return round(max(0.0, min(self::MAX_RATE, $number)), 4);
    }

    private function nonNegative(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;

        return is_finite($number) ? max(0.0, $number) : 0.0;
    }

    private function money(float $value): float
    {
        $rounded = round($value, 2);

        // กัน -0.0 โผล่ใน JSON
        return $rounded == 0.0 ? 0.0 : $rounded;
    }

    /**
     * @return array{key: string, label_th: string, amount: float, kind: string}
     */
    private function line(string $key, string $label, float $amount, string $kind): array
    {
        return ['key' => $key, 'label_th' => $label, 'amount' => $this->money($amount), 'kind' => $kind];
    }

    /**
     * แสดงตัวเลขอัตราแบบไม่มีศูนย์ท้าย เช่น 15 → "15", 7.5 → "7.5"
     */
    private function fmt(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $text === '' || $text === '-0' ? '0' : $text;
    }

    private function fmtMoney(float $value): string
    {
        return number_format($value, 2);
    }
}
