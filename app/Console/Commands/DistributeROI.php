<?php

namespace App\Console\Commands;

use App\Services\StakingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DistributeROI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investment:distribute-roi
                            {--force : Force distribution even if already run today}
                            {--retry : Retry failed distributions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Distribute ROI to all eligible staking positions';

    protected StakingService $stakingService;

    /**
     * Create a new command instance.
     */
    public function __construct(StakingService $stakingService)
    {
        parent::__construct();
        $this->stakingService = $stakingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ROI distribution...');
        Log::info('ROI Distribution Command Started');

        try {
            // Distribute ROI
            $this->info('Processing ROI distributions...');
            $result = $this->stakingService->distributeROI();

            if (isset($result['success']) && is_numeric($result['success'])) {
                $this->info("✓ Successfully distributed ROI to {$result['success']} positions");
                $this->info("✗ Failed: {$result['failed']} positions");
                $this->info("Total amount distributed: ฿" . number_format($result['total_amount'], 2));

                if (!empty($result['errors'])) {
                    $this->warn('Errors encountered:');
                    foreach ($result['errors'] as $error) {
                        $this->error("Position #{$error['position_id']}: {$error['error']}");
                    }
                }
            } else {
                $this->error('ROI distribution failed: ' . ($result['error'] ?? 'Unknown error'));
                return 1;
            }

            // Process mature positions
            $this->info('Processing mature positions...');
            $matureResult = $this->stakingService->processMaturePositions();

            if (isset($matureResult['success']) && is_numeric($matureResult['success'])) {
                $this->info("✓ Processed {$matureResult['success']} mature positions");
            }

            // Retry failed distributions if flag is set
            if ($this->option('retry')) {
                $this->info('Retrying failed distributions...');
                $retryResult = $this->stakingService->retryFailedDistributions();

                if (isset($retryResult['success']) && is_numeric($retryResult['success'])) {
                    $this->info("✓ Successfully retried {$retryResult['success']} distributions");
                }
            }

            // Show statistics
            $this->newLine();
            $this->info('=== Staking Statistics ===');
            $stats = $this->stakingService->getStakingStatistics();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Active Positions', number_format($stats['total_active_positions'])],
                    ['Total Staked Amount', '฿' . number_format($stats['total_staked_amount'], 2)],
                    ['Total ROI Distributed', '฿' . number_format($stats['total_roi_distributed'], 2)],
                    ['Total ROI Pending', '฿' . number_format($stats['total_roi_pending'], 2)],
                    ['Distributions Today', number_format($stats['distributions_today'])],
                    ['Total Investors', number_format($stats['total_investors'])],
                ]
            );

            $this->newLine();
            $this->info('ROI distribution completed successfully!');
            Log::info('ROI Distribution Command Completed Successfully', $result);

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('ROI Distribution Command Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
