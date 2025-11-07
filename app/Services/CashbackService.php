<?php

namespace App\Services;

use App\Models\CashbackSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CashbackService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Calculate total cashback for an order
     */
    public function calculateOrderCashback(Order $order): float
    {
        $totalCashback = 0;

        foreach ($order->items as $item) {
            $itemCashback = $this->calculateItemCashback($item);
            $totalCashback += $itemCashback;
        }

        return round($totalCashback, 2);
    }

    /**
     * Calculate cashback for a single order item
     */
    public function calculateItemCashback(OrderItem $item): float
    {
        $product = $item->product;
        $itemTotal = $item->price * $item->quantity;

        // Check for product-specific cashback first
        $productSetting = CashbackSetting::getProductSetting($product->id);

        if ($productSetting && $productSetting->qualifiesForCashback($itemTotal)) {
            return $productSetting->calculateCashback($itemTotal);
        }

        // Fall back to global cashback
        $globalSetting = CashbackSetting::getGlobalSetting();

        if ($globalSetting && $globalSetting->qualifiesForCashback($itemTotal)) {
            return $globalSetting->calculateCashback($itemTotal);
        }

        return 0;
    }

    /**
     * Calculate cashback for a product (for preview purposes)
     * Returns both the cashback amount and setting info
     */
    public function calculateProductCashback($product, float $price, int $quantity = 1): array
    {
        $itemTotal = $price * $quantity;

        // Check for product-specific cashback first
        $productSetting = CashbackSetting::getProductSetting($product->id);

        if ($productSetting && $productSetting->qualifiesForCashback($itemTotal)) {
            return [
                'cashback' => $productSetting->calculateCashback($itemTotal),
                'setting' => $productSetting,
                'type' => 'product',
            ];
        }

        // Fall back to global cashback
        $globalSetting = CashbackSetting::getGlobalSetting();

        if ($globalSetting && $globalSetting->qualifiesForCashback($itemTotal)) {
            return [
                'cashback' => $globalSetting->calculateCashback($itemTotal),
                'setting' => $globalSetting,
                'type' => 'global',
            ];
        }

        return [
            'cashback' => 0,
            'setting' => null,
            'type' => null,
        ];
    }

    /**
     * Process cashback for an order
     * Should be called when payment is completed (not for COD until admin approves)
     */
    public function processOrderCashback(Order $order): ?WalletTransaction
    {
        // Check if already processed
        if ($order->cashback_processed) {
            Log::info('Cashback already processed for order', ['order_id' => $order->id]);
            return null;
        }

        // Check if payment is completed
        if ($order->payment_status !== 'paid') {
            Log::info('Order not paid yet, skipping cashback', ['order_id' => $order->id]);
            return null;
        }

        // For COD orders, don't process cashback until admin approves
        // This will be handled separately when admin confirms receipt
        if ($order->payment_method === 'cod' && $order->status !== 'delivered') {
            Log::info('COD order not delivered yet, skipping cashback', ['order_id' => $order->id]);
            return null;
        }

        try {
            return DB::transaction(function () use ($order) {
                // Calculate cashback
                $cashbackAmount = $this->calculateOrderCashback($order);

                if ($cashbackAmount <= 0) {
                    Log::info('No cashback applicable for order', ['order_id' => $order->id]);

                    // Mark as processed even if no cashback
                    $order->update([
                        'cashback_amount' => 0,
                        'cashback_processed' => true,
                        'cashback_processed_at' => now(),
                    ]);

                    return null;
                }

                // Get or create user's wallet
                $wallet = $this->walletService->getOrCreateWallet($order->user);

                // Add cashback to wallet
                $transaction = $this->walletService->deposit(
                    $wallet,
                    $cashbackAmount,
                    "Cash Back จากคำสั่งซื้อ #{$order->order_number}",
                    'cashback',
                    $order->id,
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'type' => 'order_cashback',
                    ]
                );

                // Update order cashback status
                $order->update([
                    'cashback_amount' => $cashbackAmount,
                    'cashback_processed' => true,
                    'cashback_processed_at' => now(),
                ]);

                Log::info('Cashback processed successfully', [
                    'order_id' => $order->id,
                    'cashback_amount' => $cashbackAmount,
                    'transaction_id' => $transaction->id,
                ]);

                return $transaction;
            });
        } catch (Exception $e) {
            Log::error('Failed to process cashback', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process COD order cashback after admin approval
     */
    public function processCodOrderCashback(Order $order): ?WalletTransaction
    {
        // Verify it's a COD order
        if ($order->payment_method !== 'cod') {
            throw new Exception('This is not a COD order');
        }

        // Mark as delivered if not already
        if ($order->status !== 'delivered') {
            $order->markAsDelivered();
        }

        // Process cashback
        return $this->processOrderCashback($order);
    }

    /**
     * Get cashback preview for an order (before checkout)
     */
    public function getCashbackPreview($cartItems, float $orderTotal = 0): array
    {
        $breakdown = [];
        $totalCashback = 0;

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            $quantity = $cartItem->quantity;
            $itemTotal = $product->price * $quantity;

            // Calculate cashback for this item
            $cashbackInfo = $this->calculateProductCashback($product, $product->price, $quantity);

            if ($cashbackInfo['cashback'] > 0) {
                $breakdown[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'item_total' => $itemTotal,
                    'cashback' => $cashbackInfo['cashback'],
                    'type' => $cashbackInfo['type'],
                    'setting' => $cashbackInfo['setting'],
                ];

                $totalCashback += $cashbackInfo['cashback'];
            }
        }

        return [
            'breakdown' => $breakdown,
            'total_cashback' => round($totalCashback, 2),
            'formatted_total' => '฿' . number_format($totalCashback, 2),
        ];
    }

    /**
     * Get cashback statistics for admin
     */
    public function getCashbackStatistics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = WalletTransaction::where('reference_type', 'cashback');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $totalCashback = $query->sum('amount');
        $totalTransactions = $query->count();
        $averageCashback = $totalTransactions > 0 ? $totalCashback / $totalTransactions : 0;

        return [
            'total_cashback' => $totalCashback,
            'total_transactions' => $totalTransactions,
            'average_cashback' => round($averageCashback, 2),
            'formatted_total' => '฿' . number_format($totalCashback, 2),
            'formatted_average' => '฿' . number_format($averageCashback, 2),
        ];
    }
}
