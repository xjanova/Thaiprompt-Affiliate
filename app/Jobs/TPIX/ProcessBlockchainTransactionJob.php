<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXTokenTransfer;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBlockchainTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 120;
    public $backoff = [30, 60, 120, 300, 600];

    protected string $txHash;
    protected ?int $transferId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $txHash, ?int $transferId = null)
    {
        $this->txHash = $txHash;
        $this->transferId = $transferId;
        $this->onQueue('blockchain');
    }

    /**
     * Execute the job.
     */
    public function handle(TPIXBlockchainService $blockchainService): void
    {
        try {
            Log::info("Processing blockchain transaction: {$this->txHash}");

            // Get transaction details from blockchain
            $txData = $blockchainService->getTransaction($this->txHash);

            if (!$txData) {
                Log::warning("Transaction not found yet: {$this->txHash}");
                $this->release(30); // Retry after 30 seconds
                return;
            }

            // Check confirmations
            $confirmations = $txData['confirmations'] ?? 0;
            $requiredConfirmations = config('crypto.networks.tpix.required_confirmations', 12);

            if ($confirmations < $requiredConfirmations) {
                Log::info("Transaction {$this->txHash} has {$confirmations}/{$requiredConfirmations} confirmations");
                $this->release(15); // Check again in 15 seconds
                return;
            }

            // Update transfer record if exists
            if ($this->transferId) {
                $transfer = TPIXTokenTransfer::find($this->transferId);

                if ($transfer) {
                    $transfer->update([
                        'status' => $txData['status'] === 'success' ? 'confirmed' : 'failed',
                        'confirmations' => $confirmations,
                        'block_number' => $txData['blockNumber'] ?? null,
                        'gas_used' => $txData['gasUsed'] ?? null,
                    ]);

                    Log::info("Transfer {$this->transferId} updated: {$transfer->status}");
                }
            }

            // Trigger events based on transaction status
            if ($txData['status'] === 'success') {
                event(new \App\Events\TPIX\TransactionConfirmed($this->txHash, $txData));
            } else {
                event(new \App\Events\TPIX\TransactionFailed($this->txHash, $txData));
            }

        } catch (\Exception $e) {
            Log::error("Failed to process transaction {$this->txHash}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Transaction processing failed permanently for {$this->txHash}: " . $exception->getMessage());

        // Update transfer status to failed
        if ($this->transferId) {
            $transfer = TPIXTokenTransfer::find($this->transferId);
            if ($transfer) {
                $transfer->update(['status' => 'failed']);
            }
        }
    }
}
