<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-data
                            {--except-admin : Keep admin user data}
                            {--force : Skip confirmation}
                            {--tables=* : Specific tables to clear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application data (except admin user by default)';

    /**
     * Tables to clear (except users table which is handled separately)
     */
    protected array $clearableTables = [
        'affiliates',
        'commissions',
        'wallets',
        'wallet_transactions',
        'withdrawals',
        'mlm_ranks',
        'mlm_rank_achievements',
        'products',
        'product_categories',
        'orders',
        'order_items',
        'notifications',
        'ai_conversations',
        'ai_messages',
        'ai_bot_rentals',
        'ai_knowledge_bases',
        'ai_knowledge_chunks',
        'line_rich_menus',
        'line_flex_messages',
        'pages',
        'seo_meta',
        'email_templates',
        'failed_jobs',
        'jobs',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('╔════════════════════════════════════════════════════════════╗');
        $this->warn('║                 ⚠️  WARNING ⚠️                              ║');
        $this->warn('║          Data Clearing Operation                           ║');
        $this->warn('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Check if specific tables requested
        $specificTables = $this->option('tables');
        if (! empty($specificTables)) {
            return $this->clearSpecificTables($specificTables);
        }

        // Show what will be cleared
        $this->info('The following data will be PERMANENTLY DELETED:');
        $this->newLine();

        if ($this->option('except-admin')) {
            $this->line('  • All users EXCEPT admin users');
        } else {
            $this->line('  • ALL users (including admin)');
        }

        foreach ($this->clearableTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("  • {$table}: {$count} records");
            }
        }

        $this->newLine();
        $this->warn('This action CANNOT be undone!');
        $this->newLine();

        // Confirmation
        if (! $this->option('force')) {
            if (! $this->confirm('Are you absolutely sure you want to continue?', false)) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }

            // Double confirmation
            $confirmation = $this->ask('Type "DELETE ALL DATA" to confirm');
            if ($confirmation !== 'DELETE ALL DATA') {
                $this->error('Confirmation failed. Operation cancelled.');

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->info('Starting data clearing operation...');
        $this->newLine();

        DB::beginTransaction();

        try {
            // Clear users (conditionally)
            $this->clearUsers();

            // Clear other tables
            foreach ($this->clearableTables as $table) {
                if (Schema::hasTable($table)) {
                    $this->clearTable($table);
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('✓ Data cleared successfully!');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('✗ Failed to clear data: '.$e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }
    }

    /**
     * ล้างข้อมูลในตาราง users
     *
     * เมื่อใช้ --except-admin จะเก็บ Admin/Super Admin ไว้โดยเช็คจาก:
     * - is_super_admin = true (วิธีหลักสำหรับ Super Admin)
     * - role = 'admin' (สำรอง เพื่อป้องกัน Admin ทั่วไป)
     *
     * ⚠️ หมายเหตุ: Super Admin ใช้ is_super_admin = true + role = 'admin'
     *    ไม่ได้ใช้ role = 'super_admin'
     */
    protected function clearUsers(): void
    {
        if ($this->option('except-admin')) {
            // ลบ users ทั้งหมด ยกเว้น Super Admin และ Admin
            // เก็บไว้ถ้า: is_super_admin = true หรือ role = 'admin'
            $deleted = DB::table('users')
                ->where(function ($query) {
                    // ไม่ใช่ super admin: is_super_admin = false หรือ NULL
                    // ⚠️ ต้องเช็ค NULL แยก เพราะ MySQL: NULL != true → NULL (ไม่ใช่ TRUE)
                    $query->where('is_super_admin', false)
                        ->orWhereNull('is_super_admin');
                })
                ->where('role', '!=', 'admin')  // และไม่ใช่ admin
                ->delete();

            // นับจำนวน admin ที่เหลือ
            $adminCount = DB::table('users')
                ->where(function ($query) {
                    $query->where('is_super_admin', true)
                        ->orWhere('role', 'admin');
                })
                ->count();

            $this->line("  ✓ Cleared users table (kept {$adminCount} admin users)");
            $this->line("    Deleted: {$deleted} users");
        } else {
            // Delete all users
            $count = DB::table('users')->count();
            DB::table('users')->truncate();

            $this->line('  ✓ Cleared users table');
            $this->line("    Deleted: {$count} users");
        }
    }

    /**
     * Clear specific table
     */
    protected function clearTable(string $table): void
    {
        $count = DB::table($table)->count();

        DB::table($table)->truncate();

        $this->line("  ✓ Cleared {$table}");
        $this->line("    Deleted: {$count} records");
    }

    /**
     * Clear specific tables only
     */
    protected function clearSpecificTables(array $tables): int
    {
        $this->info('Clearing specific tables:');
        $this->newLine();

        foreach ($tables as $table) {
            $this->line("  • {$table}");
        }

        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Continue?', false)) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        DB::beginTransaction();

        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->clearTable($table);
                } else {
                    $this->warn("  ⚠ Table '{$table}' does not exist");
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('✓ Tables cleared successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error('✗ Failed to clear tables: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
