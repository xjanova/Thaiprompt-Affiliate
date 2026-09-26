<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * รัน migration ที่ค้างทีละตัวด้วย up() "ของจริง" เสมอ (deploy.sh เรียกเมื่อมี migration ค้าง)
 *
 * 🚨 ประวัติ (2026-09-26) — ทำไมคำสั่งนี้ถึงไม่ "ฉลาด" อีกต่อไป:
 *   1. เดิม markMigrationsAsRan() ใส่ migration "ทุกตัว" ลงตาราง migrations แม้ตัวนั้น throw + exit 0
 *      (2025_12_21_000001_fix_mobile_banner_images → mobile_banners.position ค้าง enum เก่า 9 เดือน)
 *      ⇒ แก้แล้ว: บันทึกทีละตัวหลังสำเร็จจริง · หยุดที่ตัวแรกที่ล้ม · exit ≠ 0
 *   2. เดิม migration ชื่อ create_X_table / add_..._to_X_table ที่ตาราง X มีอยู่แล้ว "ไม่เคยถูกรัน up()"
 *      คำสั่งนี้ regex หา `$table->type('col')` ทั้งไฟล์ (รวม down()) แล้วเพิ่มคอลัมน์ที่ขาดด้วยชนิดที่เดาเอง
 *      (string/int nullable) หรือบันทึกว่ารันแล้วเฉย ๆ ถ้าคอลัมน์ครบ ⇒ schema prod drift ทั้งระบบ:
 *      fortune_readings.is_floating เป็น varchar NULL (ตัวกรองบิลชำระแล้วคืน 0 แถว), boolean/int/bigint
 *      กลายเป็น varchar, unique/index/FK หาย, คอลัมน์ขยะชื่อตามอาร์กิวเมนต์ของ dropColumn()/dropIndex()
 *      ⇒ ลบทางเดาทิ้งทั้งหมด: ตารางมีอยู่หรือไม่ก็รัน up() จริง — migration ของโปรเจกต์ต้อง idempotent
 *      (hasTable สำหรับ CREATE, hasColumn/SafeMigration สำหรับ ALTER — ดู CLAUDE.md)
 *
 * 🛡️ กันบันทึก migration ที่ทำไปครึ่งเดียว:
 *   MySQL/MariaDB ไม่มี transaction ครอบ DDL — ตัวที่ล้มกลางทางอาจสร้างตารางไปแล้ว (แต่ FK/index/ข้อมูลยังไม่มี)
 *   deploy รอบถัดไปรัน up() ซ้ำ ถ้ามี guard ทั้งก้อน (hasTable → return) มันจะ no-op แล้วถูกบันทึกว่ารันแล้ว
 *   ⇒ ตัวที่ล้ม "หลังเปลี่ยนฐานข้อมูลไปแล้วบางส่วน" ถูกจำไว้ในตาราง smart_migrate_failures และถ้ารอบซ้ำของมัน
 *   "ไม่ได้เปลี่ยนอะไรเลย" (นับเฉพาะคำสั่งที่ไม่ใช่ SELECT/SHOW/...) คำสั่งนี้จะไม่บันทึก + exit 1 ให้คนตรวจของที่ค้าง
 *
 * exit code (deploy.sh ใช้ตัดสินว่าจะ fallback ไป `migrate --force` หรือไม่):
 *   0 = สำเร็จทั้งหมด
 *   1 = migration ล้มใน up() / รอบซ้ำถูกปฏิเสธ / พังก่อนเริ่ม ⇒ ห้าม fallback: `migrate --force` จะรัน up() ตัวเดิมซ้ำ
 *       แล้วถ้า guard ทำให้ no-op มันจะถูกบันทึกว่ารันแล้ว = บั๊กเดิมกลับมา
 *   2 = (สงวนไว้ — ไม่มีทางไหนคืนค่านี้แล้วตั้งแต่ลบการเดาคอลัมน์ 2026-09-26)
 *       deploy.sh ยังตีความ 2 ว่า "up() ยังไม่เคยรัน fallback ไป migrate --force ได้"
 */
