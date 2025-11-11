<?php

namespace App\Jobs;

use App\Services\TrendDetection\TrendScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeSourcesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(TrendScraperService $scraperService): void
    {
        Log::info('Starting scheduled scraping of trend sources');

        try {
            $results = $scraperService->scrapeAllReady();

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            Log::info("Scraped {$successCount}/{$totalCount} sources successfully");
        } catch (\Exception $e) {
            Log::error('Failed to scrape sources: ' . $e->getMessage());
            throw $e;
        }
    }
}
