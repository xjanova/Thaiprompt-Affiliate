<?php

namespace App\Services\TpixPay;

use App\Models\TpixPayment;

/**
 * ตรวจธุรกรรมบนเชน TPIX ว่าจ่ายใบเสนอยอดนี้จริงหรือไม่ — อ่านจากโหนดเชนเท่านั้น ไม่เชื่อข้อมูลจากผู้ใช้
 *
 * เงื่อนไขทั้งหมด (ต้นแบบ: TokenFactoryFeeVerifier ของ tpix.online):
 *  1. ธุรกรรมมีอยู่บนเชน 4289 และโอน "เข้ากระเป๋ารับเงินของใบนี้"
 *  2. ยอดเหรียญ (wei) ตรงกับใบ "ทุกหลัก" — ใบแต่ละใบมีเลขท้ายเฉพาะ จึงจับคู่ได้ใบเดียว
 *     (ถ้ายอมรับ ≥ ยอด คนอื่นจะเอาธุรกรรมของผู้จ่ายจริงมาอ้างกับใบยอดต่ำกว่าของตัวเองได้)
 *  3. receipt สถานะสำเร็จ (0x1)
 *  4. เกิดหลังออกใบ (กันเอาธุรกรรมเก่ามาอ้าง) — เกิดหลังหมดเวลา = late
 *  5. confirmation ครบตามที่แอดมินตั้ง (ยังไม่ครบ = confirming ไม่ใช่ปฏิเสธ)
 *
 * ❗ RPC ล่ม = โยน RuntimeException (ผู้เรียกต้องถือว่า "ยังไม่รู้" ไม่ใช่ "ไม่ผ่าน")
 */
class TpixTxVerifier
{
    public const OK = 'ok';

    public const CONFIRMING = 'confirming';

    public const NOT_FOUND = 'not_found';

    public const LATE = 'late';

    public const REJECTED = 'rejected';

    public function __construct(
        private readonly TpixRpcClient $rpc,
        private readonly TpixPaySettings $settings,
    ) {}

    public static function isValidHash(?string $hash): bool
    {
        return is_string($hash) && preg_match('/^0x[0-9a-fA-F]{64}$/', $hash) === 1;
    }

    /**
     * @return array{status: string, reason: string|null, from: string|null, value_wei: string|null, block_number: int|null, confirmations: int, timestamp: int|null}
     */
    public function check(TpixPayment $payment, string $hash): array
    {
        $result = [
            'status' => self::REJECTED,
            'reason' => null,
            'from' => null,
            'value_wei' => null,
            'block_number' => null,
            'confirmations' => 0,
            'timestamp' => null,
        ];

        if (! self::isValidHash($hash)) {
            return ['reason' => 'รูปแบบเลขธุรกรรมไม่ถูกต้อง (ต้องขึ้นต้น 0x ตามด้วยตัวอักษร 64 ตัว)'] + $result;
        }

        $tx = $this->rpc->getTransactionByHash($hash);
        if ($tx === null) {
            return ['status' => self::NOT_FOUND, 'reason' => 'ยังไม่พบธุรกรรมนี้บนเชน TPIX — ถ้าเพิ่งโอน กรุณารอสักครู่'] + $result;
        }

        $result['from'] = isset($tx['from']) ? strtolower((string) $tx['from']) : null;

        if (strtolower((string) ($tx['to'] ?? '')) !== strtolower($payment->receive_address)) {
            return ['reason' => 'ธุรกรรมนี้ไม่ได้โอนเข้ากระเป๋ารับเงินของรายการนี้'] + $result;
        }

        if (! $this->chainMatches($tx)) {
            return ['reason' => 'ธุรกรรมนี้ไม่ได้อยู่บนเชน TPIX (chain '.config('tpix_pay.chain_id').')'] + $result;
        }

        $value = TpixAmount::hexToDec((string) ($tx['value'] ?? '0x0'));
        $result['value_wei'] = $value;
        if (bccomp($value, (string) $payment->amount_wei, 0) !== 0) {
            return ['reason' => sprintf(
                'ยอดที่โอน (%s TPIX) ไม่ตรงกับยอดที่ต้องชำระ (%s TPIX) — ต้องโอนให้ตรงทุกหลัก',
                TpixAmount::display(TpixAmount::weiToTpix($value), 8),
                TpixAmount::display((string) $payment->amount_tpix, 8)
            )] + $result;
        }

        if (empty($tx['blockNumber'])) {
            return ['status' => self::CONFIRMING, 'reason' => 'ธุรกรรมยังรอเข้าบล็อก'] + $result;
        }

        $receipt = $this->rpc->getTransactionReceipt($hash);
        if ($receipt === null || empty($receipt['blockNumber'])) {
            return ['status' => self::CONFIRMING, 'reason' => 'ธุรกรรมยังรอเข้าบล็อก'] + $result;
        }

        if (strtolower((string) ($receipt['status'] ?? '')) !== '0x1') {
            return ['reason' => 'ธุรกรรมนี้ล้มเหลวบนเชน (ไม่มีการโอนเหรียญจริง)'] + $result;
        }

        $blockNumber = (int) TpixAmount::hexToDec((string) $receipt['blockNumber']);
        $result['block_number'] = $blockNumber;

        $latest = $this->rpc->blockNumber();
        $result['confirmations'] = max(0, $latest - $blockNumber + 1);

        $block = $this->rpc->getBlockByNumber($blockNumber);
        if ($block === null || ! isset($block['timestamp'])) {
            return ['status' => self::CONFIRMING, 'reason' => 'กำลังอ่านข้อมูลบล็อก'] + $result;
        }

        $timestamp = (int) TpixAmount::hexToDec((string) $block['timestamp']);
        $result['timestamp'] = $timestamp;
        $skew = (int) config('tpix_pay.clock_skew_seconds', 30);

        $createdAt = $payment->created_at?->getTimestamp() ?? 0;
        if ($timestamp < $createdAt - $skew) {
            return ['reason' => 'ธุรกรรมนี้เกิดขึ้นก่อนออกรายการชำระเงิน จึงใช้กับรายการนี้ไม่ได้'] + $result;
        }

        if ($payment->expires_at !== null && $timestamp > $payment->expires_at->getTimestamp() + $skew) {
            return ['status' => self::LATE, 'reason' => 'โอนหลังรายการหมดเวลา'] + $result;
        }

        if ($result['confirmations'] < $this->settings->confirmations()) {
            return ['status' => self::CONFIRMING, 'reason' => null] + $result;
        }

        return ['status' => self::OK, 'reason' => null] + $result;
    }

    /**
     * ธุรกรรมอยู่บนเชน 4289 — ดึงจากโหนดของเชนนี้อยู่แล้ว ปฏิเสธเฉพาะเมื่อระบุ chain อื่นชัดเจน
     *
     * @param  array<string, mixed>  $tx
     */
    private function chainMatches(array $tx): bool
    {
        $expected = (int) config('tpix_pay.chain_id');

        if (isset($tx['chainId']) && $tx['chainId'] !== null && $tx['chainId'] !== '') {
            return (int) TpixAmount::hexToDec((string) $tx['chainId']) === $expected;
        }

        if (isset($tx['v'])) {
            $v = (int) TpixAmount::hexToDec((string) $tx['v']);
            if ($v >= 35) {
                return intdiv($v - 35, 2) === $expected;
            }
        }

        return true;
    }
}
