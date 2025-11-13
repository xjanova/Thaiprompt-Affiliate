<?php

namespace App\Services;

use App\Models\FoodProduct;
use App\Models\CarbonCredit;
use App\Models\ProductJourney;
use App\Models\FoodCertification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TpixBlockchainService
{
    protected string $rpcUrl;
    protected string $platformWallet;
    protected string $platformPrivateKey;
    protected bool $gasSubsidyEnabled;

    public function __construct()
    {
        $this->rpcUrl = config('tpix-blockchain.network.rpc_url');
        $this->platformWallet = config('tpix-blockchain.wallet.platform_address');
        $this->platformPrivateKey = config('tpix-blockchain.wallet.platform_private_key');
        $this->gasSubsidyEnabled = config('tpix-blockchain.wallet.gas_subsidy_enabled', true);
    }

    /**
     * Mint Food Passport NFT when product is created.
     */
    public function mintFoodPassportNFT(FoodProduct $product): array
    {
        try {
            // Prepare NFT metadata
            $metadata = $this->prepareFoodPassportMetadata($product);

            // Upload metadata to IPFS
            $ipfsHash = $this->uploadToIPFS($metadata);

            // Call smart contract to mint NFT
            $tokenId = $this->callContract(
                'food_passport_nft',
                'mint',
                [
                    $product->farmer->wallet_address ?? $this->platformWallet,
                    $product->food_passport_id,
                    "ipfs://{$ipfsHash}",
                ]
            );

            Log::info('Food Passport NFT minted', [
                'product_id' => $product->id,
                'token_id' => $tokenId,
                'ipfs_hash' => $ipfsHash,
            ]);

            return [
                'token_id' => $tokenId,
                'ipfs_hash' => $ipfsHash,
                'nft_url' => $this->getNFTUrl($tokenId),
                'metadata_url' => "ipfs://{$ipfsHash}",
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mint Food Passport NFT', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record journey stage on TPIX blockchain.
     */
    public function recordJourneyStage(ProductJourney $journey): string
    {
        try {
            $txHash = $this->callContract(
                'journey_record',
                'addStage',
                [
                    $journey->foodProduct->food_passport_id,
                    $journey->stage,
                    $journey->location_name,
                    json_encode($journey->location_coordinates),
                    $journey->arrived_at->timestamp,
                    $journey->departed_at?->timestamp ?? 0,
                ]
            );

            Log::info('Journey stage recorded on TPIX blockchain', [
                'journey_id' => $journey->id,
                'tx_hash' => $txHash,
            ]);

            return $txHash;
        } catch (\Exception $e) {
            Log::error('Failed to record journey stage', [
                'journey_id' => $journey->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Issue carbon credit token (TPCC).
     */
    public function issueCarbonCreditToken(CarbonCredit $credit): array
    {
        try {
            // Calculate token amount (with 18 decimals)
            $tokenAmount = bcmul($credit->credit_amount, bcpow('10', '18'));

            // Mint carbon credit tokens
            $txHash = $this->callContract(
                'carbon_credit_token',
                'mint',
                [
                    $credit->user->wallet_address ?? $this->platformWallet,
                    $tokenAmount,
                    json_encode([
                        'credit_id' => $credit->id,
                        'product_id' => $credit->food_product_id,
                        'reduction' => $credit->reduction_percentage,
                    ]),
                ]
            );

            Log::info('Carbon credit token issued', [
                'credit_id' => $credit->id,
                'amount' => $credit->credit_amount,
                'tx_hash' => $txHash,
            ]);

            return [
                'tx_hash' => $txHash,
                'token_amount' => $credit->credit_amount,
                'token_symbol' => 'TPCC',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to issue carbon credit token', [
                'credit_id' => $credit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Trade carbon credit on TPIX DEX.
     */
    public function tradeCarbonCredit(CarbonCredit $credit, string $buyerWallet): array
    {
        try {
            $tokenAmount = bcmul($credit->credit_amount, bcpow('10', '18'));
            $priceInTPIX = $this->convertToTPIX($credit->trade_price);

            // Transfer tokens from seller to buyer
            $txHash = $this->callContract(
                'carbon_credit_token',
                'transfer',
                [
                    $buyerWallet,
                    $tokenAmount,
                ],
                $credit->user->wallet_address // From seller's wallet
            );

            Log::info('Carbon credit traded on blockchain', [
                'credit_id' => $credit->id,
                'buyer_wallet' => $buyerWallet,
                'tx_hash' => $txHash,
            ]);

            return [
                'tx_hash' => $txHash,
                'price_in_tpix' => $priceInTPIX,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to trade carbon credit', [
                'credit_id' => $credit->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mint Quality Certificate NFT.
     */
    public function mintQualityCertificateNFT(FoodCertification $certification): array
    {
        try {
            $metadata = $this->prepareCertificationMetadata($certification);
            $ipfsHash = $this->uploadToIPFS($metadata);

            $tokenId = $this->callContract(
                'quality_certificate_nft',
                'mint',
                [
                    $certification->foodProduct->farmer->wallet_address ?? $this->platformWallet,
                    $certification->certificate_number,
                    "ipfs://{$ipfsHash}",
                ]
            );

            Log::info('Quality Certificate NFT minted', [
                'certification_id' => $certification->id,
                'token_id' => $tokenId,
            ]);

            return [
                'token_id' => $tokenId,
                'ipfs_hash' => $ipfsHash,
                'nft_url' => $this->getNFTUrl($tokenId, 'quality_certificate_nft'),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mint Quality Certificate NFT', [
                'certification_id' => $certification->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Call smart contract method.
     */
    protected function callContract(
        string $contractKey,
        string $method,
        array $params,
        ?string $fromWallet = null
    ): string {
        $contractConfig = config("tpix-blockchain.contracts.{$contractKey}");
        $fromWallet = $fromWallet ?? $this->platformWallet;

        // Estimate gas
        $gasLimit = $contractConfig['gas_limit'];
        $gasPrice = config('tpix-blockchain.gas.price_in_tpix');

        // Build transaction
        $transaction = [
            'from' => $fromWallet,
            'to' => $contractConfig['address'],
            'gas' => $gasLimit,
            'gasPrice' => $this->convertToWei($gasPrice),
            'data' => $this->encodeMethodCall($method, $params),
        ];

        // Sign and send transaction
        $txHash = $this->sendTransaction($transaction);

        // Wait for confirmation
        $this->waitForConfirmation($txHash);

        return $txHash;
    }

    /**
     * Send transaction to TPIX network.
     */
    protected function sendTransaction(array $transaction): string
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [$transaction],
            'id' => 1,
        ]);

        if ($response->failed() || isset($response['error'])) {
            throw new \Exception('Transaction failed: ' . ($response['error']['message'] ?? 'Unknown error'));
        }

        return $response['result'];
    }

    /**
     * Wait for transaction confirmation.
     */
    protected function waitForConfirmation(string $txHash, int $maxAttempts = 30): void
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $receipt = $this->getTransactionReceipt($txHash);

            if ($receipt && isset($receipt['status'])) {
                if ($receipt['status'] === '0x1') {
                    return; // Success
                } else {
                    throw new \Exception('Transaction reverted');
                }
            }

            sleep(2);
            $attempts++;
        }

        throw new \Exception('Transaction confirmation timeout');
    }

    /**
     * Get transaction receipt.
     */
    protected function getTransactionReceipt(string $txHash): ?array
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionReceipt',
            'params' => [$txHash],
            'id' => 1,
        ]);

        return $response['result'] ?? null;
    }

    /**
     * Prepare Food Passport metadata for NFT.
     */
    protected function prepareFoodPassportMetadata(FoodProduct $product): array
    {
        return [
            'name' => "Food Passport #{$product->food_passport_id}",
            'description' => "Blockchain-verified food traceability record for {$product->product_type}",
            'image' => $product->qr_code_url,
            'external_url' => config('app.url') . "/food-passport/{$product->food_passport_id}",
            'attributes' => [
                ['trait_type' => 'Passport ID', 'value' => $product->food_passport_id],
                ['trait_type' => 'Product Type', 'value' => $product->product_type],
                ['trait_type' => 'Variety', 'value' => $product->variety],
                ['trait_type' => 'Farmer', 'value' => $product->farmer_name],
                ['trait_type' => 'Farm Location', 'value' => $product->farm_location],
                ['trait_type' => 'Harvest Date', 'value' => $product->harvest_date?->format('Y-m-d')],
                ['trait_type' => 'Carbon Score', 'value' => $product->carbon_score ?? 'N/A'],
                ['trait_type' => 'Quality Status', 'value' => $product->quality_status],
                ['trait_type' => 'Certification Status', 'value' => $product->certification_status],
                ['trait_type' => 'Blockchain Verified', 'value' => 'Yes'],
            ],
        ];
    }

    /**
     * Prepare certification metadata.
     */
    protected function prepareCertificationMetadata(FoodCertification $certification): array
    {
        return [
            'name' => "{$certification->certification_name} Certificate",
            'description' => "Official {$certification->certification_type} certification",
            'image' => $certification->certificate_url,
            'attributes' => [
                ['trait_type' => 'Certificate Number', 'value' => $certification->certificate_number],
                ['trait_type' => 'Certification Type', 'value' => $certification->certification_type],
                ['trait_type' => 'Certifying Body', 'value' => $certification->certifying_body],
                ['trait_type' => 'Issued Date', 'value' => $certification->issued_date?->format('Y-m-d')],
                ['trait_type' => 'Expiry Date', 'value' => $certification->expiry_date?->format('Y-m-d')],
                ['trait_type' => 'Status', 'value' => $certification->status],
            ],
        ];
    }

    /**
     * Upload metadata to IPFS.
     */
    protected function uploadToIPFS(array $metadata): string
    {
        $ipfsGateway = config('tpix-blockchain.nft.ipfs_gateway');
        $apiKey = config('tpix-blockchain.nft.ipfs_api_key');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->post("{$ipfsGateway}/api/v0/add", [
            'file' => json_encode($metadata),
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to upload to IPFS');
        }

        return $response['Hash'];
    }

    /**
     * Get NFT URL on TPIX explorer.
     */
    protected function getNFTUrl(int $tokenId, string $contractKey = 'food_passport_nft'): string
    {
        $explorerUrl = config('tpix-blockchain.network.explorer_url');
        $contractAddress = config("tpix-blockchain.contracts.{$contractKey}.address");

        return "{$explorerUrl}/token/{$contractAddress}?a={$tokenId}";
    }

    /**
     * Convert price to TPIX.
     */
    protected function convertToTPIX(float $usdAmount): float
    {
        // Get TPIX price from cache or API
        $tpixPrice = Cache::remember('tpix_usd_price', 300, function () {
            // Fetch from your DEX or price oracle
            return 0.10; // Example: 1 TPIX = $0.10
        });

        return $usdAmount / $tpixPrice;
    }

    /**
     * Convert TPIX to Wei (18 decimals).
     */
    protected function convertToWei(float $tpix): string
    {
        return bcmul($tpix, bcpow('10', '18'));
    }

    /**
     * Encode method call for contract.
     */
    protected function encodeMethodCall(string $method, array $params): string
    {
        // This would use Web3 PHP library or similar
        // For now, return placeholder
        return '0x' . bin2hex($method . json_encode($params));
    }
}
