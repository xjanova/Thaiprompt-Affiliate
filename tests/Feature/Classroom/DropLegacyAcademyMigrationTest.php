<?php

namespace Tests\Feature\Classroom;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 🗑️ migration ลบ Academy เดิม (2026_10_16_100200) — สำรอง JSON ก่อน DROP เสมอ · ต้องใช้ MySQL
 *
 * สร้างตาราง Academy คืนจาก CREATE TABLE ใน database/schema/mysql-schema.sql (โครงเดียวกับ prod/CI)
 * ใส่แถวปลอม + แถวเมนูตาย + ตาราง "ที่เก็บไว้" ที่มี FK ชี้เข้า Academy → รัน up()
 *   → ไฟล์ JSON ครบ จำนวนแถว/ชุด id ตรง · manifest มี create_sql · ตารางหาย · FK ค้างถูกถอด · เมนูตายถูกลบ
 *   → รันซ้ำ = ไม่ทำอะไร (ไม่สร้างโฟลเดอร์ใหม่)
 *
 * ⚠️ CREATE/DROP TABLE ใน MySQL commit transaction ของ RefreshDatabase ทันที — เทสต์นี้จึงเก็บกวาดเองทุกอย่าง
 *    (ตารางถูก migration ลบเอง · แถวเมนูถูกลบเอง · ตารางทดสอบ/โฟลเดอร์สำรองลบใน tearDown)
 */
