<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CashbackService;
use App\Services\MlmCalculationService;
use App\Services\OrderDistributionService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

/**
 * OrderObserver
 *
 * จัดการ events ของ Order:
 * - เมื่อชำระเงินสำเร็จ (payment_status = 'paid')
 *   → คำนวณ MLM Commission
 *   → จ่าย Cashback
 *   → แบ่งเงินให้ Seller + เก็บ Fee/VAT
 *
 * - เมื่อ COD ส่งสำเร็จ (status = 'delivered')
 *   → จ่าย Cashback
 */
class OrderObserver
{
    protected $mlmService;

    protected $cashbackService;

    protected $distributionService;

    public function __construct()
    {
        $this->mlmService = new MlmCalculationService;
        $this->cashbackService = new CashbackService(new WalletService);
        $this->distributionService = new OrderDistributionService;
    }

    /**
     * ⚠️ หมายเหตุ: ห้ามใส่ handler 'created' เพื่อจับออเดอร์ที่สร้างเป็น paid
     * เพราะ event ยิงกลาง DB transaction ก่อน OrderItems ถูกสร้าง
     * → distribution จะเห็น 0 items แล้ว mark ว่า distribute แล้วอย่างผิดๆ
     * flow ที่สร้างออเดอร์เป็น paid (เช่น mobile wallet checkout) ต้องเรียก
     * OrderDistributionService เองหลัง DB::commit() — ดู MobileApiController::checkout
     */

    /**
     * Handle the Order "updated" event.
     *
     * เมื่อ Order ถูกอัพเดท จะตรวจสอบและประมวลผล:
     * 1. ถ้า payment_status เปลี่ยนเป็น 'paid' → ประมวลผลทั้งหมด
     * 2. ถ้า COD และ status เปลี่ยนเป็น 'delivered' → จ่าย cashback
     */
    public function updated(Order $order): void
    {
        // ประมวลผลเมื่อชำระเงินสำเร็จ (ไม่ใช่ COD)
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            // แก้ Bug PV-1: ลบ processMlmCommissions() ออกเพราะ processOrderDistribution()
            // มีการคำนวณ MLM Commission อยู่ภายในแล้ว (ผ่าน OrderDistributionService)
            // การเรียกซ้ำ 2 ทาง ทำให้เกิด duplicate commission

            // 1. จ่าย Cashback ให้ลูกค้า
            $this->processCashback($order);

            // 2. แบ่งเงินให้ Seller + เก็บ Fee/VAT/MLM Pool + คำนวณ MLM Commission
            $this->processOrderDistribution($order);
        }

        // สำหรับ COD: จ่าย cashback + distribution เมื่อส่งสำเร็จ
        if ($order->wasChanged('status') && $order->status === 'delivered' && $order->payment_method === 'cod') {
            $this->processCashback($order);

            // สำหรับ COD ต้อง distribute เมื่อส่งของสำเร็จด้วย
            if (! $this->distributionService->isOrderDistributed($order)) {
                $this->processOrderDistribution($order);
            }
        }
    }

    /**
     * ประมวลผล MLM Commission สำหรับ Order
     */
    protected function processMlmCommissions(Order $order): void
    {
        try {
            $this->mlmService->processOrderCommissions($order);
            Log::info('MLM commissions processed for order', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('Failed to process MLM commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ประมวลผล Cashback สำหรับ Order
     */
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
        } catch (\Exception $e) {
            Log::error('Failed to process cashback', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ประมวลผลการแบ่งเงิน Order
     *
     * หักค่า Fee, VAT, MLM Pool แล้วบันทึกรายได้ให้ Seller
     */
    protected function processOrderDistribution(Order $order): void
    {
        try {
            // ตรวจสอบว่า distribute ไปแล้วหรือยัง
            if ($this->distributionService->isOrderDistributed($order)) {
                Log::info('Order already distributed, skipping', ['order_id' => $order->id]);

                return;
            }

            $result = $this->distributionService->processOrderDistribution($order);

            Log::info('Order distribution processed', [
                'order_id' => $order->id,
                'total_amount' => $result['total_amount'] ?? 0,
                'distributions_count' => count($result['distributions'] ?? []),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process order distribution', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
