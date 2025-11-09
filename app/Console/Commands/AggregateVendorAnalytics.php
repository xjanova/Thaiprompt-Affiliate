<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VendorStore;
use App\Services\VendorAnalyticsService;
use Carbon\Carbon;

class AggregateVendorAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate-vendor
                            {--date= : The date to aggregate (Y-m-d format, defaults to yesterday)}
                            {--store= : Specific store ID to aggregate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate vendor store analytics data';

    protected VendorAnalyticsService $analyticsService;

    /**
     * Execute the console command.
     */
    public function handle(VendorAnalyticsService $analyticsService): int
    {
        $this->analyticsService = $analyticsService;

        // Get the date to aggregate
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->info("Aggregating vendor analytics for date: {$date}");

        // Get stores to aggregate
        $stores = $this->option('store')
            ? VendorStore::where('id', $this->option('store'))->get()
            : VendorStore::where('status', 'active')->get();

        if ($stores->isEmpty()) {
            $this->warn('No stores found to aggregate.');
            return 1;
        }

        $this->info("Found {$stores->count()} store(s) to process.");

        $progressBar = $this->output->createProgressBar($stores->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($stores as $store) {
            try {
                $this->analyticsService->recordDailyAnalytics($store, $date);
                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nFailed to aggregate analytics for store {$store->id}: " . $e->getMessage());
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Aggregation complete!");
        $this->info("Success: {$successCount}");

        if ($errorCount > 0) {
            $this->warn("Errors: {$errorCount}");
        }

        return 0;
    }
}
