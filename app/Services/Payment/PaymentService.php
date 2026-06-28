<?php

namespace App\Services\Payment;

use App\Events\NewTransactionCreated;
use App\Models\Order;
use App\Models\PaymentBankAccount;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\UniquePaymentAmount;
use App\Models\User;
use App\Models\VendorStore;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Service
 *
 * บริการจัดการการชำระเงินแบบรวมศูนย์
 * รองรับหลาย payment providers
 */
class PaymentService
{
    protected $providers = [];

    /**
     * Provider class mapping
     *
     * @var array
     */
    protected static $providerClasses = [
        'wallet' => WalletPaymentProvider::class,
        'promptpay' => PromptPayProvider::class,
        'credit_card' => CreditCardProvider::class,
        'bank_transfer' => BankTransferProvider::class,
        'cash_on_delivery' => CashOnDeliveryProvider::class,
        'paysolutions' => PaySolutionsProvider::class,
        'stripe' => StripeProvider::class,
        'omise' => OmiseProvider::class,
        'paypal' => PayPalProvider::class,
        'razorpay' => RazorpayProvider::class,
        'truemoney' => TrueMoneyProvider::class,
        'nfc_card' => NFCCardProvider::class,
    ];

    public function __construct()
    {
        // ⚠️ ไม่สร้าง providers ใน constructor — ใช้ lazy loading ผ่าน getProvider()
        // เหตุผล: constructor ของแต่ละ provider ยิง DB query หา gateway config
        // ถ้า DB ไม่พร้อมจะเกิด timeout 8 วิ/provider × 12 providers = 96 วิ/request
    }

    /**
     * Check if a provider is available
     *
     * เช็คจาก static mapping ไม่ต้อง instantiate (หลีกเลี่ยง DB query)
     */
    public function hasProvider(string $method): bool
    {
        return isset(self::$providerClasses[$method]);
    }

    /**
     * Get all registered providers
     */
    public function getRegisteredProviders(): array
    {
        return array_keys(self::$providerClasses);
    }

