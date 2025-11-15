<?php

namespace App\Console\Commands\ArrowX;

use App\Services\ThemeCompilerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Artisan Command: Clear Arrow X Theme Cache
 *
 * ลบ cache และ compiled files
 */
class ClearThemeCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arrowx:clear
                            {--files : Also delete compiled files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ลบ Arrow X Theme cache และ compiled files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🗑️  Arrow X Theme Cache Cleaner');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Clear cache
        $compiler = app(ThemeCompilerService::class);
        $compiler->clearCache();
        $this->info('✅ Theme cache cleared');

        // Delete compiled files
        if ($this->option('files')) {
            $publicPath = public_path('themes/arrow-x');

            if (File::exists($publicPath)) {
                $files = File::files($publicPath);
                $count = count($files);

                File::deleteDirectory($publicPath);
                $this->info("✅ Deleted {$count} compiled file(s)");
            } else {
                $this->warn('⚠️  No compiled files found');
            }
        }

        $this->newLine();
        $this->info('🎉 Done!');

        return self::SUCCESS;
    }
}
