<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;

class ShopeeApiService extends BaseMarketplaceService
{
    protected string $baseUrl = 'https://partner.shopeemobile.com';
    private string $partnerId;
    private string $partnerKey;

    public function __construct(MarketplaceAccount $account)
    {
        parent::__construct($account);

        // Shopee uses Partner ID and Partner Key from additional_credentials
        $credentials = $account->additional_credentials ?? [];
        $this->partnerId = $credentials['partner_id'] ?? $account->app_key;
        $this->partnerKey = $credentials['partner_key'] ?? $account->app_secret;
    }

    /**
     * Generate API signature for Shopee
     *
     * @param string $path
     * @param int $timestamp
     * @param string $body
     * @return string
     */
    private function generateSignature(string $path, int $timestamp, string $body = ''): string
    {
        $baseString = sprintf('%s%s%s%s%s',
            $this->partnerId,
            $path,
            $timestamp,
            $this->account->access_token ?? '',
            $this->account->shop_id ?? ''
        );

        return hash_hmac('sha256', $baseString, $this->partnerKey);
    }

    /**
     * Make signed request to Shopee API
     *
     * @param string $path
     * @param array $body
     * @param string $method
     * @return array|null
     */
    private function makeSignedRequest(string $path, array $body = [], string $method = 'POST'): ?array
    {
        $timestamp = time();
        $bodyString = json_encode($body);

        $sign = $this->generateSignature($path, $timestamp, $bodyString);

        // Build full path with query parameters
        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];

        if ($this->account->access_token) {
            $queryParams['access_token'] = $this->account->access_token;
        }

        if ($this->account->shop_id) {
            $queryParams['shop_id'] = $this->account->shop_id;
        }

        $queryString = http_build_query($queryParams);
        $fullPath = $path . '?' . $queryString;

        $headers = [
            'Content-Type' => 'application/json',
        ];

        return $this->makeRequest($method, $fullPath, $body, $headers);
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        $response = $this->makeSignedRequest('/api/v2/shop/get_shop_info');

        if ($response && isset($response['error']) && $response['error'] === '') {
            $this->account->update(['status' => 'active']);
            return true;
        }

