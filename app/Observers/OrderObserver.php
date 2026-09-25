<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CashbackService;
use App\Services\OrderDistributionService;
use App\Services\SellerPayoutService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

/**
 * OrderObserver — ผูกเงินของออเดอร์กับสถานะออเดอร์
 *
 * - payment_status เปลี่ยนเป็น 'paid' (ได้รับเงินจริงแล้ว ทั้งโอน/wallet/COD ที่เก็บเงินแล้ว)
 *   → จ่ายเงินคืนลูกค้า (cashback) + แบ่งเงินผู้ขาย/แพลตฟอร์ม (OrderDistributionService)
 * - status เปลี่ยนเป็น delivered/completed
 *   → เริ่มนับวันพักเงินของผู้ขาย (รายได้โอนเข้ากระเป๋าหลังส่งของถึง + holding days)
 *   → ถ้าจ่ายแล้วแต่ยังไม่ถูกแบ่ง (เช่น COD ที่บันทึกรับเงินก่อน) แบ่งเงินตอนนี้
 *   → COD ที่ส่งถึงแต่ยังไม่บันทึกรับเงิน (payment_status ยังไม่ใช่ paid) จะยังไม่แบ่งเงิน
 *     (สถานะส่งถึง ≠ หลักฐานว่าเก็บเงินได้) รอผู้เก็บเงิน (ไรเดอร์/แอดมิน) บันทึก payment_status = paid
 *
 * ทุกขั้นกันทำซ้ำอยู่ใน service แล้ว (lock แถวออเดอร์) — observer จับ \Throwable เพื่อไม่ให้การอัปเดต
 * สถานะออเดอร์ล้มเพราะงานเงิน (cron orders:process-distribution จะเก็บตกให้)
 */
class OrderObserver
{
    protected CashbackService $cashbackService;

    protected OrderDistributionService $distributionService;

    protected SellerPayoutService $payoutService;

    public function __construct()
    {
        $this->cashbackService = new CashbackService(new WalletService);
        $this->distributionService = new OrderDistributionService;
        $this->payoutService = new SellerPayoutService;
    }

    /**
     * ⚠️ ห้ามใส่ handler 'created' เพื่อจับออเดอร์ที่สร้างเป็น paid
     * เพราะ event ยิงกลาง DB transaction ก่อน OrderItems ถูกสร้าง
     * flow ที่สร้างออเดอร์เป็น paid (เช่น wallet checkout) ต้องเรียก OrderDistributionService เองหลัง commit
     */
    public function updated(Order $order): void
    {
        $becamePaid = $order->wasChanged('payment_status') && $order->payment_status === 'paid';
        $becameDelivered = $order->wasChanged('status')
            && in_array($order->status, SellerPayoutService::DELIVERED_STATUSES, true);

        if ($becamePaid) {
            $this->processCashback($order);
            $this->processOrderDistribution($order);
        }

        if ($becameDelivered) {
            if ($order->payment_status === 'paid') {
                // COD: เงินคืนลูกค้าจ่ายหลังส่งถึง (CashbackService ข้าม COD ที่ยังไม่ delivered)
                if ($order->payment_method === 'cod') {
                    $this->processCashback($order);
                }

                if (! $becamePaid) {
                    $this->processOrderDistribution($order);
                }
            } elseif ($order->payment_method === 'cod') {
                Log::info('COD order delivered but payment not recorded yet, distribution waits', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ]);
            }

            $this->startHoldingClock($order);
        } elseif ($order->wasChanged('status') && $order->status === 'shipped' && $order->payment_status === 'paid') {
            // ส่งพัสดุแล้ว: ตั้งวันที่คาดว่าจะปล่อยเงิน (ถ้าลูกค้าไม่กดยืนยันรับของภายในกำหนด)
            $this->startHoldingClock($order);
        }
    }

    protected function processCashback(Order $order): void
    {
        try {
            $transaction = $this->cashbackService->processOrderCashback($order);
            if ($transaction) {
                Log::info('Cashback processed for order', [
                    'order_id' => $order->id,
                    'cashback_amount' => $transaction->amount,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to process cashback', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * แบ่งเงินออเดอร์ (idempotent ใน service)
     */
    protected function processOrderDistribution(Order $order): void
    {
        try {
            if ($this->distributionService->isOrderDistributed($order)) {
                return;
            }

            $result = $this->distributionService->processOrderDistribution($order);

            Log::info('Order distribution processed (observer)', [
                'order_id' => $order->id,
                'skipped' => $result['skipped'] ?? false,
                'reason' => $result['reason'] ?? null,
                'distributions_count' => count($result['distributions'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to process order distribution', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function startHoldingClock(Order $order): void
    {
        try {
            $this->payoutService->startHoldingClock($order);
        } catch (\Throwable $e) {
            Log::warning('Failed to start seller holding clock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
