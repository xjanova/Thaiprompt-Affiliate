<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🗑️ ลบระบบ Academy รุ่นเก่า (LMS เดิม: บทความ/หมวด/แบบทดสอบ/ใบประกาศ/ตั้งค่า) — สำรองเป็น JSON ก่อน DROP เสมอ
 *
 * เจ้าของสั่ง (2026-10-13): ลบ Academy เก่าทิ้งทั้งหมด แต่ต้องเก็บข้อมูลไว้ก่อน
 *   prod ณ วันสั่ง: learning_articles 53 · learning_categories 18 · quizzes 15 · quiz_questions 99
 *   ตารางอื่นของ Academy = 0 แถว (ห้องเรียนใหม่ใช้ตาราง classroom_* แยกต่างหาก ไม่เกี่ยวกับไฟล์นี้)
 *
 * ⚠️ ไม่แตะ training_courses / training_enrollments — เป็นของ HRM (admin.hrm.training.*) ไม่ใช่ Academy
 * ⚠️ ไม่แตะ food_certifications — ใบรับรอง Food Passport / NFT บน TPIX ใช้ตารางนั้น ไม่ใช่ certificates
 *
 * ลำดับการทำงาน (ทุกขั้นก่อน DROP ถ้าพัง = throw ทันที ยังไม่มีตารางไหนถูกลบ):
 *   1. เลือกเฉพาะตารางที่ยังมีอยู่ — ไม่มีแล้ว = ข้าม (รันซ้ำ = no-op)
 *   2. นับแถว → export ทุกแถวเป็น JSON ที่ backups/academy-legacy-<YmdHis>/<table>.json
 *      + manifest.json (จำนวนแถว + SHOW CREATE TABLE ไว้สร้างตารางคืนตอนกู้)
 *      backups/ รอด `git clean` ของ deploy.sh (-e 'backups/') และ cleanup_old_backups ลบแค่ *.sql* / critical_*
 *   3. อ่านไฟล์กลับมาตรวจ: จำนวนแถว + ชุด id ต้องตรงกับในตาราง
 *   4. นับแถวซ้ำอีกรอบก่อน DROP — มีแถวเพิ่ม/หายระหว่างทาง = throw
 *   5. ถอด FK จากตาราง "ที่เก็บไว้" ที่ชี้มายังตารางเหล่านี้ (กัน FK ค้างชี้ตารางที่ไม่มีแล้ว)
 *   6. DROP ตารางลูก → แม่
 *   7. ลบแถวเมนูใน menu_items ที่ชี้ไป route ของ Academy ที่ถูกลบ (สำรองไว้ในโฟลเดอร์เดียวกันก่อน)
 *
 * ตารางว่างทั้งหมด (เช่น CI ที่โหลด database/schema/mysql-schema.sql) = ไม่สร้างโฟลเดอร์สำรอง ลบตารางเลย
 *
 * down(): ไม่สร้างอะไรคืน — กู้ข้อมูล = สร้างตารางจาก create_sql ใน manifest.json แล้ว import JSON กลับ
 *         (หรือใช้ DB dump ที่ deploy.sh ทำไว้ก่อน migrate ใน backups/)
 */
