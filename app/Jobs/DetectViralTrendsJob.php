<?php

namespace App\Jobs;

use App\Services\TrendDetection\ViralTrendDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectViralTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(ViralTrendDetectionService $detectionService): void
    {
        Log::info('Starting viral trend detection');

        try {
            $trends = $detectionService->detectViralTrends();

            $newTrends = collect($trends)->filter(function ($trend) {
                return $trend->wasRecentlyCreated;
            })->count();

            $totalTrends = count($trends);

            Log::info("Detected {$newTrends} new viral trends (total processed: {$totalTrends})");
        } catch (\Exception $e) {
            Log::error('Failed to detect viral trends: ' . $e->getMessage());
            throw $e;
        }
    }
}
