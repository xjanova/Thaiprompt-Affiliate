<?php

namespace App\Services\Payment;

use App\Models\PaymentBankAccount;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * PromptPay Payment Provider
 *
 * รองรับการชำระเงินผ่าน QR Code พร้อมเพย์
 * สร้าง QR Code ตามมาตรฐาน EMVCo ของ BOT (ธนาคารแห่งประเทศไทย)
 */
class PromptPayProvider implements PaymentProviderInterface
{
    protected $gateway;

    /** @var PaymentBankAccount|null บัญชีธนาคารหลักที่มี PromptPay */
    protected $bankAccount;

    /**
     * PromptPay AID (Application ID) ตามมาตรฐาน BOT
     */
    private const PROMPTPAY_AID = 'A000000677010111';

    public function __construct()
    {
        try {
            $this->gateway = PaymentGateway::findByCode('promptpay');
        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน ให้ข้ามการโหลด config
            Log::debug('PromptPayProvider: Cannot load gateway config - '.$e->getMessage());
            $this->gateway = null;
        }

        // ดึงบัญชีธนาคารที่ตั้งเป็นหลัก + มี PromptPay
        try {
            $this->bankAccount = PaymentBankAccount::active()
                ->hasPromptpay()
                ->orderByDesc('is_default')
                ->first();
        } catch (\Exception $e) {
            Log::debug('PromptPayProvider: Cannot load bank account - '.$e->getMessage());
            $this->bankAccount = null;
        }
    }

    /**
     * Validate PromptPay payment
     *
     * @throws Exception
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบจำนวนเงิน
        if ($transaction->amount <= 0) {
            throw new Exception('Invalid payment amount');
        }

        // ตรวจสอบ limits ถ้ามี gateway config
        if ($this->gateway) {
            $limits = $this->gateway->limits ?? [];
            $minDeposit = $limits['min_deposit'] ?? 1;
            $maxDeposit = $limits['max_deposit'] ?? 50000;

            if ($transaction->amount < $minDeposit) {
                throw new Exception("จำนวนเงินขั้นต่ำคือ {$minDeposit} บาท");
            }

            if ($transaction->amount > $maxDeposit) {
                throw new Exception("จำนวนเงินสูงสุดคือ {$maxDeposit} บาท");
            }
        }

        return true;
    }

    /**
     * Process PromptPay payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        // ดึง PromptPay ID - ใช้ PaymentBankAccount ก่อน ถ้าไม่มีจึง fallback ไป PaymentGateway
        $promptPayId = $this->bankAccount?->promptpay_id
            ?? $this->gateway?->getCredential('promptpay_id')
            ?? config('payment.promptpay.id', '');
        $promptPayName = $this->bankAccount?->account_name
            ?? $this->gateway?->getCredential('promptpay_name')
            ?? config('app.name');
        $promptPayType = $this->bankAccount?->promptpay_type
            ?? $this->gateway?->getCredential('promptpay_type')
            ?? 'phone'; // phone, citizen_id, ewallet

        // สร้าง reference number
        $refNo = 'PP-'.strtoupper(substr($transaction->transaction_id, -8));

        // สร้าง QR Code image (data URI) ตามมาตรฐาน EMVCo
        $qrDataUri = $this->generatePromptPayQRCode($transaction, $refNo, $promptPayId, $promptPayType);

        Log::info('PromptPay payment initiated', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'ref_no' => $refNo,
            'promptpay_type' => $promptPayType,
            'has_qr_image' => str_starts_with($qrDataUri, 'data:image/'),
        ]);

        return [
            'status' => 'processing',
            'gateway' => 'promptpay',
            'qr_code' => $qrDataUri,
            'ref_no' => $refNo,
            'response' => [
                'promptpay_id' => $this->maskPromptPayId($promptPayId),
                'promptpay_name' => $promptPayName,
                'amount' => $transaction->amount,
                'currency' => 'THB',
                'expires_at' => $transaction->expired_at?->toIso8601String() ?? now()->addMinutes(30)->toIso8601String(),
            ],
        ];
    }

    /**
     * Verify PromptPay payment (webhook callback)
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // ตรวจสอบ reference number
        if (isset($data['ref_no']) && $data['ref_no'] === $transaction->promptpay_ref_no) {
            // ตรวจสอบสถานะ
            if (isset($data['status']) && in_array($data['status'], ['success', 'completed', 'paid'])) {
                // ตรวจสอบจำนวนเงิน (anti-tampering)
                if (isset($data['amount'])) {
                    $paidAmount = (float) $data['amount'];
                    if (abs($paidAmount - $transaction->amount) > 0.01) {
                        Log::warning('PromptPay payment amount mismatch', [
                            'transaction_id' => $transaction->transaction_id,
                            'expected' => $transaction->amount,
                            'received' => $paidAmount,
                        ]);

                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Refund PromptPay payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        // PromptPay ไม่รองรับ refund อัตโนมัติ ต้องทำ manual
        Log::info('PromptPay refund requested', [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $amount,
        ]);

        return [
            'status' => 'pending',
            'refund_amount' => $amount,
            'message' => 'Refund will be processed manually via bank transfer.',
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    /**
     * สร้าง PromptPay QR Code Image ตามมาตรฐาน EMVCo
     *
     * สร้าง payload ตามรูปแบบ TLV (Tag-Length-Value) ของ BOT
     * แล้วใช้ BaconQrCode render เป็น SVG data URI
     *
     * @return string data URI ของ QR Code image (data:image/svg+xml;base64,...)
     */
    protected function generatePromptPayQRCode(PaymentTransaction $transaction, string $refNo, string $promptPayId, string $type): string
    {
        if (empty($promptPayId)) {
            Log::warning('PromptPay ID not configured — ไม่สามารถสร้าง QR ได้');

            return '';
        }

        try {
            // สร้าง EMVCo PromptPay payload
            $emvPayload = $this->buildPromptPayPayload($promptPayId, $type, (float) $transaction->amount);

            // ใช้ BaconQrCode สร้าง QR Code image
            return $this->renderQrCodeToDataUri($emvPayload);
        } catch (\Exception $e) {
            Log::error('Failed to generate PromptPay QR', [
                'error' => $e->getMessage(),
                'promptpay_id' => $this->maskPromptPayId($promptPayId),
                'amount' => $transaction->amount,
            ]);

            return '';
        }
    }

