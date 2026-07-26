<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCommission;
use App\Models\MarketplaceOrder;

/**
 * ด่านตรวจ "เงินเข้าจริงหรือยัง" ก่อนอนุมัติ/จ่ายค่าคอม Affiliate
 *
 * ╔══════════════════════════════════════════════════════════════════════════════════════╗
 * ║ 🚨 กฎเหล็กของไฟล์นี้: คำตอบเริ่มต้นคือ "ไม่อนุญาต" (DO NOT PAY)                          ║
 * ║                                                                                      ║
 * ║ คำสั่งเจ้าของระบบ: "จ่าย/อนุมัติ PV ได้ก็ต่อเมื่อ ลูกค้าชำระเงินเสร็จ                        ║
 * ║ และลาซาด้าจ่ายค่าคอมให้เราจริง"                                                        ║
 * ║                                                                                      ║
 * ║ LazadaConversionSyncService กันฝั่ง "ระบบอัตโนมัติ" ไว้แล้ว                              ║
 * ║ ไฟล์นี้กันอีกฝั่งหนึ่ง — ฝั่ง "คนกดปุ่มในหน้าแอดมิน"                                        ║
 * ║ เพราะปุ่ม อนุมัติ/จ่าย ในหน้าแอดมินเรียก MarketplaceCommissionService ตรง ๆ              ║
 * ║ ถ้าไม่มีด่านนี้ แอดมินกดปุ่มเดียวก็จ่ายเงินออกจากบริษัทได้                                   ║
 * ║ ทั้งที่ลาซาด้ายังไม่เคยโอนค่าคอมงวดนั้นมาให้เราเลย = บริษัทเสียเงินจริง                       ║
 * ╚══════════════════════════════════════════════════════════════════════════════════════╝
 *
 * ขอบเขต (สำคัญ):
 *   - บังคับใช้เฉพาะ "ออเดอร์ลาซาด้าที่มีเลขออเดอร์จากลาซาด้า" เท่านั้น
 *   - ค่าคอมจากช่องทางอื่น (Shopee / TikTok / ออเดอร์ในระบบเราเอง) → ปล่อยผ่าน ไม่ยุ่ง
 *     เพื่อไม่ให้ไปทำงานเดิมของหลังบ้านพัง
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" — ไม่แก้ DB ไม่แตะเงิน แค่ตอบว่า "จ่ายได้ / ยังจ่ายไม่ได้ เพราะอะไร"
 */
class AffiliateSettlementGuard
{
    /**
     * 🚨 สถานะของ "ออเดอร์" ที่แปลว่าลาซาด้าเคลียร์ค่าคอมให้เราแล้ว
     *
     * ค่านี้ถูกเขียนโดย LazadaConversionSyncService เมื่อผ่านด่านตรวจ 7 ข้อครบเท่านั้น
     * (ห้ามเติม 'calculated' เข้ามาเด็ดขาด — 'calculated' แปลว่า "เราคำนวณเองแล้ว"
     *  ไม่ได้แปลว่า "ลาซาด้าจ่ายเงินให้เราแล้ว" คนละเรื่องกันคนละโลก)
     */
    public const ORDER_SETTLED_STATUSES = ['approved', 'paid'];

    /**
     * ตรวจว่าค่าคอมแถวนี้ "อนุมัติ/จ่ายได้หรือยัง"
     *
     * @return array{allowed:bool, reason:?string, severity:string}
     *                                                              allowed  = จ่ายได้หรือไม่ (false = ห้ามแตะเงิน)
     *                                                              reason   = เหตุผลภาษาไทย (null = ผ่านสะอาด ไม่มีอะไรต้องเตือน)
     *                                                              severity = 'block' คือห้ามจ่าย | 'warn' คือจ่ายได้ แต่ถ้ามี reason ให้คนอ่านก่อน
     *
     * @example
     * $verdict = (new AffiliateSettlementGuard)->check($commission);
     * if (! $verdict['allowed']) { return response()->json(['success' => false, 'message' => $verdict['reason']], 422); }
     */
    /**
     * ตรวจที่ "ตัวออเดอร์" ตรงๆ — ใช้ก่อน *สร้าง* แถวค่าคอม
     *
     * ทำไมต้องมีแยกจาก check(): ด่านที่ปุ่มอนุมัติ/จ่ายอย่างเดียวไม่พอ
     * เพราะปุ่ม "คำนวณคอมมิชชั่น" สร้างแถวค่าคอมได้ตั้งแต่ออเดอร์ยังไม่ผ่านด่าน
     * พอมีแถวแล้วมันจะไปนั่งรอในคิวอนุมัติ = เปิดทางให้เงินไหลออกทีหลัง
     * กันตั้งแต่ยังไม่ให้เกิดแถวจะปลอดภัยกว่า
     *
     * @return array{allowed:bool, reason:?string, severity:string}
     */
    public function checkOrder(MarketplaceOrder $order): array
    {
        // ไม่ใช่ออเดอร์ลาซาด้า → ไม่ใช่หน้าที่ของด่านนี้ (ห้ามไปกระทบ marketplace อื่น)
        if (! $this->isLazadaAffiliateOrder($order)) {
            return $this->pass();
        }

        if (! in_array((string) $order->commission_status, self::ORDER_SETTLED_STATUSES, true)) {
            return $this->block(
                'ลาซาด้ายังไม่ยืนยันว่าเคลียร์ค่าคอมให้เรา — ยังคำนวณ/จ่ายค่าคอมไม่ได้'
                .' (สถานะตอนนี้: '.($order->commission_status ?: 'ไม่ระบุ').')'
            );
        }

        if (empty($order->paid_at)) {
            return $this->block('ยังไม่มีหลักฐานว่าชำระเงินเสร็จ (ออเดอร์ไม่มีวันเวลาที่ชำระเงิน)');
        }

        return $this->pass($this->cautionFromSyncMeta($order));
    }

