<?php

namespace App\Console\Commands;

use App\Services\MembershipRetentionService;
use Illuminate\Console\Command;

class ProcessMembershipRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:process-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly membership renewal and check retention status';

    protected $retentionService;

    /**
     * Create a new command instance.
     */
    public function __construct(MembershipRetentionService $retentionService)
    {
        parent::__construct();
        $this->retentionService = $retentionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processing monthly membership renewal...');

        try {
            $results = $this->retentionService->processMonthlyRenewal();

            $this->info("✅ Renewal processing completed!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Processed', $results['processed']],
                    ['Succeeded', $results['succeeded']],
                    ['Failed', $results['failed']],
                    ['Errors', count($results['errors'])],
                ]
            );

            if (!empty($results['errors'])) {
                $this->error('⚠️  Errors occurred during processing:');
                foreach ($results['errors'] as $error) {
                    $this->error("User ID {$error['user_id']}: {$error['error']}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error processing renewal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
