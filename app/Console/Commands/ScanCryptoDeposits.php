<?php

namespace App\Console\Commands;

use App\Services\Crypto\DepositDetectionService;
use Illuminate\Console\Command;

class ScanCryptoDeposits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:scan-deposits {--continuous : Run continuously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan blockchain for new crypto deposits';

    protected DepositDetectionService $depositService;

    public function __construct(DepositDetectionService $depositService)
    {
        parent::__construct();
        $this->depositService = $depositService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $continuous = $this->option('continuous');

        if ($continuous) {
            $this->info('Starting continuous deposit scanning...');
            $this->info('Press Ctrl+C to stop');

            while (true) {
                $this->scanOnce();
                sleep(30); // Scan every 30 seconds
            }
        } else {
            return $this->scanOnce();
        }
    }

    protected function scanOnce(): int
    {
        $this->info('Scanning for new deposits...');

        try {
            // Scan for new deposits
            $scanResults = $this->depositService->scanForDeposits();

            $this->info("Scanned: {$scanResults['scanned']} addresses");
            $this->info("Detected: {$scanResults['detected']} deposits");
            $this->info("Processed: {$scanResults['processed']} deposits");

            if ($scanResults['errors'] > 0) {
                $this->warn("Errors: {$scanResults['errors']}");
            }

            // Update pending deposits confirmations
            $this->info('Updating pending deposit confirmations...');
            $updateResults = $this->depositService->updatePendingDeposits();

            $this->info("Checked: {$updateResults['checked']} pending deposits");
            $this->info("Confirmed: {$updateResults['confirmed']} deposits");

            if ($updateResults['errors'] > 0) {
                $this->warn("Errors: {$updateResults['errors']}");
            }

            $this->info('✅ Deposit scan completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Deposit scan failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
