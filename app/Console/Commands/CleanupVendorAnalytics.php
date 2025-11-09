<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VendorStore;
use App\Models\VendorAnalytics;
use App\Models\VendorStoreVisit;
use App\Models\VendorAnalyticsSetting;
use Carbon\Carbon;

class CleanupVendorAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:cleanup-vendor
                            {--store= : Specific store ID to cleanup}
                            {--days= : Override retention days (delete data older than this)}
                            {--force : Force cleanup even if auto-cleanup is disabled}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old vendor analytics data based on retention settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting vendor analytics cleanup...');

        // Get stores to cleanup
        $stores = $this->option('store')
            ? VendorStore::where('id', $this->option('store'))->get()
            : VendorStore::where('status', 'active')->get();

        if ($stores->isEmpty()) {
            $this->warn('No stores found to cleanup.');
            return 1;
        }

        $this->info("Found {$stores->count()} store(s) to process.");

        $totalAnalyticsDeleted = 0;
        $totalVisitsDeleted = 0;

        foreach ($stores as $store) {
            $settings = VendorAnalyticsSetting::getForStore($store);

            // Check if auto-cleanup is enabled (unless forced)
            if (!$settings->auto_cleanup_enabled && !$this->option('force')) {
                $this->info("Store {$store->id}: Auto-cleanup disabled, skipping...");
                continue;
            }

            // Get retention days
            $retentionDays = $this->option('days') ?? $settings->retention_days;
            $cutoffDate = Carbon::now()->subDays($retentionDays);

            $this->info("Store {$store->id}: Cleaning data older than {$cutoffDate->toDateString()} ({$retentionDays} days)");

            // Count records to delete
            $analyticsCount = VendorAnalytics::where('store_id', $store->id)
                ->where('date', '<', $cutoffDate)
                ->count();

            $visitsCount = VendorStoreVisit::where('store_id', $store->id)
                ->where('visited_at', '<', $cutoffDate)
                ->count();

            if ($this->option('dry-run')) {
                $this->info("  [DRY RUN] Would delete:");
                $this->info("    - {$analyticsCount} analytics records");
                $this->info("    - {$visitsCount} visit records");
            } else {
                // Delete old analytics
                $deleted = VendorAnalytics::where('store_id', $store->id)
                    ->where('date', '<', $cutoffDate)
                    ->delete();

                $totalAnalyticsDeleted += $deleted;

                // Delete old visits
                $deletedVisits = VendorStoreVisit::where('store_id', $store->id)
                    ->where('visited_at', '<', $cutoffDate)
                    ->delete();

                $totalVisitsDeleted += $deletedVisits;

                $this->info("  Deleted:");
                $this->info("    - {$deleted} analytics records");
                $this->info("    - {$deletedVisits} visit records");
            }
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No data was actually deleted.');
        } else {
            $this->info('Cleanup complete!');
            $this->info("Total analytics records deleted: {$totalAnalyticsDeleted}");
            $this->info("Total visit records deleted: {$totalVisitsDeleted}");
        }

        return 0;
    }
}
