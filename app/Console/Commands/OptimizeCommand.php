<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize {--clear : Clear all caches before optimizing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('⚡ Optimizing TP-Affiliate for Production...');
        $this->newLine();

        // Clear caches if requested
        if ($this->option('clear')) {
            $this->task('Clearing all caches', function () {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                return true;
            });
        }

        // Cache configuration
        $this->task('Caching configuration', function () {
            Artisan::call('config:cache');
            return true;
        });

        // Cache routes
        $this->task('Caching routes', function () {
            Artisan::call('route:cache');
            return true;
        });

        // Cache views
        $this->task('Caching views', function () {
            Artisan::call('view:cache');
            return true;
        });

        // Optimize application
        $this->task('Optimizing application', function () {
            Artisan::call('optimize');
            return true;
        });

        $this->newLine();
        $this->info('✨ Application optimized successfully!');
        $this->newLine();

        // Display tips
        $this->comment('💡 Performance Tips:');
        $this->line('  1. Use Redis for cache and sessions');
        $this->line('  2. Enable OPcache in PHP');
        $this->line('  3. Use a CDN for static assets');
        $this->line('  4. Enable gzip compression');
        $this->line('  5. Use Laravel Octane for even better performance');
        $this->newLine();

        return Command::SUCCESS;
    }
}