    /**
     * สร้าง PromptPay EMVCo Payload ตามมาตรฐาน BOT
     *
     * รูปแบบ TLV (Tag-Length-Value):
     * - Tag 00: Payload Format Indicator = "01"
     * - Tag 01: Point of Initiation Method = "12" (Dynamic QR with amount)
     * - Tag 29: Merchant Account Info (PromptPay)
     *   - Sub 00: AID = "A000000677010111"
     *   - Sub 01: เบอร์โทรศัพท์ (format 0066XXXXXXXXX) หรือ
     *   - Sub 02: เลขบัตรประชาชน (13 หลัก) หรือ
     *   - Sub 03: E-Wallet ID
     * - Tag 53: Transaction Currency = "764" (THB)
     * - Tag 54: Transaction Amount
     * - Tag 58: Country Code = "TH"
     * - Tag 63: CRC-16 Checksum
     *
     * @param  string  $promptPayId  เลข PromptPay (เบอร์โทร, บัตร ปชช., หรือ e-wallet)
     * @param  string  $type  ประเภท: phone, citizen_id, ewallet
     * @param  float  $amount  จำนวนเงิน (บาท)
     * @return string EMVCo payload string พร้อม CRC
     */
    public function buildPromptPayPayload(string $promptPayId, string $type, float $amount): string
    {
        // ล้าง ID (เอา - และ space ออก)
        $cleanId = preg_replace('/[\s\-]/', '', $promptPayId);

        // แปลง ID ตามประเภท
        $subTag = match ($type) {
            'citizen_id' => '02',
            'ewallet' => '03',
            default => '01',  // phone
        };

        // สำหรับเบอร์โทร: แปลง 0812345678 → 0066812345678
        if ($subTag === '01') {
            $cleanId = $this->formatThaiPhone($cleanId);
        }

        // สร้าง Merchant Account Info (Tag 29)
        $merchantAccountInfo = $this->tlv('00', self::PROMPTPAY_AID)
            .$this->tlv($subTag, $cleanId);

        // สร้าง payload หลัก (ยังไม่มี CRC)
        $payload = $this->tlv('00', '01')                                       // Payload Format Indicator
            .$this->tlv('01', '12')                                             // Point of Initiation (Dynamic QR)
            .$this->tlv('29', $merchantAccountInfo)                             // Merchant Account Info (PromptPay)
            .$this->tlv('53', '764')                                            // Currency (THB)
            .$this->tlv('54', number_format($amount, 2, '.', ''))               // Amount
            .$this->tlv('58', 'TH');                                            // Country Code

        // เพิ่ม CRC (Tag 63, ความยาว 04)
        // ต้องคำนวณจาก payload + "6304" (tag + length ของ CRC เอง)
        $payload .= '6304';
        $crc = $this->crc16($payload);
        $payload .= strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));

        return $payload;
    }

    /**
     * สร้าง TLV (Tag-Length-Value) entry
     *
     * @param  string  $tag  2-digit tag
     * @param  string  $value  ค่า
     * @return string TLV string
     */
    private function tlv(string $tag, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);

        return $tag.$length.$value;
    }

    /**
     * คำนวณ CRC-16 CCITT-FALSE
     *
     * Polynomial: 0x1021, Initial: 0xFFFF
     * ใช้ตามมาตรฐาน EMVCo QR Code
     */
    private function crc16(string $data): int
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return $crc & 0xFFFF;
    }

    /**
     * แปลงเบอร์โทรไทยเป็นรูปแบบ PromptPay (0066...)
     *
     * - 0812345678 → 0066812345678
     * - +66812345678 → 0066812345678
     * - 66812345678 → 0066812345678
     */
    private function formatThaiPhone(string $phone): string
    {
        // ลบ + นำหน้า
        $phone = ltrim($phone, '+');

        // ถ้าขึ้นต้นด้วย 0 (เบอร์ไทย) → แปลงเป็น 0066
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '0066'.substr($phone, 1);
        }

        // ถ้าขึ้นต้นด้วย 66 → เพิ่ม 00 นำหน้า
        if (str_starts_with($phone, '66') && strlen($phone) === 11) {
            return '00'.$phone;
        }

        // ถ้าเป็น 0066 อยู่แล้ว → ใช้เลย
        if (str_starts_with($phone, '0066')) {
            return $phone;
        }

        // Fallback: ใส่ 0066 ข้างหน้า
        return '0066'.$phone;
    }

    /**
     * Render QR Code เป็น SVG data URI ด้วย BaconQrCode
     *
     * @param  string  $payload  ข้อมูลที่จะใส่ใน QR Code
     * @return string data URI (data:image/svg+xml;base64,...) หรือ empty string ถ้าไม่สามารถสร้างได้
     */
    private function renderQrCodeToDataUri(string $payload): string
    {
        // ลอง BaconQrCode (ติดตั้งผ่าน composer)
        if (class_exists(\BaconQrCode\Renderer\ImageRenderer::class)) {
            return $this->renderWithBaconQrCode($payload);
        }

        // ลอง SimpleSoftwareIO/QrCode (ถ้ามี — Laravel package ยอดนิยม)
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return $this->renderWithSimpleQrCode($payload);
        }

        // Fallback: สร้าง SVG ด้วย PHP เอง (ไม่ต้องพึ่ง library)
        return $this->renderWithPurePHP($payload);
    }

    /**
     * Render QR Code ด้วย BaconQrCode
     *
     * ใช้ Error Correction Level H (สูงสุด) เหมือนกับ QR ของ SMS Checker
     * เพื่อให้สแกนได้เร็วมาก (recover 30% data loss)
     */
    private function renderWithBaconQrCode(string $payload): string
    {
        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(
                    300, 1, null, null,
                    \BaconQrCode\Renderer\RendererStyle\Fill::uniformColor(
                        new \BaconQrCode\Renderer\Color\Rgb(255, 255, 255),
                        new \BaconQrCode\Renderer\Color\Rgb(0, 0, 0)
                    )
                ),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );

            $writer = new \BaconQrCode\Writer($renderer);
            $svg = $writer->writeString(
                $payload,
                \BaconQrCode\Encoder\Encoder::DEFAULT_BYTE_MODE_ECODING,
                \BaconQrCode\Common\ErrorCorrectionLevel::H()
            );

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Exception $e) {
            Log::error('BaconQrCode render failed: '.$e->getMessage());

            return $this->renderWithPurePHP($payload);
        }
    }

    /**
     * Render QR Code ด้วย SimpleSoftwareIO/QrCode
     */
    private function renderWithSimpleQrCode(string $payload): string
    {
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(300)
                ->generate($payload);

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (\Exception $e) {
            Log::error('SimpleSoftwareIO QrCode render failed: '.$e->getMessage());

            return $this->renderWithPurePHP($payload);
        }
    }

    /**
     * Fallback: สร้าง QR Code ด้วย PHP ล้วน (ไม่ต้องพึ่ง library ภายนอก)
     *
     * ใช้ Google Charts API สร้าง QR Code URL
     * หมายเหตุ: ต้องมี internet — ถ้าอยากให้ offline ต้องติดตั้ง bacon/bacon-qr-code
     */
    private function renderWithPurePHP(string $payload): string
    {
        // ใช้ Google Charts API เป็น fallback สุดท้าย
        // URL นี้จะ return PNG image ของ QR code
        $encodedPayload = urlencode($payload);

        return "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl={$encodedPayload}&choe=UTF-8";
    }

    /**
     * สร้าง QR Code data URI จากจำนวนเงิน
     *
     * ใช้สำหรับ regenerate QR ในกรณีที่ transaction เก่าไม่มี QR image
     * ดึง PromptPay ID จาก bankAccount/gateway อัตโนมัติ
     *
     * @param  float  $amount  จำนวนเงิน (บาท)
     * @return string data URI ของ QR Code หรือ empty string ถ้าไม่สามารถสร้างได้
     */
    public function generateQrDataUri(float $amount): string
    {
        $promptPayId = $this->bankAccount?->promptpay_id
            ?? $this->gateway?->getCredential('promptpay_id')
            ?? config('payment.promptpay.id', '');
        $promptPayType = $this->bankAccount?->promptpay_type
            ?? $this->gateway?->getCredential('promptpay_type')
            ?? 'phone';

        if (empty($promptPayId)) {
            return '';
        }

        $payload = $this->buildPromptPayPayload($promptPayId, $promptPayType, $amount);

        return $this->renderQrCodeToDataUri($payload);
    }

    /**
     * Mask PromptPay ID for display
     *
     * เบอร์โทร: 081***5678
     * บัตรประชาชน: 112***90123
     */
    protected function maskPromptPayId(string $id): string
    {
        if (strlen($id) < 4) {
            return str_repeat('*', strlen($id));
        }

        return substr($id, 0, 3).str_repeat('*', strlen($id) - 6).substr($id, -3);
    }

    /**
     * Check payment status
     */
    public function checkStatus(PaymentTransaction $transaction): array
    {
        // ในการใช้งานจริง ต้อง query จาก PromptPay API หรือ bank API
        return [
            'status' => $transaction->status,
            'ref_no' => $transaction->promptpay_ref_no,
            'amount' => $transaction->amount,
        ];
    }

    /**
     * ดึงข้อมูลบัญชี PromptPay สำหรับแสดงผล
     *
     * ใช้ PaymentBankAccount ก่อน ถ้าไม่มีจึง fallback ไป PaymentGateway
     */
    public function getAccountInfo(): array
    {
        // ลำดับ: PaymentBankAccount → PaymentGateway
        if ($this->bankAccount && $this->bankAccount->hasPromptpay()) {
            return [
                'promptpay_id' => $this->maskPromptPayId($this->bankAccount->promptpay_id),
                'promptpay_name' => $this->bankAccount->account_name,
                'promptpay_type' => $this->bankAccount->promptpay_type ?? 'phone',
                'sms_checker_enabled' => $this->bankAccount->isSmsCheckerEnabled(),
                'bank_account_id' => $this->bankAccount->id,
            ];
        }

        if (! $this->gateway) {
            return [];
        }

        $id = $this->gateway->getCredential('promptpay_id') ?? '';

        return [
            'promptpay_id' => $this->maskPromptPayId($id),
            'promptpay_name' => $this->gateway->getCredential('promptpay_name'),
            'promptpay_type' => $this->gateway->getCredential('promptpay_type') ?? 'phone',
            'sms_checker_enabled' => false,
        ];
    }

    /**
     * ดึงบัญชีธนาคารที่เปิดใช้ SMS Checker สำหรับ matching
     */
    public function getSmsCheckerAccount(): ?PaymentBankAccount
    {
        if ($this->bankAccount && $this->bankAccount->isSmsCheckerEnabled()) {
            return $this->bankAccount;
        }

        // หาจากบัญชีที่เปิด SMS Checker ตัวใดก็ได้
        return PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->orderByDesc('is_default')
            ->first();
    }
}