class SmartMigrate extends Command
{
    private const EXIT_MIGRATION_FAILED = 1;

    /** ตารางจำ migration ที่ล้มข้าม deploy — 2026_09_26_180000_create_smart_migrate_failures_table */
    private const FAILURES_TABLE = 'smart_migrate_failures';

    /** คำแรกของคำสั่ง SQL ที่ไม่เปลี่ยนฐานข้อมูล (guard อย่าง hasTable/hasColumn = SELECT จาก information_schema) */
    private const READ_ONLY_STATEMENTS = ['select', 'show', 'describe', 'desc', 'explain', 'set', 'use'];

    protected $signature = 'migrate:smart
        {--force : Force the operation to run in production}
        {--path= : Absolute path of the migrations directory (default: database/migrations)}';

    protected $description = 'Run pending migrations one by one with their real up(); never record a failed or half-applied one';

    private $stats = [
        'migrations_run' => 0,
        'migrations_noop' => 0,
        'tables_created' => 0,
    ];

    /** @var array<int, array{migration: string, error: string}> migration ที่ล้ม (ไม่ถูกบันทึก) */
    private array $failures = [];

    /** @var array<int, string> migration ที่ไม่ได้ลองรันเพราะหยุดหลังตัวที่ล้ม (ยังค้าง pending) */
    private array $notAttempted = [];

    /** @var array<int, string> migration ที่บันทึกลงตาราง migrations แล้วในรอบนี้ */
    private array $marked = [];

    private int $nextBatch = 1;

    /** จำนวนคำสั่ง SQL ที่เปลี่ยนฐานข้อมูลระหว่าง up() ตัวปัจจุบัน (null = ไม่ได้อยู่ใน up()) */
    private ?int $changeStatements = null;

    /** จำนวนคำสั่งที่เปลี่ยนฐานข้อมูลสำเร็จไปแล้วก่อน up() ตัวล่าสุดจะ throw (> 0 = ค้างครึ่งทาง) */
    private int $changesBeforeFailure = 0;

    private bool $failuresTableExists = false;

