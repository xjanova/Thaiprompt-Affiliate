<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;

class TikTokApiService extends BaseMarketplaceService
{
    protected string $baseUrl = 'https://open-api.tiktokglobalshop.com';

    public function __construct(MarketplaceAccount $account)
    {
        parent::__construct($account);
    }

    /**
     * Generate API signature for TikTok Shop
     *
     * @param string $path
     * @param array $params
     * @param string $body
     * @return string
     */
    private function generateSignature(string $path, array $params, string $body = ''): string
    {
        // Sort parameters
        ksort($params);

        // Build string to sign
        $input = $path;
        foreach ($params as $key => $value) {
            $input .= $key . $value;
        }
        $input .= $body;

        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $input, $this->account->app_secret);

        return $signature;
    }

    /**
     * Make signed request to TikTok Shop API
     *
     * @param string $path
     * @param array $params
     * @param array $body
     * @param string $method
     * @return array|null
     */
    private function makeSignedRequest(string $path, array $params = [], array $body = [], string $method = 'POST'): ?array
    {
        $commonParams = [
            'app_key' => $this->account->app_key,
            'timestamp' => (string) time(),
            'shop_id' => $this->account->shop_id,
        ];

        if ($this->account->access_token) {
            $commonParams['access_token'] = $this->account->access_token;
        }

        $params = array_merge($commonParams, $params);

        // Generate signature
        $bodyString = empty($body) ? '' : json_encode($body);
        $params['sign'] = $this->generateSignature($path, $params, $bodyString);

        // Build query string
        $queryString = http_build_query($params);
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
        $response = $this->makeSignedRequest('/api/shop/get_authorized_shop');

        if ($response && isset($response['code']) && $response['code'] === 0) {
            $this->account->update(['status' => 'active']);
            return true;
        }

        return false;
    }

    /**
     * Get products from TikTok Shop
     */
    public function getProducts(array $params = []): array
    {
        $requestBody = [
            'page_size' => $params['limit'] ?? 50,
            'page_number' => ($params['offset'] ?? 0) / ($params['limit'] ?? 50) + 1,
        ];

        $response = $this->makeSignedRequest('/api/products/search', [], $requestBody);

        if (!$response || !isset($response['data']['products'])) {
            return [];
        }

        return array_map(fn($product) => $this->normalizeProduct($product), $response['data']['products']);
    }

    /**
     * Get product details
     */
    public function getProduct(string $productId): ?array
    {
        $response = $this->makeSignedRequest('/api/products/details', [], [
            'product_id' => $productId,
        ]);

        if (!$response || !isset($response['data'])) {
            return null;
        }

        return $this->normalizeProduct($response['data']);
    }

    /**
     * Get orders from TikTok Shop
     */
    public function getOrders(array $params = []): array
    {
        $requestBody = [
            'create_time_from' => $params['created_after'] ?? now()->subDays(7)->timestamp,
            'create_time_to' => $params['created_before'] ?? now()->timestamp,
            'page_size' => $params['limit'] ?? 50,
            'cursor' => $params['cursor'] ?? '',
        ];

        $response = $this->makeSignedRequest('/api/orders/search', [], $requestBody);

        if (!$response || !isset($response['data']['orders'])) {
            return [];
        }

        return array_map(fn($order) => $this->normalizeOrder($order), $response['data']['orders']);
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): ?array
    {
        $response = $this->makeSignedRequest('/api/orders/detail/query', [], [
            'order_id_list' => [$orderId],
        ]);

        if (!$response || !isset($response['data']['order_list'][0])) {
            return null;
        }

        return $this->normalizeOrder($response['data']['order_list'][0]);
    }

    /**
     * Generate affiliate link
     */
    public function generateAffiliateLink(string $productId, array $params = []): ?string
    {
        $response = $this->makeSignedRequest('/api/alliance/product/gen', [], [
            'product_id' => $productId,
            'custom_parameters' => $params['tracking_params'] ?? '',
        ]);

        if ($response && isset($response['data']['promotion_url'])) {
            return $response['data']['promotion_url'];
        }

        // Fallback to regular product URL
        return "https://www.tiktok.com/@shop/product/{$productId}";
    }

    /**
     * Refresh access token
     */
    public function refreshToken(): bool
    {
        if (!$this->account->refresh_token) {
            return false;
        }

        $params = [
            'app_key' => $this->account->app_key,
            'app_secret' => $this->account->app_secret,
            'refresh_token' => $this->account->refresh_token,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->makeRequest('GET', '/api/token/refresh', $params);

        if (!$response || !isset($response['data']['access_token'])) {
            return false;
        }

        $this->account->update([
            'access_token' => $response['data']['access_token'],
            'refresh_token' => $response['data']['refresh_token'] ?? $this->account->refresh_token,
            'token_expires_at' => now()->addSeconds($response['data']['access_token_expire_in'] ?? 3600),
            'status' => 'active',
        ]);

        return true;
    }

    /**
     * Normalize product data
     */
    protected function normalizeProduct(array $rawProduct): array
    {
        return [
            'external_product_id' => $rawProduct['product_id'] ?? $rawProduct['id'] ?? null,
            'external_sku' => $rawProduct['skus'][0]['seller_sku'] ?? null,
            'name' => $rawProduct['product_name'] ?? $rawProduct['title'] ?? 'Unknown Product',
            'description' => $rawProduct['description'] ?? null,
            'category' => $rawProduct['category_list'][0]['local_display_name'] ?? null,
            'brand' => $rawProduct['brand']['name'] ?? null,
            'price' => isset($rawProduct['skus'][0]['price']['amount']) ? $rawProduct['skus'][0]['price']['amount'] / 100000 : 0,
            'original_price' => isset($rawProduct['skus'][0]['original_price']['amount']) ? $rawProduct['skus'][0]['original_price']['amount'] / 100000 : 0,
            'currency' => $rawProduct['skus'][0]['price']['currency'] ?? 'THB',
            'stock_quantity' => array_sum(array_column($rawProduct['skus'] ?? [], 'stock_infos')),
            'is_available' => ($rawProduct['status'] ?? 0) === 1,
            'main_image_url' => $rawProduct['main_images'][0]['url_list'][0] ?? null,
            'images' => array_column($rawProduct['images'] ?? [], 'url_list'),
            'attributes' => $rawProduct['product_attributes'] ?? [],
            'variants' => $rawProduct['skus'] ?? [],
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
                'external_product_id' => $item['product_id'] ?? null,
                'external_sku' => $item['seller_sku'] ?? null,
                'product_name' => $item['product_name'] ?? 'Unknown',
                'product_image_url' => $item['sku_image'] ?? null,
                'variant_name' => $item['sku_name'] ?? null,
                'unit_price' => isset($item['sku_original_price']) ? $item['sku_original_price'] / 100000 : 0,
                'quantity' => $item['quantity'] ?? 1,
                'subtotal' => isset($item['sku_sale_price']) ? ($item['sku_sale_price'] / 100000) * ($item['quantity'] ?? 1) : 0,
                'discount_amount' => 0,
                'total_amount' => isset($item['sku_sale_price']) ? ($item['sku_sale_price'] / 100000) * ($item['quantity'] ?? 1) : 0,
            ];
        }

        return [
            'external_order_id' => $rawOrder['order_id'] ?? null,
            'order_number' => $rawOrder['order_id'] ?? null,
            'customer_name' => $rawOrder['recipient_address']['name'] ?? null,
            'customer_email' => null,
            'customer_phone' => $rawOrder['recipient_address']['phone_number'] ?? null,
            'subtotal' => isset($rawOrder['payment']['sub_total']) ? $rawOrder['payment']['sub_total'] / 100000 : 0,
            'shipping_fee' => isset($rawOrder['payment']['shipping_fee']) ? $rawOrder['payment']['shipping_fee'] / 100000 : 0,
            'tax_amount' => isset($rawOrder['payment']['taxes']) ? $rawOrder['payment']['taxes'] / 100000 : 0,
            'discount_amount' => isset($rawOrder['payment']['seller_discount']) ? $rawOrder['payment']['seller_discount'] / 100000 : 0,
            'total_amount' => isset($rawOrder['payment']['total_amount']) ? $rawOrder['payment']['total_amount'] / 100000 : 0,
            'currency' => $rawOrder['payment']['currency'] ?? 'THB',
            'order_status' => $this->mapOrderStatus($rawOrder['order_status'] ?? 0),
            'payment_status' => ($rawOrder['payment']['payment_method_name'] ?? '') ? 'paid' : 'pending',
            'ordered_at' => isset($rawOrder['create_time']) ? date('Y-m-d H:i:s', $rawOrder['create_time']) : null,
            'paid_at' => isset($rawOrder['paid_time']) ? date('Y-m-d H:i:s', $rawOrder['paid_time']) : null,
            'shipped_at' => isset($rawOrder['rts_time']) ? date('Y-m-d H:i:s', $rawOrder['rts_time']) : null,
            'delivered_at' => isset($rawOrder['delivered_time']) ? date('Y-m-d H:i:s', $rawOrder['delivered_time']) : null,
            'tracking_number' => $rawOrder['tracking_number'] ?? null,
            'shipping_provider' => $rawOrder['shipping_provider'] ?? null,
            'items' => $items,
            'raw_data' => $rawOrder,
        ];
    }

    /**
     * Map TikTok order status to our format
     */
    private function mapOrderStatus(int $status): string
    {
        return match ($status) {
            100 => 'unpaid',
            111 => 'awaiting_shipment',
            112 => 'partial_shipped',
            114 => 'shipped',
            121, 122 => 'delivered',
            130, 131, 140 => 'cancelled',
            default => 'pending',
        };
    }
}
