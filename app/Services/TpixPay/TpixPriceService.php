<?php

namespace App\Services\TpixPay;

use App\Models\Product;
use App\Models\TpixPriceSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ราคา TPIX จริง — อ่านจากพูล TPIX DEX (WTPIX/USDT_TPIX) บนเชน 4289 ตรง ๆ แล้วแปลงเป็นบาท
 *
 * แหล่งข้อมูล:
 *  - ราคา USD = สัดส่วนเหรียญในพูล (getReserves) — แหล่งเดียวกับที่หน้าเทรด tpix.online ใช้
 *  - อัตรา USD→THB = API อัตราแลกเปลี่ยน (ไม่มีบนเชน) · ดึงไม่ได้ใช้ค่าล่าสุดที่เก็บไว้ไม่เกิน 24 ชม.
 *    ❗ ไม่มีค่าตายตัวสำรอง — ราคาผิดคือคิดเงินลูกค้าผิด ไม่รู้ราคา = ไม่ออกใบเสนอยอด/ไม่โชว์ราคา
 *
 * ราคาที่ใช้ "คิดเงิน" (quoteRate) = ค่าที่ต่ำกว่าระหว่างราคาตอนนี้กับค่าเฉลี่ย 30 นาที
 * เพราะพูลยังบาง (~20 USDT): ซื้อดันราคาขึ้นไม่กี่ดอลลาร์ → ได้ใบเสนอยอดเหรียญน้อยลงมาก → ขายคืน
 * ใช้ค่าต่ำกว่า = คนปั่นไม่ได้ประโยชน์ ส่วนราคาที่ "แสดง" บนหน้าสินค้าใช้ราคาตอนนี้ (มีคำว่า ≈ กำกับ)
 */
class TpixPriceService
{
    private const SPOT_CACHE_KEY = 'tpix_pay:spot:v1';

    private const FX_CACHE_KEY = 'tpix_pay:fx:usd_thb:v1';

    private const SNAPSHOT_LOCK_KEY = 'tpix_pay:snapshot:lock';

    /** selector ของ getReserves() / token0() บนสัญญา pair (Uniswap V2) */
    private const SEL_GET_RESERVES = '0x0902f1ac';

    private const SEL_TOKEN0 = '0x0dfe1681';

    /** @var array<string, mixed>|null ราคาของคำขอนี้ (หน้าสินค้าเรียกซ้ำหลายสิบครั้ง) */
    private ?array $requestSpot = null;

    private ?int $requestSpotAt = null;

    public function __construct(private readonly TpixRpcClient $rpc) {}

    /**
     * ราคาปัจจุบัน (แคช display_cache_seconds) — null = ไม่รู้ราคา (RPC ล่ม / พูลบางเกิน / ไม่มีอัตราแลกเปลี่ยน)
     *
     * @return array{price_usd: string, usd_thb: string, price_thb: string, reserve_tpix: string, reserve_usdt: string, source: string, at: int, fx_at: int}|null
     */
    public function spot(): ?array
    {
        // จำในโปรเซสไม่เกิน 10 วิ (queue worker อยู่ยาว ห้ามจำถาวร)
        if ($this->requestSpotAt !== null && (time() - $this->requestSpotAt) < 10) {
            return $this->requestSpot;
        }

        $cached = Cache::get(self::SPOT_CACHE_KEY);

        // แคชว่าง → ให้คำขอเดียวยิงเชน (กันทุกหน้ายิงพร้อมกันตอนแคชหมด/RPC ล่ม) · ที่เหลือซ่อนราคาไปก่อน
        // ปกติตัวสแกน tpix:pay-scan อัปเดตแคชทุกนาที หน้าเว็บจึงแทบไม่ต้องยิงเอง
        if (! is_array($cached) && Cache::add(self::SPOT_CACHE_KEY.':lock', 1, 20)) {
            $fresh = $this->computeSpot();
            $cached = $fresh ?? ['unavailable' => true];
            // ไม่รู้ราคา → จำผลว่า "ไม่รู้" 30 วิ (ไม่ยิง RPC ซ้ำทุกคำขอ แต่ฟื้นเร็ว)
            Cache::put(self::SPOT_CACHE_KEY, $cached, $fresh ? $this->displayTtl() : 30);
            Cache::forget(self::SPOT_CACHE_KEY.':lock');
        }

        $this->requestSpot = is_array($cached) && ! isset($cached['unavailable']) ? $cached : null;
        $this->requestSpotAt = time();

        return $this->requestSpot;
    }

