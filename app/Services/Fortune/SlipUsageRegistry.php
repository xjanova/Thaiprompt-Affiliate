<?php

namespace App\Services\Fortune;

use App\Models\SlipVerification;

/**
 * 🧾 SlipUsageRegistry (2026-09-15) — "สลิปใบนี้ถูกใช้ไปแล้วในระบบ Thaiprompt หรือยัง" ที่เดียว
 *
 * ใช้ร่วมกันทั้งสองเส้นตรวจสลิปของเว็บ จันทรา.online:
 *   - POST /api/v1/juntra/payment/verify-slip   (ต่อผู้ใช้ — เดิม)
 *   - POST /api/v1/juntra/server/slips/verify   (เซิร์ฟเวอร์ต่อเซิร์ฟเวอร์ — ใหม่)
 * และให้ /slips/check ใช้ค้นทีละหลายเลข
 *
 * หลักการ (CONTRACT §B1): ตอบ "ใช้แล้ว" เฉพาะเมื่อมี **หลักฐานเชิงบวก** เท่านั้น
 *   - slip_registry : transRef อยู่ใน slip_verifications (บอทใช้แล้ว / จันทราเคลมแล้ว)
 *   - local_qr      : ด่านถอด QR เอง (localDuplicateFromQr) เจอเลขที่เคยบันทึก — ไม่ได้ยิง SlipOK
 *   - sms_payment   : ยอด+เวลาตรงกับ SMS ที่ตัดบิลของเราไปแล้ว (ด่าน 4.5 ของบอท)
 *   ไม่รู้ transRef = ไม่เดา (used=false) — ให้ฝั่งจันทราส่งแอดมินตรวจเอง
 *
 * ⚠️ ผลจาก localDuplicateFromQr มี transRef ของแถวที่เจอติดมาด้วย จึงเจอใน registry เสมอ
 *    → รายงานเป็น 'local_qr' (เจาะจงกว่า: รู้ว่าไม่ได้เสียโควตา SlipOK) พร้อม used_by ของแถวนั้น
 */
class SlipUsageRegistry
{
    public const SOURCE_SLIP_REGISTRY = 'slip_registry';

    public const SOURCE_SMS_PAYMENT = 'sms_payment';

    public const SOURCE_LOCAL_QR = 'local_qr';

    public function __construct(protected SlipOkService $slipok) {}

    /**
     * @param  array  $verify  ผลจาก SlipOkService::verifyByFile()/normalize()
     * @return array{used: bool, used_source: string|null, used_by: array|null}
     */
    public function usageFor(array $verify): array
    {
        $ref = trim((string) ($verify['transRef'] ?? ''));
        $fromLocalQr = SlipOkService::isLocalQrDuplicate($verify);

        if ($ref !== '') {
            $row = $this->findByRef($ref);
            if ($row !== null) {
                return [
                    'used' => true,
                    'used_source' => $fromLocalQr ? self::SOURCE_LOCAL_QR : self::SOURCE_SLIP_REGISTRY,
                    'used_by' => $this->usedBy($row),
                ];
            }
        }

        // ด่าน QR เจอแถวแต่ตอนนี้แถวหายไปแล้ว (ถูกลบกลางทาง) — ยังนับเป็นหลักฐานว่าเคยใช้
        if ($fromLocalQr) {
            return ['used' => true, 'used_source' => self::SOURCE_LOCAL_QR, 'used_by' => null];
        }

        if ($ref !== '' && $this->slipok->slipMatchesUsedSmsPayment($verify)) {
            return ['used' => true, 'used_source' => self::SOURCE_SMS_PAYMENT, 'used_by' => null];
        }

        return ['used' => false, 'used_source' => null, 'used_by' => null];
    }

    /**
     * ข้อมูลตอบกลับของเส้นตรวจสลิปทั้งสองเส้น (รูปแบบเดียวกัน — CONTRACT §B1)
     */
    public function describe(array $verify): array
    {
        $usage = $this->usageFor($verify);

        return [
            'ok' => (bool) ($verify['ok'] ?? false),
            'error_code' => isset($verify['error_code']) ? (int) $verify['error_code'] : null,
            'message' => (string) ($verify['message'] ?? ''),
            'trans_ref' => $verify['transRef'] ?? null,
            'amount' => isset($verify['amount']) ? (float) $verify['amount'] : null,
            'receiver_account' => $verify['receiver_account'] ?? null,
            'receiver_name' => $verify['receiver_name'] ?? null,
            'sender_name' => $verify['sender_name'] ?? null,
            'sending_bank' => $verify['sending_bank'] ?? null,
            'receiving_bank' => $verify['receiving_bank'] ?? null,
            'trans_timestamp' => $verify['trans_timestamp'] ?? null,
            // เข้าบัญชีร้านเราจริงไหม — กฎเดียวกับด่าน 2 ของบอท (เลขบัญชี หรือ ชื่อผู้รับ)
            'receiver_matches' => $this->receiverMatches($verify),
            // อายุสลิปไม่เกิน MAX_SLIP_AGE_DAYS (เวลาไทย) — ไม่มีเวลาในสลิป = ผ่าน
            'slip_age_ok' => $this->slipok->slipAgeOk($verify),
            'used' => $usage['used'],
            'used_source' => $usage['used_source'],
            'used_by' => $usage['used_by'],
        ];
    }

    public function receiverMatches(array $verify): bool
    {
        return $this->slipok->receiverMatchesOurAccounts($verify['receiver_account'] ?? null)
            || $this->slipok->receiverMatchesOurAccounts($verify['receiver_name'] ?? null);
    }

    public function findByRef(string $ref): ?SlipVerification
    {
        $ref = trim($ref);

        return $ref === '' ? null : SlipVerification::where('trans_ref', $ref)->first();
    }

    /**
     * ค้นหลายเลขพร้อมกัน — คืนเฉพาะเลขที่เจอ
     *
     * @param  array<int,string>  $refs
     * @return array<int, array{ref: string, platform: string|null, user: string|null, reading_id: int|null, at: string|null}>
     */
    public function lookupMany(array $refs): array
    {
        $refs = array_values(array_unique(array_filter(array_map(
            fn ($r) => trim((string) $r),
            $refs
        ), fn ($r) => $r !== '')));

        if (empty($refs)) {
            return [];
        }

        return SlipVerification::whereIn('trans_ref', $refs)
            ->orderBy('id')
            ->get()
            ->map(fn (SlipVerification $row) => ['ref' => (string) $row->trans_ref] + $this->usedBy($row))
            ->values()
            ->all();
    }

    /**
     * @return array{platform: string|null, user: string|null, reading_id: int|null, at: string|null}
     */
    public function usedBy(SlipVerification $row): array
    {
        return [
            'platform' => $row->consumed_by_platform,
            'user' => $row->consumed_by_user_id,
            'reading_id' => $row->fortune_reading_id !== null ? (int) $row->fortune_reading_id : null,
            'at' => $row->verified_at?->toIso8601String() ?? $row->created_at?->toIso8601String(),
        ];
    }
}
