<?php

namespace App\Console\Commands;

use App\Services\MembershipRetentionService;
use Illuminate\Console\Command;

class ExpireInactiveMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:expire-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire inactive memberships that passed grace period';

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
        $this->info('🔍 Checking for expired memberships...');

        try {
            $results = $this->retentionService->expireInactiveUsers();

            $this->info("✅ Expiration check completed!");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Processed', $results['processed']],
                    ['Expired', $results['expired']],
                ]
            );

            if ($results['expired'] > 0) {
                $this->warn("⚠️  {$results['expired']} membership(s) have been expired.");
            } else {
                $this->info("✨ No memberships expired.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error checking expiration: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