    /**
     * Get payment provider by method
     *
     * Lazy load — สร้าง provider instance เมื่อเรียกใช้จริงเท่านั้น
     * แล้ว cache ไว้ใน $this->providers เพื่อใช้ซ้ำ
     *
     * @throws Exception
     */
    public function getProvider(string $method): PaymentProviderInterface
    {
        // คืน instance ที่สร้างไว้แล้ว (cached)
        if (isset($this->providers[$method])) {
            return $this->providers[$method];
        }

        if (! isset(self::$providerClasses[$method])) {
            throw new Exception("Payment provider '{$method}' not found");
        }

        $class = self::$providerClasses[$method];

        try {
            // NFCCardProvider ต้องใช้ dependency injection
            if ($method === 'nfc_card') {
                $this->providers[$method] = app($class);
            } else {
                $this->providers[$method] = new $class;
            }
        } catch (\Exception $e) {
            Log::debug("Failed to instantiate payment provider: {$method}", [
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Payment provider '{$method}' could not be instantiated: ".$e->getMessage());
        }

        return $this->providers[$method];
    }

    /**
     * Create payment transaction for order
     */
    public function createOrderPayment(Order $order, string $paymentMethod, array $options = []): PaymentTransaction
    {
        return DB::transaction(function () use ($order, $paymentMethod, $options) {
            // หา store_id จาก order items (ถ้าทุก item เป็นร้านเดียวกัน)
            $storeId = $this->resolveStoreIdForOrder($order);

            $transaction = PaymentTransaction::create([
                'user_id' => $order->user_id,
                'store_id' => $storeId,
                'type' => 'order_payment',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'amount' => $order->total_amount,
                'currency' => 'THB',
                'order_id' => $order->id,
                'metadata' => $options['metadata'] ?? null,
                'expired_at' => now()->addMinutes(30), // 30 minutes expiry
            ]);

            // ส่ง FCM push ไปยัง SmsChecker app เมื่อมีบิลใหม่
            // แอพจะโหลดบิลทันทีโดยไม่ต้องรอ periodic sync
            if (in_array($paymentMethod, ['promptpay', 'bank_transfer'])) {
                NewTransactionCreated::dispatch($transaction);
            }

            return $transaction;
        });
    }

    /**
     * หา store_id ของ order สำหรับ SMS Gateway routing
     *
     * Logic:
     * - ถ้าทุก item มาจากร้านเดียวกัน (vendor store) → return store_id นั้น
     * - ถ้าเป็น Official Shop หรือ mixed stores → return platformStoreId
     *
     * ⚠️ ไม่ return null เพราะ:
     * - null ทำให้ SQL WHERE store_id = NULL ไม่ทำงาน
     * - ใช้ platformStoreId เป็นตัวแทนของร้าน admin/official เสมอ
     * - ป้องกันการสับสนระหว่าง "ไม่มีร้าน" กับ "ร้าน admin"
     */
    private function resolveStoreIdForOrder(Order $order): int
    {
        $order->loadMissing('items.product');

        $officialSellerId = Product::getOfficialSellerId();
        $storeIds = collect();

        foreach ($order->items as $item) {
            // Skip Official Shop items
            if ($item->seller_id === $officialSellerId) {
                continue;
            }

            if ($item->product && $item->product->store_id) {
                $storeIds->push($item->product->store_id);
            }
        }

        $uniqueStoreIds = $storeIds->unique();

        // ทุก item เป็นร้านเดียวกัน (vendor store)
        if ($uniqueStoreIds->count() === 1) {
            return $uniqueStoreIds->first();
        }

        // Mixed stores หรือ Official Shop only → ใช้ Platform Store ID
        return VendorStore::getPlatformStoreId();
    }

    /**
     * Create payment transaction for wallet top-up
     */
    public function createWalletTopup(User $user, float $amount, string $paymentMethod, array $options = []): PaymentTransaction
    {
        return DB::transaction(function () use ($user, $amount, $paymentMethod, $options) {
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'store_id' => VendorStore::getPlatformStoreId(),
                'type' => 'wallet_topup',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'amount' => $amount,
                'currency' => 'THB',
                'metadata' => $options['metadata'] ?? null,
                'expired_at' => now()->addMinutes(30), // 30 minutes expiry
            ]);

            // ส่ง FCM push ไปยัง SmsChecker app เมื่อมีบิลเติมเงินใหม่
            if (in_array($paymentMethod, ['promptpay', 'bank_transfer'])) {
                NewTransactionCreated::dispatch($transaction);
            }

            return $transaction;
        });
    }