        return false;
    }

    /**
     * Get products from Shopee
     */
    public function getProducts(array $params = []): array
    {
        $requestBody = [
            'offset' => $params['offset'] ?? 0,
            'page_size' => $params['limit'] ?? 50,
            'item_status' => 'NORMAL', // NORMAL, BANNED, DELETED, UNLIST
        ];

        $response = $this->makeSignedRequest('/api/v2/product/get_item_list', $requestBody);

        if (!$response || !isset($response['response']['item'])) {
            return [];
        }

        $products = [];
        foreach ($response['response']['item'] as $item) {
            // Get detailed product information
            $productDetail = $this->getProduct($item['item_id']);
            if ($productDetail) {
                $products[] = $productDetail;
            }
        }

        return $products;
    }

    /**
     * Get product details
     */
    public function getProduct(string $productId): ?array
    {
        $response = $this->makeSignedRequest('/api/v2/product/get_item_base_info', [
            'item_id_list' => [$productId],
        ]);

        if (!$response || !isset($response['response']['item_list'][0])) {
            return null;
        }

        return $this->normalizeProduct($response['response']['item_list'][0]);
    }

    /**
     * Get orders from Shopee
     */
    public function getOrders(array $params = []): array
    {
        $requestBody = [
            'time_range_field' => 'create_time',
            'time_from' => $params['created_after'] ?? now()->subDays(7)->timestamp,
            'time_to' => $params['created_before'] ?? now()->timestamp,
            'page_size' => $params['limit'] ?? 50,
            'cursor' => $params['cursor'] ?? '',
        ];

        $response = $this->makeSignedRequest('/api/v2/order/get_order_list', $requestBody);

        if (!$response || !isset($response['response']['order_list'])) {
            return [];
        }

        $orders = [];
        foreach ($response['response']['order_list'] as $orderSummary) {
            // Get detailed order information
            $orderDetail = $this->getOrder($orderSummary['order_sn']);
            if ($orderDetail) {
                $orders[] = $orderDetail;
            }
        }

        return $orders;
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): ?array
    {
        $response = $this->makeSignedRequest('/api/v2/order/get_order_detail', [
            'order_sn_list' => [$orderId],
        ]);

        if (!$response || !isset($response['response']['order_list'][0])) {
            return null;
        }

        return $this->normalizeOrder($response['response']['order_list'][0]);
    }

    /**
     * Generate affiliate link
     */
    public function generateAffiliateLink(string $productId, array $params = []): ?string
    {
        // Shopee Affiliate API
        $response = $this->makeSignedRequest('/api/v2/affiliate/generate_link', [
            'item_id' => $productId,
            'sub_id' => $params['sub_id'] ?? '',
        ]);

        if ($response && isset($response['data']['affiliate_link'])) {
            return $response['data']['affiliate_link'];
        }

        // Fallback to regular product URL
        return "https://shopee.co.th/product/{$this->account->shop_id}/{$productId}";
    }

    /**
     * Refresh access token
     */
    public function refreshToken(): bool
    {
        if (!$this->account->refresh_token) {
            return false;
        }

        $timestamp = time();
        $path = '/api/v2/auth/access_token/get';

        $body = [
            'refresh_token' => $this->account->refresh_token,
            'partner_id' => (int) $this->partnerId,
            'shop_id' => (int) $this->account->shop_id,
        ];

        $sign = $this->generateSignature($path, $timestamp, json_encode($body));

        $queryParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];

        $queryString = http_build_query($queryParams);
        $fullPath = $path . '?' . $queryString;

        $response = $this->makeRequest('POST', $fullPath, $body, ['Content-Type' => 'application/json']);

        if (!$response || !isset($response['access_token'])) {
            return false;
        }

        $this->account->update([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $this->account->refresh_token,
            'token_expires_at' => now()->addSeconds($response['expire_in'] ?? 3600),
            'status' => 'active',
        ]);

        return true;
    }

    /**
     * Normalize product data
     */
    protected function normalizeProduct(array $rawProduct): array
    {
        $price = 0;
        $originalPrice = 0;
        $stockQuantity = 0;

        if (isset($rawProduct['price_info'])) {
            $price = ($rawProduct['price_info'][0]['current_price'] ?? 0) / 100000;
            $originalPrice = ($rawProduct['price_info'][0]['original_price'] ?? 0) / 100000;
        }

        if (isset($rawProduct['stock_info'])) {
            $stockQuantity = array_sum(array_column($rawProduct['stock_info'], 'current_stock'));
        }

        return [
            'external_product_id' => $rawProduct['item_id'] ?? null,
            'external_sku' => $rawProduct['item_sku'] ?? null,
            'name' => $rawProduct['item_name'] ?? 'Unknown Product',
            'description' => $rawProduct['description'] ?? null,
            'category' => $rawProduct['category_id'] ?? null,
            'brand' => $rawProduct['brand']['original_brand_name'] ?? null,
            'price' => $price,
            'original_price' => $originalPrice,
            'currency' => 'THB',
            'stock_quantity' => $stockQuantity,
            'is_available' => ($rawProduct['item_status'] ?? '') === 'NORMAL',
            'main_image_url' => $rawProduct['image']['image_url_list'][0] ?? null,
            'images' => $rawProduct['image']['image_url_list'] ?? [],
            'attributes' => $rawProduct['attribute_list'] ?? [],
            'variants' => $rawProduct['model'] ?? [],
        ];
    }

    /**
     * Normalize order data
     */
    protected function normalizeOrder(array $rawOrder): array
    {
        $items = [];
        foreach ($rawOrder['item_list'] ?? [] as $item) {
            $items[] = [
                'external_product_id' => $item['item_id'] ?? null,
                'external_sku' => $item['item_sku'] ?? null,
                'product_name' => $item['item_name'] ?? 'Unknown',
                'product_image_url' => $item['image_info']['image_url'] ?? null,
                'variant_name' => $item['model_name'] ?? null,
                'unit_price' => isset($item['model_original_price']) ? $item['model_original_price'] / 100000 : 0,
                'quantity' => $item['model_quantity_purchased'] ?? 1,
                'subtotal' => isset($item['model_discounted_price']) ? ($item['model_discounted_price'] / 100000) * ($item['model_quantity_purchased'] ?? 1) : 0,
                'discount_amount' => isset($item['model_discount_amount']) ? $item['model_discount_amount'] / 100000 : 0,
                'total_amount' => isset($item['model_discounted_price']) ? ($item['model_discounted_price'] / 100000) * ($item['model_quantity_purchased'] ?? 1) : 0,
            ];
        }

        return [
            'external_order_id' => $rawOrder['order_sn'] ?? null,
            'order_number' => $rawOrder['order_sn'] ?? null,
            'customer_name' => $rawOrder['recipient_address']['name'] ?? null,
            'customer_email' => null,
            'customer_phone' => $rawOrder['recipient_address']['phone'] ?? null,
            'subtotal' => isset($rawOrder['total_amount']) ? $rawOrder['total_amount'] / 100000 : 0,
            'shipping_fee' => isset($rawOrder['actual_shipping_fee']) ? $rawOrder['actual_shipping_fee'] / 100000 : 0,
            'tax_amount' => 0,
            'discount_amount' => isset($rawOrder['seller_discount']) ? $rawOrder['seller_discount'] / 100000 : 0,
            'total_amount' => isset($rawOrder['total_amount']) ? $rawOrder['total_amount'] / 100000 : 0,
            'currency' => 'THB',
            'order_status' => $this->mapOrderStatus($rawOrder['order_status'] ?? ''),
            'payment_status' => isset($rawOrder['payment_method']) && $rawOrder['payment_method'] !== '' ? 'paid' : 'pending',
            'ordered_at' => isset($rawOrder['create_time']) ? date('Y-m-d H:i:s', $rawOrder['create_time']) : null,
            'paid_at' => isset($rawOrder['pay_time']) ? date('Y-m-d H:i:s', $rawOrder['pay_time']) : null,
            'shipped_at' => isset($rawOrder['ship_by_date']) ? date('Y-m-d H:i:s', $rawOrder['ship_by_date']) : null,
            'tracking_number' => $rawOrder['tracking_number'] ?? null,
            'shipping_provider' => $rawOrder['shipping_carrier'] ?? null,
            'items' => $items,
            'raw_data' => $rawOrder,
        ];
    }

    /**
     * Map Shopee order status to our format
     */
    private function mapOrderStatus(string $status): string
    {
        return match ($status) {
            'UNPAID' => 'unpaid',
            'READY_TO_SHIP' => 'awaiting_shipment',
            'PROCESSED', 'RETRY_SHIP' => 'processing',
            'SHIPPED' => 'shipped',
            'TO_CONFIRM_RECEIVE' => 'in_transit',
            'IN_CANCEL' => 'cancelling',
            'CANCELLED' => 'cancelled',
            'TO_RETURN' => 'return_requested',
            'COMPLETED' => 'delivered',
            default => 'pending',
        };
    }
}
