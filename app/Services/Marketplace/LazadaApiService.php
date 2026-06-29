<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LazadaApiService extends BaseMarketplaceService
{
    protected string $baseUrl = 'https://api.lazada.co.th/rest';

    /** Gateway สำหรับ System API (auth/token) — คนละ host กับ baseUrl */
    protected string $authUrl = 'https://auth.lazada.com/rest';

    /** URL หน้าอนุญาต (OAuth authorize) */
    protected const AUTHORIZE_ENDPOINT = 'https://auth.lazada.com/oauth/authorize';

    public function __construct(MarketplaceAccount $account)
    {
        parent::__construct($account);
        $this->setupAuth();
    }

    /**
     * Setup authentication headers
     */
    private function setupAuth(): void
    {
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Generate API signature for Lazada
     */
    private function generateSignature(string $path, array $params): string
    {
        // Add common parameters
        $params['app_key'] = $this->account->app_key;
        $params['timestamp'] = (string) (time() * 1000);
        $params['sign_method'] = 'sha256';

        if ($this->account->access_token) {
            $params['access_token'] = $this->account->access_token;
        }

        // Sort parameters
        ksort($params);

        // Concatenate all parameters
        $stringToBeSigned = $path;
        foreach ($params as $key => $value) {
            $stringToBeSigned .= $key.$value;
        }

        // Generate HMAC-SHA256 signature
        $signature = hash_hmac('sha256', $stringToBeSigned, $this->account->app_secret);

        return strtoupper($signature);
    }

    /**
     * Make signed request to Lazada API
     */
    private function makeSignedRequest(string $path, array $params = [], string $method = 'GET'): ?array
    {
        $params['app_key'] = $this->account->app_key;
        $params['timestamp'] = (string) (time() * 1000);
        $params['sign_method'] = 'sha256';

        if ($this->account->access_token) {
            $params['access_token'] = $this->account->access_token;
        }

        // Generate signature
        $params['sign'] = $this->generateSignature($path, $params);

        return $this->makeRequest($method, $path, $params);
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        $response = $this->makeSignedRequest('/seller/get');

        if ($response && isset($response['code']) && $response['code'] === '0') {
            $this->account->update(['status' => 'active']);

            return true;
        }

        return false;
    }

    /**
     * Get products from Lazada
     */
    public function getProducts(array $params = []): array
    {
        $defaultParams = [
            'filter' => 'all',
            'offset' => 0,
            'limit' => 50,
        ];

        $params = array_merge($defaultParams, $params);
        $response = $this->makeSignedRequest('/products/get', $params);

        if (! $response || ! isset($response['data']['products'])) {
            return [];
        }

        return array_map(fn ($product) => $this->normalizeProduct($product), $response['data']['products']);
    }

    /**
     * Get product details
     */
    public function getProduct(string $productId): ?array
    {
        $response = $this->makeSignedRequest('/product/item/get', [
            'item_id' => $productId,
        ]);

        if (! $response || ! isset($response['data'])) {
            return null;
        }

        return $this->normalizeProduct($response['data']);
    }

    /**
     * Get orders from Lazada
     */
    public function getOrders(array $params = []): array
    {
        $defaultParams = [
            'created_after' => now()->subDays(7)->toIso8601String(),
            'offset' => 0,
            'limit' => 50,
        ];

        $params = array_merge($defaultParams, $params);
        $response = $this->makeSignedRequest('/orders/get', $params);

        if (! $response || ! isset($response['data']['orders'])) {
            return [];
        }

        return array_map(fn ($order) => $this->normalizeOrder($order), $response['data']['orders']);
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): ?array
    {
        $response = $this->makeSignedRequest('/order/get', [
            'order_id' => $orderId,
        ]);

        if (! $response || ! isset($response['data'])) {
            return null;
        }

        return $this->normalizeOrder($response['data']);
    }

    /**
     * Generate affiliate link
     */
    public function generateAffiliateLink(string $productId, array $params = []): ?string
    {
        // Lazada uses their own affiliate program
        // This would need integration with Lazada Affiliate API
        // For now, return the product URL
        return "https://www.lazada.co.th/products/{$productId}.html";
    }

    /**
     * สร้าง URL หน้าอนุญาต (OAuth authorize) ของ Lazada
     *
     * ผู้ใช้กดลิงก์นี้ → ล็อกอิน/อนุญาต → Lazada เด้งกลับมาที่ $redirectUri พร้อม ?code=...&state=...
     *
     * @param  string  $appKey  App Key ของแอป (client_id)
     * @param  string  $redirectUri  Callback URL (ต้องตรงกับที่ลงทะเบียนในแอป Lazada)
     * @param  string|null  $state  ค่าสุ่มกัน CSRF (ส่งกลับมาใน callback)
     */
    public static function buildAuthorizationUrl(string $appKey, string $redirectUri, ?string $state = null): string
    {
        $params = [
            'response_type' => 'code',
            'force_auth' => 'true',
            'redirect_uri' => $redirectUri,
            'client_id' => $appKey,
        ];

        if ($state !== null && $state !== '') {
            $params['state'] = $state;
        }

        return self::AUTHORIZE_ENDPOINT.'?'.http_build_query($params);
    }

    /**
     * แลก authorization code → access_token (เรียก /auth/token/create)
     *
     * หมายเหตุ: เซ็นแบบเฉพาะ (ไม่ใส่ access_token เดิมลง signature เพราะกำลังจะออก token ใหม่)
     *
     * @return array|null payload ตอบกลับ (มี access_token/refresh_token/expires_in) หรือ payload error / null ถ้า transport ล้ม
     */
    public function createTokenFromCode(string $code): ?array
    {
        $path = '/auth/token/create';

        $params = [
            'app_key' => $this->account->app_key,
            'timestamp' => (string) (time() * 1000),
            'sign_method' => 'sha256',
            'code' => $code,
        ];

        ksort($params);

        $stringToSign = $path;
        foreach ($params as $key => $value) {
            $stringToSign .= $key.$value;
        }
        $params['sign'] = strtoupper(hash_hmac('sha256', $stringToSign, $this->account->app_secret));

        try {
            $response = Http::timeout(30)->get($this->authUrl.$path, $params);
            $data = $response->json();
        } catch (\Throwable $e) {
            Log::error('Lazada token/create transport error', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        if (empty($data['access_token'])) {
            Log::warning('Lazada token/create ไม่สำเร็จ', ['resp' => $data]);
        }

        return $data; // controller เป็นผู้ตรวจ access_token / message
    }

    /**
     * Refresh access token
     */
    public function refreshToken(): bool
    {
        if (! $this->account->refresh_token) {
            return false;
        }

        $response = $this->makeSignedRequest('/auth/token/refresh', [
            'refresh_token' => $this->account->refresh_token,
        ]);

        if (! $response || ! isset($response['access_token'])) {
            return false;
        }

        $this->account->update([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'] ?? $this->account->refresh_token,
            'token_expires_at' => now()->addSeconds($response['expires_in'] ?? 3600),
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
            'external_product_id' => $rawProduct['item_id'] ?? $rawProduct['sku_id'] ?? null,
            'external_sku' => $rawProduct['seller_sku'] ?? null,
            'name' => $rawProduct['attributes']['name'] ?? $rawProduct['item_name'] ?? 'Unknown Product',
            'description' => $rawProduct['attributes']['description'] ?? null,
            'category' => $rawProduct['primary_category'] ?? null,
            'brand' => $rawProduct['attributes']['brand'] ?? null,
            'price' => $rawProduct['price'] ?? 0,
            'original_price' => $rawProduct['special_price'] ?? $rawProduct['price'] ?? 0,
            'currency' => 'THB',
            'stock_quantity' => $rawProduct['quantity'] ?? 0,
            'is_available' => ($rawProduct['status'] ?? 'inactive') === 'active',
            'main_image_url' => $rawProduct['images'][0] ?? null,
            'images' => $rawProduct['images'] ?? [],
            'attributes' => $rawProduct['attributes'] ?? [],
            'variants' => $rawProduct['skus'] ?? [],
        ];
    }

    /**
     * Normalize order data
     */
    protected function normalizeOrder(array $rawOrder): array
    {
        $items = [];
        foreach ($rawOrder['items'] ?? [] as $item) {
            $items[] = [
                'external_product_id' => $item['sku_id'] ?? null,
                'external_sku' => $item['seller_sku'] ?? null,
                'product_name' => $item['name'] ?? 'Unknown',
                'product_image_url' => $item['product_main_image'] ?? null,
                'variant_name' => $item['variation'] ?? null,
                'unit_price' => $item['item_price'] ?? 0,
                'quantity' => $item['quantity'] ?? 1,
                'subtotal' => ($item['item_price'] ?? 0) * ($item['quantity'] ?? 1),
                'discount_amount' => $item['voucher_amount'] ?? 0,
                'total_amount' => $item['paid_price'] ?? 0,
            ];
        }

        return [
            'external_order_id' => $rawOrder['order_id'] ?? null,
            'order_number' => $rawOrder['order_number'] ?? null,
            'customer_name' => $rawOrder['address_billing']['first_name'] ?? null,
            'customer_email' => $rawOrder['address_billing']['customer_email'] ?? null,
            'customer_phone' => $rawOrder['address_billing']['phone'] ?? null,
            'subtotal' => $rawOrder['items_count'] ?? 0,
            'shipping_fee' => $rawOrder['shipping_fee'] ?? 0,
            'tax_amount' => 0,
            'discount_amount' => $rawOrder['voucher_amount'] ?? 0,
            'total_amount' => $rawOrder['price'] ?? 0,
            'currency' => 'THB',
            'order_status' => $rawOrder['statuses'][0] ?? 'pending',
            'payment_status' => $rawOrder['payment_method'] ?? 'pending',
            'ordered_at' => isset($rawOrder['created_at']) ? date('Y-m-d H:i:s', strtotime($rawOrder['created_at'])) : null,
            'tracking_number' => $rawOrder['tracking_number'] ?? null,
            'items' => $items,
            'raw_data' => $rawOrder,
        ];
    }
}
