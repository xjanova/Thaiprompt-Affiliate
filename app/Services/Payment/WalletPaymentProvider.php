<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WalletPaymentProvider implements PaymentProviderInterface
{
    /**
     * 🔒 (2026-10-04) G1: ข้อความ error ภาษาไทย — PaymentService ส่งต่อเป็น $result['message']
     *    หน้าเว็บแสดงข้อความนี้ตรงๆ · แอป (PaymentApiController) แปลงเป็น code ตามค่าคงที่เหล่านี้
     */
    public const ERROR_WALLET_INACTIVE = 'กระเป๋าเงินของคุณใช้งานไม่ได้ในขณะนี้ กรุณาติดต่อเจ้าหน้าที่';

    public const ERROR_ORDER_ALREADY_PAID = 'คำสั่งซื้อนี้ชำระเงินแล้ว';

    public const ERROR_ORDER_NOT_PAYABLE = 'คำสั่งซื้อนี้ชำระเงินไม่ได้แล้ว';

    /**
     * Validate wallet payment
     */
    public function validate(PaymentTransaction $transaction, array $data): bool
    {
        // Get user's wallet
        $wallet = Wallet::where('user_id', $transaction->user_id)->first();

        if (! $wallet) {
            throw new Exception('Wallet not found');
        }

        // 🔒 (2026-10-04) G1: กระเป๋าที่ถูกระงับ/ล็อกห้ามจ่าย (เหมือน ShopCheckoutService / WalletService::deductForService)
        if (! $wallet->isActive()) {
            throw new Exception(self::ERROR_WALLET_INACTIVE);
        }

        // Check if wallet has enough balance
        if ($wallet->balance < $transaction->amount) {
            throw new Exception('Insufficient wallet balance');
        }

        return true;
    }

    /**
     * Process wallet payment
     */
    public function process(PaymentTransaction $transaction, array $data): array
    {
        return DB::transaction(function () use ($transaction) {
            // 🔒 (2026-10-04) G1: ล็อกแถวออเดอร์ก่อนวอลเลต — แอป + เว็บกดจ่ายออเดอร์เดียวกันพร้อมกัน
            //    คำขอที่สองรอจนคำขอแรก commit แล้วเห็นรายการหักเงินของออเดอร์นี้ → ไม่หักซ้ำ
            $this->guardOrderNotPaid($transaction);

            $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();

            // ตรวจซ้ำหลังล็อก — แอดมินอาจระงับกระเป๋าระหว่าง validate() กับตอนนี้
            if ($wallet && ! $wallet->isActive()) {
                throw new Exception(self::ERROR_WALLET_INACTIVE);
            }

            if (! $wallet || $wallet->balance < $transaction->amount) {
                throw new Exception('Insufficient wallet balance');
            }

            // บันทึก balance ก่อนหัก
            $balanceBefore = $wallet->balance;

            // Deduct from wallet
            $wallet->decrement('balance', $transaction->amount);

            // Create wallet transaction
            // ⚠️ ใช้ type='withdrawal' แทน 'payment' (ไม่มีใน enum)
            // ⚠️ ใช้ status='completed' แทน 'approved' (ไม่มีใน enum)
            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $transaction->user_id,
                'type' => 'withdrawal',
                'amount' => $transaction->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $this->getTransactionDescription($transaction),
                // อ้างอิงออเดอร์แบบมี index (ใช้ตรวจหักซ้ำใน guardOrderNotPaid)
                'reference_type' => $transaction->order_id ? 'order' : null,
                'reference_id' => $transaction->order_id ?: null,
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'payment_transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                ],
            ]);

            return [
                'status' => 'completed',
                'gateway' => 'wallet',
                'gateway_transaction_id' => $walletTransaction->id,
                'response' => [
                    'wallet_id' => $wallet->id,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                ],
            ];
        });
    }

    /**
     * Verify wallet payment
     */
    public function verify(PaymentTransaction $transaction, array $data): bool
    {
        // Wallet payments are instant, no verification needed
        return $transaction->isCompleted();
    }

    /**
     * Refund wallet payment
     */
    public function refund(PaymentTransaction $transaction, float $amount): array
    {
        return DB::transaction(function () use ($transaction, $amount) {
            $wallet = Wallet::where('user_id', $transaction->user_id)->lockForUpdate()->first();

            if (! $wallet) {
                throw new Exception('Wallet not found');
            }

            // บันทึก balance ก่อนคืนเงิน
            $balanceBefore = $wallet->balance;

            // Add refund amount to wallet
            $wallet->increment('balance', $amount);

            // Create refund wallet transaction
            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $transaction->user_id,
                'type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => 'คืนเงินสำหรับ '.$this->getTransactionDescription($transaction),
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [
                    'payment_transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                ],
            ]);

            return [
                'status' => 'completed',
                'wallet_transaction_id' => $walletTransaction->id,
                'balance_after' => $wallet->balance,
            ];
        });
    }

    /**
     * 🔒 (2026-10-04) G1: ออเดอร์นี้ยังจ่ายได้ไหม (เรียกภายใน DB transaction เท่านั้น)
     *
     * - ล็อกแถวออเดอร์ (lockForUpdate) → คำขอจ่ายพร้อมกันเข้าคิวทีละคำขอ
     * - จ่ายแล้ว / มีรายการชำระของออเดอร์นี้สำเร็จแล้ว / มีรายการหักวอลเลตของออเดอร์นี้แล้ว → ปฏิเสธ
     * - ยกเลิก/คืนเงินแล้ว → ปฏิเสธ
     *
     * @throws Exception ข้อความภาษาไทยตามค่าคงที่ ERROR_*
     */
    protected function guardOrderNotPaid(PaymentTransaction $transaction): void
    {
        if ($transaction->type !== 'order_payment' || ! $transaction->order_id) {
            return;
        }

        $orderId = (int) $transaction->order_id;
        $order = Order::whereKey($orderId)->lockForUpdate()->first();

        if (! $order) {
            throw new Exception(self::ERROR_ORDER_NOT_PAYABLE);
        }

        if ($order->payment_status === 'paid') {
            throw new Exception(self::ERROR_ORDER_ALREADY_PAID);
        }

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            throw new Exception(self::ERROR_ORDER_NOT_PAYABLE);
        }

        // รายการชำระอื่นของออเดอร์นี้สำเร็จไปแล้ว (เช่น พร้อมเพย์จับคู่ SMS ได้แต่ยังอัปเดตออเดอร์ไม่ทัน)
        $otherCompleted = PaymentTransaction::where('order_id', $orderId)
            ->where('type', 'order_payment')
            ->where('status', 'completed')
            ->where('id', '!=', $transaction->id)
            ->exists();

        // หักวอลเลตของออเดอร์นี้ไปแล้ว (คำขอแรก commit แล้วแต่ completePayment ยังไม่ตั้งออเดอร์เป็นจ่ายแล้ว)
        $alreadyDebited = WalletTransaction::where('user_id', $transaction->user_id)
            ->whereIn('type', ['withdrawal', 'fee'])
            ->where('status', 'completed')
            ->where(function ($q) use ($orderId) {
                $q->where(function ($ref) use ($orderId) {
                    $ref->where('reference_type', 'order')->where('reference_id', $orderId);
                })
                    // รายการเก่าก่อนมี reference — order_id อยู่ใน metadata
                    ->orWhere('metadata->order_id', $orderId)
                    ->orWhere('metadata->order_id', (string) $orderId);
            })
            ->exists();

        if ($otherCompleted || $alreadyDebited) {
            throw new Exception(self::ERROR_ORDER_ALREADY_PAID);
        }
    }

    /**
     * Get transaction description
     */
    protected function getTransactionDescription(PaymentTransaction $transaction): string
    {
        if ($transaction->type === 'order_payment' && $transaction->order) {
            return 'Payment for Order #'.$transaction->order->order_number;
        }

        return 'Payment via Wallet';
    }
}
