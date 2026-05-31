<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\SlipVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 🧾 SlipOK Service (2026-05-31)
 *
 * ตรวจสลิปโอนเงินผ่าน SlipOK API — fallback ชั้นสองเมื่อ SMS checker ไม่พบยอด
 *
 * หลักการ:
 *   - SMS checker = ตัวหลัก (ฟรี) ตัดบิลก่อนเสมอ
 *   - SlipOK = เรียกเฉพาะตอน "ส่งสลิปแล้ว 1 นาทียังไม่ตัด" หรือ "ลูกค้าพิมพ์/ถาม" → ประหยัดโควตา
 *   - QR ในสลิป (EMVCo) ฝัง transRef → SlipOK ยิงถามธนาคารจริง → คืนยอด/บัญชีปลายทาง/ผู้โอน
 *   - ปลอมไม่ได้: ตัดต่อยอด→คืนยอดจริง / QR มั่ว→ไม่เจอ / สลิปซ้ำ→1012 / โอนผิดบัญชี→1014
 *
 * API (เอกสาร SlipOK):
 *   - POST https://api.slipok.com/api/line/apikey/{branchId}  header x-authorization: {apiKey}
 *   - body: data(QR string) | files(รูป) | url(ลิงก์รูป) + log:true (dedup + เช็คบัญชีผู้รับ)
 *   - GET  .../{branchId}/quota  → เช็คโควตาคงเหลือ
 *
 * @see FortuneConversationService::trySlipOkVerifyForReading
 */
class SlipOkService
{
    public const DECISION_APPROVE = 'approve';

    public const DECISION_REJECT_AMOUNT = 'reject_amount';

    public const DECISION_REJECT_RECEIVER = 'reject_receiver';

    public const DECISION_DUPLICATE = 'duplicate';

    public const DECISION_NO_QR = 'no_qr';

    public const DECISION_QUOTA = 'quota';

    public const DECISION_BANK_DELAY = 'bank_delay';

    public const DECISION_STALE = 'stale';

    public const DECISION_ERROR = 'error';

