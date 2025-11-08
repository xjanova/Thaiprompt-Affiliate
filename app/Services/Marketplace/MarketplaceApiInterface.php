<?php

namespace App\Services\Marketplace;

interface MarketplaceApiInterface
{
    /**
     * Test API connection with credentials
     */
    public function testConnection(): bool;

    /**
     * Get products from marketplace
     *
     * @param array $params
     * @return array
     */
    public function getProducts(array $params = []): array;

    /**
     * Get product details
     *
     * @param string $productId
     * @return array|null
     */
    public function getProduct(string $productId): ?array;

    /**
     * Get orders from marketplace
     *
     * @param array $params
     * @return array
     */
    public function getOrders(array $params = []): array;

    /**
     * Get order details
     *
     * @param string $orderId
     * @return array|null
     */
    public function getOrder(string $orderId): ?array;

    /**
     * Generate affiliate link for product
     *
     * @param string $productId
     * @param array $params
     * @return string|null
     */
    public function generateAffiliateLink(string $productId, array $params = []): ?string;

    /**
     * Refresh access token if needed
     *
     * @return bool
     */
    public function refreshToken(): bool;
}