return new class extends Migration
{
    /**
     * ตาราง Academy เดิม — เรียงลำดับ DROP จากลูกไปแม่ (FK ภายในชุดชี้จากบนลงล่าง)
     */
    private const TABLES = [
        'quiz_answers',
        'question_options',
        'quiz_attempts',
        'quiz_questions',
        'quizzes',
        'certificates',
        'user_article_progress',
        'article_permissions',
        'article_prerequisites',
        'learning_articles',
        'learning_categories',
        'academy_settings',
    ];

    /**
     * prefix ของชื่อ route ที่ถูกลบไปพร้อม Academy — แถว menu_items ที่ชี้ไปที่นี่ = เมนูตาย
     */
    private const MENU_ROUTE_PREFIXES = [
        'admin.academy.',
        'admin.learning-center.',
        'admin.quiz.',
        'admin.quiz-management.',
        'admin.certificates.',
        'admin.articles.',
        'admin.categories.',
        'admin.instructor.',
        'user.academy.',
        'certificate.',
    ];

    /**
     * menu_key ของกลุ่มเมนู Academy (นำเข้าจาก config/menus.php โดย MenuItemSeeder / AcademyMenuSeeder)
     */
    private const MENU_KEYS = ['admin.academy', 'admin.learning-center', 'user.academy'];

    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

    /**
     * สำรองข้อมูล Academy เดิมเป็น JSON แล้วลบตารางทิ้ง
     */
    public function up(): void
    {
        $tables = array_values(array_filter(self::TABLES, fn (string $table) => Schema::hasTable($table)));
        $menuRows = $this->legacyMenuRows();

        // ลบไปหมดแล้ว (รันซ้ำ) = ไม่มีอะไรต้องทำ
        if ($tables === [] && $menuRows->isEmpty()) {
            return;
        }

        // 1) นับแถวก่อน export — ใช้เทียบทั้งตอนตรวจไฟล์และตอนก่อน DROP
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        // 2-3) export + ตรวจไฟล์ (ข้ามเมื่อไม่มีข้อมูลสักแถว — ไม่มีอะไรให้สำรอง)
        if (array_sum($counts) > 0 || $menuRows->isNotEmpty()) {
            $directory = $this->makeBackupDirectory();
            $manifest = [
                'created_at' => now()->toIso8601String(),
                'reason' => 'drop legacy Academy (owner decision 2026-10-13)',
                'migration' => '2026_10_16_090000_drop_legacy_academy_tables',
                'restore' => 'สร้างตารางจาก tables.<name>.create_sql แล้ว import แถวจาก <name>.json (หรือใช้ DB dump ก่อน migrate)',
                'tables' => [],
                'menu_items' => null,
            ];

            foreach ($tables as $table) {
                $file = $directory.DIRECTORY_SEPARATOR.$table.'.json';
                $this->exportTable($table, $file);
                $this->verifyExport($table, $file, $counts[$table]);

                $manifest['tables'][$table] = [
                    'rows' => $counts[$table],
                    'file' => $table.'.json',
                    'create_sql' => $this->showCreateTable($table),
                ];
            }

            if ($menuRows->isNotEmpty()) {
                $file = $directory.DIRECTORY_SEPARATOR.'menu_items.academy-rows.json';
                $this->writeJsonFile($file, $menuRows->map(fn ($row) => (array) $row)->values()->all());
                $this->verifyRowsFile($file, $menuRows->pluck('id')->map(fn ($id) => (int) $id)->all(), 'menu_items');

                $manifest['menu_items'] = [
                    'rows' => $menuRows->count(),
                    'file' => 'menu_items.academy-rows.json',
                    'note' => 'แถวเมนูที่ชี้ไป route ของ Academy เดิม — ลบออกจาก menu_items แล้ว',
                ];
            }

            $this->writeJsonFile($directory.DIRECTORY_SEPARATOR.'manifest.json', $manifest, JSON_PRETTY_PRINT);

            Log::info('Legacy Academy: สำรองข้อมูลก่อนลบตารางแล้ว', [
                'directory' => $directory,
                'rows' => $counts,
                'menu_items' => $menuRows->count(),
            ]);
        }

        // 4) นับซ้ำก่อน DROP — มีคนเขียน/ลบแถวระหว่าง export = ห้ามลบ (ไฟล์สำรองไม่ครบ)
        foreach ($tables as $table) {
            $now = DB::table($table)->count();
            if ($now !== $counts[$table]) {
                throw new RuntimeException(
                    "Legacy Academy: ตาราง {$table} เปลี่ยนระหว่างสำรอง ({$counts[$table]} → {$now} แถว) — ยกเลิก ไม่ลบตาราง"
                );
            }
        }

        // 5) ถอด FK จากตารางที่เก็บไว้ที่ชี้มายังตาราง Academy
        $this->dropForeignKeysFromKeptTables($tables);

        // 6) DROP ลูก → แม่ (ปิดเช็ค FK กันกรณี prod มี FK ภายในชุดที่ลำดับต่างจาก schema dump)
        Schema::withoutForeignKeyConstraints(function () use ($tables) {
            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        });

        // 7) ลบแถวเมนูตาย (สำรองไว้แล้วข้างบน) — ลูกก่อนแม่
        if ($menuRows->isNotEmpty()) {
            $ids = $menuRows->pluck('id')->all();
            DB::table('menu_items')->whereIn('id', $ids)->whereNotNull('parent_id')->delete();
            DB::table('menu_items')->whereIn('id', $ids)->delete();
        }

        Log::info('Legacy Academy: ลบตารางแล้ว', ['tables' => $tables, 'menu_items_deleted' => $menuRows->count()]);
    }

    /**
     * ไม่สร้างตารางคืน — กู้จาก backups/academy-legacy-<YmdHis>/ (manifest.json + <table>.json) หรือ DB dump
     */
    public function down(): void
    {
        // ตั้งใจไม่ทำอะไร: Academy เดิมถูกถอดโค้ดออกทั้งหมดแล้ว สร้างตารางคืนก็ไม่มีโค้ดใช้
    }

    /**
     * แถว menu_items ที่เป็นของ Academy เดิม (กลุ่มเมนู + เมนูย่อย + เมนูที่ชี้ route ที่ถูกลบ)
     */
    private function legacyMenuRows(): Collection
    {
        if (! Schema::hasTable('menu_items')
            || ! Schema::hasColumn('menu_items', 'menu_key')
            || ! Schema::hasColumn('menu_items', 'route')) {
            return collect();
        }

        $rows = DB::table('menu_items')
            ->where(function ($query) {
                $query->whereIn('menu_key', self::MENU_KEYS);

                foreach (self::MENU_KEYS as $key) {
                    $query->orWhere('menu_key', 'like', $key.'.%');
                }

                foreach (self::MENU_ROUTE_PREFIXES as $prefix) {
                    $query->orWhere('route', 'like', $prefix.'%');
                }
            })
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // รวมเมนูลูกทุกชั้นของแถวที่จะลบ — parent_id เป็น FK ON DELETE CASCADE
        // ถ้าไม่ดึงมาด้วย ลูกที่แอดมินเพิ่มเอง (menu_key/route อื่น) จะหายตามแม่โดยไม่มีไฟล์สำรอง
        if (Schema::hasColumn('menu_items', 'parent_id')) {
            $parentIds = $rows->keys()->all();
            while ($parentIds !== []) {
                $children = DB::table('menu_items')
                    ->whereIn('parent_id', $parentIds)
                    ->whereNotIn('id', $rows->keys()->all())
                    ->get();

                foreach ($children as $child) {
                    $rows->put($child->id, $child);
                }
                $parentIds = $children->pluck('id')->all();
            }
        }

        return $rows->sortKeys()->values();
    }

    /**
     * สร้างโฟลเดอร์สำรองใหม่ทุกครั้ง (ไม่เขียนทับของรอบก่อน)
     */
    private function makeBackupDirectory(): string
    {
        $base = base_path('backups'.DIRECTORY_SEPARATOR.'academy-legacy-'.now()->format('YmdHis'));
        $directory = $base;
        $suffix = 1;

        while (File::exists($directory)) {
            $directory = $base.'-'.$suffix++;
        }

        File::ensureDirectoryExists($directory, 0750);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException("Legacy Academy: สร้างโฟลเดอร์สำรองไม่ได้ ({$directory}) — ยกเลิก ไม่ลบตาราง");
        }

        return $directory;
    }

    /**
     * เขียนทุกแถวของตารางเป็น JSON array ทีละแถว (ไม่โหลดทั้งตารางเข้าหน่วยความจำ)
     */
    private function exportTable(string $table, string $file): void
    {
        $handle = fopen($file, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Legacy Academy: เปิดไฟล์ {$file} ไม่ได้ — ยกเลิก ไม่ลบตาราง");
        }

        try {
            $this->write($handle, '[', $file);

            // lazyById เรียงตาม id ให้เอง (ห้ามใส่ orderBy ซ้อน)
            $rows = Schema::hasColumn($table, 'id')
                ? DB::table($table)->lazyById(500, 'id')
                : DB::table($table)->cursor();

            $first = true;
            foreach ($rows as $row) {
                $this->write($handle, ($first ? "\n" : ",\n").json_encode((array) $row, self::JSON_FLAGS), $file);
                $first = false;
            }

            $this->write($handle, "\n]\n", $file);
        } finally {
            if (fclose($handle) === false) {
                throw new RuntimeException("Legacy Academy: ปิดไฟล์ {$file} ไม่สำเร็จ — ยกเลิก ไม่ลบตาราง");
            }
        }
    }

    /**
     * ตรวจไฟล์ที่ export: จำนวนแถวต้องเท่ากับในตาราง และชุด id ต้องตรงกันทุกตัว
     */
    private function verifyExport(string $table, string $file, int $expectedRows): void
    {
        $rows = $this->readRowsFile($file, $table);

        if (count($rows) !== $expectedRows) {
            throw new RuntimeException(
                "Legacy Academy: ไฟล์ {$table}.json มี ".count($rows)." แถว แต่ตารางมี {$expectedRows} แถว — ยกเลิก ไม่ลบตาราง"
            );
        }

        if (Schema::hasColumn($table, 'id')) {
            $dbIds = DB::table($table)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->verifyRowsFile($file, $dbIds, $table, $rows);
        }
    }

    /**
     * ตรวจว่าไฟล์มีชุด id ตรงกับที่คาดไว้ทุกตัว
     *
     * @param  array<int, int>  $expectedIds
     * @param  array<int, mixed>|null  $rows  แถวที่อ่านไว้แล้ว (ไม่ต้องอ่านไฟล์ซ้ำ)
     */
    private function verifyRowsFile(string $file, array $expectedIds, string $label, ?array $rows = null): void
    {
        $rows ??= $this->readRowsFile($file, $label);

        $fileIds = array_map(fn ($row) => (int) ($row['id'] ?? 0), $rows);
        sort($fileIds);
        sort($expectedIds);

        if ($fileIds !== $expectedIds) {
            throw new RuntimeException("Legacy Academy: id ในไฟล์สำรองของ {$label} ไม่ตรงกับฐานข้อมูล — ยกเลิก ไม่ลบตาราง");
        }
    }

    /**
     * อ่านไฟล์ JSON กลับมาเป็น array ของแถว (พัง/ไม่ใช่ array = throw)
     *
     * @return array<int, mixed>
     */
    private function readRowsFile(string $file, string $label): array
    {
        $raw = @file_get_contents($file);
        if ($raw === false) {
            throw new RuntimeException("Legacy Academy: อ่านไฟล์สำรองของ {$label} กลับไม่ได้ — ยกเลิก ไม่ลบตาราง");
        }

        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new RuntimeException("Legacy Academy: ไฟล์สำรองของ {$label} ไม่ใช่ JSON array — ยกเลิก ไม่ลบตาราง");
        }

        return $rows;
    }

    /**
     * เขียนข้อมูลทั้งก้อนเป็นไฟล์ JSON (ใช้กับ manifest และแถวเมนูซึ่งมีไม่กี่แถว)
     */
    private function writeJsonFile(string $file, array $data, int $extraFlags = 0): void
    {
        $json = json_encode($data, self::JSON_FLAGS | $extraFlags)."\n";

        if (file_put_contents($file, $json) !== strlen($json)) {
            throw new RuntimeException("Legacy Academy: เขียนไฟล์ {$file} ไม่ครบ — ยกเลิก ไม่ลบตาราง");
        }
    }

    /**
     * fwrite ที่ throw เมื่อเขียนไม่ครบ (ดิสก์เต็ม ฯลฯ)
     *
     * @param  resource  $handle
     */
    private function write($handle, string $chunk, string $file): void
    {
        if (fwrite($handle, $chunk) !== strlen($chunk)) {
            throw new RuntimeException("Legacy Academy: เขียนไฟล์ {$file} ไม่ครบ (ดิสก์เต็ม?) — ยกเลิก ไม่ลบตาราง");
        }
    }

    /**
     * โครงสร้างตาราง (CREATE TABLE) เก็บไว้ใน manifest สำหรับสร้างคืนตอนกู้
     */
    private function showCreateTable(string $table): ?string
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");

        return $row['Create Table'] ?? null;
    }

    /**
     * ถอด FK ที่ตาราง "ที่ไม่ได้ลบ" ใช้ชี้มายังตาราง Academy — ไม่งั้นจะเหลือ FK ค้างชี้ตารางที่หายไป
     *
     * @param  array<int, string>  $tables
     */
    private function dropForeignKeysFromKeptTables(array $tables): void
    {
        if ($tables === [] || ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($tables), '?'));

        $foreignKeys = DB::select(
            "SELECT DISTINCT TABLE_NAME AS table_name, CONSTRAINT_NAME AS constraint_name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME IN ({$placeholders})
               AND TABLE_NAME NOT IN ({$placeholders})",
            [...$tables, ...$tables]
        );

        foreach ($foreignKeys as $foreignKey) {
            DB::statement("ALTER TABLE `{$foreignKey->table_name}` DROP FOREIGN KEY `{$foreignKey->constraint_name}`");

            Log::info('Legacy Academy: ถอด FK จากตารางที่เก็บไว้', [
                'table' => $foreignKey->table_name,
                'foreign_key' => $foreignKey->constraint_name,
            ]);
        }
    }
};
