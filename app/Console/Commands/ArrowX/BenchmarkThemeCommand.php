<?php

namespace App\Console\Commands\ArrowX;

use App\Services\ThemeCompilerService;
use App\Models\ThemeSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Artisan Command: Benchmark Arrow X Theme Performance
 *
 * วัดประสิทธิภาพการ compile theme
 */
class BenchmarkThemeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arrowx:benchmark
                            {--iterations=10 : จำนวนรอบที่ทดสอบ}
                            {--clear-cache : ล้าง cache ก่อนทดสอบ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'วัดประสิทธิภาพการ compile Arrow X Theme';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('⚡ Arrow X Theme Performance Benchmark');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $iterations = (int) $this->option('iterations');

        if ($this->option('clear-cache')) {
            $this->info('🗑️  Clearing cache...');
            Cache::flush();
        }

        $compiler = app(ThemeCompilerService::class);
        $themeSetting = ThemeSetting::active();

        if (!$themeSetting) {
            $this->error('❌ No active theme found');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("📊 Running {$iterations} iterations...");
        $this->newLine();

        // ทดสอบ Compile with Cache
        $this->line('🔷 Test 1: Compile with Cache (First Run)');
        $cacheTime = $this->benchmarkCompile($compiler, $themeSetting, false);
        $this->line("   Time: {$cacheTime}ms");

        // ทดสอบ Compile with Cache (Cached)
        $this->line('🔷 Test 2: Compile with Cache (Cached)');
        $cachedTime = $this->benchmarkCompile($compiler, $themeSetting, false);
        $this->line("   Time: {$cachedTime}ms");

        // ทดสอบ Compile without Cache
        $this->line('🔷 Test 3: Compile Force Refresh');
        $noCache = [];
        for ($i = 0; $i < $iterations; $i++) {
            $noCache[] = $this->benchmarkCompile($compiler, $themeSetting, true);
        }
        $avgNoCache = round(array_sum($noCache) / count($noCache), 2);
        $this->line("   Average: {$avgNoCache}ms ({$iterations} iterations)");

        // ทดสอบ File Compilation
        $this->line('🔷 Test 4: Compile to Files');
        $fileTime = $this->benchmarkCompileToFile($compiler, $themeSetting);
        $this->line("   Time: {$fileTime}ms");

        // Summary
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📈 Performance Summary');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(
            ['Test', 'Time (ms)', 'Status'],
            [
                ['First Compile (Cache)', $cacheTime, $this->getStatus($cacheTime, 500)],
                ['Cached Compile', $cachedTime, $this->getStatus($cachedTime, 100)],
                ['Force Refresh (Avg)', $avgNoCache, $this->getStatus($avgNoCache, 500)],
                ['Compile to Files', $fileTime, $this->getStatus($fileTime, 1000)],
            ]
        );

        // Cache Performance
        $cacheImprovement = round((($avgNoCache - $cachedTime) / $avgNoCache) * 100, 1);
        $this->newLine();
        $this->info("🚀 Cache Performance Improvement: {$cacheImprovement}%");

        // Recommendations
        $this->newLine();
        $this->info('💡 Recommendations:');

        if ($avgNoCache > 1000) {
            $this->warn('   ⚠️  Compilation is slow. Consider optimizing CSS/JS generation.');
        } else {
            $this->line('   ✅ Compilation performance is good.');
        }

        if ($cacheImprovement > 50) {
            $this->line('   ✅ Cache is working effectively.');
        } else {
            $this->warn('   ⚠️  Cache improvement is low. Check cache configuration.');
        }

        return self::SUCCESS;
    }

    /**
     * Benchmark compile
     *
     * @param ThemeCompilerService $compiler
     * @param ThemeSetting $theme
     * @param bool $forceRefresh
     * @return float milliseconds
     */
    protected function benchmarkCompile(ThemeCompilerService $compiler, ThemeSetting $theme, bool $forceRefresh): float
    {
        $start = microtime(true);
        $compiler->compile($theme, $forceRefresh);
        $end = microtime(true);

        return round(($end - $start) * 1000, 2);
    }

    /**
     * Benchmark compile to file
     *
     * @param ThemeCompilerService $compiler
     * @param ThemeSetting $theme
     * @return float milliseconds
     */
    protected function benchmarkCompileToFile(ThemeCompilerService $compiler, ThemeSetting $theme): float
    {
        $start = microtime(true);
        $files = $compiler->compileToFile($theme);
        $end = microtime(true);

        // Cleanup
        @unlink($files['css_path']);
        @unlink($files['js_path']);

        return round(($end - $start) * 1000, 2);
    }

    /**
     * Get performance status
     *
     * @param float $time
     * @param float $threshold
     * @return string
     */
    protected function getStatus(float $time, float $threshold): string
    {
        if ($time < $threshold * 0.5) {
            return '🟢 Excellent';
        } elseif ($time < $threshold) {
            return '🟡 Good';
        } else {
            return '🔴 Slow';
        }
    }
}
