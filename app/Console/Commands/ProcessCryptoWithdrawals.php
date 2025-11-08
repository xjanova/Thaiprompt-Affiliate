<?php

namespace App\Console\Commands;

use App\Services\Crypto\WithdrawalProcessingService;
use Illuminate\Console\Command;

class ProcessCryptoWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:process-withdrawals {--continuous : Run continuously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending crypto withdrawal requests';

    protected WithdrawalProcessingService $withdrawalService;

    public function __construct(WithdrawalProcessingService $withdrawalService)
    {
        parent::__construct();
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $continuous = $this->option('continuous');

        if ($continuous) {
            $this->info('Starting continuous withdrawal processing...');
            $this->info('Press Ctrl+C to stop');

            while (true) {
                $this->processOnce();
                sleep(60); // Process every 60 seconds
            }
        } else {
            return $this->processOnce();
        }
    }

    protected function processOnce(): int
    {
        $this->info('Processing pending withdrawals...');

        try {
            // Process pending withdrawals
            $results = $this->withdrawalService->processPendingWithdrawals();

            $this->info("Processed: {$results['processed']} requests");
            $this->info("Approved: {$results['approved']} requests");
            $this->info("Sent: {$results['sent']} transactions");

            if ($results['errors'] > 0) {
                $this->warn("Errors: {$results['errors']}");
            }

            // Update withdrawal confirmations
            $this->info('Updating withdrawal confirmations...');
            $updateResults = $this->withdrawalService->updateWithdrawalConfirmations();

            $this->info("Checked: {$updateResults['checked']} processing withdrawals");
            $this->info("Confirmed: {$updateResults['confirmed']} withdrawals");
            $this->info("Failed: {$updateResults['failed']} withdrawals");

            // Show stats
            $stats = $this->withdrawalService->getWithdrawalStats();
            $this->newLine();
            $this->info('📊 Withdrawal Queue Stats:');
            $this->line("  Pending: {$stats['pending']}");
            $this->line("  Reviewing: {$stats['reviewing']}");
            $this->line("  Approved: {$stats['approved']}");
            $this->line("  Processing: {$stats['processing']}");
            $this->line("  Completed (24h): {$stats['completed_24h']}");
            $this->line("  Failed (24h): {$stats['failed_24h']}");

            $this->newLine();
            $this->info('✅ Withdrawal processing completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Withdrawal processing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
