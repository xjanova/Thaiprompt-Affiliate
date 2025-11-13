<?php

namespace App\Services\TPIX;

use App\Models\TPIXToken;
use App\Models\CMCTokenListing;
use App\Models\CMCSyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * CoinMarketCap Integration Service
 *
 * Manual professional import of cryptocurrency data from CoinMarketCap
 */
class CMCIntegrationService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://pro-api.coinmarketcap.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.coinmarketcap.api_key', '');
    }

    /**
     * Manual import token from CoinMarketCap by ID or slug
     */
    public function importToken(string $cmcIdOrSlug, ?User $importedBy = null): CMCTokenListing
    {
        DB::beginTransaction();
        try {
            // Check if already imported
            $existing = CMCTokenListing::where('cmc_id', $cmcIdOrSlug)
                ->orWhere('slug', $cmcIdOrSlug)
                ->first();

            if ($existing) {
                // Update existing
                $this->syncTokenData($existing);
                DB::commit();
                return $existing->fresh();
            }

            // Fetch from CMC API
            $data = $this->fetchTokenData($cmcIdOrSlug);

            // Create new listing
            $listing = CMCTokenListing::create([
                'cmc_id' => $data['id'],
                'name' => $data['name'],
                'symbol' => $data['symbol'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,

                // Market data
                'price_usd' => $data['quote']['USD']['price'] ?? null,
                'price_btc' => $data['quote']['BTC']['price'] ?? null,
                'market_cap_usd' => $data['quote']['USD']['market_cap'] ?? null,
                'volume_24h_usd' => $data['quote']['USD']['volume_24h'] ?? null,
                'circulating_supply' => $data['circulating_supply'] ?? null,
                'total_supply' => $data['total_supply'] ?? null,
                'max_supply' => $data['max_supply'] ?? null,

                // Price changes
                'percent_change_1h' => $data['quote']['USD']['percent_change_1h'] ?? null,
                'percent_change_24h' => $data['quote']['USD']['percent_change_24h'] ?? null,
                'percent_change_7d' => $data['quote']['USD']['percent_change_7d'] ?? null,
                'percent_change_30d' => $data['quote']['USD']['percent_change_30d'] ?? null,

                // Rankings
                'cmc_rank' => $data['cmc_rank'] ?? null,
                'num_market_pairs' => $data['num_market_pairs'] ?? null,

                // Platform
                'platform_name' => $data['platform']['name'] ?? null,
                'token_address' => $data['platform']['token_address'] ?? null,

                // Metadata
                'tags' => $data['tags'] ?? null,
                'urls' => $data['urls'] ?? null,
                'date_added' => $data['date_added'] ?? null,
                'last_updated' => $data['last_updated'] ?? null,

                'imported_by' => $importedBy?->id,
                'is_active' => true,
            ]);

            DB::commit();

            Log::info('CMC token imported', [
                'cmc_id' => $data['id'],
                'symbol' => $data['symbol'],
                'imported_by' => $importedBy?->id
            ]);

            return $listing;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CMC import failed', [
                'cmc_id' => $cmcIdOrSlug,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync TPIX token with CMC data
     */
    public function syncTPIXToken(TPIXToken $token, ?User $syncedBy = null): CMCSyncLog
    {
        if (!$token->cmc_id) {
            throw new Exception('Token does not have CMC ID');
        }

        DB::beginTransaction();
        try {
            // Get CMC listing
            $cmcListing = CMCTokenListing::where('cmc_id', $token->cmc_id)->first();

            if (!$cmcListing) {
                $cmcListing = $this->importToken($token->cmc_id, $syncedBy);
            } else {
                $this->syncTokenData($cmcListing);
            }

            // Calculate price in TPIX (assume 1 TPIX = 20 USD for example)
            $tpixPriceUsd = 20;
            $oldPrice = $token->current_price_tpix;
            $newPriceTpix = $cmcListing->price_usd ? $cmcListing->price_usd / $tpixPriceUsd : null;

            // Update TPIX token
            $token->update([
                'current_price_tpix' => $newPriceTpix,
                'market_cap_tpix' => $cmcListing->market_cap_usd ? $cmcListing->market_cap_usd / $tpixPriceUsd : null,
                'volume_24h_tpix' => $cmcListing->volume_24h_usd ? $cmcListing->volume_24h_usd / $tpixPriceUsd : null,
                'cmc_data' => [
                    'cmc_rank' => $cmcListing->cmc_rank,
                    'num_market_pairs' => $cmcListing->num_market_pairs,
                    'percent_change_24h' => $cmcListing->percent_change_24h,
                    'price_usd' => $cmcListing->price_usd,
                    'market_cap_usd' => $cmcListing->market_cap_usd,
                ],
                'cmc_last_sync' => now(),
            ]);

            // Calculate price change
            $priceChange = $oldPrice && $newPriceTpix
                ? (($newPriceTpix - $oldPrice) / $oldPrice) * 100
                : null;

            // Create sync log
            $syncLog = CMCSyncLog::create([
                'token_id' => $token->id,
                'sync_type' => $syncedBy ? 'manual' : 'auto',
                'cmc_id' => $token->cmc_id,
                'cmc_slug' => $cmcListing->slug,
                'price_data' => [
                    'price_usd' => $cmcListing->price_usd,
                    'price_tpix' => $newPriceTpix,
                ],
                'market_data' => [
                    'market_cap' => $cmcListing->market_cap_usd,
                    'volume_24h' => $cmcListing->volume_24h_usd,
                    'circulating_supply' => $cmcListing->circulating_supply,
                ],
                'old_price_usd' => $oldPrice ? $oldPrice * $tpixPriceUsd : null,
                'new_price_usd' => $cmcListing->price_usd,
                'price_change_percent' => $priceChange,
                'status' => 'success',
                'synced_by' => $syncedBy?->id,
            ]);

            DB::commit();

            Log::info('TPIX token synced with CMC', [
                'token_id' => $token->id,
                'cmc_id' => $token->cmc_id,
                'new_price_tpix' => $newPriceTpix
            ]);

            return $syncLog;

        } catch (Exception $e) {
            DB::rollBack();

            // Create failed log
            $syncLog = CMCSyncLog::create([
                'token_id' => $token->id,
                'sync_type' => $syncedBy ? 'manual' : 'auto',
                'cmc_id' => $token->cmc_id,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'synced_by' => $syncedBy?->id,
            ]);

            Log::error('CMC sync failed', [
                'token_id' => $token->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch token data from CoinMarketCap API
     */
    protected function fetchTokenData(string $cmcIdOrSlug): array
    {
        if (!$this->apiKey) {
            throw new Exception('CoinMarketCap API key not configured');
        }

        try {
            // Try to fetch by ID first, then by slug
            $isNumeric = is_numeric($cmcIdOrSlug);

            $endpoint = $isNumeric
                ? '/cryptocurrency/quotes/latest'
                : '/cryptocurrency/info';

            $params = $isNumeric
                ? ['id' => $cmcIdOrSlug]
                : ['slug' => $cmcIdOrSlug];

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . $endpoint, $params);

            if (!$response->successful()) {
                throw new Exception('CMC API request failed: ' . $response->status());
            }

            $data = $response->json();

            if (!isset($data['data']) || empty($data['data'])) {
                throw new Exception('No data returned from CMC API');
            }

            // Extract first result
            $tokenData = is_array($data['data']) ? reset($data['data']) : $data['data'];

            return $tokenData;

        } catch (Exception $e) {
            Log::error('CMC API fetch failed', [
                'cmc_id' => $cmcIdOrSlug,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync existing CMC listing with latest data
     */
    protected function syncTokenData(CMCTokenListing $listing): void
    {
        try {
            $data = $this->fetchTokenData($listing->cmc_id);

            $listing->update([
                'price_usd' => $data['quote']['USD']['price'] ?? $listing->price_usd,
                'price_btc' => $data['quote']['BTC']['price'] ?? $listing->price_btc,
                'market_cap_usd' => $data['quote']['USD']['market_cap'] ?? $listing->market_cap_usd,
                'volume_24h_usd' => $data['quote']['USD']['volume_24h'] ?? $listing->volume_24h_usd,
                'circulating_supply' => $data['circulating_supply'] ?? $listing->circulating_supply,
                'total_supply' => $data['total_supply'] ?? $listing->total_supply,
                'max_supply' => $data['max_supply'] ?? $listing->max_supply,
                'percent_change_1h' => $data['quote']['USD']['percent_change_1h'] ?? $listing->percent_change_1h,
                'percent_change_24h' => $data['quote']['USD']['percent_change_24h'] ?? $listing->percent_change_24h,
                'percent_change_7d' => $data['quote']['USD']['percent_change_7d'] ?? $listing->percent_change_7d,
                'percent_change_30d' => $data['quote']['USD']['percent_change_30d'] ?? $listing->percent_change_30d,
                'cmc_rank' => $data['cmc_rank'] ?? $listing->cmc_rank,
                'num_market_pairs' => $data['num_market_pairs'] ?? $listing->num_market_pairs,
                'last_updated' => $data['last_updated'] ?? now(),
            ]);

            Log::info('CMC listing synced', ['cmc_id' => $listing->cmc_id]);

        } catch (Exception $e) {
            Log::warning('Failed to sync CMC listing', [
                'cmc_id' => $listing->cmc_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Batch import multiple tokens
     */
    public function batchImport(array $cmcIds, ?User $importedBy = null): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($cmcIds as $cmcId) {
            try {
                $listing = $this->importToken($cmcId, $importedBy);
                $results['success'][] = [
                    'cmc_id' => $cmcId,
                    'symbol' => $listing->symbol,
                    'name' => $listing->name,
                ];
            } catch (Exception $e) {
                $results['failed'][] = [
                    'cmc_id' => $cmcId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Search CMC tokens
     */
    public function searchTokens(string $query): array
    {
        // This would use CMC search API
        // For now, return empty array
        return [];
    }

    /**
     * Get trending tokens from CMC
     */
    public function getTrending(int $limit = 10): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-CMC_PRO_API_KEY' => $this->apiKey,
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . '/cryptocurrency/trending/latest', [
                    'limit' => $limit
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }

            return [];

        } catch (Exception $e) {
            Log::error('Failed to get trending tokens', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