    /** อายุแคชราคาแสดงผล — ยาวกว่ารอบตัวสแกน (1 นาที) เพื่อให้แคชอุ่นตลอด */
    private function displayTtl(): int
    {
        return max(70, (int) config('tpix_pay.display_cache_seconds', 120));
    }

    /**
     * ราคาสำหรับออกใบเสนอยอด (คิดเงินจริง)
     *
     * ราคา USD ที่ใช้ = ค่าที่ต่ำกว่าระหว่าง "ราคาพูลตอนนี้" กับ "ค่าเฉลี่ยราคาพูล twap_minutes นาที" แล้วคูณอัตรา USD/THB
     * - คิดค่าเฉลี่ยฝั่ง USD (ไม่ขึ้นกับ API อัตราแลกเปลี่ยน — API ล่มแล้วค่าเฉลี่ยไม่หาย)
     * - ไม่มีค่าเฉลี่ย (ประวัติไม่ถึง twap_min_snapshots = ตัวสแกนไม่ทำงาน) → ไม่ออกใบ ไม่ยอมคิดเงินจากราคาจุดเดียว
     * - ราคาพูลที่อ่านไม่เกิน 30 วิ ใช้ซ้ำได้ (ลดการยิงเชนระหว่างถือล็อกฐานข้อมูล)
     *
     * @return array{rate_thb: string, spot_thb: string, twap_thb: string|null, basis: string, price_usd: string, usd_thb: string, reserve_usdt: string, at: int}|null
     */
    public function quoteRate(): ?array
    {
        $cached = Cache::get(self::SPOT_CACHE_KEY);
        $spot = is_array($cached) && ! isset($cached['unavailable']) && (time() - (int) ($cached['at'] ?? 0)) <= 30
            ? $cached
            : $this->computeSpot();
        if ($spot === null) {
            return null;
        }

        Cache::put(self::SPOT_CACHE_KEY, $spot, $this->displayTtl());
        $this->requestSpot = $spot;
        $this->requestSpotAt = time();

        $twapUsd = $this->twapUsd();
        if ($twapUsd === null) {
            Log::warning('TpixPriceService: not enough price history for a TWAP — refusing to quote');

            return null;
        }

        $usd = $spot['price_usd'];
        $basis = 'spot';
        if (bccomp($twapUsd, $usd, 12) < 0) {
            $usd = $twapUsd;
            $basis = 'twap';
        }

        $rate = bcmul($usd, $spot['usd_thb'], 12);
        if (bccomp($rate, '0', 12) <= 0) {
            return null;
        }

        return [
            'rate_thb' => $rate,
            'spot_thb' => $spot['price_thb'],
            'twap_thb' => bcmul($twapUsd, $spot['usd_thb'], 12),
            'basis' => $basis,
            'price_usd' => $usd,
            'usd_thb' => $spot['usd_thb'],
            'reserve_usdt' => $spot['reserve_usdt'],
            'at' => $spot['at'],
        ];
    }

    /**
     * บาท → TPIX ที่ราคาให้ (ปัดขึ้น 4 ตำแหน่ง)
     */
    public static function thbToTpix(string $thb, string $rateThb): string
    {
        if (bccomp($rateThb, '0', 12) <= 0) {
            throw new \InvalidArgumentException('ราคา TPIX ต้องมากกว่า 0');
        }

        return TpixAmount::ceil(bcdiv($thb, $rateThb, 18), 4);
    }

