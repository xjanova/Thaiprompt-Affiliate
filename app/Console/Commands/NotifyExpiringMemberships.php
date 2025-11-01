<?php

namespace App\Console\Commands;

use App\Services\MembershipRetentionService;
use App\Models\MembershipRetentionSetting;
use Illuminate\Console\Command;

class NotifyExpiringMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:notify-expiring {--days= : Number of days before expiry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users whose memberships are expiring soon';

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
        $days = $this->option('days') ?? MembershipRetentionSetting::get('warning_days_before_expiry', 7);

        $this->info("🔔 Checking for memberships expiring in {$days} days...");

        try {
            $expiringUsers = $this->retentionService->getExpiringUsers($days);

            if ($expiringUsers->isEmpty()) {
                $this->info("✨ No expiring memberships found.");
                return Command::SUCCESS;
            }

            $this->info("📧 Found {$expiringUsers->count()} expiring membership(s).");

            $table = [];
            foreach ($expiringUsers as $status) {
                $table[] = [
                    $status->user->name,
                    $status->user->email,
                    $status->daysRemaining() . ' days',
                    $status->next_renewal_date?->format('Y-m-d'),
                ];
            }

            $this->table(
                ['Name', 'Email', 'Days Left', 'Renewal Date'],
                $table
            );

            // Here you would send actual notifications
            // For now, just showing info
            $this->info("✅ Notification check completed!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error checking expiring memberships: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
