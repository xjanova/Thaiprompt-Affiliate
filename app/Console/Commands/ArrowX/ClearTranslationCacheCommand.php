<?php

namespace App\Console\Commands\ArrowX;

use App\Models\TranslationCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Artisan Command: Clear Translation Cache
 *
 * ลบ translation cache ทั้งหมดหรือเฉพาะภาษา
 */
class ClearTranslationCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arrowx:clear-translations
                            {--lang= : ภาษาเป้าหมาย (เช่น en, th)}
                            {--all : ลบทั้งหมด}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ลบ Arrow X Translation cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌐 Arrow X Translation Cache Cleaner');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $lang = $this->option('lang');
        $all = $this->option('all');

        // Clear memory cache
        $this->info('🗑️  Clearing memory cache...');
        Cache::flush();

        // Clear database cache
        $query = TranslationCache::query();

        if ($lang && !$all) {
            $query->where('target_language', $lang);
            $this->info("🎯 Clearing cache for language: {$lang}");
        } else {
            $this->info('🎯 Clearing all language caches');
        }

        $count = $query->count();
        $query->delete();

        $this->newLine();
        $this->info("✅ Cleared {$count} cached translation(s)");
        $this->info('🎉 Done!');

        return self::SUCCESS;
    }
}