#[Group('classroom')]
class DropLegacyAcademyMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** ลำดับสร้าง: แม่ → ลูก (กลับด้านกับลำดับ DROP ใน migration) */
    private const TABLES = [
        'academy_settings',
        'learning_categories',
        'learning_articles',
        'article_prerequisites',
        'article_permissions',
        'user_article_progress',
        'certificates',
        'quizzes',
        'quiz_questions',
        'quiz_attempts',
        'question_options',
        'quiz_answers',
    ];

    private const KEPT_TABLE = 'zz_classroom_kept_ref';

    /** @var array<int, string> */
    private array $backupDirsBefore = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirsBefore = $this->backupDirs();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::KEPT_TABLE);
        Schema::withoutForeignKeyConstraints(function () {
            foreach (array_reverse(self::TABLES) as $table) {
                Schema::dropIfExists($table);
            }
        });

        // ลบเฉพาะโฟลเดอร์สำรองที่เทสต์นี้สร้าง
        foreach (array_diff($this->backupDirs(), $this->backupDirsBefore) as $dir) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    public function test_exports_every_legacy_row_to_json_then_drops_tables_and_rerun_is_noop(): void
    {
        $this->createLegacyTablesFromSchemaDump();
        $userId = (int) DB::table('users')->min('id') ?: 1;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $expected = $this->insertFakeRows($userId);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ตารางที่เก็บไว้แต่มี FK ชี้เข้า Academy → ต้องถูกถอด FK ไม่ใช่ถูกลบ
        DB::statement('CREATE TABLE `'.self::KEPT_TABLE.'` (`id` bigint unsigned NOT NULL AUTO_INCREMENT, `category_id` bigint unsigned NULL, PRIMARY KEY (`id`), CONSTRAINT `zz_kept_cat_fk` FOREIGN KEY (`category_id`) REFERENCES `learning_categories` (`id`)) ENGINE=InnoDB');
        DB::table(self::KEPT_TABLE)->insert(['category_id' => 1]);

        $menuIds = $this->insertLegacyMenuRows();
        $keptMenuId = DB::table('menu_items')->insertGetId($this->menuRow('admin.classroom', 'admin.classroom.index', null));

        $migration = require database_path('migrations/2026_10_16_100200_drop_legacy_academy_tables.php');
        $migration->up();

        // 1) โฟลเดอร์สำรองใหม่ 1 โฟลเดอร์
        $created = array_values(array_diff($this->backupDirs(), $this->backupDirsBefore));
        $this->assertCount(1, $created);
        $dir = $created[0];

        // 2) JSON ทุกตารางที่มีข้อมูล จำนวนแถว + id ตรง · manifest ครบ
        $manifest = json_decode(File::get($dir.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (self::TABLES as $table) {
            $this->assertFileExists($dir.'/'.$table.'.json');
            $rows = json_decode(File::get($dir.'/'.$table.'.json'), true, 512, JSON_THROW_ON_ERROR);
            $this->assertCount($expected[$table], $rows, $table);
            $this->assertSame($expected[$table], $manifest['tables'][$table]['rows'], $table);
            $this->assertStringContainsString('CREATE TABLE `'.$table.'`', (string) $manifest['tables'][$table]['create_sql']);
            $this->assertFalse(Schema::hasTable($table), $table.' ต้องถูกลบ');
        }
        $articles = json_decode(File::get($dir.'/learning_articles.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('บทความภาษาไทย 1', $articles[0]['title'], 'ภาษาไทยต้องไม่เพี้ยนในไฟล์สำรอง');

        // 3) เมนูตายถูกสำรอง + ลบ · เมนูอื่นไม่แตะ
        $menuBackup = json_decode(File::get($dir.'/menu_items.academy-rows.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEqualsCanonicalizing($menuIds, array_map(fn ($r) => (int) $r['id'], $menuBackup));
        $this->assertSame(0, DB::table('menu_items')->whereIn('id', $menuIds)->count());
        $this->assertTrue(DB::table('menu_items')->where('id', $keptMenuId)->exists());
        DB::table('menu_items')->where('id', $keptMenuId)->delete();

        // 4) ตารางที่เก็บไว้ยังอยู่ ข้อมูลอยู่ครบ แต่ไม่มี FK ค้างชี้ตารางที่หายไป
        $this->assertTrue(Schema::hasTable(self::KEPT_TABLE));
        $this->assertSame(1, DB::table(self::KEPT_TABLE)->count());
        $this->assertSame(0, (int) DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [self::KEPT_TABLE]
        )->c);

        // 5) รันซ้ำ = no-op
        $migration->up();
        $this->assertCount(1, array_diff($this->backupDirs(), $this->backupDirsBefore), 'รันซ้ำต้องไม่สร้างโฟลเดอร์สำรองใหม่');
    }

    public function test_empty_legacy_tables_are_dropped_without_backup_folder(): void
    {
        $this->createLegacyTablesFromSchemaDump();

        $migration = require database_path('migrations/2026_10_16_100200_drop_legacy_academy_tables.php');
        $migration->up();

        foreach (self::TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }
        $this->assertSame([], array_values(array_diff($this->backupDirs(), $this->backupDirsBefore)));
    }

    /**
     * สร้างตาราง Academy จาก CREATE TABLE ใน schema dump (ที่ CI ใช้)
     */
    private function createLegacyTablesFromSchemaDump(): void
    {
        $dump = File::get(database_path('schema/mysql-schema.sql'));

        Schema::withoutForeignKeyConstraints(function () use ($dump) {
            foreach (self::TABLES as $table) {
                // ตัดด้วย strpos — regex /s ข้ามไฟล์ dump ใหญ่บน CI ชนขีดจำกัด PCRE แล้วได้ 0 (หาไม่เจอ)
                $start = strpos($dump, 'CREATE TABLE `'.$table.'` (');
                $this->assertNotFalse($start, $table.' ต้องอยู่ใน schema dump');
                $end = strpos($dump, ';', strpos($dump, ') ENGINE=', $start));
                $this->assertNotFalse($end, $table.' ต้องมีจุดจบคำสั่ง');
                Schema::dropIfExists($table);
                DB::unprepared(substr($dump, $start, $end - $start + 1));
            }
        });
    }

    /**
     * ใส่แถวปลอมทุกตาราง (กรอกเฉพาะคอลัมน์ที่ต้องมีค่า ที่เหลือใช้ค่าเริ่มต้นของตาราง)
     *
     * @return array<string, int> จำนวนแถวที่ใส่ต่อตาราง
     */
    private function insertFakeRows(int $userId): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $n = $table === 'learning_articles' ? 3 : 2;
            for ($i = 1; $i <= $n; $i++) {
                $row = $this->requiredValues($table, $i, $userId);
                if ($table === 'learning_articles' && Schema::hasColumn($table, 'title')) {
                    $row['title'] = 'บทความภาษาไทย '.$i;
                }
                // ทุกคอลัมน์มีค่าเริ่มต้น (เช่น academy_settings) → insert([]) ของ Laravel ไม่ยิงอะไรเลย
                $row === [] ? DB::insert('INSERT INTO `'.$table.'` () VALUES ()') : DB::table($table)->insert($row);
            }
            $counts[$table] = $n;
        }

        return $counts;
    }

    /**
     * ค่าให้ทุกคอลัมน์ NOT NULL ที่ไม่มีค่าเริ่มต้น (ตามชนิดคอลัมน์)
     *
     * @return array<string, mixed>
     */
    private function requiredValues(string $table, int $i, int $userId): array
    {
        $columns = DB::select(
            'SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_TYPE AS ctype, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS def, EXTRA AS extra
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table]
        );

        $row = [];
        foreach ($columns as $col) {
            if (str_contains((string) $col->extra, 'auto_increment')) {
                continue;
            }
            if ($col->nullable === 'YES' || $col->def !== null) {
                continue;
            }

            $row[$col->name] = match (true) {
                $col->name === 'user_id' => $userId,
                str_ends_with($col->name, '_id') => $i,
                in_array($col->type, ['int', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double'], true) => $i,
                $col->type === 'enum' => $this->firstEnumValue((string) $col->ctype),
                $col->type === 'json' => '[]',
                in_array($col->type, ['date'], true) => '2026-01-0'.$i,
                in_array($col->type, ['datetime', 'timestamp'], true) => '2026-01-0'.$i.' 10:00:00',
                default => $table.'-'.$col->name.'-'.$i,
            };
        }

        return $row;
    }

    private function firstEnumValue(string $columnType): string
    {
        preg_match("/^enum\\('([^']*)'/", $columnType, $m);

        return $m[1] ?? '';
    }

    /**
     * แถวเมนูตายของ Academy: กลุ่มเมนู + เมนูย่อย + ลูกที่แอดมินเพิ่มเอง (route อื่น)
     *
     * @return array<int, int>
     */
    private function insertLegacyMenuRows(): array
    {
        $group = DB::table('menu_items')->insertGetId($this->menuRow('admin.academy', null, null));
        $child = DB::table('menu_items')->insertGetId($this->menuRow('admin.academy.courses', 'admin.academy.courses.index', $group));
        $custom = DB::table('menu_items')->insertGetId($this->menuRow('custom.link', 'admin.dashboard', $group));
        $loose = DB::table('menu_items')->insertGetId($this->menuRow('admin.something', 'admin.quiz-management.index', null));

        return [$group, $child, $custom, $loose];
    }

    /**
     * @return array<string, mixed>
     */
    private function menuRow(string $key, ?string $route, ?int $parentId): array
    {
        $row = $this->requiredValues('menu_items', 1, 1);
        $row['menu_key'] = $key;
        $row['route'] = $route;
        if (Schema::hasColumn('menu_items', 'parent_id')) {
            $row['parent_id'] = $parentId;
        }
        if (Schema::hasColumn('menu_items', 'label')) {
            $row['label'] = 'เมนู '.$key;
        }

        return $row;
    }

    /**
     * @return array<int, string>
     */
    private function backupDirs(): array
    {
        return File::isDirectory(base_path('backups')) ? glob(base_path('backups/academy-legacy-*'), GLOB_ONLYDIR) ?: [] : [];
    }
}
