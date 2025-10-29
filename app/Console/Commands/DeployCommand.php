<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy {--skip-maintenance : Skip maintenance mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy the application to production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting TP-Affiliate Deployment...');
        $this->newLine();

        $skipMaintenance = $this->option('skip-maintenance');

        // Step 1: Maintenance Mode
        if (!$skipMaintenance) {
            $this->task('Enabling maintenance mode', function () {
                Artisan::call('down', ['--retry' => 60]);
                return true;
            });
        }

        // Step 2: Clear Cache
        $this->task('Clearing application cache', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return true;
        });

        // Step 3: Run Migrations
        $this->task('Running database migrations', function () {
            Artisan::call('migrate', ['--force' => true]);
            return true;
        });

        // Step 4: Cache Configuration
        $this->task('Caching configuration', function () {
            Artisan::call('config:cache');
            return true;
        });

        // Step 5: Cache Routes
        $this->task('Caching routes', function () {
            Artisan::call('route:cache');
            return true;
        });

        // Step 6: Cache Views
        $this->task('Caching views', function () {
            Artisan::call('view:cache');
            return true;
        });

        // Step 7: Optimize
        $this->task('Optimizing application', function () {
            Artisan::call('optimize');
            return true;
        });

        // Step 8: Disable Maintenance Mode
        if (!$skipMaintenance) {
            $this->task('Disabling maintenance mode', function () {
                Artisan::call('up');
                return true;
            });
        }

        $this->newLine();
        $this->info('✨ Deployment completed successfully!');
        $this->newLine();

        // Display summary
        $this->comment('Deployment Summary:');
        $this->line('  - Environment: ' . config('app.env'));
        $this->line('  - Time: ' . now()->format('Y-m-d H:i:s'));
        $this->line('  - Laravel Version: ' . app()->version());
        $this->newLine();

        $this->info('📋 Post-Deployment Checklist:');
        $this->line('  □ Test the application');
        $this->line('  □ Check logs: storage/logs/laravel.log');
        $this->line('  □ Monitor performance');
        $this->line('  □ Verify database migrations');
        $this->newLine();

        return Command::SUCCESS;
    }
}