    public function check(MarketplaceCommission $commission): array
    {
        $order = $commission->order;

        // 1) ไม่มีออเดอร์ต้นทาง = ตรวจสอบอะไรไม่ได้เลย → ไม่อนุญาต (ค่าเริ่มต้นคือไม่จ่าย)
        if (! $order instanceof MarketplaceOrder) {
            return $this->block('ไม่พบออเดอร์ต้นทาง — ตรวจสอบไม่ได้ว่าลาซาด้าจ่ายค่าคอมให้เราหรือยัง');
        }

        // 2) ไม่ใช่ออเดอร์ลาซาด้า → ไม่ใช่หน้าที่ของด่านนี้ ปล่อยผ่านตามพฤติกรรมเดิม
        if (! $this->isLazadaAffiliateOrder($order)) {
            return $this->pass();
        }

        // 3) ลาซาด้ายืนยันเคลียร์ค่าคอมให้เราแล้วหรือยัง
        if (! in_array((string) $order->commission_status, self::ORDER_SETTLED_STATUSES, true)) {
            return $this->block(
                'ลาซาด้ายังไม่ยืนยันว่าเคลียร์ค่าคอมให้เรา — ยังจ่ายไม่ได้'
                .' (สถานะค่าคอมของออเดอร์ตอนนี้: '.($order->commission_status ?: 'ไม่ระบุ').')'
            );
        }

        // 4) มีหลักฐานว่าลูกค้าชำระเงินเสร็จหรือยัง
        if (empty($order->paid_at)) {
            return $this->block('ยังไม่มีหลักฐานว่าชำระเงินเสร็จ (ออเดอร์ไม่มีวันเวลาที่ชำระเงิน)');
        }

        // 5) ยอดค่าคอมต้องมีจริง — 0 หรือติดลบ ห้ามจ่าย
        if ((float) $commission->commission_amount <= 0) {
            return $this->block('ยอดค่าคอมเป็น 0 หรือติดลบ — ไม่มีอะไรให้จ่าย');
        }

        // 5.1) เงินที่โอนเข้าวอลเลตจริงคือ net_commission ไม่ใช่ commission_amount
        //      ถ้า net ติดลบแล้วปล่อยผ่าน วอลเลตลูกค้าจะถูก "หักเงิน" แทนที่จะได้รับ
        if ((float) $commission->net_commission <= 0) {
            return $this->block('ยอดสุทธิที่จะโอนเข้าวอลเลต (net_commission) เป็น 0 หรือติดลบ — ไม่จ่าย');
        }

        // ── ผ่านด่านแล้ว แต่ยังมีเรื่องที่ควรให้คนดูก่อน ─────────────────────────────
        $caution = $this->cautionFromSyncMeta($order);
        if ($caution !== null) {
            return $this->pass($caution);
        }

        return $this->pass();
    }

    /**
     * ป้ายสถานะสำหรับแสดงในหน้าแอดมิน (ให้ Blade ไม่ต้องคิดเอง)
     *
     * @return array{state:string, label:string, tooltip:string, blocked:bool}
     *                                                                         state = 'settled' | 'blocked' | 'warn' | 'na'
     */
    public function settlementBadge(MarketplaceCommission $commission): array
    {
        $order = $commission->order;

        // ไม่มีออเดอร์ต้นทาง = ตรวจไม่ได้ → ห้ามจ่าย (ป้ายต้องบอกตรง ๆ ว่าตรวจไม่ได้)
        if (! $order instanceof MarketplaceOrder) {
            return [
                'state' => 'blocked',
                'label' => 'ตรวจสอบไม่ได้',
                'tooltip' => 'ไม่พบออเดอร์ต้นทาง — ตรวจสอบไม่ได้ว่าลาซาด้าจ่ายค่าคอมให้เราหรือยัง',
                'blocked' => true,
            ];
        }

        if (! $this->isLazadaAffiliateOrder($order)) {
            return [
                'state' => 'na',
                'label' => 'ไม่ใช่ออเดอร์ลาซาด้า',
                'tooltip' => 'ค่าคอมรายการนี้ไม่ได้มาจาก Lazada Affiliate จึงไม่ต้องรอลาซาด้ายืนยัน',
                'blocked' => false,
            ];
        }

        $verdict = $this->check($commission);

        if (! $verdict['allowed']) {
            return [
                'state' => 'blocked',
                'label' => 'รอลาซาด้ายืนยัน',
                'tooltip' => (string) $verdict['reason'],
                'blocked' => true,
            ];
        }

        if ($verdict['reason'] !== null) {
            return [
                'state' => 'warn',
                'label' => 'ยืนยันแล้ว (ควรตรวจ)',
                'tooltip' => (string) $verdict['reason'],
                'blocked' => false,
            ];
        }

        return [
            'state' => 'settled',
            'label' => 'ลาซาด้ายืนยันแล้ว',
            'tooltip' => 'ออเดอร์นี้ลาซาด้าเคลียร์ค่าคอมให้เราแล้ว และมีวันเวลาที่ชำระเงินครบ',
            'blocked' => false,
        ];
    }

