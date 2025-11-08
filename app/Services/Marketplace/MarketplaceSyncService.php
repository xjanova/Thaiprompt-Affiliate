<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncService
{
    private MarketplaceApiInterface $apiService;
    private MarketplaceAccount $account;

    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
        $this->apiService = MarketplaceFactory::create($account);
    }

    /**
     * Sync products from marketplace
     *
     * @param array $params
     * @return MarketplaceSyncLog
     */
    public function syncProducts(array $params = []): MarketplaceSyncLog
    {
        $log = MarketplaceSyncLog::create([
            'account_id' => $this->account->id,
            'platform_id' => $this->account->platform_id,
            'sync_type' => 'products',
            'sync_status' => 'running',
            'triggered_by' => auth()->id(),
        ]);

        try {
            $products = $this->apiService->getProducts($params);

            $processed = 0;
            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach ($products as $productData) {
                $processed++;

                try {
                    $existingProduct = MarketplaceProduct::where('account_id', $this->account->id)
                        ->where('external_product_id', $productData['external_product_id'])
                        ->first();

                    if ($existingProduct) {
                        $existingProduct->update($productData);
                        $updated++;
                    } else {
                        MarketplaceProduct::create(array_merge($productData, [
                            'account_id' => $this->account->id,
                            'platform_id' => $this->account->platform_id,
                        ]));
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to sync product: {$e->getMessage()}", [
                        'product_data' => $productData,
                    ]);
                }
            }

            // Update account stats
            $this->account->update([
                'total_products_synced' => $this->account->total_products_synced + $created,
                'last_sync_at' => now(),
                'status' => 'active',
            ]);

            $log->update([
                'sync_status' => $failed > 0 ? 'partial' : 'completed',
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
            ]);

            Log::info("Product sync completed", [
                'account_id' => $this->account->id,
                'processed' => $processed,
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            $log->update([
                'sync_status' => 'failed',
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
            ]);

            Log::error("Product sync failed: {$e->getMessage()}", [
                'account_id' => $this->account->id,
            ]);
        }

        return $log;
    }

    /**
     * Sync orders from marketplace
     *
     * @param array $params
     * @return MarketplaceSyncLog
     */
    public function syncOrders(array $params = []): MarketplaceSyncLog
    {
        $log = MarketplaceSyncLog::create([
            'account_id' => $this->account->id,
            'platform_id' => $this->account->platform_id,
            'sync_type' => 'orders',
            'sync_status' => 'running',
            'triggered_by' => auth()->id(),
        ]);

        try {
            $orders = $this->apiService->getOrders($params);

            $processed = 0;
            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach ($orders as $orderData) {
                $processed++;

                DB::beginTransaction();

                try {
                    // Extract items from order data
                    $items = $orderData['items'] ?? [];
                    $rawData = $orderData['raw_data'] ?? [];
                    unset($orderData['items'], $orderData['raw_data']);

                    // Add order_data field
                    $orderData['order_data'] = $rawData;

                    $existingOrder = MarketplaceOrder::where('account_id', $this->account->id)
                        ->where('external_order_id', $orderData['external_order_id'])
                        ->first();

                    if ($existingOrder) {
                        $existingOrder->update($orderData);
                        $order = $existingOrder;
                        $updated++;
                    } else {
                        $order = MarketplaceOrder::create(array_merge($orderData, [
                            'account_id' => $this->account->id,
                            'platform_id' => $this->account->platform_id,
                        ]));
                        $created++;
                    }

                    // Sync order items
                    if (!empty($items)) {
                        // Delete existing items
                        $order->items()->delete();

                        // Create new items
                        foreach ($items as $itemData) {
                            // Try to find matching product
                            $product = MarketplaceProduct::where('account_id', $this->account->id)
                                ->where('external_product_id', $itemData['external_product_id'] ?? null)
                                ->first();

                            MarketplaceOrderItem::create(array_merge($itemData, [
                                'order_id' => $order->id,
                                'product_id' => $product->id ?? null,
                            ]));
                        }
                    }

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    Log::error("Failed to sync order: {$e->getMessage()}", [
                        'order_data' => $orderData,
                    ]);
                }
            }

            // Update account stats
            $this->account->update([
                'total_orders_synced' => $this->account->total_orders_synced + $created,
                'last_sync_at' => now(),
                'status' => 'active',
            ]);

            $log->update([
                'sync_status' => $failed > 0 ? 'partial' : 'completed',
                'items_processed' => $processed,
                'items_created' => $created,
                'items_updated' => $updated,
                'items_failed' => $failed,
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
            ]);

            Log::info("Order sync completed", [
                'account_id' => $this->account->id,
                'processed' => $processed,
                'created' => $created,
                'updated' => $updated,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            $log->update([
                'sync_status' => 'failed',
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'completed_at' => now(),
                'duration_seconds' => now()->diffInSeconds($log->started_at),
            ]);

            Log::error("Order sync failed: {$e->getMessage()}", [
                'account_id' => $this->account->id,
            ]);
        }

        return $log;
    }

    /**
     * Sync both products and orders
     *
     * @param array $params
     * @return array
     */
    public function syncAll(array $params = []): array
    {
        return [
            'products' => $this->syncProducts($params),
            'orders' => $this->syncOrders($params),
        ];
    }
}
