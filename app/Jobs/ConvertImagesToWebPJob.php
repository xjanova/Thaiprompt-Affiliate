<?php

namespace App\Jobs;

use App\Services\WebPService;
use App\Services\WebPDatabaseUpdateService;
use App\Models\WebPConversionStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConvertImagesToWebPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 1;

    protected string $directory;
    protected int $quality;
    protected bool $deleteOriginal;
    protected string $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $directory = 'all',
        int $quality = 85,
        bool $deleteOriginal = false,
        ?string $jobId = null
    ) {
        $this->directory = $directory;
        $this->quality = $quality;
        $this->deleteOriginal = $deleteOriginal;
        $this->jobId = $jobId ?? uniqid('webp_', true);
    }

    /**
     * Execute the job.
     */
    public function handle(
        WebPService $webpService,
        WebPDatabaseUpdateService $dbUpdateService
    ): void {
        try {
            // Initialize progress
            $this->updateProgress(0, 'กำลังเริ่มต้น...', 'processing');

            // Get directories to process
            $directories = $this->directory === 'all'
                ? ['branding', 'avatars', 'rich-menus', 'withdrawal-slips', 'page-loaders']
                : [$this->directory];

            // Count total files
            $totalFiles = 0;
            $filesToProcess = [];

            foreach ($directories as $dir) {
                if (!Storage::disk('public')->exists($dir)) {
                    continue;
                }

                $files = Storage::disk('public')->allFiles($dir);
                foreach ($files as $file) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                    // Only process convertible images (skip GIF and SVG)
                    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                        $filesToProcess[] = [
                            'path' => $file,
                            'directory' => $dir,
                            'extension' => $extension,
                        ];
                        $totalFiles++;
                    }
                }
            }

            if ($totalFiles === 0) {
                $this->updateProgress(100, 'ไม่พบไฟล์ที่ต้องแปลง', 'completed');
                return;
            }

            $this->updateProgress(5, "พบ {$totalFiles} ไฟล์ที่ต้องแปลง", 'processing');

            // Process files
            $processedFiles = 0;
            $convertedFiles = 0;
            $errorFiles = 0;
            $updatedRecords = [];

            foreach ($filesToProcess as $fileInfo) {
                $file = $fileInfo['path'];
                $directory = $fileInfo['directory'];

                try {
                    // Update progress
                    $percentage = 5 + (($processedFiles / $totalFiles) * 85); // 5% to 90%
                    $this->updateProgress(
                        $percentage,
                        "กำลังแปลง ({$processedFiles}/{$totalFiles}): " . basename($file),
                        'processing'
                    );

                    // Convert image
                    $result = $webpService->convertExistingImage(
                        $file,
                        $this->quality,
                        $this->deleteOriginal
                    );

                    if ($result) {
                        $convertedFiles++;

                        // Store for database update
                        $updatedRecords[] = [
                            'old_path' => $file,
                            'new_path' => $result['path'],
                            'directory' => $directory,
                        ];
                    }

                    $processedFiles++;

                } catch (\Exception $e) {
                    $errorFiles++;
                    Log::error("WebP conversion error for {$file}: " . $e->getMessage());
                }
            }

            // Update progress to database update phase
            $this->updateProgress(90, 'กำลังอัพเดตฐานข้อมูล...', 'processing');

            // Update database records
            $dbUpdateResults = $dbUpdateService->updateAllImagePaths($updatedRecords);

            // Track batch conversion if there are converted files
            if ($convertedFiles > 0) {
                try {
                    WebPConversionStat::incrementBatchConversion($convertedFiles);
                } catch (\Exception $e) {
                    Log::warning('Failed to track WebP batch conversion stat: ' . $e->getMessage());
                }
            }

            // Final progress
            $this->updateProgress(
                100,
                "เสร็จสิ้น! แปลง {$convertedFiles} ไฟล์, อัพเดต {$dbUpdateResults['total_updated']} records",
                'completed',
                [
                    'total_files' => $totalFiles,
                    'converted' => $convertedFiles,
                    'errors' => $errorFiles,
                    'database_updates' => $dbUpdateResults,
                ]
            );

        } catch (\Exception $e) {
            Log::error('WebP Job failed: ' . $e->getMessage());
            $this->updateProgress(
                0,
                'เกิดข้อผิดพลาด: ' . $e->getMessage(),
                'failed'
            );
            throw $e;
        }
    }

    /**
     * Update job progress in cache
     */
    protected function updateProgress(
        float $percentage,
        string $message,
        string $status,
        array $details = []
    ): void {
        Cache::put("webp_job:{$this->jobId}", [
            'percentage' => round($percentage, 2),
            'message' => $message,
            'status' => $status, // processing, completed, failed
            'details' => $details,
            'updated_at' => now()->toISOString(),
        ], now()->addHours(24));
    }

    /**
     * Get job ID
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateProgress(
            0,
            'เกิดข้อผิดพลาด: ' . $exception->getMessage(),
            'failed'
        );
    }
}