    /**
     * ออเดอร์นี้เป็น "ออเดอร์ลาซาด้าที่ต้องรอลาซาด้าเคลียร์เงิน" หรือไม่
     *
     * เกณฑ์:
     *   - ต้องมีเลขออเดอร์จากภายนอก (external_order_id) — ไม่มี = ไม่ใช่ออเดอร์ที่ซิงก์มาจากลาซาด้า
     *   - และแพลตฟอร์มต้องเป็นลาซาด้า
     *   - เผื่อกรณีแถวแพลตฟอร์มหาย/ถูกลบ: ดูร่องรอยการซิงก์ใน order_data['_sync_meta']['source'] แทน
     *     (ไม่งั้นแค่ลบแถว platform ทิ้ง ด่านนี้ก็หมดผลทันที)
     */
    public function isLazadaAffiliateOrder(MarketplaceOrder $order): bool
    {
        if (trim((string) $order->external_order_id) === '') {
            return false;
        }

        $slug = strtolower(trim((string) ($order->platform?->slug ?? '')));
        if ($slug !== '') {
            return str_contains($slug, 'lazada');
        }

        // แพลตฟอร์มระบุไม่ได้ → ดูร่องรอยการซิงก์แทน
        $source = strtolower((string) ($this->syncMeta($order)['source'] ?? ''));

        return $source !== '' && str_contains($source, 'lazada');
    }

    /**
     * ข้อควรระวังที่อ่านได้จากผลด่านตรวจตอนซิงก์ (order_data['_sync_meta'])
     *
     * ใช้จับเคส "ออเดอร์ถูกตั้งเป็นอนุมัติด้วยมือ/แก้ DB" ซึ่งผลด่านตรวจที่บันทึกไว้จะไม่ครบ
     * → ยังให้จ่ายได้ (เพราะมีคนตัดสินใจไปแล้ว) แต่ต้องขึ้นเตือนให้คนอ่านก่อนกด
     *
     * @return string|null null = ไม่มีอะไรต้องเตือน
     */
    protected function cautionFromSyncMeta(MarketplaceOrder $order): ?string
    {
        $meta = $this->syncMeta($order);

        if ($meta === []) {
            return 'ออเดอร์นี้ไม่มีบันทึกผลตรวจจากการซิงก์ (_sync_meta) — ตรวจที่มาของยอดก่อนอนุมัติ';
        }

        if (! empty($meta['mapping_fields_are_guessed'])) {
            return 'ยังไม่ได้ยืนยันชื่อฟิลด์ของรายงานลาซาด้า — ตัวเลขในออเดอร์นี้มาจากการเดาชื่อฟิลด์';
        }

        $gate = is_array($meta['gate'] ?? null) ? $meta['gate'] : [];
        $failed = array_keys(array_filter($gate, fn ($ok) => ! $ok));

        if ($failed !== []) {
            return 'ผลตรวจตอนซิงก์ไม่ผ่านบางข้อ ('.implode(', ', $failed).') แต่ออเดอร์ถูกตั้งเป็นอนุมัติแล้ว — ตรวจก่อนจ่าย';
        }

        return null;
    }

    /**
     * อ่าน order_data['_sync_meta'] แบบปลอดภัย (คืน [] เมื่ออ่านไม่ได้)
     *
     * @return array<string,mixed>
     */
    public function syncMeta(MarketplaceOrder $order): array
    {
        $data = $order->order_data;

        if (! is_array($data)) {
            return [];
        }

        $meta = $data['_sync_meta'] ?? null;

        return is_array($meta) ? $meta : [];
    }

    /** คำตอบ "ห้ามจ่าย" */
    protected function block(string $reason): array
    {
        return ['allowed' => false, 'reason' => $reason, 'severity' => 'block'];
    }

    /** คำตอบ "จ่ายได้" (ใส่ข้อความเตือนได้ถ้ามีเรื่องที่คนควรอ่านก่อน) */
    protected function pass(?string $caution = null): array
    {
        return ['allowed' => true, 'reason' => $caution, 'severity' => 'warn'];
    }
}
