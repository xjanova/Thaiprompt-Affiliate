<?php

namespace App\Jobs\FoodPassport;

use App\Models\CarbonCredit;
use App\Services\TpixBlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Record Carbon Credit on Blockchain Job
 *
 * Records carbon credit issuance and trades on TPIX blockchain
 */
class RecordCarbonCreditOnBlockchainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CarbonCredit $credit
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TpixBlockchainService $blockchain): void
    {
        Log::info('Recording carbon credit on blockchain', [
            'credit_id' => $this->credit->id,
            'status' => $this->credit->status,
        ]);

        try {
            $product = $this->credit->foodProduct;
            if (!$product) {
                throw new \Exception('Product not found for carbon credit');
            }

            // Determine operation type
            $operation = $this->determineOperation();

            // Handle based on status and operation
            match($operation) {
                'mint' => $this->mintCarbonCreditToken($blockchain),
                'transfer' => $this->transferCarbonCreditToken($blockchain),
                'retire' => $this->retireCarbonCreditToken($blockchain),
                'update' => $this->updateCarbonCreditMetadata($blockchain),
                default => Log::warning('Unknown carbon credit operation', ['operation' => $operation]),
            };

            Log::info('Carbon credit blockchain operation completed', [
                'credit_id' => $this->credit->id,
                'operation' => $operation,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record carbon credit on blockchain', [
                'credit_id' => $this->credit->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Determine blockchain operation type.
     */
    protected function determineOperation(): string
    {
        // If no blockchain hash, this is initial minting
        if (!$this->credit->blockchain_hash) {
            return 'mint';
        }

        // If traded to another user, this is a transfer
        if ($this->credit->isDirty('traded_to_user_id') && $this->credit->traded_to_user_id) {
            return 'transfer';
        }

        // If status changed to retired
        if ($this->credit->status === 'retired') {
            return 'retire';
        }

        // Otherwise, just update metadata
        return 'update';
    }

    /**
     * Mint TPCC token on blockchain.
     */
    protected function mintCarbonCreditToken(TpixBlockchainService $blockchain): void
    {
        if ($this->credit->blockchain_hash) {
            Log::info('Carbon credit already minted', ['credit_id' => $this->credit->id]);
            return;
        }

        Log::info('Minting TPCC token on blockchain', [
            'credit_id' => $this->credit->id,
            'amount' => $this->credit->credit_amount,
        ]);

        // Mint TPCC token via blockchain service
        $result = $blockchain->mintCarbonCreditToken($this->credit);

        // Update credit with blockchain info
        $this->credit->update([
            'blockchain_hash' => $result['tx_hash'],
            'token_id' => $result['token_id'] ?? null,
            'ipfs_hash' => $result['ipfs_hash'] ?? null,
        ]);

        Log::info('TPCC token minted successfully', [
            'credit_id' => $this->credit->id,
            'tx_hash' => $result['tx_hash'],
            'token_id' => $result['token_id'] ?? null,
        ]);
    }

    /**
     * Transfer TPCC token to new owner.
     */
    protected function transferCarbonCreditToken(TpixBlockchainService $blockchain): void
    {
        $buyer = $this->credit->tradedToUser;
        if (!$buyer || !$buyer->wallet_address) {
            throw new \Exception('Buyer wallet address not found');
        }

        Log::info('Transferring TPCC token on blockchain', [
            'credit_id' => $this->credit->id,
            'to_address' => $buyer->wallet_address,
        ]);

        // Transfer token on blockchain
        $txHash = $blockchain->transferCarbonCreditToken(
            $this->credit,
            $buyer->wallet_address
        );

        // Record trade transaction hash
        $this->credit->update([
            'trade_tx_hash' => $txHash,
        ]);

        Log::info('TPCC token transferred successfully', [
            'credit_id' => $this->credit->id,
            'tx_hash' => $txHash,
        ]);
    }

    /**
     * Retire TPCC token (burn).
     */
    protected function retireCarbonCreditToken(TpixBlockchainService $blockchain): void
    {
        Log::info('Retiring TPCC token on blockchain', [
            'credit_id' => $this->credit->id,
            'amount' => $this->credit->credit_amount,
        ]);

        // Burn/retire token on blockchain
        $txHash = $blockchain->retireCarbonCreditToken($this->credit);

        // Update credit with retirement tx
        $this->credit->update([
            'retirement_tx_hash' => $txHash,
        ]);

        Log::info('TPCC token retired successfully', [
            'credit_id' => $this->credit->id,
            'tx_hash' => $txHash,
        ]);
    }

    /**
     * Update carbon credit metadata on IPFS.
     */
    protected function updateCarbonCreditMetadata(TpixBlockchainService $blockchain): void
    {
        Log::info('Updating carbon credit metadata', [
            'credit_id' => $this->credit->id,
        ]);

        // Upload updated metadata to IPFS
        $metadata = [
            'credit_id' => $this->credit->id,
            'credit_amount' => $this->credit->credit_amount,
            'current_value' => $this->credit->current_value,
            'status' => $this->credit->status,
            'issued_date' => $this->credit->issued_date?->toIso8601String(),
            'expiry_date' => $this->credit->expiry_date?->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $ipfsHash = $blockchain->uploadToIPFS($metadata);

        $this->credit->update([
            'ipfs_hash' => $ipfsHash,
        ]);

        Log::info('Carbon credit metadata updated on IPFS', [
            'credit_id' => $this->credit->id,
            'ipfs_hash' => $ipfsHash,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Carbon credit blockchain recording failed permanently', [
            'credit_id' => $this->credit->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
