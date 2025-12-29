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
     */
    public function getProducts(array $params = []): array;

    /**
     * Get product details
     */
    public function getProduct(string $productId): ?array;

    /**
     * Get orders from marketplace
     */
    public function getOrders(array $params = []): array;

    /**
     * Get order details
     */
    public function getOrder(string $orderId): ?array;

    /**
     * Generate affiliate link for product
     */
    public function generateAffiliateLink(string $productId, array $params = []): ?string;

    /**
     * Refresh access token if needed
     */
    public function refreshToken(): bool;
}
