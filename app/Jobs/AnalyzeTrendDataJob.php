<?php

namespace App\Jobs;

use App\Services\TrendDetection\KeywordAnalyzerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeTrendDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(KeywordAnalyzerService $analyzerService): void
    {
        Log::info('Starting keyword analysis of trend data');

        try {
            // Analyze unprocessed data
            $results = $analyzerService->analyzeUnprocessed();

            $successCount = collect($results)->where('success', true)->count();
            $totalCount = count($results);

            Log::info("Analyzed {$successCount}/{$totalCount} trend data records");

            // Update trend scores
            $analyzerService->updateTrendScores();

            Log::info('Updated trend scores for all keywords');
        } catch (\Exception $e) {
            Log::error('Failed to analyze trend data: ' . $e->getMessage());
            throw $e;
        }
    }
}
