<?php

namespace App\Console\Commands\ArrowX;

use App\Services\ThemeCompilerService;
use Illuminate\Console\Command;

/**
 * Artisan Command: Warm Up Arrow X Theme Cache
 *
 * Pre-compile ทุก theme ลง cache เพื่อเพิ่มประสิทธิภาพ
 */
class WarmUpThemeCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arrowx:warmup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-compile ทุก Arrow X Theme ลง cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Arrow X Theme Cache Warm-up');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $compiler = app(ThemeCompilerService::class);

        $this->info('📦 Compiling all themes to cache...');

        try {
            $count = $compiler->warmUpCache();

            $this->newLine();
            $this->info("✅ Successfully warmed up {$count} theme(s)");
            $this->info('🎉 Cache is ready!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Warm-up failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
