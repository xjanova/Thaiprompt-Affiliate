<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDemoData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:reset {--fresh : Run migrate:fresh before seeding}';

    /**
     * The console command description.
     */
    protected $description = 'Reset demo data (clear and reseed database)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('');
        $this->info('🔄 Starting demo data reset...');
        $this->info('');

        // Confirm action
        if (!$this->option('fresh')) {
            if (!$this->confirm('This will delete all demo data and reseed. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Run migrate:fresh if --fresh option is provided
        if ($this->option('fresh')) {
            $this->warn('⚠️  Running migrate:fresh (This will drop all tables!)');
            if (!$this->confirm('Are you absolutely sure? This cannot be undone!')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            $this->info('Running migrations...');
            Artisan::call('migrate:fresh', [], $this->getOutput());
            $this->info('✅ Migrations completed');
        } else {
            // Clear specific tables
            $this->info('Clearing tables...');

            $tables = [
                'pages',
                'commissions',
                'affiliates',
                'users',
            ];

            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line("  - Cleared {$table}");
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('✅ Tables cleared');
        }

        // Run seeders
        $this->info('');
        $this->info('🌱 Seeding demo data...');
        Artisan::call('db:seed', [], $this->getOutput());

        $this->info('');
        $this->newLine();
        $this->info('✨ Demo data reset completed successfully!');
        $this->info('');
        $this->info('📧 Demo accounts:');
        $this->info('   Super Admin: superadmin@thaiprompt.com');
        $this->info('   Admin: admin@thaiprompt.com');
        $this->info('   Manager: manager@thaiprompt.com');
        $this->info('   Password: password123');
        $this->info('');

        return 0;
    }
}
