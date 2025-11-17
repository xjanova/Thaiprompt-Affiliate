<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\MlmCalculationService;
use App\Services\CashbackService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $mlmService;
    protected $cashbackService;

    public function __construct()
    {
        $this->mlmService = new MlmCalculationService();
        $this->cashbackService = new CashbackService(new WalletService());
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Process MLM and Affiliate commissions when order is marked as paid
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            $this->processMlmCommissions($order);
            $this->processAffiliateCommissions($order);
            $this->processCashback($order);
        }

        // Process cashback for COD orders when marked as delivered
        if ($order->wasChanged('status') && $order->status === 'delivered' && $order->payment_method === 'cod') {
            $this->processCashback($order);
        }
    }

    /**
     * Process MLM commissions for an order
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
     * Process Affiliate commissions for an order
     */
    protected function processAffiliateCommissions(Order $order): void
    {
        try {
            $user = $order->user;

            if (!$user || !$user->affiliate) {
                return;
            }

            $affiliate = $user->affiliate;

            // คำนวณคอมมิชชั่น affiliate (ถ้ามี parent)
            if ($affiliate->parent_id) {
                $parent = $affiliate->parent;
                $commissionAmount = $order->total * ($parent->commission_rate ?? 0.1);

                // สร้าง commission record
                \App\Models\Commission::create([
                    'affiliate_id' => $parent->id,
                    'user_id' => $parent->user_id,
                    'order_id' => $order->id,
                    'amount' => $commissionAmount,
                    'type' => 'pending',
                    'level' => 1,
                ]);

                // อัพเดท total_earnings ของ parent
                $parent->increment('total_earnings', $commissionAmount);

                Log::info('Affiliate commission created', [
                    'order_id' => $order->id,
                    'affiliate_id' => $parent->id,
                    'amount' => $commissionAmount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process affiliate commissions', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process cashback for an order
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
}
