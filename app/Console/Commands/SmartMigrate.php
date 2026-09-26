<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * รัน migration ที่ค้างแบบ "รู้ว่าตารางมีอยู่แล้ว" (deploy.sh เรียกก่อน fallback ไป `migrate --force`)
 *
 * 🚨 (2026-09-26) เดิม markMigrationsAsRan() ใส่ migration "ทุกตัว" ลงตาราง migrations แม้ตัวนั้น throw
 *   แล้วยังพิมพ์ว่าสำเร็จ + exit 0 ⇒ schema prod ค้างเงียบ
 *   (2025_12_21_000001_fix_mobile_banner_images → mobile_banners.position ค้าง enum เก่า 9 เดือน,
 *    2026_09_26_133100_seed_launch_app_campaign_banners ล้มซ้ำแบบเดียวกันใน v4.0.521)
 *   ตอนนี้: บันทึกทีละตัว "หลังสำเร็จจริง" เท่านั้น · ตัวที่ล้มยังค้าง pending · หยุดที่ตัวแรกที่ล้ม
 *   (ตัวถัดไปอาจพึ่งตัวที่ล้ม — แบบเดียวกับ `php artisan migrate`) · exit ≠ 0 + สรุปข้อความ error
 *
 * exit code (deploy.sh ใช้ตัดสินว่าจะ fallback ไป `migrate --force` หรือไม่):
 *   0 = สำเร็จทั้งหมด
 *   1 = migration ล้มใน up() (หรือพังก่อนเริ่ม) ⇒ ห้าม fallback: `migrate --force` จะรัน up() ตัวเดิมซ้ำ
 *       ถ้า up() มี guard (hasTable → return) มันจะ no-op แล้วถูกบันทึกว่ารันแล้ว = บั๊กเดิมกลับมา
 *   2 = ล้มตอนเพิ่มคอลัมน์ที่คำสั่งนี้ "เดาชนิด" เอง (up() ยังไม่เคยถูกรัน) ⇒ fallback รัน up() จริงได้
 */
class SmartMigrate extends Command
{
    private const EXIT_MIGRATION_FAILED = 1;

    private const EXIT_COLUMN_GUESS_FAILED = 2;

    protected $signature = 'migrate:smart
        {--force : Force the operation to run in production}
        {--path= : Absolute path of the migrations directory (default: database/migrations)}';

    protected $description = 'Smart migration system that handles existing tables';

    private $stats = [
        'migrations_run' => 0,
        'migrations_marked_without_up' => 0,
        'tables_created' => 0,
        'tables_updated' => 0,
        'tables_skipped' => 0,
        'columns_added' => 0,
        'columns_modified' => 0,
    ];

    /** @var array<int, array{migration: string, error: string}> migration ที่ล้ม (ไม่ถูกบันทึก) */
    private array $failures = [];

    /** @var array<int, string> migration ที่ไม่ได้ลองรันเพราะหยุดหลังตัวที่ล้ม (ยังค้าง pending) */
    private array $notAttempted = [];

    /** @var array<int, string> migration ที่บันทึกลงตาราง migrations แล้วในรอบนี้ */
    private array $marked = [];

    private int $nextBatch = 1;

    /** ล้มในส่วนที่คำสั่งนี้เดาชนิดคอลัมน์เอง (ไม่ใช่ใน up()) — ดู EXIT_COLUMN_GUESS_FAILED */
    private bool $failedInColumnGuess = false;