    /**
     * Process payment
     */
    public function processPayment(PaymentTransaction $transaction, array $paymentData = [])
    {
        try {
            // SECURITY: Validate transaction amount hasn't been tampered with
            // ตรวจสอบทั้ง amount ปัจจุบัน และ original_amount (กรณี unique amount)
            if (isset($paymentData['amount'])) {
                $metadata = $transaction->metadata ?? [];
                $originalAmount = $metadata['original_amount'] ?? $transaction->amount;
                if ($paymentData['amount'] != $transaction->amount && $paymentData['amount'] != $originalAmount) {
                    throw new Exception('Payment amount mismatch. Possible tampering detected.');
                }
            }

            // SECURITY: Check transaction hasn't expired
            if ($transaction->isExpired()) {
                throw new Exception('Payment transaction has expired');
            }

            // SECURITY: Check transaction is in valid state
            if (! in_array($transaction->status, ['pending', 'processing'])) {
                throw new Exception('Invalid transaction status for processing: '.$transaction->status);
            }

            $provider = $this->getProvider($transaction->payment_method);

            // สร้าง unique amount สำหรับ SMS Checker auto-matching
            // เฉพาะ promptpay/bank_transfer ที่เปิดใช้ SMS Checker
            $uniqueAmountRecord = $this->generateUniqueAmountIfNeeded($transaction);

            // Validate payment
            if (! $provider->validate($transaction, $paymentData)) {
                throw new Exception('Payment validation failed');
            }

            // Process payment
            $result = $provider->process($transaction, $paymentData);

            // Update transaction
            $transaction->update([
                'gateway' => $result['gateway'] ?? null,
                'gateway_transaction_id' => $result['gateway_transaction_id'] ?? null,
                'gateway_response' => $result['response'] ?? null,
                'promptpay_qr_code' => $result['qr_code'] ?? null,
                'promptpay_ref_no' => $result['ref_no'] ?? null,
            ]);

            // If payment is completed immediately (e.g., wallet payment)
            if ($result['status'] === 'completed') {
                $this->completePayment($transaction);
            } else {
                $transaction->update(['status' => 'processing']);
            }

            return [
                'success' => true,
                'transaction' => $transaction->fresh(),
                'data' => $result,
            ];
        } catch (Exception $e) {
            $transaction->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction' => $transaction->fresh(),
            ];
        }
    }

    /**
     * Complete payment transaction
     */
    public function completePayment(PaymentTransaction $transaction)
    {
        return DB::transaction(function () use ($transaction) {
            // Pessimistic lock: ป้องกัน double-spend / race condition
            // โหลด transaction ใหม่พร้อม lock เพื่อให้แน่ใจว่าไม่มี process อื่นทำพร้อมกัน
            $transaction = PaymentTransaction::lockForUpdate()->find($transaction->id);
            if (! $transaction) {
                Log::warning('PaymentService: Transaction not found during completePayment', [
                    'id' => $transaction->id ?? 'unknown',
                ]);

                return false;
            }

            if ($transaction->isCompleted()) {
                return true;
            }

            $transaction->markAsCompleted();

            // Handle different transaction types
            switch ($transaction->type) {
                case 'order_payment':
                    $this->completeOrderPayment($transaction);
                    break;

                case 'wallet_topup':
                    $this->completeWalletTopup($transaction);
                    break;

                case 'withdrawal':
                    $this->completeWithdrawal($transaction);
                    break;
            }

            // 🔮 บิลทำนายไพ่ทาโร่ต์ — ดูจาก metadata.type เพราะมีทั้ง
            // type='order_payment' (single flow) และ type='tarot_reading' (cart flow)
            // เงินเข้าจริงแล้วค่อย: mark reading paid + จ่ายคอมมิชชั่น + นับ limit
            if (($transaction->metadata['type'] ?? null) === 'tarot_reading') {
                try {
                    app(\App\Services\TarotPaymentService::class)->finalizePaidTransaction($transaction);
                } catch (\Throwable $tarotErr) {
                    // ห้าม error กระทบการ complete transaction (เงินเข้าแล้ว)
                    // reading ที่ค้าง pending จะถูก retry จาก paymentStatus polling
                    Log::error('PaymentService: finalize tarot reading ล้มเหลว', [
                        'transaction_id' => $transaction->id,
                        'error' => $tarotErr->getMessage(),
                    ]);
                }
            }

            return true;
        });
    }

    /**
     * Complete order payment
     */
    protected function completeOrderPayment(PaymentTransaction $transaction)
    {
        $order = $transaction->order;

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'status' => 'processing', // Move to processing after payment
            ]);

            // Reduce product stock (ป้องกัน stock ติดลบ)
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product && $product->track_inventory) {
                    // ใช้ DB query ป้องกัน stock ติดลบ (atomic decrement with floor 0)
                    // ใช้ (int) cast ป้องกัน SQL Injection ใน DB::raw()
                    $qty = (int) $item->quantity;
                    Product::where('id', $product->id)
                        ->where('stock_quantity', '>=', $qty)
                        ->update([
                            'stock_quantity' => DB::raw('stock_quantity - '.$qty),
                            'sales_count' => DB::raw('sales_count + '.$qty),
                        ]);

                    // ถ้า stock ไม่พอ (race condition) → log แต่ไม่ block payment
                    $product->refresh();
                    if ($product->stock_quantity < 0) {
                        Log::warning('PaymentService: stock ติดลบหลัง decrement (race condition)', [
                            'product_id' => $product->id,
                            'ordered_qty' => $item->quantity,
                            'remaining_stock' => $product->stock_quantity,
                            'order_id' => $order->id,
                        ]);
                    }
                } elseif ($product) {
                    // ไม่ track inventory → แค่เพิ่ม sales_count
                    $product->increment('sales_count', $item->quantity);
                }
            }
        }
    }

    /**
     * Complete wallet top-up
     *
     * ใช้ original_amount (ก่อนเพิ่มทศนิยม) เป็นยอดเติมเงินจริง
     * เพราะ transaction->amount อาจเป็น unique amount (เช่น 500.37) แทนที่จะเป็น 500
     */
    protected function completeWalletTopup(PaymentTransaction $transaction)
    {
        $user = $transaction->user;

        if (! $user) {
            Log::error('completeWalletTopup: ไม่พบ user สำหรับ transaction', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
            ]);
            throw new \Exception('User not found for wallet topup transaction #'.$transaction->id);
        }

        // ใช้ original_amount ถ้ามี (กรณี unique amount ถูกสร้าง) มิเช่นนั้นใช้ transaction amount
        $metadata = $transaction->metadata ?? [];
        $depositAmount = (float) ($metadata['original_amount'] ?? $transaction->amount);

        Log::info('completeWalletTopup: เริ่มเติมเงิน', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'original_amount' => $metadata['original_amount'] ?? 'N/A (ใช้ transaction amount)',
            'deposit_amount' => $depositAmount,
            'transaction_amount' => $transaction->amount,
        ]);

        // Get or create wallet
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );

        // Create wallet transaction
        // ⚠️ ต้องใส่ user_id, balance_before, status='completed' ให้ถูกต้องตาม schema
        $walletTransaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $depositAmount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance + $depositAmount,
            'description' => 'เติมเงินผ่าน '.$transaction->payment_method,
            'status' => 'completed',
            'completed_at' => now(),
            'metadata' => [
                'payment_transaction_id' => $transaction->id,
                'source' => $metadata['source'] ?? 'wallet_topup',
            ],
        ]);

        // Update wallet balance + total_income + last_transaction_at
        $wallet->update([
            'balance' => $wallet->balance + $depositAmount,
            'total_income' => ($wallet->total_income ?? 0) + $depositAmount,
            'last_transaction_at' => now(),
        ]);

        // Link wallet transaction
        $transaction->update(['wallet_transaction_id' => $walletTransaction->id]);

        Log::info('completeWalletTopup: เติมเงินสำเร็จ', [
            'transaction_id' => $transaction->id,
            'wallet_transaction_id' => $walletTransaction->id,
            'deposit_amount' => $depositAmount,
            'new_balance' => $wallet->fresh()->balance,
        ]);
    }

    /**
     * Complete withdrawal
     *
     * บันทึกสถานะและหักเงินจาก wallet ของผู้ใช้
     * การโอนเงินจริงไปบัญชีธนาคารต้อง admin ดำเนินการแยก
     */
    protected function completeWithdrawal(PaymentTransaction $transaction)
    {
        $user = $transaction->user;
        if (! $user) {
            Log::error('completeWithdrawal: ไม่พบ user', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();
        if (! $wallet) {
            Log::error('completeWithdrawal: ไม่พบ wallet', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        // ตรวจสอบยอดเงินเพียงพอ
        if ($wallet->balance < $transaction->amount) {
            Log::error('completeWithdrawal: ยอดเงินไม่เพียงพอ', [
                'transaction_id' => $transaction->id,
                'wallet_balance' => $wallet->balance,
                'withdraw_amount' => $transaction->amount,
            ]);

            return;
        }

        // สร้าง wallet transaction
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'withdrawal',
            'amount' => $transaction->amount,
            'balance_before' => $wallet->balance,
            'balance_after' => $wallet->balance - $transaction->amount,
            'description' => 'ถอนเงินผ่าน '.$transaction->payment_method,
            'reference_type' => 'PaymentTransaction',
            'reference_id' => $transaction->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // หักเงินจาก wallet
        $wallet->update([
            'balance' => $wallet->balance - $transaction->amount,
            'total_expense' => ($wallet->total_expense ?? 0) + $transaction->amount,
            'last_transaction_at' => now(),
        ]);

        Log::info('completeWithdrawal: ถอนเงินสำเร็จ', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'new_balance' => $wallet->fresh()->balance,
        ]);
    }

    /**
     * สร้าง unique amount (เพิ่มทศนิยม) สำหรับ SMS Checker auto-matching
     *
     * ตรวจสอบว่า payment method เป็น promptpay/bank_transfer
     * และมีบัญชีธนาคารที่เปิดใช้ SMS Checker หรือไม่
     * ถ้ามี → สร้างยอดชำระที่มีทศนิยมเฉพาะ (เช่น 1000.37)
     * แล้วอัพเดท PaymentTransaction.amount เป็นยอดใหม่
     */
    protected function generateUniqueAmountIfNeeded(PaymentTransaction $transaction): ?UniquePaymentAmount
    {
        Log::info('SMS Checker: generateUniqueAmountIfNeeded() called', [
            'transaction_id' => $transaction->id,
            'payment_method' => $transaction->payment_method,
            'amount' => $transaction->amount,
        ]);

        // เฉพาะ promptpay และ bank_transfer เท่านั้น
        if (! in_array($transaction->payment_method, ['promptpay', 'bank_transfer'])) {
            Log::debug('SMS Checker: ข้าม - payment method ไม่ใช่ promptpay/bank_transfer', [
                'method' => $transaction->payment_method,
            ]);

            return null;
        }

        // ตรวจสอบว่ามี unique amount แล้วหรือยัง (จาก Model boot หรือการเรียกก่อนหน้า)
        $metadata = $transaction->metadata ?? [];
        if (! empty($metadata['unique_amount_id'])) {
            Log::debug('SMS Checker: ข้าม - มี unique amount แล้ว', [
                'transaction_id' => $transaction->id,
                'unique_amount_id' => $metadata['unique_amount_id'],
            ]);

            // คืน UniquePaymentAmount record ที่มีอยู่
            return UniquePaymentAmount::find($metadata['unique_amount_id']);
        }

        // ตรวจสอบว่าระบบ SMS Checker เปิดใช้งานหรือไม่
        // ถ้า config เปิด (default = true) → สร้าง unique amount เสมอ
        // ถ้า config ปิด → fallback เช็ค device/bank account แบบเดิม
        //
        // ⚠️ ใช้ default = true เพื่อให้ทำงานแม้ config ยังไม่ถูก cache
        // (config file มี default = true อยู่แล้ว)
        $smsCheckerEnabled = config('smschecker.enabled', true);
        Log::info('SMS Checker: config enabled = '.var_export($smsCheckerEnabled, true));

        if (! $smsCheckerEnabled) {
            // config ปิด → ตรวจแบบเดิม (ต้องมี bank account หรือ device ที่ active)
            try {
                $hasSmsChecker = false;

                // ทาง 1: ตรวจบัญชีธนาคารที่เปิด SMS Checker
                try {
                    $hasSmsChecker = PaymentBankAccount::where('is_active', true)
                        ->where('sms_checker_enabled', true)
                        ->exists();
                } catch (\Exception $e) {
                    Log::debug('PaymentBankAccount check skipped: '.$e->getMessage());
                }

                // ทาง 2: ถ้าไม่มีบัญชี ให้ตรวจว่ามีอุปกรณ์ SMS Checker ที่ active อยู่
                if (! $hasSmsChecker) {
                    $hasSmsChecker = \App\Models\SmsCheckerDevice::where('status', 'active')->exists();
                }

                if (! $hasSmsChecker) {
                    Log::debug('SMS Checker: ไม่พบ active device หรือ bank account ที่เปิด SMS Checker');

                    return null;
                }
            } catch (\Exception $e) {
                Log::debug('SMS Checker check skipped: '.$e->getMessage());

                return null;
            }
        }

        Log::info('SMS Checker: กำลังสร้าง unique amount สำหรับ transaction #'.$transaction->id);

        // สร้าง unique amount
        try {
            $transactionType = $transaction->type ?? 'order';
            $uniqueAmount = UniquePaymentAmount::generate(
                $transaction->amount,
                $transaction->id,
                $transactionType,
                config('smschecker.unique_amount_expiry', 30)
            );

            if ($uniqueAmount) {
                // เก็บยอดเดิมใน metadata แล้วอัพเดทยอดชำระเป็น unique amount
                $metadata = $transaction->metadata ?? [];
                $metadata['original_amount'] = $transaction->amount;
                $metadata['unique_amount_id'] = $uniqueAmount->id;
                $metadata['decimal_suffix'] = $uniqueAmount->decimal_suffix;

                $transaction->update([
                    'amount' => $uniqueAmount->unique_amount,
                    'metadata' => $metadata,
                ]);

                Log::info('SMS Checker: สร้าง unique amount สำเร็จ', [
                    'transaction_id' => $transaction->id,
                    'original_amount' => $metadata['original_amount'],
                    'unique_amount' => $uniqueAmount->unique_amount,
                    'suffix' => $uniqueAmount->decimal_suffix,
                ]);

                return $uniqueAmount;
            }

            Log::warning('SMS Checker: ไม่สามารถสร้าง unique amount (suffix เต็ม)', [
                'transaction_id' => $transaction->id,
                'base_amount' => $transaction->amount,
            ]);
        } catch (\Exception $e) {
            Log::error('SMS Checker: เกิดข้อผิดพลาดในการสร้าง unique amount', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Refund payment
     */
    public function refundPayment(PaymentTransaction $transaction, ?float $amount = null)
    {
        return DB::transaction(function () use ($transaction, $amount) {
            $refundAmount = $amount ?? $transaction->amount;

            // Create refund transaction
            $refund = PaymentTransaction::create([
                'user_id' => $transaction->user_id,
                'store_id' => $transaction->store_id,  // Inherit from original transaction
                'type' => 'refund',
                'payment_method' => $transaction->payment_method,
                'status' => 'completed',
                'amount' => $refundAmount,
                'currency' => $transaction->currency,
                'order_id' => $transaction->order_id,
                'metadata' => [
                    'original_transaction_id' => $transaction->id,
                ],
            ]);

            // Process refund based on payment method
            if ($transaction->payment_method === 'wallet') {
                $wallet = Wallet::firstOrCreate(
                    ['user_id' => $transaction->user_id],
                    ['balance' => 0]
                );

                $wallet->increment('balance', $refundAmount);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $transaction->user_id,
                    'type' => 'refund',
                    'amount' => $refundAmount,
                    'balance_before' => $wallet->balance - $refundAmount,
                    'balance_after' => $wallet->balance,
                    'description' => 'คืนเงินสำหรับคำสั่งซื้อ #'.$transaction->order->order_number,
                    'status' => 'completed',
                    'completed_at' => now(),
                    'metadata' => [
                        'payment_transaction_id' => $transaction->id,
                        'refund_transaction_id' => $refund->id ?? null,
                    ],
                ]);
            }

            // Update original transaction
            $transaction->update(['status' => 'refunded']);

            // Update order if exists
            if ($transaction->order) {
                $transaction->order->update([
                    'payment_status' => 'refunded',
                    'status' => 'refunded',
                ]);
            }

            return $refund;
        });
    }

    /**
     * Verify payment (for webhook callbacks)
     */
    public function verifyPayment(string $transactionId, array $data)
    {
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        $provider = $this->getProvider($transaction->payment_method);

        if ($provider->verify($transaction, $data)) {
            $this->completePayment($transaction);

            return true;
        }

        return false;
    }

    /**
     * Get available payment methods
     *
     * ดึงรายการ payment methods ที่พร้อมใช้งาน
     * โดยรวมทั้ง built-in methods และ gateways ที่ active
     */
    public function getAvailablePaymentMethods(): array
    {
        // Built-in payment methods (ใช้งานได้เสมอ)
        $methods = [
            [
                'id' => 'wallet',
                'name' => 'Wallet',
                'description' => 'จ่ายด้วยยอดเงินในกระเป๋า',
                'icon' => '👛',
                'enabled' => true,
                'category' => 'internal',
            ],
            [
                'id' => 'cash_on_delivery',
                'name' => 'Cash on Delivery',
                'description' => 'เก็บเงินปลายทาง',
                'icon' => '💵',
                'enabled' => true,
                'category' => 'offline',
            ],
        ];

        // ดึง payment gateways ที่ active จาก database
        try {
            if (class_exists(PaymentGateway::class)) {
                $gateways = PaymentGateway::where('is_active', true)
                    ->where('is_available', true)
                    ->where('is_coming_soon', false)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($gateways as $gateway) {
                    // ตรวจสอบว่ามี provider สำหรับ gateway นี้หรือไม่
                    if (! $this->hasProvider($gateway->code)) {
                        continue;
                    }

                    $methods[] = [
                        'id' => $gateway->code,
                        'name' => $gateway->name,
                        'description' => $gateway->description,
                        'icon' => $gateway->icon,
                        'enabled' => $gateway->isConfigured(),
                        'category' => $gateway->category,
                        'color' => $gateway->color,
                        'test_mode' => $gateway->test_mode,
                        'supports_deposit' => $gateway->supports_deposit,
                        'supports_withdrawal' => $gateway->supports_withdrawal,
                        'fees' => $gateway->fees,
                        'limits' => $gateway->limits,
                    ];
                }
            }
        } catch (\Exception $e) {
            // PaymentGateway model not available or database not ready
            Log::warning('Could not load payment gateways: '.$e->getMessage());

            // Fallback to basic methods
            $methods = array_merge($methods, [
                [
                    'id' => 'promptpay',
                    'name' => 'PromptPay',
                    'description' => 'สแกน QR Code เพื่อชำระเงิน',
                    'icon' => '📱',
                    'enabled' => true,
                    'category' => 'thai',
                ],
                [
                    'id' => 'credit_card',
                    'name' => 'Credit/Debit Card',
                    'description' => 'บัตรเครดิต/เดบิต',
                    'icon' => '💳',
                    'enabled' => true,
                    'category' => 'card',
                ],
                [
                    'id' => 'bank_transfer',
                    'name' => 'Bank Transfer',
                    'description' => 'โอนเงินผ่านธนาคาร',
                    'icon' => '🏦',
                    'enabled' => true,
                    'category' => 'bank',
                ],
            ]);
        }

        // 💳 บัตรเครดิต/เดบิต ผ่าน Stripe (ใช้บัญชี/คีย์เดียวกับดูดวง) —
        //    แสดงอัตโนมัติเมื่อเปิดใช้งาน Stripe เท่านั้น (กันซ้ำถ้ามี gateway 'credit_card' อยู่แล้ว)
        try {
            if (app(OrderStripeService::class)->isEnabled()) {
                $hasCard = collect($methods)->contains(fn ($m) => ($m['id'] ?? null) === 'credit_card');
                if (! $hasCard) {
                    $methods[] = [
                        'id' => 'credit_card',
                        'name' => 'บัตรเครดิต/เดบิต',
                        'description' => 'ชำระผ่าน Stripe ปลอดภัย (Visa / Mastercard / JCB)',
                        'icon' => '💳',
                        'enabled' => true,
                        'category' => 'card',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::debug('OrderStripe availability check failed: '.$e->getMessage());
        }

        return $methods;
    }

    /**
     * Get payment methods for deposit
     */
    public function getDepositMethods(): array
    {
        return array_filter($this->getAvailablePaymentMethods(), function ($method) {
            return ($method['supports_deposit'] ?? true) && ($method['enabled'] ?? false);
        });
    }

    /**
     * Get payment methods for withdrawal
     */
    public function getWithdrawalMethods(): array
    {
        return array_filter($this->getAvailablePaymentMethods(), function ($method) {
            return ($method['supports_withdrawal'] ?? false) && ($method['enabled'] ?? false);
        });
    }
}