    public const DECISION_DISABLED = 'disabled';

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_slipok_verify ?? false)
            && ! empty($this->settings->slipok_branch_id)
            && ! empty($this->settings->slipok_api_key);
    }

    protected function baseUrl(): string
    {
        $branch = trim((string) $this->settings->slipok_branch_id);

        return 'https://api.slipok.com/api/line/apikey/'.rawurlencode($branch);
    }

    /**
     * 📊 เช็คโควตาคงเหลือ (ใช้ในหน้า admin ปุ่มทดสอบ)
     *
     * @return array{success: bool, quota?: int, overQuota?: int, message?: string}
     */
    public function checkQuota(): array
    {
        if (empty($this->settings->slipok_branch_id) || empty($this->settings->slipok_api_key)) {
            return ['success' => false, 'message' => 'ยังไม่ได้กรอก Branch ID / API Key'];
        }

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['x-authorization' => (string) $this->settings->slipok_api_key])
                ->get($this->baseUrl().'/quota');

            $json = $resp->json() ?? [];

            if ($resp->successful() && ($json['success'] ?? false)) {
                $data = $json['data'] ?? [];

                return [
                    'success' => true,
                    'quota' => (int) ($data['quota'] ?? 0),
                    'overQuota' => (int) ($data['overQuota'] ?? 0),
                    'specialQuota' => (int) ($data['specialQuota'] ?? 0),
                    'endDate' => $data['endDate'] ?? null,
                    'message' => 'เชื่อมต่อสำเร็จ',
                ];
            }

            return [
                'success' => false,
                'message' => $json['message'] ?? ('เช็คโควตาไม่สำเร็จ (HTTP '.$resp->status().')'),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'เชื่อมต่อ SlipOK ไม่ได้: '.$e->getMessage()];
        }
    }

    /**
     * 🔎 ตรวจสลิปจากไฟล์ในเครื่อง (multipart) — ใช้กับสลิปที่ดาวน์โหลด/เก็บไว้
     */
    public function verifyByFile(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            return $this->normalize(0, ['message' => 'ไฟล์สลิปไม่พบ'], null);
        }

        try {
            $req = Http::timeout(25)
                ->withHeaders(['x-authorization' => (string) $this->settings->slipok_api_key])
                ->attach('files', file_get_contents($absolutePath), 'slip.jpg');

            $resp = $req->post($this->baseUrl(), [
                'log' => $this->settings->slipok_use_log ? 'true' : 'false',
            ]);

            return $this->normalize($resp->status(), $resp->json() ?? [], $resp->json());
        } catch (\Throwable $e) {
            Log::warning('SlipOkService: verifyByFile exception', ['error' => $e->getMessage()]);

            return $this->normalize(0, ['message' => $e->getMessage()], null);
        }
    }

    /**
     * 🔎 ตรวจสลิปจาก URL รูป (FB CDN เป็น public URL)
     */
    public function verifyByUrl(string $url): array
    {
        try {
            $resp = Http::timeout(25)
                ->withHeaders(['x-authorization' => (string) $this->settings->slipok_api_key])
                ->asJson()
                ->post($this->baseUrl(), [
                    'url' => $url,
                    'log' => (bool) $this->settings->slipok_use_log,
                ]);

            return $this->normalize($resp->status(), $resp->json() ?? [], $resp->json());
        } catch (\Throwable $e) {
            Log::warning('SlipOkService: verifyByUrl exception', ['error' => $e->getMessage()]);

            return $this->normalize(0, ['message' => $e->getMessage()], null);
        }
    }

    /**
     * 🧹 แปลง response ดิบ → รูปแบบมาตรฐาน
     *
     * @return array{ok: bool, http: int, error_code: int|null, message: string,
     *   transRef: string|null, amount: float|null, receiver_account: string|null,
     *   receiving_bank: string|null, sending_bank: string|null, sender_name: string|null,
     *   trans_timestamp: string|null, raw: array}
     */
    protected function normalize(int $http, array $json, ?array $raw): array
    {
        $data = $json['data'] ?? [];
        $dataSuccess = is_array($data) ? ($data['success'] ?? null) : null;
        $topSuccess = $json['success'] ?? null;

        // error code อาจอยู่ที่ top-level หรือใน data
        $errorCode = $json['code'] ?? ($data['code'] ?? null);
        $errorCode = $errorCode !== null ? (int) $errorCode : null;

        $ok = $http === 200 && ($topSuccess === true) && ($dataSuccess !== false) && ! empty($data['transRef']);

        return [
            'ok' => $ok,
            'http' => $http,
            'error_code' => $errorCode,
            'message' => (string) ($json['message'] ?? ($data['message'] ?? '')),
            'transRef' => $data['transRef'] ?? null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'receiver_account' => $data['receiver']['account']['value']
                ?? ($data['receiver']['proxy']['value'] ?? null),
            'receiver_name' => $data['receiver']['displayName'] ?? ($data['receiver']['name'] ?? null),
            'receiving_bank' => $data['receivingBank'] ?? null,
            'sending_bank' => $data['sendingBank'] ?? null,
            'sender_name' => $data['sender']['displayName'] ?? ($data['sender']['name'] ?? null),
            'trans_timestamp' => $data['transTimestamp'] ?? null,
            'raw' => $raw ?? $json,
        ];
    }

    /**
     * 🚦 ตัดสินใจจากผล verify เทียบกับบิล — 4 ด่าน
     *
     * @return array{decision: string, reason: string, verify: array}
     */
    public function evaluateForReading(FortuneReading $reading, array $verify): array
    {
        // ── ด่าน 1: verify จริงไหม (map error code) ──────────────
        if (! $verify['ok']) {
            $decision = match ($verify['error_code']) {
                1012 => self::DECISION_DUPLICATE,
                1014 => self::DECISION_REJECT_RECEIVER,
                1007, 1008, 1011 => self::DECISION_NO_QR,
                1004, 1003, 1015 => self::DECISION_QUOTA,
                1010, 1009 => self::DECISION_BANK_DELAY,
                default => self::DECISION_ERROR,
            };

            return ['decision' => $decision, 'reason' => $verify['message'] ?: ('error '.$verify['error_code']), 'verify' => $verify];
        }

        // ── ด่าน 4 (ทำก่อนเพราะถูกสุด): transRef ซ้ำไหม ──────────
        $transRef = (string) $verify['transRef'];
        $existing = SlipVerification::where('trans_ref', $transRef)->first();
        if ($existing) {
            return ['decision' => self::DECISION_DUPLICATE, 'reason' => 'transRef ซ้ำ (เคยใช้กับบิลอื่นแล้ว)', 'verify' => $verify];
        }

        // ── ด่าน 2: บัญชีปลายทาง = ของเราไหม ─────────────────────
        if (! $this->receiverMatchesOurAccounts($verify['receiver_account'])
            && ! $this->receiverMatchesOurAccounts($verify['receiver_name'])) {
            return ['decision' => self::DECISION_REJECT_RECEIVER, 'reason' => 'บัญชีปลายทางไม่ตรงกับบัญชีร้าน', 'verify' => $verify];
        }

        // ── ด่าน 3: ยอด ≥ ขั้นต่ำ (ไม่ต่ำกว่า 99 / ราคาบิล) ──────
        $minAmount = $this->minAmountForReading($reading);
        if (($verify['amount'] ?? 0) + 0.001 < $minAmount) {
            return [
                'decision' => self::DECISION_REJECT_AMOUNT,
                'reason' => 'ยอดโอน ฿'.number_format((float) $verify['amount'], 2).' ต่ำกว่าขั้นต่ำ ฿'.number_format($minAmount, 2),
                'verify' => $verify,
            ];
        }

        // ── ด่าน 5: สลิปวันนี้เท่านั้น (user spec 2026-05-31) ─────
        if (! $this->isTransToday($verify)) {
            return [
                'decision' => self::DECISION_STALE,
                'reason' => 'สลิปไม่ใช่ของวันนี้ ('.($verify['trans_timestamp'] ?? '-').')',
                'verify' => $verify,
            ];
        }

        return ['decision' => self::DECISION_APPROVE, 'reason' => 'ผ่านทุกด่าน', 'verify' => $verify];
    }

    /**
     * 📅 สลิปเป็นของวันนี้ไหม (โซนเวลาไทย) — user spec: รับเฉพาะสลิปวันนี้
     *   ไม่มี timestamp → ไม่บล็อก (เชื่อว่า SlipOK verify แล้ว)
     */
    protected function isTransToday(array $verify): bool
    {
        $ts = $verify['trans_timestamp'] ?? null;
        if (empty($ts)) {
            return true;
        }

        try {
            $slipDate = \Carbon\Carbon::parse($ts)->setTimezone('Asia/Bangkok')->toDateString();
            $today = \Carbon\Carbon::now('Asia/Bangkok')->toDateString();

            return $slipDate === $today;
        } catch (\Throwable $e) {
            return true; // parse ไม่ได้ → ไม่บล็อก
        }
    }

    /**
     * ยอดขั้นต่ำที่อนุมัติได้ = max(slipok_min_amount, ราคาบิลจริง)
     *   - กันโอนต่ำกว่าราคาสินค้า (Celtic 99 / Deep 39)
     *   - user spec: "ไม่ต่ำกว่า 99"
     */
    protected function minAmountForReading(FortuneReading $reading): float
    {
        // ราคาบิลจริง (Celtic 99 / Deep 39) — ลูกค้าต้องโอน ≥ ราคาสินค้านั้น
        $basePrice = (float) ($reading->uniquePaymentAmount?->base_amount ?? 0);
        if ($basePrice > 0) {
            return $basePrice;
        }

        // ไม่มี UPA (เช่นเคสสร้างบิลจากสลิป) → ใช้ floor ขั้นต่ำ (default 99 — user spec "ไม่ต่ำกว่า 99")
        return (float) ($this->settings->slipok_min_amount ?? 99.00);
    }

    /**
     * 📛 บัญชีปลายทางตรงกับบัญชีร้านไหม
     *   SlipOK mask เลขบัญชี (เช่น "xxx-x-x5514-x") → เทียบ 4 หลักท้ายของบัญชี active
     */
    public function receiverMatchesOurAccounts(?string $receiverValue): bool
    {
        if (empty($receiverValue)) {
            return false;
        }

        // ดึงเฉพาะตัวเลขจาก masked value
        $recvDigits = preg_replace('/\D/', '', $receiverValue);
        if (mb_strlen($recvDigits) < 4) {
            // ไม่มีเลขให้เทียบ (proxy เป็นชื่อ) → เชื่อได้เฉพาะตอน log:true (SlipOK บังคับบัญชีผู้รับ = 1014)
            //   ถ้า log ปิด = ไม่มีใครเช็คบัญชีปลายทาง → ปฏิเสธไว้ก่อน (กันอนุมัติสลิปที่โอนเข้าบัญชีคนอื่น)
            return (bool) ($this->settings->slipok_use_log ?? false);
        }
        $recvLast4 = mb_substr($recvDigits, -4);

        $accounts = PaymentBankAccount::active()->get();
        foreach ($accounts as $acc) {
            foreach ([$acc->account_number, $acc->promptpay_id] as $mine) {
                $mineDigits = preg_replace('/\D/', '', (string) $mine);
                if (mb_strlen($mineDigits) >= 4 && mb_substr($mineDigits, -4) === $recvLast4) {
                    return true;
                }
            }
        }

        return false;
    }
}
