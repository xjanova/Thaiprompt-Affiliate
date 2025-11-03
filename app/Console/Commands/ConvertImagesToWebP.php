<?php

namespace App\Console\Commands;

use App\Services\WebPService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertImagesToWebP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:convert-webp
                            {--directory= : Specific directory to convert (e.g., branding, avatars)}
                            {--delete-original : Delete original images after conversion}
                            {--quality=85 : WebP quality (0-100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing images in storage to WebP format';

    protected WebPService $webpService;

    public function __construct(WebPService $webpService)
    {
        parent::__construct();
        $this->webpService = $webpService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🖼️  Starting image conversion to WebP...');
        $this->newLine();

        $directory = $this->option('directory');
        $deleteOriginal = $this->option('delete-original');
        $quality = (int) $this->option('quality');

        // Get all directories to process
        $directories = $directory
            ? [$directory]
            : ['branding', 'avatars', 'rich-menus', 'withdrawal-slips', 'page-loaders'];

        $totalConverted = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($directories as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                $this->warn("⚠️  Directory '{$dir}' does not exist. Skipping...");
                continue;
            }

            $this->info("📁 Processing directory: {$dir}");

            $files = Storage::disk('public')->allFiles($dir);

            if (empty($files)) {
                $this->warn("   No files found in {$dir}");
                continue;
            }

            $bar = $this->output->createProgressBar(count($files));
            $bar->start();

            foreach ($files as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                // Skip non-image files and already converted WebP files
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $totalSkipped++;
                    $bar->advance();
                    continue;
                }

                // Skip GIF files to preserve animation
                if ($extension === 'gif') {
                    $totalSkipped++;
                    $bar->advance();
                    continue;
                }

                // Convert image
                $result = $this->webpService->convertExistingImage($file, $quality, $deleteOriginal);

                if ($result) {
                    $totalConverted++;
                } else {
                    $totalErrors++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
        }

        // Summary
        $this->newLine();
        $this->info('✅ Conversion complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Converted', $totalConverted],
                ['Skipped', $totalSkipped],
                ['Errors', $totalErrors],
            ]
        );

        if ($totalConverted > 0) {
            $this->info("🎉 Successfully converted {$totalConverted} images to WebP format!");
        }

        if ($deleteOriginal && $totalConverted > 0) {
            $this->warn("🗑️  Original images have been deleted.");
        }

        return Command::SUCCESS;
    }
}
