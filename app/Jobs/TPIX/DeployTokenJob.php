<?php

namespace App\Jobs\TPIX;

use App\Models\TPIXToken;
use App\Services\TPIX\TokenFactoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected int $tokenId;
    protected ?array $deploymentOptions;

    /**
     * Create a new job instance.
     */
    public function __construct(int $tokenId, ?array $deploymentOptions = null)
    {
        $this->tokenId = $tokenId;
        $this->deploymentOptions = $deploymentOptions ?? [];
        $this->onQueue('blockchain');
    }

    /**
     * Execute the job.
     */
    public function handle(TokenFactoryService $tokenFactory): void
    {
        try {
            $token = TPIXToken::find($this->tokenId);

            if (!$token) {
                Log::error("Token {$this->tokenId} not found");
                return;
            }

            if ($token->status !== 'draft') {
                Log::warning("Token {$this->tokenId} is not in draft status");
                return;
            }

            Log::info("Starting deployment for token: {$token->symbol}");

            // Update status to deploying
            $token->update(['status' => 'deploying']);

            // Deploy token to blockchain
            $result = $tokenFactory->deployToken($token);

            if ($result['success']) {
                Log::info("Token {$token->symbol} deployed successfully at {$result['contract_address']}");

                // Dispatch notification
                dispatch(new \App\Jobs\TPIX\SendTokenDeployedNotificationJob($token->id));

                // Trigger event
                event(new \App\Events\TPIX\TokenDeployed($token));

            } else {
                Log::error("Token {$token->symbol} deployment failed: " . ($result['error'] ?? 'Unknown error'));
                $token->update([
                    'status' => 'failed',
                    'deployment_error' => $result['error'] ?? 'Deployment failed',
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to deploy token {$this->tokenId}: " . $e->getMessage());

            $token = TPIXToken::find($this->tokenId);
            if ($token) {
                $token->update([
                    'status' => 'failed',
                    'deployment_error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Token deployment failed permanently for token {$this->tokenId}: " . $exception->getMessage());

        $token = TPIXToken::find($this->tokenId);
        if ($token) {
            $token->update([
                'status' => 'failed',
                'deployment_error' => 'Deployment failed after multiple retries: ' . $exception->getMessage(),
            ]);
        }
    }
}
