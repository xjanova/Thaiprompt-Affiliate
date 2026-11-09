<?php

namespace App\Services;

use App\Events\NewTransactionCreated;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\WalletSetting;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PromptPayProvider;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentGatewayService
{
    /**
     * payment_method ของบิลเติมเงินแบบ "โอนแล้วแนบสลิป" — ต้องรอแอดมินอนุมัติเท่านั้น
     * แยกจาก 'bank_transfer' เพื่อไม่ให้ระบบ SMS จับคู่ยอดแล้วอนุมัติเอง (ดู processBankTransfer)
     */
    public const BANK_SLIP_METHOD = 'bank_transfer_slip';

    protected $walletService;

    protected $notificationService;

    public function __construct(WalletService $walletService, NotificationService $notificationService)
    {
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process PromptPay deposit
     */
    public function processPromptPayDeposit(User $user, float $amount, array $metadata = []): array
    {
        if (! WalletSetting::isPaymentMethodEnabled('promptpay')) {
            throw new Exception('PromptPay ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (! empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        // Generate PromptPay QR Code
        // In production, you would integrate with a real PromptPay API
        $qrCode = $this->generatePromptPayQRCode($amount, $metadata);

        return [
            'status' => 'pending',
            'payment_method' => 'promptpay',
            'amount' => $amount,
            'qr_code' => $qrCode,
            'reference' => 'PP'.strtoupper(uniqid()),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    /**
     * Verify PromptPay payment
     */
    public function verifyPromptPayPayment(string $reference): bool
    {
        // In production, integrate with PromptPay API to verify payment
        // For now, return true for demonstration
        return true;
    }

    /**
     * Process Stripe payment
     */
    public function processStripePayment(User $user, float $amount, string $paymentMethodId): array
    {
        // 🚨 (2026-11-04) ปิดถาวรจนกว่าจะเชื่อม Stripe API จริง — โค้ดด้านล่างเติมเงินเข้ากระเป๋า
        //    โดย "ไม่ได้ตัดบัตรเลย" (มีแค่ comment ว่า In production, integrate...) ถ้ามีใครเปิด
        //    wallet setting stripe_enabled ผู้ใช้ทุกคนจะเสกเงินเข้ากระเป๋าได้ฟรี
        //    ใช้ Stripe Checkout ของออเดอร์ (OrderStripeService) ซึ่งตรวจยอดจริงแทน
        Log::warning('Blocked unverified Stripe wallet deposit attempt', ['user_id' => $user->id, 'amount' => $amount]);
        throw new Exception('ช่องทาง Stripe สำหรับเติมกระเป๋ายังไม่เปิดให้บริการ — กรุณาใช้ PromptPay');
        if (! WalletSetting::isPaymentMethodEnabled('stripe')) {
            throw new Exception('Stripe ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (! empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        try {
            // In production, integrate with Stripe API
            // \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            // $charge = \Stripe\Charge::create([...]);

            $wallet = $this->walletService->getOrCreateWallet($user);

            // Create deposit transaction
            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'Stripe deposit',
                'stripe_payment',
                null,
                ['payment_method_id' => $paymentMethodId]
            );

            // Notify user
            $this->notificationService->notifyDeposit($user, $amount);

            return [
                'status' => 'success',
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
            ];
        } catch (Exception $e) {
            Log::error('Stripe payment failed: '.$e->getMessage());
            throw new Exception('การชำระเงินผ่าน Stripe ล้มเหลว: '.$e->getMessage());
        }
    }

    /**
     * Process PayPal payment
     */
    public function processPayPalPayment(User $user, float $amount, string $orderId): array
    {
        // 🚨 (2026-11-04) ปิดถาวรจนกว่าจะเชื่อม PayPal API จริง — โค้ดด้านล่างเติมเงินโดยไม่ตรวจ/ไม่ capture order
        Log::warning('Blocked unverified PayPal wallet deposit attempt', ['user_id' => $user->id, 'amount' => $amount]);
        throw new Exception('ช่องทาง PayPal สำหรับเติมกระเป๋ายังไม่เปิดให้บริการ — กรุณาใช้ PromptPay');
        if (! WalletSetting::isPaymentMethodEnabled('paypal')) {
            throw new Exception('PayPal ไม่สามารถใช้งานได้ในขณะนี้');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (! empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        try {
            // In production, integrate with PayPal API
            // Verify PayPal order and capture payment

            $wallet = $this->walletService->getOrCreateWallet($user);

            // Create deposit transaction
            $transaction = $this->walletService->deposit(
                $wallet,
                $amount,
                'PayPal deposit',
                'paypal_payment',
                null,
                ['order_id' => $orderId]
            );

            // Notify user
            $this->notificationService->notifyDeposit($user, $amount);

            return [
                'status' => 'success',
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
            ];
        } catch (Exception $e) {
            Log::error('PayPal payment failed: '.$e->getMessage());
            throw new Exception('การชำระเงินผ่าน PayPal ล้มเหลว: '.$e->getMessage());
        }
    }

    /**
     * รับคำขอฝากเงินแบบโอนธนาคาร + แนบสลิป → สร้างบิลเติมเงิน "รอแอดมินตรวจ"
     *
     * 🐛 (2026-11-09) เดิมฟังก์ชันนี้แค่เก็บไฟล์สลิปแล้วคืนเลขอ้างอิง "BT…" ที่ไม่ได้บันทึกที่ไหนเลย
     *    ไม่มีแถวใน DB ให้แอดมินเห็น/อนุมัติ → ลูกค้าโอนเงินแล้วได้ข้อความ "รอตรวจสอบ 1-24 ชม." แต่เงินไม่มีวันเข้า
     *    และ getAvailablePaymentMethods() เปิดช่องทางนี้แบบ hardcode (ไม่อ่าน bank_transfer_enabled) ฟอร์มจึงโชว์บน prod อยู่
     *
     * ตอนนี้:
     *  - ต้องเปิด bank_transfer_enabled ก่อน (ปิดอยู่ = ปฏิเสธ)
     *  - เก็บสลิปใน disk 'local' (ส่วนตัว) — สลิปมีชื่อ/เลขบัญชีผู้โอน (PDPA) ห้ามอยู่ใน public
     *  - สร้าง PaymentTransaction type=wallet_topup, payment_method=bank_transfer_slip, status=pending, ไม่มีวันหมดอายุ
     *    บิลนี้โผล่ในคิว "รออนุมัติ" ของแอป SMS Checker → แอดมินกดอนุมัติ = PaymentService::completePayment (ครั้งเดียว)
     *  - ใช้ payment_method แยก (ไม่ใช่ 'bank_transfer') โดยตั้งใจ:
     *      1) ไม่ให้ model สร้าง unique amount (เติมทศนิยม) — ลูกค้าโอนไปแล้วด้วยยอดกลม
     *      2) ไม่ให้ SMS fallback ขั้นที่ 2 (จับคู่ยอดตรงๆ ของ promptpay/bank_transfer แล้วอนุมัติอัตโนมัติ) หยิบบิลนี้
     *         — บิลยอดกลมที่สร้าง "หลัง" โอน จะถูกจับกับ SMS ยอดเท่ากันของคนอื่นที่มาทีหลัง = แนบสลิปปลอมแล้วได้เงินจริง
     *  - สลิปเดียวกัน (sha256 เดียวกัน) ส่งซ้ำไม่ได้ ถ้าบิลเดิมยังรอตรวจหรืออนุมัติไปแล้ว
     *
     * @param  User  $user  ผู้ฝากเงิน
     * @param  float  $amount  ยอดที่ลูกค้าแจ้งว่าโอน (บาท)
     * @param  \Illuminate\Http\UploadedFile  $slipFile  รูปสลิป
     * @param  array  $metadata  note / ip_address
     * @return array{status:string,payment_method:string,amount:float,reference:string,transaction:PaymentTransaction}
     *
     * @throws Exception ข้อความภาษาไทยที่แสดงให้ลูกค้าได้
     */
    public function processBankTransfer(User $user, float $amount, $slipFile, array $metadata = []): array
    {
        if (! WalletSetting::isPaymentMethodEnabled('bank_transfer')) {
            throw new Exception('ช่องทางโอนผ่านธนาคารยังไม่เปิดให้บริการ — กรุณาใช้พร้อมเพย์');
        }

        // Validate amount
        $errors = WalletSetting::validateDepositAmount($amount);
        if (! empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        $amount = round($amount, 2);
        $slipHash = hash_file('sha256', $slipFile->getRealPath());
        $slipKey = 'slip:'.$slipHash;

        // สลิปใบเดิมถูกใช้ไปแล้ว (ยังรอตรวจ/อนุมัติแล้ว) → ไม่สร้างบิลซ้ำ · บิลที่ถูกปฏิเสธ/ยกเลิกส่งใหม่ได้
        $duplicate = PaymentTransaction::where('payment_method', self::BANK_SLIP_METHOD)
            ->where('gateway_transaction_id', $slipKey)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->first();
        if ($duplicate) {
            throw new Exception('สลิปนี้ถูกส่งไปแล้ว (รหัสอ้างอิง '.$duplicate->transaction_id.') กรุณารอแอดมินตรวจสอบ');
        }

        $slipPath = null;

        try {
            // เก็บสลิปแบบส่วนตัว — ดูได้ผ่าน API ของเครื่องแอดมินเท่านั้น
            $slipPath = $slipFile->store('bank-transfer-slips/'.now()->format('Y/m'), 'local');
            if (! $slipPath) {
                throw new Exception('store() returned false');
            }

            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'store_id' => VendorStore::getPlatformStoreId(),
                'type' => 'wallet_topup',
                'payment_method' => self::BANK_SLIP_METHOD,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'THB',
                'gateway' => 'manual_slip',
                'gateway_transaction_id' => $slipKey,
                // ไม่มีวันหมดอายุ: ลูกค้าโอนเงินไปแล้ว ต้องรอแอดมินตัดสินเท่านั้น (อนุมัติ/ปฏิเสธ)
                'expired_at' => null,
                'metadata' => [
                    'source' => 'deposit_bank_transfer',
                    'original_amount' => $amount,
                    'slip_path' => $slipPath,
                    'slip_disk' => 'local',
                    'slip_sha256' => $slipHash,
                    'slip_original_name' => mb_substr((string) $slipFile->getClientOriginalName(), 0, 120),
                    'note' => isset($metadata['note']) ? mb_substr((string) $metadata['note'], 0, 500) : null,
                    'ip_address' => $metadata['ip_address'] ?? null,
                    'requires_admin_review' => true,
                ],
            ]);
        } catch (Exception $e) {
            if ($slipPath) {
                Storage::disk('local')->delete($slipPath);
            }
            Log::error('Bank transfer processing failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw new Exception('บันทึกคำขอฝากเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        Log::info('Bank transfer deposit: pending admin review', [
            'transaction_id' => $transaction->transaction_id,
            'user_id' => $user->id,
            'amount' => $amount,
        ]);

        // แจ้งแอป SMS Checker ว่ามีบิลใหม่รอตรวจ · แจ้งไม่ได้ห้ามทำให้คำขอล้ม (บิลอยู่ใน DB แล้ว)
        try {
            NewTransactionCreated::dispatch($transaction);
        } catch (\Throwable $e) {
            Log::warning('Bank transfer deposit: FCM dispatch failed', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'status' => 'pending',
            'payment_method' => self::BANK_SLIP_METHOD,
            'amount' => $amount,
            'reference' => $transaction->transaction_id,
            'transaction' => $transaction,
        ];
    }

    /**
     * แอดมินอนุมัติบิลโอนธนาคาร + สลิป → เติมเงินเข้ากระเป๋า "ครั้งเดียว"
     *
     * 🐛 (2026-11-09) เดิมรับ (user, amount, reference) แล้วเติมเงินตรงๆ ทุกครั้งที่เรียก
     *    ไม่ผูกกับบิลใด ไม่กันเรียกซ้ำ = กดซ้ำได้เงินซ้ำ (ยังไม่มีใครเรียก แต่เป็นกับดัก)
     *    ตอนนี้ผูกกับ PaymentTransaction และเดินทาง PaymentService::completePayment
     *    ซึ่ง lock แถวบิล + แถวกระเป๋า และคืนก่อนถ้าบิล completed แล้ว
     *
     * @return bool true = เติมเงินแล้ว (หรือเคยอนุมัติไปแล้ว)
     *
     * @throws Exception บิลไม่ใช่บิลโอนธนาคาร + สลิป
     */
    public function approveBankTransfer(PaymentTransaction $transaction, ?User $admin = null): bool
    {
        if ($transaction->type !== 'wallet_topup' || $transaction->payment_method !== self::BANK_SLIP_METHOD) {
            throw new Exception('บิลนี้ไม่ใช่คำขอฝากเงินแบบโอนธนาคาร');
        }

        $metadata = $transaction->metadata ?? [];
        if ($admin && empty($metadata['approved_by'])) {
            $metadata['approved_by'] = $admin->id;
            $metadata['approved_at'] = now()->toIso8601String();
            $transaction->update(['metadata' => $metadata]);
        }

        return (bool) app(PaymentService::class)->completePayment($transaction);
    }

    /**
     * สร้าง PromptPay QR Code จริง ผ่าน PromptPayProvider
     *
     * ใช้ EMVCo payload ตามมาตรฐาน BOT + BaconQrCode render เป็น SVG
     * Fallback เป็น Google Charts API ถ้าไม่มี library
     *
     * @param  float  $amount  จำนวนเงิน (บาท)
     * @param  array  $metadata  ข้อมูลเพิ่มเติม
     * @return string data URI ของ QR Code หรือ URL ของ QR Code
     */
    protected function generatePromptPayQRCode(float $amount, array $metadata = []): string
    {
        try {
            $provider = app(PromptPayProvider::class);
            $qrDataUri = $provider->generateQrDataUri($amount);

            if (! empty($qrDataUri)) {
                return $qrDataUri;
            }

            Log::warning('PromptPay QR Code: generateQrDataUri คืนค่าว่าง — อาจยังไม่ได้ตั้งค่า PromptPay ID');

            return '';
        } catch (\Exception $e) {
            Log::error('PromptPay QR Code generation failed: '.$e->getMessage());

            return '';
        }
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): array
    {
        return [
            'promptpay' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('promptpay'),
                'name' => 'พร้อมเพย์',
                'icon' => '💳',
                'description' => 'ชำระเงินผ่าน QR Code พร้อมเพย์',
            ],
            'bank_transfer' => [
                // 🐛 (2026-11-09) เดิม hardcode true → ฟอร์มโอนธนาคารโชว์แม้แอดมินไม่ได้เปิด
                'enabled' => WalletSetting::isPaymentMethodEnabled('bank_transfer'),
                'name' => 'โอนผ่านธนาคาร',
                'icon' => '🏦',
                'description' => 'โอนเงินผ่านธนาคารและอัพโหลดสลิป',
            ],
            'stripe' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('stripe'),
                'name' => 'Stripe',
                'icon' => '💰',
                'description' => 'ชำระเงินด้วยบัตรเครดิต/เดบิต',
            ],
            'paypal' => [
                'enabled' => WalletSetting::isPaymentMethodEnabled('paypal'),
                'name' => 'PayPal',
                'icon' => '💵',
                'description' => 'ชำระเงินผ่าน PayPal',
            ],
        ];
    }
}