    /**
     * ข้อมูลราคา TPIX สำหรับแสดงคู่กับราคาบาท (null = ซ่อน)
     *
     * @param  float|int|string|null  $thb  ราคาบาท
     * @param  float|int|string|null  $fixedTpix  ราคา TPIX คงที่ (ถ้ามี — ผู้เรียกต้องกรองว่าสินค้านี้ใช้ราคาคงที่ได้)
     * @return array{tpix: string, tpix_raw: string, fixed: bool, rate_thb: string, rate_display: string, price_usd: string, updated_at: string, updated_time: string}|null
     */
    public function forDisplay(float|int|string|null $thb, float|int|string|null $fixedTpix = null): ?array
    {
        try {
            if (! app(TpixPaySettings::class)->showPrices()) {
                return null;
            }

            $spot = $this->spot();
            if ($spot === null) {
                return null;
            }

            $hasThb = $thb !== null && $thb !== '' && (float) $thb > 0;
            $converted = $hasThb ? self::thbToTpix(TpixAmount::fromNumber($thb, 2), $spot['price_thb']) : null;

            // ราคาคงที่ใช้ได้เมื่อ "ไม่ต่ำกว่ามูลค่าตลาด" ของราคาบาท (ตรงกับตอนคิดเงินจริงใน orderTpixTotal)
            // ราคาเหรียญขึ้นจนราคาคงที่ถูกกว่าราคาบาท → แสดงราคาตามตลาดแทน (กันซื้อด้วยเหรียญถูกแล้วยกเลิกรับคืนเป็นบาท)
            $fixed = $fixedTpix !== null && $fixedTpix !== '' && (float) $fixedTpix > 0;
            $tpix = $fixed ? TpixAmount::fromNumber($fixedTpix, 8) : $converted;
            if ($fixed && $converted !== null && bccomp($converted, $tpix, 8) > 0) {
                $tpix = $converted;
                $fixed = false;
            }
            if ($tpix === null) {
                return null;
            }

            $at = Carbon::createFromTimestamp($spot['at'])->timezone(config('app.timezone'));

            return [
                'tpix' => TpixAmount::display($tpix, bccomp($tpix, '100', 4) >= 0 ? 2 : 4),
                'tpix_raw' => $tpix,
                'fixed' => $fixed,
                'rate_thb' => $spot['price_thb'],
                'rate_display' => self::formatRate($spot['price_thb']),
                'price_usd' => $spot['price_usd'],
                'updated_at' => $at->toIso8601String(),
                'updated_time' => $at->format('H:i'),
            ];
        } catch (\Throwable $e) {
            Log::debug('TpixPriceService::forDisplay failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ฟิลด์ราคา TPIX สำหรับ API แอป (เพิ่มต่อท้ายข้อมูลสินค้าเดิม — แอปรุ่นเก่าไม่อ่านก็ไม่พัง)
     *
     *   price_tpix  string|null  ยอด TPIX แบบตัวเลขล้วน (เช่น "3812.5") — null = ไม่รู้ราคา/ปิดการแสดง
     *   tpix        array|null   {amount: "3,812.50", raw, fixed, rate_thb, rate_display, updated_at}
     *
     * @return array{price_tpix: string|null, tpix: array<string, mixed>|null}
     */
    public function apiFields(float|int|string|null $thb, float|int|string|null $fixedTpix = null): array
    {
        $d = $this->forDisplay($thb, $fixedTpix);
        if ($d === null) {
            return ['price_tpix' => null, 'tpix' => null];
        }

        // ตัดศูนย์ท้ายเฉพาะส่วนทศนิยม ("100" ต้องคงเป็น "100")
        $raw = str_contains($d['tpix_raw'], '.') ? rtrim(rtrim($d['tpix_raw'], '0'), '.') : $d['tpix_raw'];

        return [
            'price_tpix' => $raw,
            'tpix' => [
                'amount' => $d['tpix'],
                'raw' => $raw,
                'fixed' => $d['fixed'],
                'rate_thb' => $d['rate_thb'],
                'rate_display' => $d['rate_display'],
                'updated_at' => $d['updated_at'],
            ],
        ];
    }

    /**
     * ฟิลด์ API ของสินค้า (ราคาคงที่ใช้เฉพาะ Official Shop)
     *
     * @return array{price_tpix: string|null, tpix: array<string, mixed>|null}
     */
    public function apiFieldsForProduct(?Product $product, float|int|string|null $thb = null): array
    {
        if (! $product) {
            return $this->apiFields($thb);
        }

        if (self::isExternalProduct($product)) {
            return ['price_tpix' => null, 'tpix' => null];
        }

        return $this->apiFields($thb ?? $product->price, self::fixedPriceFor($product));
    }

    /**
     * สินค้าจาก Lazada (ลิงก์ affiliate หรือดึงเข้ามาขายในร้าน) — ไม่แสดงราคา TPIX และจ่ายด้วย TPIX ไม่ได้
     *
     * เจ้าของสั่ง (2026-10-13): "บน Thaiprompt สินค้า Lazada ไม่ต้องแสดงราคา TPIX"
     * ร้านต้องใช้เงินบาทซื้อของจาก Lazada มาส่ง จึงรับเหรียญแทนเงินบาทไม่ได้
     *
     * ตัวบ่งชี้ (มีอย่างใดอย่างหนึ่ง): is_affiliate · external_platform ไม่ว่าง · sku ขึ้นต้น LZD- (LazadaImportService)
     * · attributes.source.platform = lazada
     */
    public static function isExternalProduct(?Product $product): bool
    {
        if (! $product) {
            return false;
        }

        if ($product->is_affiliate || filled($product->external_platform)) {
            return true;
        }

        if (str_starts_with(strtoupper((string) $product->sku), 'LZD-')) {
            return true;
        }

        $attributes = $product->getAttribute('attributes');
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true);
        }

        return is_array($attributes) && strtolower((string) ($attributes['source']['platform'] ?? '')) === 'lazada';
    }

    /**
     * ราคา TPIX คงที่ของสินค้า — ใช้ได้เฉพาะสินค้า Official Shop (แพลตฟอร์มเป็นทั้งผู้ขายและผู้รับเหรียญ)
     *
     * ❗ สินค้าร้านค้าทั่วไปห้ามใช้ราคาคงที่: ร้านได้เงินบาทตามราคาที่ตั้ง แต่ลูกค้าจ่ายเหรียญตามราคาคงที่
     *    → ร้านตั้งราคาเหรียญต่ำแล้วซื้อเอง = ดูดเงินบาทจากแพลตฟอร์มได้
     */
    public static function fixedPriceFor(?Product $product): ?string
    {
        if (! $product || $product->price_tpix === null || (float) $product->price_tpix <= 0) {
            return null;
        }

        if ($product->seller_id && ! $product->isOfficialShopProduct()) {
            return null;
        }

        return TpixAmount::fromNumber((string) $product->price_tpix, 8);
    }

    /** "฿3.2790" — ราคา 1 TPIX เป็นบาท ทศนิยมพอให้เห็นการเปลี่ยนแปลง */
    public static function formatRate(string $rateThb): string
    {
        $decimals = bccomp($rateThb, '1', 12) >= 0 ? 2 : 4;

        return '฿'.TpixAmount::display($rateThb, $decimals, $decimals);
    }

    /**
     * ค่าเฉลี่ยราคา USD ของ TPIX (จากพูล) ช่วง twap_minutes (null = ข้อมูลไม่พอ)
     */
    public function twapUsd(): ?string
    {
        try {
            $rows = TpixPriceSnapshot::where('created_at', '>=', now()->subMinutes((int) config('tpix_pay.twap_minutes', 30)))
                ->pluck('price_usd');
        } catch (\Throwable $e) {
            return null;
        }

        if ($rows->count() < (int) config('tpix_pay.twap_min_snapshots', 3)) {
            return null;
        }

        $sum = '0';
        foreach ($rows as $value) {
            $sum = bcadd($sum, (string) $value, 12);
        }

        return bcdiv($sum, (string) $rows->count(), 12);
    }

    /**
     * ค่าเฉลี่ยราคาบาท (แสดงในหน้าแอดมิน) = ค่าเฉลี่ย USD × อัตราปัจจุบัน
     */
    public function twapThb(): ?string
    {
        $usd = $this->twapUsd();
        $fx = $usd !== null ? $this->usdThb() : null;

        return $usd !== null && $fx !== null ? bcmul($usd, $fx['rate'], 12) : null;
    }

    /**
     * อ่านพูล + อัตราแลกเปลี่ยนสด แล้วบันทึกประวัติ (ไม่เกินนาทีละครั้ง)
     *
     * @return array{price_usd: string, usd_thb: string, price_thb: string, reserve_tpix: string, reserve_usdt: string, source: string, at: int, fx_at: int}|null
     */
    public function computeSpot(): ?array
    {
        // เทสต์ห้ามยิงเชน/อัตราแลกเปลี่ยนจริง (หน้าสินค้า/API เรียกราคานี้ทุกที่) — เทสต์ที่จำลองเชนด้วย Http::fake เปิดเอง
        if (app()->runningUnitTests() && ! config('tpix_pay.allow_network_in_tests', false)) {
            return null;
        }

        try {
            $pool = $this->readPool();
        } catch (\Throwable $e) {
            Log::warning('TpixPriceService: read DEX pool failed', ['error' => $e->getMessage()]);

            return null;
        }

        if ($pool === null) {
            return null;
        }

        $fx = $this->usdThb();
        if ($fx === null) {
            return null;
        }

        $priceThb = bcmul($pool['price_usd'], $fx['rate'], 12);

        $spot = [
            'price_usd' => $pool['price_usd'],
            'usd_thb' => $fx['rate'],
            'price_thb' => $priceThb,
            'reserve_tpix' => $pool['reserve_tpix'],
            'reserve_usdt' => $pool['reserve_usdt'],
            'source' => 'dex',
            'at' => time(),
            'fx_at' => $fx['at'],
        ];

        // บันทึกประวัติเสมอ (ค่าเฉลี่ยคิดจากราคา USD ของพูล) — อัตราแลกเปลี่ยนสำรองติดป้าย dex_fx_stale
        // เพื่อไม่ให้ตัวสำรองอัตราแลกเปลี่ยนหยิบอัตราเก่ามาต่ออายุตัวเอง
        $spot['source'] = empty($fx['fallback']) ? 'dex' : 'dex_fx_stale';
        $this->recordSnapshot($spot);

        return $spot;
    }

    /**
     * สัดส่วนเหรียญในพูล → ราคา USD ต่อ 1 TPIX (null = พูลบางกว่า min_pool_usd)
     *
     * @return array{price_usd: string, reserve_tpix: string, reserve_usdt: string}|null
     */
    private function readPool(): ?array
    {
        $pair = (string) config('tpix_pay.dex.pair');
        $wtpix = strtolower((string) config('tpix_pay.dex.wtpix'));

        $usdt = strtolower((string) config('tpix_pay.dex.usdt'));
        $cacheKey = 'tpix_pay:pair_token0:'.strtolower($pair);
        $token0 = Cache::get($cacheKey);
        if (! in_array($token0, [$wtpix, $usdt], true)) {
            $hex = (string) preg_replace('/^0x/', '', $this->rpc->ethCall($pair, self::SEL_TOKEN0));
            $token0 = strlen($hex) >= 40 ? '0x'.strtolower(substr($hex, -40)) : '';
            // โหนดตอบผิด (เช่น "0x" จากโหนดที่ sync ไม่ทัน) ห้ามจำ — จำผิดครั้งเดียว reserve สลับข้าง ราคาเพี้ยนถาวร
            if (! in_array($token0, [$wtpix, $usdt], true)) {
                throw new \RuntimeException('token0 ของพูลไม่ใช่ WTPIX/USDT');
            }
            Cache::forever($cacheKey, $token0);
        }

        $hex = preg_replace('/^0x/', '', $this->rpc->ethCall($pair, self::SEL_GET_RESERVES));
        if (strlen($hex) < 128) {
            throw new \RuntimeException('getReserves ตอบข้อมูลไม่ครบ');
        }

        $r0 = TpixAmount::hexToDec(substr($hex, 0, 64));
        $r1 = TpixAmount::hexToDec(substr($hex, 64, 64));

        [$rawTpix, $rawUsdt] = $token0 === $wtpix ? [$r0, $r1] : [$r1, $r0];

        $usdtDecimals = (int) config('tpix_pay.dex.usdt_decimals', 6);
        $reserveTpix = bcdiv($rawTpix, bcpow('10', '18', 0), 18);
        $reserveUsdt = bcdiv($rawUsdt, bcpow('10', (string) $usdtDecimals, 0), 18);

        if (bccomp($reserveTpix, '0', 18) <= 0
            || bccomp($reserveUsdt, TpixAmount::fromNumber((float) config('tpix_pay.dex.min_pool_usd', 5), 6), 18) < 0) {
            Log::warning('TpixPriceService: DEX pool too thin for pricing', [
                'reserve_tpix' => $reserveTpix,
                'reserve_usdt' => $reserveUsdt,
            ]);

            return null;
        }

        return [
            'price_usd' => bcdiv($reserveUsdt, $reserveTpix, 12),
            'reserve_tpix' => $reserveTpix,
            'reserve_usdt' => $reserveUsdt,
        ];
    }

    /**
     * อัตรา 1 USD เป็นบาท (แคช fx_cache_minutes) — ดึงไม่ได้ใช้ค่าล่าสุดในประวัติราคาไม่เกิน fx_max_age_hours
     *
     * @return array{rate: string, at: int, fallback?: bool}|null
     */
    public function usdThb(): ?array
    {
        $cached = Cache::get(self::FX_CACHE_KEY);
        if (is_array($cached) && isset($cached['rate'])) {
            return $cached;
        }

        foreach ((array) config('tpix_pay.fx_urls', []) as $url) {
            try {
                $response = Http::withHeaders(['User-Agent' => (string) config('tpix_pay.user_agent')])
                    ->timeout((int) config('tpix_pay.http_timeout', 8))
                    ->get($url);

                $rate = $response->successful() ? ($response->json('rates.THB') ?? null) : null;
                if (is_numeric($rate) && (float) $rate > 20 && (float) $rate < 60) {
                    $fx = ['rate' => TpixAmount::fromNumber((float) $rate, 6), 'at' => time()];
                    Cache::put(self::FX_CACHE_KEY, $fx, now()->addMinutes((int) config('tpix_pay.fx_cache_minutes', 30)));

                    return $fx;
                }
            } catch (\Throwable $e) {
                Log::debug('TpixPriceService: FX source failed', ['error' => $e->getMessage()]);
            }
        }

        // สำรอง: อัตราล่าสุดที่เคยใช้ (ไม่เกิน fx_max_age_hours)
        try {
            $last = TpixPriceSnapshot::where('created_at', '>=', now()->subHours((int) config('tpix_pay.fx_max_age_hours', 24)))
                ->where('source', 'dex')
                ->orderByDesc('id')
                ->first(['usd_thb', 'created_at']);
        } catch (\Throwable $e) {
            $last = null;
        }

        if ($last && (float) $last->usd_thb > 0) {
            // fallback=true → computeSpot ไม่บันทึกประวัติด้วยอัตรานี้ (ไม่งั้นอัตราเก่าจะถูกต่ออายุไปเรื่อย ๆ)
            $fx = ['rate' => TpixAmount::fromNumber((string) $last->usd_thb, 6), 'at' => $last->created_at?->getTimestamp() ?? time(), 'fallback' => true];
            // จำสั้น ๆ แล้วลองแหล่งจริงใหม่
            Cache::put(self::FX_CACHE_KEY, $fx, now()->addMinutes(5));

            return $fx;
        }

        Log::warning('TpixPriceService: no USD/THB rate available — TPIX prices hidden');

        return null;
    }

    /**
     * @param  array<string, mixed>  $spot
     */
    private function recordSnapshot(array $spot): void
    {
        try {
            if (! Cache::add(self::SNAPSHOT_LOCK_KEY, 1, 55)) {
                return;
            }

            $recent = TpixPriceSnapshot::where('created_at', '>=', now()->subSeconds(55))->exists();
            if ($recent) {
                return;
            }

            TpixPriceSnapshot::create([
                'price_usd' => $spot['price_usd'],
                'usd_thb' => $spot['usd_thb'],
                'price_thb' => $spot['price_thb'],
                'reserve_tpix' => $spot['reserve_tpix'],
                'reserve_usdt' => $spot['reserve_usdt'],
                'source' => $spot['source'],
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('TpixPriceService: snapshot write failed', ['error' => $e->getMessage()]);
        }
    }
}
