<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceAffiliateLink;
use App\Models\MarketplaceCommission;
use App\Models\MarketplaceLinkClick;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MlmGlobalSetting;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ซิงก์ "รายงาน Conversion" ของ Lazada Affiliate → ตาราง marketplace_orders
 *
 * ╔══════════════════════════════════════════════════════════════════════════════════════╗
 * ║ 🚨 กฎเหล็กของไฟล์นี้: ค่าเริ่มต้นคือ "ไม่จ่าย" (DO NOT PAY)                              ║
 * ║                                                                                      ║
 * ║ คำสั่งเจ้าของระบบ: "เราต้องเช็คได้ว่า การสั่งซื้อของลูกค้าคนที่กดลิ้งค์ไป ชำระเงินเสร็จสิ้น    ║
 * ║ ลาซาด้าชำระค่าคอมให้เราจริง จึงจะจ่าย อนุมัติ pv"                                        ║
 * ║                                                                                      ║
 * ║ แปลเป็นโค้ด: จะตั้ง commission_status = 'approved' ได้ก็ต่อเมื่อ "ครบทุกข้อ" เท่านั้น        ║
 * ║   1) แอดมินยืนยัน mapping ชื่อฟิลด์แล้ว (ธง lazada_conversion_mapping_verified = true)   ║
 * ║   2) สถานะจากลาซาด้าอยู่ในกลุ่ม "จ่ายค่าคอมให้เราแล้ว" (SETTLED_STATUSES)                 ║
 * ║   3) ยอดค่าคอมที่ map ได้ > 0 (ไม่ใช่ null / ไม่ใช่ 0)                                    ║
 * ║   4) ระบุตัวผู้แนะนำได้แน่ชัดจาก click log ของเราเอง (ไม่กำกวม)                            ║
 * ║   5) มีเลขออเดอร์จริงจากลาซาด้า (ไม่ใช่เลขที่เราสังเคราะห์เอง)                              ║
 * ║                                                                                      ║
 * ║ กรณีอื่น ๆ "ทั้งหมด" → commission_status = 'pending' ค้างไว้ตลอดจนกว่าคนจะมาอนุมัติเอง     ║
 * ║ และ "ห้าม" สร้างแถว marketplace_commissions เด็ดขาด                                    ║
 * ║ เหตุผล: จ่ายค่าคอมออกไปโดยที่ลาซาด้ายังไม่จ่ายให้เรา = บริษัทเสียเงินจริง                    ║
 * ╚══════════════════════════════════════════════════════════════════════════════════════╝
 *
 * ⚠️ ชื่อฟิลด์ในแต่ละแถวของรายงาน "ยังไม่เคยเห็นของจริง" (ทุกเดือนที่ยิงคืน rows = 0)
 *    → normalizeRow() จึงเป็นการ "เดาแบบเผื่อหลายชื่อ" ทั้งหมด ไม่ใช่ spec ที่ยืนยันแล้ว
 *    → พอมีแถวจริงแถวแรก ระบบจะ Log::warning รายชื่อคีย์ทั้งหมด (ครั้งเดียวต่อรอบ sync)
 *      ให้คนเอาไปล็อก mapping ให้ตรง แล้วค่อยเปิดธง lazada_conversion_mapping_verified
 *    → แถวดิบถูกเก็บลง marketplace_orders.order_data ทุกครั้ง (ตรวจย้อนหลังได้เสมอ)
 */
class LazadaConversionSyncService
{
    /**
     * 🚨 สถานะที่ถือว่า "ลาซาด้าจ่ายค่าคอมให้เราเรียบร้อยแล้ว" — เทียบแบบตัวพิมพ์เล็ก
     *
     * ⚠️ รายการนี้คือ "การเดา" จากคำที่ marketplace ทั่วไปใช้ ยังไม่ได้ยืนยันกับข้อมูลจริงของ Lazada
     *    จึงต้องใช้คู่กับธง lazada_conversion_mapping_verified เสมอ (ข้อ 1 ของกฎเหล็ก)
     */
    /**
     * สถานะที่แปลว่า "ลาซาด้าเคลียร์ค่าคอมให้เราแล้วจริงๆ"
     *
     * 🚫 จงใจตัด 'completed' / 'fulfilled' / 'confirmed' ออก —
     *    สามคำนั้นเป็นคำของ "วงจรออเดอร์" (ของส่งถึง/ยืนยันแล้ว) ไม่ใช่คำของ "การเคลียร์เงิน"
     *    ออเดอร์ที่ส่งของถึงแล้วแต่ค่าคอมยังไม่เคลียร์ = ยังห้ามจ่าย
     */
    public const SETTLED_STATUSES = ['settled', 'paid', 'settlement_done', 'payout_paid'];

    /** ธงใน mlm_global_settings ที่คนต้องเปิดเองหลังยืนยันชื่อฟิลด์จริงแล้ว (default = ปิด) */
    public const MAPPING_VERIFIED_KEY = 'lazada_conversion_mapping_verified';

    /** หน้าต่างการให้เครดิตคลิก: คลิกต้องเกิดไม่เกินกี่วันก่อนออเดอร์ */
    public const ATTRIBUTION_WINDOW_DAYS = 30;

    /**
     * เผื่อความคลาดเคลื่อนของโซนเวลา (ยังไม่ยืนยันว่าเวลาที่ลาซาด้าคืนมาเป็นโซนไหน — ไทย/จีน/UTC)
     * ใช้ผ่อนเงื่อนไข "คลิกต้องมาก่อนออเดอร์" เท่านั้น ไม่ได้ผ่อนกฎการอนุมัติ
     */
    public const CLICK_TIME_SKEW_HOURS = 12;

    /** จำนวนแถวต่อหน้าเวลาเรียก API */
    public const PAGE_LIMIT = 50;

    /** กันลูปไม่รู้จบ กรณี API ไม่เลื่อนหน้า */
    public const MAX_PAGES = 200;

    /** ล็อกรายชื่อคีย์ที่เจอจริงแล้วหรือยัง (ครั้งเดียวต่อรอบ sync ไม่ใช่ต่อแถว) */
    protected bool $fieldsDiscovered = false;

    /** รายชื่อคีย์ที่เจอจากแถวจริงแถวแรก — ให้ command เอาไปโชว์บนจอ */
    protected ?array $discoveredKeys = null;