    public function handle()
    {
        $this->info('🎯 Smart Migration System');
        $this->newLine();

        // Check database connection
        if (! $this->checkDatabaseConnection()) {
            $this->error('✗ Database connection failed');

            return self::EXIT_MIGRATION_FAILED;
        }
        $this->info('✓ Database connection OK');
        $this->newLine();

        $directory = $this->option('path') ?: database_path('migrations');

        if (! is_dir($directory)) {
            $this->error("✗ Migrations directory not found: {$directory}");

            return self::EXIT_MIGRATION_FAILED;
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

        // นับคำสั่งที่เปลี่ยนฐานข้อมูลระหว่าง up() — แยก "up() ทำงานจริง" ออกจาก "guard ข้ามทั้งก้อน"
        DB::listen(function (QueryExecuted $query) {
            if ($this->changeStatements !== null && ! $this->isReadOnlyStatement($query->sql)) {
                $this->changeStatements++;
            }
        });

        foreach ($pending as $index => $migration) {
            try {
                $this->processMigration($migration);
            } catch (\Throwable $e) {
                // ตัวที่ล้ม "ห้าม" บันทึกว่ารันแล้ว — ต้องค้าง pending ให้ deploy รอบหน้ารันใหม่
                $this->error('  ✗ FAILED — not marked as ran: '.$e->getMessage());
                $this->failures[] = [
                    'migration' => $migration['name'],
                    'error' => get_class($e).': '.$e->getMessage(),
                ];
                $this->rememberFailure($migration['name'], $e);
                $this->notAttempted = array_column(array_slice($pending, $index + 1), 'name');

                break;
            }

            // บันทึกทันทีหลังสำเร็จ — ถ้า process ตายกลางทาง ตัวที่รันไปแล้วจะไม่ถูกรันซ้ำ
            $this->markMigrationAsRan($migration);
            $this->forgetFailure($migration['name']);
        }

        // Show summary
        $this->showSummary();

        if (! empty($this->failures)) {
            $this->error('✗ Smart Migration FAILED — '.count($this->failures).' failed, '
                .count($this->notAttempted).' not attempted; they remain pending');

            return self::EXIT_MIGRATION_FAILED;
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
        $this->changesBeforeFailure = 0;

        $previousFailure = $this->previousFailure($migration['name']);

        if ($previousFailure) {
            $this->warn("  ⚠ Failed on an earlier run ({$previousFailure->attempts}x since {$previousFailure->created_at}):"
                ." {$previousFailure->error}");
        }

        // ชื่อ create_X_table / add_..._to_X_table ใช้ประกอบ log เท่านั้น — ไม่ได้ใช้ตัดสินว่าจะรัน up() หรือไม่
        $tableName = $this->extractTableName($migration['name']);
        $tableExisted = $tableName !== null && Schema::hasTable($tableName);

        if ($tableExisted) {
            $this->line("  → Table '{$tableName}' already exists — running the real up() anyway (migrations must be idempotent)");
        }

        $changes = $this->runMigrationUp($migration);

        if ($changes > 0) {
            if ($tableName !== null && ! $tableExisted && Schema::hasTable($tableName)) {
                $this->stats['tables_created']++;
            }
            $this->info("  ✓ up() ran ({$changes} change statement(s))");

            return;
        }

        if ($previousFailure) {
            // รอบก่อนล้มกลางทาง รอบนี้ up() ไม่แตะอะไรเลย = guard น่าจะข้ามของที่ทำไปครึ่งเดียว ⇒ ห้ามบันทึก
            throw new \RuntimeException('Refusing to record: this migration failed on an earlier run and this retry\'s'
                .' up() changed nothing — a guard such as hasTable() → return most likely skipped a half-applied'
                .' migration. Check what the failed run left behind (tables/columns/FKs/indexes/data), then finish'
                .' it by hand and INSERT it into `migrations`, or undo the partial changes, or make up() complete'
                .' the missing parts. To accept the current schema as is, DELETE its row from `'.self::FAILURES_TABLE.'`.');
        }

        $this->stats['migrations_noop']++;
        $this->info('  ✓ up() ran — no changes (already applied, or skipped by its own guard)');
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

    /**
     * รัน up() ของ migration แล้วคืนจำนวนคำสั่งที่เปลี่ยนฐานข้อมูล — throw เมื่อล้ม
     */
    private function runMigrationUp(array $migration): int
    {
        // require ครั้งเดียวพอ (เดิม require_once แล้ว include ซ้ำ = รันไฟล์สองรอบ)
        $migrationClass = require $migration['file'];

        if (! is_object($migrationClass) || ! method_exists($migrationClass, 'up')) {
            throw new \RuntimeException("Migration file does not return an object with up(): {$migration['file']}");
        }

        $transactionLevel = DB::transactionLevel();
        $this->changeStatements = 0;

        try {
            $migrationClass->up();
        } catch (\Throwable $e) {
            // QueryExecuted ยิงเฉพาะคำสั่งที่สำเร็จ ⇒ ค่านี้ = ของที่ถูกเปลี่ยนไปแล้วก่อนล้ม (DDL ย้อนไม่ได้)
            $this->changesBeforeFailure = $this->changeStatements;

            // up() ที่เปิด transaction เองแล้ว throw — ปิดให้ ไม่งั้นการจดความล้มเหลวจะค้างใน transaction ที่ไม่มีวัน commit
            try {
                DB::rollBack($transactionLevel);
            } catch (\Throwable) {
                // ไม่บัง exception ตัวจริง
            }

            throw $e;
        } finally {
            $changes = $this->changeStatements;
            $this->changeStatements = null;
        }

        $this->stats['migrations_run']++;

        return $changes;
    }

    private function isReadOnlyStatement(string $sql): bool
    {
        if (! preg_match('/^[\s(]*([a-z]+)/i', $sql, $matches)) {
            return false;
        }

        return in_array(strtolower($matches[1]), self::READ_ONLY_STATEMENTS, true);
    }

    private function markMigrationAsRan(array $migration): void
    {
        DB::table('migrations')->insert([
            'migration' => $migration['name'],
            'batch' => $this->nextBatch,
        ]);

        $this->marked[] = $migration['name'];
    }

    /**
     * ตารางจำความล้มเหลวมีหรือยัง (deploy แรกหลังเพิ่มตารางนี้ มันอาจถูกสร้างกลางรอบ — เช็คใหม่จนกว่าจะเจอ)
     */
    private function failuresTableExists(): bool
    {
        return $this->failuresTableExists = $this->failuresTableExists || Schema::hasTable(self::FAILURES_TABLE);
    }

    private function previousFailure(string $migration): ?object
    {
        if (! $this->failuresTableExists()) {
            return null;
        }

        return DB::table(self::FAILURES_TABLE)->where('migration', $migration)->first();
    }

    /**
     * จำความล้มเหลวข้าม deploy — เฉพาะตัวที่ล้มหลังเปลี่ยนฐานข้อมูลไปแล้วบางส่วน (ตัวที่ล้มตั้งแต่ยังไม่แตะอะไร
     * รันซ้ำได้ตามปกติ) · ครั้งแรกเก็บ error ไว้ ครั้งถัดไปเพิ่มแค่ attempts (error ตัวแรกคือต้นเหตุ)
     */
    private function rememberFailure(string $migration, \Throwable $e): void
    {
        try {
            if (! $this->failuresTableExists()) {
                if ($this->changesBeforeFailure > 0) {
                    $this->warn('  ⚠ Half-applied, but '.self::FAILURES_TABLE.' does not exist yet — not remembered across deploys');
                }

                return;
            }

            $existing = DB::table(self::FAILURES_TABLE)->where('migration', $migration)->first();

            if ($existing) {
                DB::table(self::FAILURES_TABLE)->where('id', $existing->id)->update([
                    'attempts' => $existing->attempts + 1,
                    'updated_at' => now(),
                ]);

                return;
            }

            if ($this->changesBeforeFailure === 0) {
                $this->line('  → Failed before changing anything — nothing half-applied, a retry is safe');

                return;
            }

            $this->warn("  ⚠ Failed after {$this->changesBeforeFailure} change statement(s) — remembered in "
                .self::FAILURES_TABLE.'; a retry that changes nothing will not be recorded');

            DB::table(self::FAILURES_TABLE)->insert([
                'migration' => $migration,
                'error' => Str::limit("[after {$this->changesBeforeFailure} change statement(s)] "
                    .get_class($e).': '.$e->getMessage(), 5000),
                'attempts' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $inner) {
            // ห้ามบังความล้มเหลวตัวจริง — เตือนแล้วไปต่อ (exit 1 อยู่แล้ว)
            $this->warn('  ⚠ Could not remember the failure: '.$inner->getMessage());
        }
    }

    private function forgetFailure(string $migration): void
    {
        try {
            if ($this->failuresTableExists()) {
                DB::table(self::FAILURES_TABLE)->where('migration', $migration)->delete();
            }
        } catch (\Throwable $e) {
            // migration ถูกบันทึกไปแล้ว — แถวค้างไม่อันตราย (เช็คเฉพาะตัวที่ยัง pending) แค่เตือน
            $this->warn('  ⚠ Could not clear the remembered failure: '.$e->getMessage());
        }
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('📊 Smart Migration Summary:');
        $this->line("  • Migrations run (real up()): {$this->stats['migrations_run']}");
        $this->line("  • ...of which changed nothing (already applied / own guard): {$this->stats['migrations_noop']}");
        $this->line("  • Tables created: {$this->stats['tables_created']}");

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
