<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\MlmCalculationService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected $mlmService;

    public function __construct()
    {
        $this->mlmService = new MlmCalculationService();
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Process MLM commissions when order is marked as paid
        if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
            $this->processmlmCommissions($order);
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
}