    /** cache ผลอ่านธงยืนยัน mapping (อ่านครั้งเดียวต่อรอบ) */
    protected ?bool $mappingVerified = null;

    /**
     * @param  bool  $dryRun  true = ไม่เขียน DB เลย (แค่ดึงมานับ/ดูผล)
     */
    public function __construct(protected bool $dryRun = false) {}

    /** เปิด/ปิดโหมดซ้อมแห้ง */
    public function dryRun(bool $on = true): static
    {
        $this->dryRun = $on;

        return $this;
    }

    /** รายชื่อคีย์ของแถวจริงแถวแรกที่เจอในรอบนี้ (null = ยังไม่เจอแถวจริง) */
    public function getDiscoveredKeys(): ?array
    {
        return $this->discoveredKeys;
    }

    /**
     * ซิงก์รายงาน conversion ของ "หนึ่งเดือนปฏิทิน"
     *
     * 🚨 API รองรับช่วงวันที่ภายในเดือนเดียวเท่านั้น → ห้ามส่งช่วงคร่อมเดือน
     *    วันสิ้นสุดถูกตัดไม่ให้เกินวันนี้ (ขอข้อมูลอนาคตไม่มีประโยชน์และอาจ error)
     *
     * @param  Carbon  $month  วันไหนก็ได้ในเดือนที่ต้องการ
     * @return array<string,mixed> สรุปผล (ดู emptySummary)
     */
    public function syncMonth(MarketplaceAccount $account, Carbon $month): array
    {
        $summary = $this->emptySummary();
        $summary['month'] = $month->format('Y-m');

        $start = $month->copy()->startOfMonth();
        $today = Carbon::today();

        // เดือนอนาคต = ไม่มีข้อมูล ข้ามไปเลย
        if ($start->greaterThan($today)) {
            $summary['errors'][] = 'ข้ามเดือน '.$summary['month'].' (เป็นเดือนในอนาคต)';

            return $summary;
        }

        // ตัดปลายช่วงไม่ให้เกินวันนี้ แต่ยังต้องอยู่ในเดือนเดียวกัน (ข้อจำกัด API)
        $end = $month->copy()->endOfMonth()->startOfDay();
        if ($end->greaterThan($today)) {
            $end = $today->copy();
        }

        $api = new LazadaAffiliateService($account);
        $page = 1;
        $previousPageSignature = null;

        while ($page <= self::MAX_PAGES) {
            $res = $api->getConversionReport(
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
                $page,
                self::PAGE_LIMIT
            );

            if (! $res['ok']) {
                $summary['errors'][] = 'เดือน '.$summary['month'].' หน้า '.$page.': '.(string) $res['error'];
                break;
            }

            $rows = $res['rows'];
            if (empty($rows)) {
                break;
            }

            // กัน API ที่ไม่เลื่อนหน้า (คืนชุดเดิมซ้ำ ๆ) → จะกลายเป็นลูปไม่รู้จบ
            $signature = md5((string) json_encode($rows));
            if ($signature === $previousPageSignature) {
                $summary['errors'][] = 'เดือน '.$summary['month'].': API คืนข้อมูลชุดเดิมซ้ำที่หน้า '.$page.' — หยุดอ่านต่อ';
                break;
            }
            $previousPageSignature = $signature;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    $summary['failed']++;

                    continue;
                }

                $summary['fetched']++;
                $this->discoverFields($row);

                // 🛡️ แถวเสียหนึ่งแถว ต้องไม่ทำให้ทั้งรอบ sync ล้ม
                try {
                    $this->processRow($account, $row, $summary);
                } catch (\Throwable $e) {
                    $summary['failed']++;
                    $summary['errors'][] = 'แถวผิดพลาด: '.mb_substr($e->getMessage(), 0, 160);
                    Log::error('lazada conversion: ประมวลผลแถวไม่สำเร็จ', [
                        'account_id' => $account->id,
                        'month' => $summary['month'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (count($rows) < self::PAGE_LIMIT) {
                break; // หน้าไม่เต็ม = หน้าสุดท้าย
            }
            $page++;
        }

        return $summary;
    }

    /**
     * ซิงก์ย้อนหลังหลายเดือน (ยังยิงทีละเดือนตามข้อจำกัด API)
     *
     * @param  int  $months  จำนวนเดือน นับรวมเดือนเริ่มต้นด้วย
     * @param  Carbon|null  $from  เดือนเริ่มต้น (ไม่ระบุ = เดือนปัจจุบัน) แล้วไล่ย้อนหลัง
     * @return array<int,array<string,mixed>> สรุปผลรายเดือน (ใหม่ → เก่า)
     */
    public function syncMonths(MarketplaceAccount $account, int $months = 1, ?Carbon $from = null): array
    {
        $cursor = ($from ?? Carbon::now())->copy()->startOfMonth();
        $out = [];

        for ($i = 0; $i < max(1, $months); $i++) {
            $out[] = $this->syncMonth($account, $cursor->copy());
            $cursor->subMonthNoOverflow();
        }

        return $out;
    }

    /** โครงสรุปผลเปล่า */
    public function emptySummary(): array
    {
        return [
            'month' => null,
            'fetched' => 0,     // แถวที่ดึงมาได้จาก API
            'stored' => 0,      // สร้างออเดอร์ใหม่
            'updated' => 0,     // อัปเดตออเดอร์เดิม
            'attributed' => 0,  // ระบุตัวผู้แนะนำได้
            'approved' => 0,    // ผ่านด่านตรวจ → อนุมัติค่าคอม
            'pending' => 0,     // ค้างรอคนตรวจ
            'failed' => 0,      // แถวที่ประมวลผลไม่ได้
            'errors' => [],
        ];
    }

    /**
     * รวมสรุปผลหลายเดือนเป็นก้อนเดียว
     *
     * @param  array<int,array<string,mixed>>  $summaries
     */
    public function mergeSummaries(array $summaries): array
    {
        $total = $this->emptySummary();
        $total['month'] = 'รวมทุกเดือน';

        foreach ($summaries as $s) {
            foreach (['fetched', 'stored', 'updated', 'attributed', 'approved', 'pending', 'failed'] as $k) {
                $total[$k] += (int) ($s[$k] ?? 0);
            }
            $total['errors'] = array_merge($total['errors'], (array) ($s['errors'] ?? []));
        }

        return $total;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Field discovery — ชื่อฟิลด์จริงยังไม่รู้ ต้องบันทึกไว้ตอนเจอแถวแรก
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ครั้งแรกที่เจอ "แถวจริง" ให้ log รายชื่อคีย์ทั้งหมด + ตัวอย่างแถว
     * เพื่อให้คนเอาไปล็อก mapping ให้ตรงของจริง แล้วค่อยเปิดธงยืนยัน
     *
     * ทำครั้งเดียวต่อรอบ sync (ไม่ใช่ทุกแถว) กัน log ท่วม
     *
     * @param  array<string,mixed>  $row
     */
    protected function discoverFields(array $row): void
    {
        if ($this->fieldsDiscovered || empty($row)) {
            return;
        }

        $this->fieldsDiscovered = true;
        $this->discoveredKeys = array_keys($row);

        Log::warning('lazada conversion: discovered fields', [
            'keys' => $this->discoveredKeys,
            'sample' => mb_substr((string) json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 2000),
            'หมายเหตุ' => 'นี่คือแถวจริงแถวแรกจาก /marketing/conversion/report — เอาชื่อคีย์นี้ไปล็อก mapping ใน LazadaConversionSyncService::normalizeRow() แล้วจึงเปิดธง '.self::MAPPING_VERIFIED_KEY,
        ]);
    }

    /**
     * แปลงแถวดิบ → ฟิลด์กลางของเรา
     *
     * 🚨 ชื่อคีย์ทุกตัวด้านล่างคือ "การเดา" (guessed) ทั้งหมด — ยังไม่เคยเห็นแถวจริงสักแถว
     *    จึงเดาเผื่อไว้หลายชื่อ + เทียบแบบไม่สนตัวพิมพ์เล็กใหญ่/ขีดล่าง (orderId = order_id = ORDER_ID)
     *    ฟิลด์ไหนหาไม่เจอ → คืน null เท่านั้น ห้ามใส่ค่า default โดยเฉพาะฟิลด์ที่เป็นเงิน
     *    (ใส่ 0 แทน null = หลอกด่านตรวจว่า "รู้ค่าแล้ว" ซึ่งอันตราย)
     *
     * @param  array<string,mixed>  $row
     * @return array{orderId:?string, productId:?string, payout:?float, amount:?float, status:?string, time:?Carbon, currency:?string}
     */
    public function normalizeRow(array $row): array
    {
        // ทำดัชนีคีย์แบบ normalize ครั้งเดียวต่อแถว
        $idx = [];
        foreach ($row as $k => $v) {
            $idx[$this->normalizeKey((string) $k)] = $v;
        }

        $orderId = $this->pick($idx, ['orderId', 'orderNumber', 'order_no', 'tradeId', 'orderSn', 'orderCode', 'id']);
        $productId = $this->pick($idx, ['productId', 'itemId', 'sku', 'skuId', 'item_id']);

        // 💰 ยอดค่าคอม — เอาเฉพาะชื่อที่หมายถึง "เงินที่เคลียร์แล้ว" เท่านั้น
        //    🚫 ห้ามใส่ estimatedCommission / income กลับเข้ามาเด็ดขาด
        //       "ค่าคอมโดยประมาณ" ไม่ใช่เงินที่ได้รับจริง ถ้าเอามาผ่านด่าน (payout > 0)
        //       เราจะจ่ายคอมจากตัวเลขที่ลาซาด้ายังไม่ได้จ่ายให้เรา
        $payout = $this->pick($idx, ['commission', 'payoutAmount', 'commissionAmount', 'payout', 'totalCommission', 'settledCommission']);
        // เก็บ "ยอดประมาณ" แยกไว้ดูเฉยๆ ห้ามใช้ตัดสินใจจ่าย
        $estimated = $this->pick($idx, ['estimatedCommission', 'income', 'estCommission']);

        $amount = $this->pick($idx, ['orderAmount', 'paidAmount', 'gmv', 'totalAmount', 'price', 'payAmount']);

        // 🚨 ลำดับสำคัญมาก: ต้องอ่าน "สถานะการเคลียร์เงิน" ก่อน "สถานะออเดอร์" เสมอ
        //    เคสจริงที่พบบ่อยในรายงาน affiliate: orderStatus=completed (ของส่งถึงแล้ว)
        //    แต่ settlementStatus=pending (ยังไม่เคลียร์ค่าคอมให้เรา)
        //    ถ้าอ่าน status/orderStatus ก่อน จะเห็นว่า "completed" แล้วอนุมัติจ่าย
        //    = จ่ายคอมสำหรับเงินที่ยังไม่ได้รับ ซึ่งคือสิ่งที่กฎข้อนี้ตั้งมาเพื่อกัน
        $status = $this->pick($idx, ['settlementStatus', 'settleStatus', 'payoutStatus', 'commissionStatus', 'status', 'orderStatus', 'checkoutStatus']);

        $time = $this->pick($idx, ['orderTime', 'clickTime', 'createTime', 'orderCreateTime', 'gmtCreate', 'orderDate']);
        $currency = $this->pick($idx, ['currency', 'currencyCode']);

        return [
            'orderId' => $this->toIdString($orderId),
            'productId' => $this->toIdString($productId),
            'payout' => $this->toMoney($payout),
            'amount' => $this->toMoney($amount),
            // คอลัมน์ order_status เป็น varchar(50) → ตัดความยาวกันแถวพังตอน insert (strict mode)
            'status' => is_scalar($status) && (string) $status !== '' ? mb_substr(strtolower(trim((string) $status)), 0, 50) : null,
            'time' => $this->toTime($time),
            'currency' => is_string($currency) && $currency !== '' ? mb_substr(strtoupper($currency), 0, 10) : null,
            // 👀 ยอด "ประมาณการ" — เก็บไว้ให้คนดูเปรียบเทียบเท่านั้น
            //    ด่านตรวจห้ามใช้ค่านี้ตัดสินจ่ายเด็ดขาด (ยังไม่ใช่เงินที่เข้ากระเป๋าจริง)
            'estimated_payout' => $this->toMoney($estimated),
        ];
    }

    /** ตัดอักขระที่ไม่ใช่ a-z0-9 + ทำตัวพิมพ์เล็ก เพื่อให้ orderId / order_id / ORDER-ID เทียบกันติด */
    protected function normalizeKey(string $key): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $key));
    }

    /**
     * หยิบค่าจากดัชนีตามลำดับชื่อผู้สมัคร — ข้ามค่าว่าง/null/array ว่าง
     *
     * @param  array<string,mixed>  $idx
     * @param  array<int,string>  $candidates
     */
    protected function pick(array $idx, array $candidates): mixed
    {
        foreach ($candidates as $candidate) {
            $k = $this->normalizeKey($candidate);
            if (! array_key_exists($k, $idx)) {
                continue;
            }
            $v = $idx[$k];
            if ($v === null || $v === '' || (is_array($v) && $v === [])) {
                continue;
            }

            return $v;
        }

        return null;
    }

    /** แปลงเป็นสตริง id (ตัดช่องว่าง) — ไม่ใช่ scalar = คืน null */
    protected function toIdString(mixed $v): ?string
    {
        if (! is_scalar($v)) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : mb_substr($s, 0, 100);
    }

    /**
     * แปลงค่าเงิน — แปลงไม่ได้ = null (ห้ามคืน 0 เพราะ 0 แปลว่า "รู้แล้วว่าศูนย์" คนละความหมายกับ "ไม่รู้")
     */
    protected function toMoney(mixed $v): ?float
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        if (! is_string($v)) {
            return null;
        }

        $s = trim($v);

        // 🚨 รูปแบบกำกวมห้ามเดา — "1.234,56" (ยุโรป) กับ "1,234.56" (ไทย/อังกฤษ) ตัวเลขต่างกัน 1000 เท่า
        //    เดาผิด = จ่ายค่าคอมผิดจำนวน → คืน null (ไม่รู้) ให้ตกไปค้าง pending ให้คนตรวจแทน
        $lastComma = strrpos($s, ',');
        $lastDot = strrpos($s, '.');
        if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
            return null;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $s);
        if ($clean === null || $clean === '' || ! is_numeric($clean)) {
            return null;
        }