    public function handle()
    {
        $this->info('🎯 Smart Migration System');
        $this->newLine();

        // Check database connection
        if (! $this->checkDatabaseConnection()) {
            $this->error('✗ Database connection failed');

            return 1;
        }
        $this->info('✓ Database connection OK');
        $this->newLine();

        $directory = $this->option('path') ?: database_path('migrations');

        if (! is_dir($directory)) {
            $this->error("✗ Migrations directory not found: {$directory}");

            return 1;
        }

        // Get pending migrations
        $pending = $this->getPendingMigrations($directory);

        if (empty($pending)) {
            $this->info('✓ No pending migrations');

            return 0;
        }

        $this->info('Found '.count($pending).' pending migration(s)');
        $this->newLine();

        // ทุกตัวในรอบนี้อยู่ batch เดียวกัน (แบบ migrator ของ Laravel)
        $this->nextBatch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;

        foreach ($pending as $index => $migration) {
            try {
                $this->processMigration($migration);
            } catch (\Throwable $e) {
                // ตัวที่ล้ม "ห้าม" บันทึกว่ารันแล้ว — ต้องค้าง pending ให้ deploy รอบหน้า/fallback รันใหม่
                $this->error('  ✗ FAILED — not marked as ran: '.$e->getMessage());
                $this->failures[] = [
                    'migration' => $migration['name'],
                    'error' => get_class($e).': '.$e->getMessage(),
                ];
                $this->notAttempted = array_column(array_slice($pending, $index + 1), 'name');

                break;
            }

            // บันทึกทันทีหลังสำเร็จ — ถ้า process ตายกลางทาง ตัวที่รันไปแล้วจะไม่ถูกรันซ้ำ
            $this->markMigrationAsRan($migration);
        }

        // Show summary
        $this->showSummary();

        if (! empty($this->failures)) {
            $this->error('✗ Smart Migration FAILED — '.count($this->failures).' failed, '
                .count($this->notAttempted).' not attempted; they remain pending');

            return $this->failedInColumnGuess ? self::EXIT_COLUMN_GUESS_FAILED : self::EXIT_MIGRATION_FAILED;
        }

        return 0;
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return false;
        }
    }

    private function getPendingMigrations(string $directory): array
    {
        $migrations = [];
        $migrationFiles = glob(rtrim($directory, '/\\').'/*.php') ?: [];

        // ดึงรายชื่อที่รันแล้วครั้งเดียว แทนการยิง query ต่อไฟล์ (900+ ไฟล์)
        $ran = DB::table('migrations')->pluck('migration')->flip();

        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');

            if (! $ran->has($migrationName)) {
                $migrations[] = [
                    'name' => $migrationName,
                    'file' => $file,
                ];
            }
        }

        return $migrations;
    }

    /**
     * ประมวลผล migration หนึ่งตัว — ล้มเมื่อไหร่ต้อง throw (ห้ามกลืน error) ให้ handle() ไม่บันทึกตัวนี้
     */
    private function processMigration(array $migration): void
    {
        $this->line("→ Processing: {$migration['name']}");

        // Detect table name from migration filename
        $tableName = $this->extractTableName($migration['name']);

        if (! $tableName) {
            $this->warn('  ⚠ Could not detect table name, running normal migration');
            $this->runNormalMigration($migration);
            $this->info('  ✓ Migration ran successfully');

            return;
        }

        // Check if table exists
        if (Schema::hasTable($tableName)) {
            $this->handleExistingTable($migration, $tableName);
        } else {
            $this->handleNewTable($migration, $tableName);
        }
    }

    private function extractTableName(string $migrationName): ?string
    {
        // Pattern: create_TABLE_NAME_table
        if (preg_match('/create_(.+?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        // Pattern: add_COLUMN_to_TABLE_table
        if (preg_match('/add_.+?_to_(.+?)_table/', $migrationName, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function handleExistingTable(array $migration, string $tableName): void
    {
        $this->line("  → Table '{$tableName}' exists, checking schema...");

        // Get expected columns from migration
        $expectedColumns = $this->parseExpectedColumns($migration['file']);

        if (empty($expectedColumns)) {
            // เดิม "skipping" แล้วบันทึกว่ารันแล้วทั้งที่ไม่รู้ว่าทำอะไร (เช่น DB::statement ALTER) ⇒ รันจริงแทน
            $this->warn('  ⚠ Could not parse columns from migration, running normal migration');
            $this->runNormalMigration($migration);
            $this->info('  ✓ Migration ran successfully');

            return;
        }

        // Get existing columns
        $existingColumns = $this->getTableColumns($tableName);

        // Find missing columns
        $missingColumns = array_diff_key($expectedColumns, $existingColumns);

        if (empty($missingColumns)) {
            // ข้ามโดยตั้งใจ: ตาราง + คอลัมน์ที่ parse ได้มีครบ ⇒ บันทึกว่ารันแล้วโดยไม่เรียก up()
            // บอกให้ชัดใน log ว่าส่วนที่ไม่ใช่การเพิ่มคอลัมน์ (enum/index/ข้อมูล) ไม่ได้ถูกรัน
            $this->info("  ✓ Table '{$tableName}' and all ".count($expectedColumns)
                .' parsed column(s) already exist — marking as ran WITHOUT running up()'
                .' (enum/index/data changes in this migration are not applied)');
            $this->stats['tables_skipped']++;
            $this->stats['migrations_marked_without_up']++;

            return;
        }

        // Add missing columns
        $this->line('  → Adding '.count($missingColumns).' missing column(s)...');

        foreach ($missingColumns as $columnName => $columnDef) {
            // addColumn() throw เมื่อเพิ่มไม่ได้ ⇒ migration นี้ล้มทั้งตัว (คอลัมน์ที่เพิ่มไปแล้วรอบหน้าจะถูกข้าม)
            try {
                $this->addColumn($tableName, $columnName, $columnDef);
            } catch (\Throwable $e) {
                // ล้มในชนิดคอลัมน์ที่เราเดาเอง ไม่ใช่ใน up() ⇒ exit 2 ให้ deploy.sh ลองรัน up() จริงผ่าน migrate
                $this->failedInColumnGuess = true;

                throw $e;
            }
            $this->info("    ✓ Added: {$columnName}");
            $this->stats['columns_added']++;
        }

        // กิ่งนี้ก็ไม่ได้เรียก up() — คอลัมน์ถูกเพิ่มด้วยชนิดที่เดา บอกให้ชัดใน log เผื่อไล่ schema drift ทีหลัง
        $this->info('  ✓ Added missing column(s) with generic types — marking as ran WITHOUT running up()'
            .' (enum/index/data changes in this migration are not applied)');
        $this->stats['migrations_marked_without_up']++;
        $this->stats['tables_updated']++;
    }

    private function handleNewTable(array $migration, string $tableName): void
    {
        $this->line("  → Creating new table '{$tableName}'...");

        $this->runNormalMigration($migration);
        $this->info('  ✓ Table created successfully');
        $this->stats['tables_created']++;
    }

    private function parseExpectedColumns(string $migrationFile): array
    {
        $columns = [];
        $content = file_get_contents($migrationFile);

        // Common column patterns
        $patterns = [
            '/\$table->(\w+)\(\'(\w+)\'[^;]*\)/',  // $table->string('name')
            '/\$table->(\w+)\(\)/',                  // $table->timestamps()
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $type = $match[1];
                    $name = $match[2] ?? $this->inferColumnName($type);

                    if ($name) {
                        $columns[$name] = [
                            'type' => $type,
                            'definition' => $match[0],
                        ];
                    }
                }
            }
        }

        // Handle timestamps()
        if (stripos($content, '$table->timestamps()') !== false) {
            $columns['created_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'created_at\')->nullable()'];
            $columns['updated_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'updated_at\')->nullable()'];
        }

        // Handle softDeletes()
        if (stripos($content, '$table->softDeletes()') !== false) {
            $columns['deleted_at'] = ['type' => 'timestamp', 'definition' => '$table->timestamp(\'deleted_at\')->nullable()'];
        }

        // Handle id()
        if (stripos($content, '$table->id()') !== false) {
            $columns['id'] = ['type' => 'bigInteger', 'definition' => '$table->id()'];
        }

        return $columns;
    }

    private function inferColumnName(string $type): ?string
    {
        $mapping = [
            'timestamps' => null,  // Returns multiple columns
            'softDeletes' => 'deleted_at',
            'id' => 'id',
            'rememberToken' => 'remember_token',
        ];

        return $mapping[$type] ?? null;
    }

    private function getTableColumns(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $result = [];

        foreach ($columns as $column) {
            $result[$column] = true;
        }

        return $result;
    }

    /**
     * เพิ่มคอลัมน์ที่ขาด — throw เมื่อเพิ่มไม่ได้ (เดิม catch แล้วคืน false ⇒ migration ยังถูกบันทึก)
     */
    private function addColumn(string $tableName, string $columnName, array $columnDef): void
    {
        Schema::table($tableName, function ($table) use ($columnName, $columnDef) {
            // Determine column type and add accordingly
            $type = $columnDef['type'];

            switch ($type) {
                case 'string':
                    $table->string($columnName)->nullable();
                    break;
                case 'text':
                    $table->text($columnName)->nullable();
                    break;
                case 'integer':
                case 'int':
                    $table->integer($columnName)->nullable()->default(0);
                    break;
                case 'bigInteger':
                    $table->bigInteger($columnName)->nullable();
                    break;
                case 'decimal':
                    $table->decimal($columnName, 10, 2)->nullable()->default(0);
                    break;
                case 'boolean':
                    $table->boolean($columnName)->default(false);
                    break;
                case 'json':
                    $table->json($columnName)->nullable();
                    break;
                case 'timestamp':
                    $table->timestamp($columnName)->nullable();
                    break;
                case 'date':
                    $table->date($columnName)->nullable();
                    break;
                case 'foreignId':
                    $table->foreignId($columnName)->nullable();
                    break;
                default:
                    // Generic nullable column
                    $table->string($columnName)->nullable();
            }
        });
    }

    /**
     * รัน up() ของ migration — throw เมื่อล้ม (เดิม catch \Exception แล้วคืน false ซึ่งไม่มีใครเช็ค)
     */
    private function runNormalMigration(array $migration): void
    {
        // require ครั้งเดียวพอ (เดิม require_once แล้ว include ซ้ำ = รันไฟล์สองรอบ)
        $migrationClass = require $migration['file'];

        if (! is_object($migrationClass) || ! method_exists($migrationClass, 'up')) {
            throw new \RuntimeException("Migration file does not return an object with up(): {$migration['file']}");
        }

        $migrationClass->up();

        $this->stats['migrations_run']++;
    }

    private function markMigrationAsRan(array $migration): void
    {
        DB::table('migrations')->insert([
            'migration' => $migration['name'],
            'batch' => $this->nextBatch,
        ]);

        $this->marked[] = $migration['name'];
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('📊 Smart Migration Summary:');
        $this->line("  • Migrations run (up() executed): {$this->stats['migrations_run']}");
        $this->line("  • Marked without running up() (table existed): {$this->stats['migrations_marked_without_up']}");
        $this->line("  • Tables created: {$this->stats['tables_created']}");
        $this->line("  • Tables updated: {$this->stats['tables_updated']}");
        $this->line("  • Tables skipped: {$this->stats['tables_skipped']}");
        $this->line("  • Columns added: {$this->stats['columns_added']}");

        if (! empty($this->failures)) {
            $this->newLine();
            $this->error('✗ Failed migrations (NOT marked as ran — still pending):');
            foreach ($this->failures as $failure) {
                $this->line("  • {$failure['migration']}");
                $this->line("    {$failure['error']}");
            }
            // บรรทัดให้ deploy.sh อ่าน — ห้าม auto-register ตัวนี้ในกิ่ง "table already exists"
            foreach ($this->failures as $failure) {
                $this->line("SMART_MIGRATE_FAILED={$failure['migration']}");
            }
        }

        if (! empty($this->notAttempted)) {
            $this->newLine();
            $this->warn('⏸ Not attempted (stopped after the first failure — still pending):');
            foreach ($this->notAttempted as $name) {
                $this->line("  • {$name}");
            }
        }

        $this->newLine();
        $this->info('✓ Marked '.count($this->marked)." migration(s) as ran (batch: {$this->nextBatch})");
        $this->newLine();
    }
}