        // จุดหลายตัว = คั่นหลักพันด้วยจุด (1.234.567) → อ่านค่าไม่ชัด ห้ามเดาเช่นกัน
        if (substr_count($clean, '.') > 1) {
            return null;
        }

        return (float) $clean;
    }

    /** แปลงเวลา — รองรับทั้ง epoch (วินาที/มิลลิวินาที) และสตริงวันที่ | แปลงไม่ได้ = null */
    protected function toTime(mixed $v): ?Carbon
    {
        if ($v === null || $v === '' || is_array($v)) {
            return null;
        }

        try {
            if (is_numeric($v)) {
                $n = (float) $v;
                if ($n <= 0) {
                    return null;
                }
                $t = $n > 100000000000 ? Carbon::createFromTimestampMs((int) $n) : Carbon::createFromTimestamp((int) $n);
            } else {
                $t = Carbon::parse((string) $v);
            }
        } catch (\Throwable $e) {
            return null;
        }

        // กันค่าเพี้ยน (ปี 1970 / ปีอนาคตไกล) — ถือว่าอ่านไม่ออก
        if ($t->year < 2015 || $t->greaterThan(Carbon::now()->addYear())) {
            return null;
        }

        return $t;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ด่านตรวจก่อนจ่ายเงิน (settlement gate)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * อ่านธง "แอดมินยืนยัน mapping ชื่อฟิลด์แล้ว" — ค่าเริ่มต้น = false (ไม่จ่าย)
     *
     * ⚠️ ค่าใน mlm_global_settings อาจถูกเก็บเป็นสตริง ('false', '0', 'no')
     *    ถ้า cast ด้วย (bool) ตรง ๆ สตริง 'false' จะกลายเป็น true → เปิดประตูจ่ายเงินโดยไม่ตั้งใจ
     *    จึงเทียบเฉพาะค่าที่อนุญาตแบบเข้มงวดเท่านั้น
     *
     * 🚨 ชั้นกันซ้ำ: MlmGlobalSetting::getTypedValue() ทำ (bool) ให้เองเมื่อ type='boolean'
     *    ทำให้แถวที่เก็บค่า 'false' คืนกลับมาเป็น true ได้ → จึงอ่านค่าดิบจากแถวมาตรวจซ้ำอีกรอบ
     *    (คิวรี่แค่ครั้งเดียวต่อรอบ sync — คุ้มกับความเสี่ยง "จ่ายเงินโดยไม่ได้ตั้งใจ")
     */
    public function isMappingVerified(): bool
    {
        if ($this->mappingVerified !== null) {
            return $this->mappingVerified;
        }

        $raw = MlmGlobalSetting::get(self::MAPPING_VERIFIED_KEY, false);
        if (is_string($raw)) {
            $raw = strtolower(trim($raw));
        }
        $verified = in_array($raw, [true, 1, '1', 'true', 'yes', 'on'], true);

        if ($verified) {
            $stored = MlmGlobalSetting::where('key', self::MAPPING_VERIFIED_KEY)->value('value');
            if ($stored !== null && in_array(strtolower(trim((string) $stored)), ['', '0', 'false', 'no', 'off', 'null'], true)) {
                Log::warning('lazada conversion: ธงยืนยัน mapping ถูก cast เป็น true ทั้งที่ค่าจริงคือปิด — บังคับปิดเพื่อไม่ให้อนุมัติจ่าย', [
                    'key' => self::MAPPING_VERIFIED_KEY,
                    'stored' => (string) $stored,
                ]);
                $verified = false;
            }
        }

        return $this->mappingVerified = $verified;
    }

    /**
     * 🚨 ด่านตรวจกลาง — ตัดสินว่าแถวนี้ "อนุมัติจ่าย" ได้หรือไม่
     *
     * ต้องผ่านครบทุกข้อ ถ้าขาดข้อใดข้อหนึ่ง → pending ค้างรอคนตรวจ (ค่าเริ่มต้นคือไม่จ่าย)
     *
     * @param  array<string,mixed>  $mapped  ผลจาก normalizeRow()
     * @param  array<string,mixed>  $attribution  ผลจาก attribute()
     * @return array{approved:bool, checks:array<string,bool>, reason:string}
     */
    protected function evaluateGate(array $mapped, array $attribution, bool $syntheticOrderId): array
    {
        $checks = [
            // 1) คนยืนยันชื่อฟิลด์จริงแล้วหรือยัง — ถ้ายัง แปลว่าเรายัง "อ่านรายงานไม่เป็น" จะจ่ายไม่ได้
            'mapping_verified' => $this->isMappingVerified(),
            // 2) ลาซาด้าบอกว่าเคลียร์ค่าคอมให้เราแล้วจริง
            'settled' => $mapped['status'] !== null && in_array($mapped['status'], self::SETTLED_STATUSES, true),
            // 3) มียอดค่าคอมจริงมากกว่า 0
            'payout_positive' => $mapped['payout'] !== null && $mapped['payout'] > 0,
            // 4) รู้ว่าใครเป็นคนแนะนำ (จาก click log ของเราเอง — ลาซาด้าไม่ส่ง subId มาให้)
            'attributed' => ! empty($attribution['user_id']),
            // 5) มีเลขออเดอร์จริง (เลขสังเคราะห์ = กันซ้ำไม่ได้ 100% → ห้ามอนุมัติ)
            'real_order_id' => ! $syntheticOrderId,
            // 6) อ่าน "เวลาออเดอร์" ได้จริง
            //    ⚠️ ถ้าเวลาเป็น null หน้าต่าง attribution จะกลายเป็น [ตอนนี้-30วัน, ตอนนี้]
            //       = ผลการจับคู่ "ขึ้นกับว่า sync ตอนไหน" → sync วันนี้ได้คน A, sync อีก 40 วันได้คน B
            //       แล้วทั้งคู่ถูกอนุมัติ = จ่ายซ้ำสองคนสำหรับออเดอร์เดียว ห้ามเด็ดขาด
            'order_time_known' => ! empty($mapped['time']),
            // 7) สกุลเงินต้องเป็นบาท (หรือไม่ระบุแต่ยืนยัน mapping แล้ว)
            //    กันเคสออเดอร์ข้ามประเทศรายงานเป็น USD/CNY แล้วเราบันทึกเป็นบาทตรงๆ = ผิดหลายเท่า
            'currency_ok' => $mapped['currency'] === null
                ? false
                : in_array(strtoupper((string) $mapped['currency']), ['THB'], true),
        ];

        $failed = array_keys(array_filter($checks, fn ($ok) => ! $ok));

        return [
            'approved' => empty($failed),
            'checks' => $checks,
            'reason' => empty($failed) ? 'ผ่านครบทุกข้อ' : ('ไม่ผ่าน: '.implode(', ', $failed)),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // การระบุตัวผู้แนะนำ (attribution) — ต้องมาจาก click log ของเราเอง
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * จับคู่แถว conversion กับคลิกในระบบเรา
     *
     * ⚠️ ลาซาด้า "ไม่รองรับ" subId/trackingId ต่อผู้ใช้ (ทดสอบแล้ว 6 รูปแบบได้ token เดียวกันหมด)
     *    → ตัวระบุตัวคนเดียวที่เชื่อได้คือ marketplace_link_clicks ของเราเอง
     *
     * เกณฑ์ (เข้มไว้ก่อน):
     *   - คลิกต้องเกิดก่อนเวลาออเดอร์ (เผื่อคลาดเคลื่อนโซนเวลา CLICK_TIME_SKEW_HOURS)
     *   - และอยู่ในหน้าต่าง ATTRIBUTION_WINDOW_DAYS วัน
     *   - ถ้าคลิกที่เข้าเกณฑ์มาจาก "ผู้ใช้คนเดียว" → ให้เครดิตคลิกล่าสุดของคนนั้น
     *   - ถ้าไม่เจอเลย หรือมีหลายคน (กำกวม) → ไม่เดา คืน null ทั้งหมด → ออเดอร์ค้าง pending
     *
     * @param  array<string,mixed>  $mapped
     * @return array{user_id:?int, link_id:?int, click_id:?int, reason:string}
     */
    protected function attribute(MarketplaceAccount $account, array $mapped, ?MarketplaceOrder $existing): array
    {
        $none = fn (string $reason) => ['user_id' => null, 'link_id' => null, 'click_id' => null, 'reason' => $reason];

        $productId = $mapped['productId'];
        if ($productId === null) {
            return $none('ไม่มี productId ในแถว — ระบุสินค้าไม่ได้');
        }

        $links = $this->resolveCandidateLinks($account, $productId);
        if ($links->isEmpty()) {
            return $none('ไม่พบลิงก์ affiliate ในระบบที่ผูกกับสินค้า '.$productId);
        }

        $orderTime = $mapped['time'] instanceof Carbon ? $mapped['time']->copy() : null;
        $windowEnd = $orderTime ? $orderTime->copy()->addHours(self::CLICK_TIME_SKEW_HOURS) : Carbon::now();
        $windowStart = ($orderTime ?? Carbon::now())->copy()->subDays(self::ATTRIBUTION_WINDOW_DAYS);

        $query = MarketplaceLinkClick::whereIn('affiliate_link_id', $links->pluck('id')->all())
            ->where('clicked_at', '>=', $windowStart)
            ->where('clicked_at', '<=', $windowEnd);

        // กัน "คลิกใบเดียวถูกใช้ซ้ำ" — คลิกที่ถูกผูกกับออเดอร์อื่นไปแล้วห้ามเอามาใช้อีก
        // ยกเว้นคลิกที่ผูกกับออเดอร์ใบนี้เอง (จำเป็นเพื่อให้รัน sync ซ้ำได้ผลเดิม)
        $query->where(function ($w) use ($existing) {
            $w->where('converted', false);
            if ($existing) {
                $w->orWhere('order_id', $existing->id);
            }
        });

        // 🚨 นับ "จำนวนผู้แนะนำที่เป็นไปได้" จากชุดเต็มก่อนเสมอ ห้ามนับจากชุดที่ถูกตัดแล้ว
        //    เดิมดึงมาแค่ 200 คลิกล่าสุดแล้วค่อยเช็คความกำกวม → ถ้าสินค้าฮิตมีคลิก 250 ครั้ง
        //    (249 ครั้งของคน A ที่กดรัวๆ + 1 ครั้งของคน B ที่ซื้อจริงและเก่ากว่า)
        //    คลิกของ B จะถูกตัดทิ้ง เหลือผู้แนะนำคนเดียวคือ A → ระบบ "มั่นใจผิด" แล้วจ่ายให้ A
        //    ด่านกันเดานี้คือหัวใจของงานนี้ ห้ามให้มันทำงานบนข้อมูลที่ถูกตัด
        $linkIdsForCount = $links->pluck('id')->all();
        $distinctUserCount = (int) (clone $query)
            ->join('marketplace_affiliate_links as mal_c', 'mal_c.id', '=', 'marketplace_link_clicks.affiliate_link_id')
            ->whereIn('marketplace_link_clicks.affiliate_link_id', $linkIdsForCount)
            ->distinct()
            ->count('mal_c.user_id');

        if ($distinctUserCount > 1) {
            return $none('กำกวม: มีผู้แนะนำ '.$distinctUserCount.' คนที่คลิกเข้าเกณฑ์ ต้องให้คนตัดสิน');
        }

        $clicks = $query->orderByDesc('clicked_at')->orderByDesc('id')->limit(200)->get();
        if ($clicks->isEmpty()) {
            return $none('ไม่พบคลิกที่เข้าเกณฑ์ (สินค้า '.$productId.', หน้าต่าง '.self::ATTRIBUTION_WINDOW_DAYS.' วัน)');
        }

        $userByLink = $links->pluck('user_id', 'id');
        $users = $clicks->map(fn ($c) => (int) ($userByLink[$c->affiliate_link_id] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($users->isEmpty()) {
            return $none('คลิกที่เจอไม่มีเจ้าของลิงก์ (user_id ว่าง)');
        }
        if ($users->count() > 1) {
            // 🚨 กำกวม = มีผู้แนะนำมากกว่า 1 คนที่อาจเป็นเจ้าของยอดนี้ → ห้ามเดา
            return $none('กำกวม: มีผู้แนะนำ '.$users->count().' คนที่คลิกเข้าเกณฑ์ ต้องให้คนตัดสิน');
        }

        $userId = (int) $users->first();
        $winner = $clicks->first(fn ($c) => (int) ($userByLink[$c->affiliate_link_id] ?? 0) === $userId);

        return [
            'user_id' => $userId,
            'link_id' => (int) $winner->affiliate_link_id,
            'click_id' => (int) $winner->id,
            'reason' => 'จับคู่กับคลิก #'.$winner->id.' เมื่อ '.optional($winner->clicked_at)->toDateTimeString(),
        ];
    }

    /**
     * หาลิงก์ affiliate ในระบบที่ผูกกับ external product id ของลาซาด้า
     *
     * ⚠️ มี id 2 ชุดที่อาจถูกเก็บใน marketplace_affiliate_links.product_id
     *    (ก) marketplace_products.id  ← ตรงตาม foreign key ของตาราง = ชุดที่ถูกต้องตาม schema
     *    (ข) products.id (สินค้าหน้าร้าน is_affiliate)  ← เผื่อกรณีระบบคลิกใช้ id ชุดนี้
     *    ห้ามรวม 2 ชุดเข้าด้วยกันเด็ดขาด เพราะเลข id อาจชนกันข้ามตาราง → ให้เครดิตผิดคน = จ่ายเงินผิด
     *    วิธีแก้: ใช้ชุด (ก) ก่อนเสมอ ถ้าไม่เจอค่อยลองชุด (ข) และตัดลิงก์ที่ product_id
     *    ไปตรงกับแถวใน marketplace_products ออก (แปลว่าลิงก์นั้นเป็นของชุด ก อยู่แล้ว เลขชนกันเฉย ๆ)
     *
     * @return Collection<int,MarketplaceAffiliateLink>
     */
    protected function resolveCandidateLinks(MarketplaceAccount $account, string $externalProductId): Collection
    {
        // (ก) ชุดที่ถูกต้องตาม foreign key
        $marketplaceIds = MarketplaceProduct::query()
            ->where('platform_id', $account->platform_id)
            ->where('external_product_id', $externalProductId)
            ->pluck('id')
            ->all();

        if (! empty($marketplaceIds)) {
            return MarketplaceAffiliateLink::whereIn('product_id', $marketplaceIds)->get();
        }

        // (ข) fallback: สินค้าหน้าร้าน products (เฉพาะกรณีระบบคลิกเก็บ id ชุดนี้)
        $storefrontIds = Product::query()
            ->where('external_platform', 'lazada')
            ->where('external_product_id', $externalProductId)
            ->pluck('id')
            ->all();

        if (empty($storefrontIds)) {
            return collect();
        }

        $candidates = MarketplaceAffiliateLink::whereIn('product_id', $storefrontIds)->get();
        if ($candidates->isEmpty()) {
            return $candidates;
        }

        // ตัดลิงก์ที่ product_id ชนกับ id ของ marketplace_products (ไม่ใช่ชุด id เดียวกัน = ห้ามนับ)
        $collision = MarketplaceProduct::whereIn('id', $candidates->pluck('product_id')->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $candidates->reject(fn ($l) => in_array((int) $l->product_id, $collision, true))->values();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // เขียนลง DB
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * ประมวลผล 1 แถว → upsert marketplace_orders (idempotent)
     *
     * @param  array<string,mixed>  $row  แถวดิบจาก API
     * @param  array<string,mixed>  $summary  ตัวนับ (แก้ไขในตัว)
     */
    protected function processRow(MarketplaceAccount $account, array $row, array &$summary): void
    {
        $mapped = $this->normalizeRow($row);

        // เลขออเดอร์: ถ้าลาซาด้าไม่ส่งมา ต้องสังเคราะห์เพื่อให้ upsert ซ้ำไม่เกิดแถวซ้ำ
        // แต่แถวสังเคราะห์ "ห้ามอนุมัติ" (ดู evaluateGate ข้อ real_order_id)
        $syntheticOrderId = false;
        $externalOrderId = $mapped['orderId'];
        if ($externalOrderId === null) {
            $syntheticOrderId = true;
            // ใช้เฉพาะฟิลด์ที่ "ไม่เปลี่ยนตามสถานะ" มาทำ hash เพื่อให้แถวเดิมได้ id เดิมเสมอ
            // (ถ้าใช้ยอดเงิน/สถานะด้วย พอสถานะเปลี่ยนจะกลายเป็นออเดอร์ใหม่ = แถวซ้ำ)
            $hasSeed = $mapped['productId'] !== null || $mapped['time'] !== null || $mapped['amount'] !== null;
            $seed = $hasSeed
                ? (string) json_encode([
                    'p' => $mapped['productId'],
                    't' => $mapped['time']?->toIso8601String(),
                    'a' => $mapped['amount'],
                ])
                // ⚠️ แถวที่ไม่มีอะไรให้ยึดเลย → hash ทั้งแถว ไม่งั้นแถวขยะทุกแถวจะยุบรวมเป็นใบเดียว
                //    แล้วคนจะตรวจย้อนหลังไม่เห็นว่ามีกี่รายการจริง
                : (string) json_encode($row);
            $externalOrderId = 'LZD-NOID-'.substr(sha1($seed), 0, 24);
        }

        // unique key จริงของตารางคือ (account_id, external_order_id)
        $existing = MarketplaceOrder::where('account_id', $account->id)
            ->where('external_order_id', $externalOrderId)
            ->first();

        $attribution = $this->attribute($account, $mapped, $existing);
        $gate = $this->evaluateGate($mapped, $attribution, $syntheticOrderId);

        if ($attribution['user_id']) {
            $summary['attributed']++;
        }
        $gate['approved'] ? $summary['approved']++ : $summary['pending']++;

        if ($this->dryRun) {
            $existing ? $summary['updated']++ : $summary['stored']++;

            return;
        }

        DB::transaction(function () use ($account, $row, $mapped, $attribution, $gate, $externalOrderId, $syntheticOrderId, $existing, &$summary) {
            $settled = $gate['checks']['settled'];

            $payload = [
                'platform_id' => $account->platform_id,
                'order_number' => $mapped['orderId'],
                // ⚠️ คอลัมน์ subtotal/total_amount เป็น NOT NULL → ต้องใส่ตัวเลข
                //    map ไม่ได้จึงใส่ 0 แต่ "0 ตรงนี้ไม่ได้แปลว่ายืนยันยอดแล้ว" — ค่าจริงดูที่ order_data
                //    ด่านตรวจใช้ค่าจาก $mapped (null = ไม่รู้) ไม่ได้ใช้คอลัมน์นี้
                'subtotal' => $mapped['amount'] ?? 0,
                'total_amount' => $mapped['amount'] ?? 0,
                // ห้าม default เป็น THB — ถ้าลาซาด้าไม่ส่งสกุลเงินมา แล้วเราเดาว่าบาท
                // ออเดอร์ข้ามประเทศที่รายงานเป็น USD จะถูกบันทึกเป็นบาท = ผิดหลายสิบเท่า
                'currency' => $mapped['currency'],
                'commission_amount' => $mapped['payout'] ?? 0,
                'net_commission' => $mapped['payout'] ?? 0,
                'commission_rate' => $this->deriveRate($mapped),
                'order_status' => $mapped['status'],
                // 🚨 เขียน "จ่ายแล้ว" ได้เฉพาะตอนที่ยืนยัน mapping แล้วเท่านั้น
                //    ตอนธงยังปิด = เรายัง "อ่านรายงานไม่เป็น" การเดาสถานะจากชื่อฟิลด์ที่เดาเอา
                //    แล้วปั๊มลงช่อง payment_status='paid' เท่ากับหลอกคนตรวจเอง —
                //    แอดมินเปิดหน้ามาเห็น "จ่ายแล้ว" ก็จะกดอนุมัติ ทั้งที่ระบบไม่เคยแน่ใจ
                //    ความไม่แน่ใจต้องไปถึงคนตัดสิน ไม่ใช่ถูกกลบด้วยคำว่า paid
                'payment_status' => ($settled && $this->isMappingVerified()) ? 'paid' : null,
                'ordered_at' => $mapped['time'],
                'paid_at' => ($settled && $this->isMappingVerified()) ? $mapped['time'] : null,
                'order_data' => [
                    'lazada_raw' => $row, // 🔒 เก็บแถวดิบไว้เสมอ ให้คนตรวจย้อนหลังได้ 100%
                    '_sync_meta' => [
                        'source' => 'lazada:/marketing/conversion/report',
                        'synced_at' => Carbon::now()->toIso8601String(),
                        'synthetic_order_id' => $syntheticOrderId,
                        'mapped' => [
                            'orderId' => $mapped['orderId'],
                            'productId' => $mapped['productId'],
                            'payout' => $mapped['payout'],
                            'amount' => $mapped['amount'],
                            'status' => $mapped['status'],
                            'time' => $mapped['time']?->toIso8601String(),
                            'currency' => $mapped['currency'],
                        ],
                        'gate' => $gate['checks'],
                        'gate_reason' => $gate['reason'],
                        'attribution_reason' => $attribution['reason'],
                        'mapping_fields_are_guessed' => ! $this->isMappingVerified(),
                    ],
                ],
                'last_synced_at' => Carbon::now(),
                'commission_calculated_at' => Carbon::now(),
            ];

            if ($existing) {
                // 🚨 ห้าม "ลดชั้น" การตัดสินใจของคน: ถ้าเคยอนุมัติ/จ่าย/ยกเลิกไปแล้ว ปล่อยไว้อย่างนั้น
                $locked = in_array((string) $existing->commission_status, ['approved', 'paid', 'cancelled'], true);
                if (! $locked) {
                    $payload['commission_status'] = $gate['approved'] ? 'approved' : 'pending';
                    $payload['commission_approved_at'] = $gate['approved'] ? Carbon::now() : null;
                }

                // เติมผู้แนะนำเฉพาะตอนที่ยังว่าง (คนอาจแก้มือไว้ ห้ามทับ)
                if (! $existing->affiliate_user_id && $attribution['user_id']) {
                    $payload['affiliate_user_id'] = $attribution['user_id'];
                    $payload['affiliate_link_id'] = $attribution['link_id'];
                }

                // 🚨 ออเดอร์ที่ "จ่ายไปแล้ว/ยกเลิกแล้ว" = หลักฐานการเงิน ห้ามเขียนทับตัวเลขเด็ดขาด
                //    เคสจริง: sync เดือนเดิมซ้ำอีกครั้ง แล้วลาซาด้าเปลี่ยนชื่อฟิลด์/มีการคืนเงิน
                //    → payout อ่านไม่ได้เป็น null → commission_amount ถูกทับเป็น 0
                //      ทั้งที่ commission_paid_at ยังมีค่า = บันทึกว่า "จ่ายเท่าไหร่" หายไปตลอดกาล
                //    ล็อกแล้วให้แตะได้แค่ร่องรอยการ sync + payload ดิบ
                if ($locked) {
                    $payload = array_intersect_key($payload, array_flip([
                        'order_data', 'last_synced_at', 'affiliate_user_id', 'affiliate_link_id',
                    ]));
                }

                $existing->fill($payload)->save();
                $order = $existing;
                $summary['updated']++;
            } else {
                $payload['account_id'] = $account->id;
                $payload['external_order_id'] = $externalOrderId;
                $payload['affiliate_user_id'] = $attribution['user_id'];
                $payload['affiliate_link_id'] = $attribution['link_id'];
                $payload['commission_status'] = $gate['approved'] ? 'approved' : 'pending';
                $payload['commission_approved_at'] = $gate['approved'] ? Carbon::now() : null;

                $order = MarketplaceOrder::create($payload);
                $summary['stored']++;
            }

            // ผูกคลิกกับออเดอร์ (ทำได้แม้ยัง pending — เป็นแค่ร่องรอย ไม่ใช่การจ่ายเงิน)
            if ($attribution['click_id']) {
                MarketplaceLinkClick::where('id', $attribution['click_id'])
                    ->where(function ($w) use ($order) {
                        $w->where('converted', false)->orWhere('order_id', $order->id);
                    })
                    ->update(['converted' => true, 'order_id' => $order->id]);
            }

            // 🚨 สร้างแถวค่าคอมได้ "เฉพาะเมื่อผ่านด่านตรวจครบทุกข้อ" เท่านั้น
            //
            // ⚠️ ต้องเช็คสถานะ "ที่บันทึกไว้จริงในออเดอร์" ด้วย ไม่ใช่ดูแค่ผลด่านตรวจ:
            //    เคสจริง — แอดมินเห็นออเดอร์นี้แล้วตัดสินว่าทุจริต ตั้ง commission_status='cancelled'
            //    ต่อมามีคนเปิดธง mapping_verified ด้วยเหตุผลอื่น → sync รอบใหม่ $gate['approved']=true
            //    ถ้าไม่เช็คตรงนี้ ระบบจะสร้างแถวค่าคอม 'approved' ใหม่ = ข้ามคำสั่งยกเลิกของแอดมินเงียบๆ
            //
            // และต้องจ่ายให้ "เจ้าของออเดอร์ที่บันทึกไว้" เท่านั้น ไม่ใช่ผู้ที่เพิ่งจับคู่ได้รอบนี้
            //    ไม่งั้นออเดอร์เดียวอาจจ่ายสองคน (รอบแรกคน A, sync รอบหลังจับได้คน B)
            $orderStatusNow = (string) $order->commission_status;
            $payeeId = $order->affiliate_user_id ?: $attribution['user_id'];

            if ($gate['approved']
                && $payeeId
                && in_array($orderStatusNow, ['approved', 'paid'], true)) {
                $this->ensureCommissionRow($order, $account, $mapped, [
                    'user_id' => $payeeId,
                    'link_id' => $order->affiliate_link_id ?: $attribution['link_id'],
                    'click_id' => $attribution['click_id'],
                ]);
            }
        });
    }

    /**
     * อัตราค่าคอมเป็น % — คำนวณได้เฉพาะเมื่อรู้ทั้งยอดขายและค่าคอม
     * (คอลัมน์เป็น decimal(5,2) → เพดาน 999.99)
     *
     * @param  array<string,mixed>  $mapped
     */
    protected function deriveRate(array $mapped): float
    {
        if ($mapped['payout'] === null || $mapped['amount'] === null || $mapped['amount'] <= 0) {
            return 0.0;
        }

        // คอลัมน์ decimal(5,2) รับได้ -999.99 ถึง 999.99 → ต้องคุมทั้งสองฝั่ง
        // (ค่าคอมติดลบเกิดได้จากการคืนสินค้า ถ้าไม่คุมฝั่งลบ insert จะพัง)
        return round(max(-999.99, min(999.99, ($mapped['payout'] / $mapped['amount']) * 100)), 2);
    }

    /**
     * สร้างแถว marketplace_commissions แบบ idempotent (รันซ้ำไม่เกิดซ้ำ / ไม่จ่ายซ้ำ)
     *
     * สถานะ = 'approved' (อนุมัติแล้ว แต่ยัง "ไม่จ่าย") — การจ่ายเข้าวอลเลตเป็นอีกขั้นตอน
     * ที่ต้องมีคนกดต่างหาก ไฟล์นี้ไม่แตะเงินในวอลเลตเลย
     *
     * @param  array<string,mixed>  $mapped
     * @param  array<string,mixed>  $attribution
     */
    protected function ensureCommissionRow(MarketplaceOrder $order, MarketplaceAccount $account, array $mapped, array $attribution): void
    {
        MarketplaceCommission::firstOrCreate(
            [
                'order_id' => $order->id,
                'user_id' => $attribution['user_id'],
                'commission_type' => 'direct',
            ],
            [
                'affiliate_link_id' => $attribution['link_id'],
                'platform_id' => $account->platform_id,
                'order_amount' => $mapped['amount'] ?? 0,
                'commission_rate' => $this->deriveRate($mapped),
                'commission_amount' => $mapped['payout'],
                'net_commission' => $mapped['payout'],
                // ห้าม default เป็น THB — ถ้าลาซาด้าไม่ส่งสกุลเงินมา แล้วเราเดาว่าบาท
                // ออเดอร์ข้ามประเทศที่รายงานเป็น USD จะถูกบันทึกเป็นบาท = ผิดหลายสิบเท่า
                'currency' => $mapped['currency'],
                'status' => 'approved',
                'approved_at' => Carbon::now(),
                'notes' => 'อนุมัติอัตโนมัติจากรายงาน conversion ของ Lazada (ลาซาด้ายืนยันเคลียร์ค่าคอมแล้ว) '
                    .'| ออเดอร์ '.$order->external_order_id
                    .' | สถานะจากลาซาด้า: '.(string) $mapped['status'],
            ]
        );
    }
}
