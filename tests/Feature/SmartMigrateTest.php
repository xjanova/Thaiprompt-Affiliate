<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * migrate:smart ต้องรัน up() ของจริงเสมอ และไม่บันทึก migration ที่ล้ม/ทำไปครึ่งเดียว (ต้องใช้ MySQL — รันบน CI)
 *
 * เคสจริง (2026-09-26):
 * - 2025_12_21_000001_fix_mobile_banner_images ล้มบน prod แต่ถูกบันทึกว่ารันแล้ว + exit 0
 *   ⇒ mobile_banners.position ค้าง enum เก่า 9 เดือน
 * - 2026_01_30_100000_add_sms_payment_to_fortune_readings_table ชื่อเข้า pattern add_..._to_X_table + ตารางมีอยู่แล้ว
 *   ⇒ up() ไม่เคยรัน คำสั่งนี้เดาชนิดคอลัมน์เอง ⇒ fortune_readings.is_floating เป็น varchar NULL
 *
 * เทสต์นี้ไม่รัน DDL (กัน implicit commit ทำลายทรานแซกชันของ RefreshDatabase) —
 * migration ปลอมเขียนลงโฟลเดอร์ชั่วคราวแล้วส่งให้คำสั่งผ่าน --path · "คำสั่งที่เปลี่ยนฐานข้อมูล" ใช้ UPDATE 0 แถว
 */
class SmartMigrateTest extends TestCase
{
    use RefreshDatabase;

    /** UPDATE ที่ไม่ตรงแถวไหน — นับเป็นคำสั่งเปลี่ยนแปลงโดยไม่แตะข้อมูลจริง */
    private const CHANGE_STATEMENT = "\\Illuminate\\Support\\Facades\\DB::table('migrations')->where('migration', '__smartmig_none__')->update(['batch' => 0]);";

    /** SQL error จริงจาก MySQL (ตารางไม่มีอยู่) — แทน UPDATE ที่ล้มด้วย Data truncated บน prod */
    private const FAILING_STATEMENT = "\\Illuminate\\Support\\Facades\\DB::update('UPDATE smartmig_missing_table SET position = ?', ['home']);";

    /** @var array<int, string> migration ปลอมตัวไหนถูกเรียก up() บ้าง */
    public static array $ran = [];

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        self::$ran = [];
        $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'smart-migrate-'.uniqid();
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_failing_migration_stays_pending_and_command_exits_non_zero(): void
    {
        $this->writeMigration('2999_01_01_000001_smartmig_first_ok', $this->ran('first'));
        $this->writeMigration('2999_01_01_000002_smartmig_breaks', self::FAILING_STATEMENT);
        $this->writeMigration('2999_01_01_000003_smartmig_after_failure', $this->ran('after'));

        // exit 1 = ล้มใน up() ⇒ deploy.sh ต้องหยุด ไม่ fallback ไป migrate --force
        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain('smartmig_missing_table')
            ->expectsOutputToContain('Failed migrations (NOT marked as ran')
            ->expectsOutputToContain('Failed before changing anything')
            ->expectsOutputToContain('SMART_MIGRATE_FAILED=2999_01_01_000002_smartmig_breaks')
            ->assertExitCode(1);

        // ตัวที่สำเร็จก่อนหน้าถูกบันทึก · ตัวที่ล้มและตัวหลังจากนั้นยังค้าง pending
        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000001_smartmig_first_ok']);
        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000002_smartmig_breaks']);
        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000003_smartmig_after_failure']);

        // หยุดที่ตัวแรกที่ล้ม — ตัวถัดไปอาจพึ่งตัวที่ล้ม จึงต้องไม่ถูกรัน
        $this->assertSame(['first'], self::$ran);

        // ล้มตั้งแต่คำสั่งแรก = ไม่มีอะไรค้างครึ่งทาง ⇒ ไม่ต้องจำ (รอบหน้ารันซ้ำได้ตามปกติ)
        $this->assertDatabaseMissing('smart_migrate_failures', ['migration' => '2999_01_01_000002_smartmig_breaks']);
    }

    public function test_existing_table_runs_the_real_up_and_never_guesses_columns(): void
    {
        $nextBatch = (int) DB::table('migrations')->max('batch') + 1;

        // create_X_table + ตารางมีอยู่แล้ว + มี guard ⇒ up() ถูกรันจริง แล้ว guard ข้าม ⇒ บันทึกได้ (เดิม: ไม่รัน up())
        $this->writeMigration(
            '2999_01_01_000001_create_migrations_table',
            "if (\\Illuminate\\Support\\Facades\\Schema::hasTable('migrations')) {\n"
            .'            '.$this->ran('create_guarded')."\n\n"
            ."            return;\n"
            ."        }\n\n"
            ."        throw new \\RuntimeException('guard must stop here, the migrations table exists');"
        );

        // add_..._to_X_table + ตารางมีอยู่แล้ว ⇒ เดิม regex หา boolean('smartmig_flag') / dropColumn('smartmig_junk')
        // แล้ว ALTER เพิ่มเป็นคอลัมน์ "ชนิดเดา" โดยไม่เคยรัน up() — ตอนนี้ต้องรัน up() และห้ามเพิ่มคอลัมน์เอง
        $this->writeMigration(
            '2999_01_01_000002_add_smartmig_flag_to_migrations_table',
            $this->ran('add')."\n\n"
            ."        if (false) {\n"
            ."            \\Illuminate\\Support\\Facades\\Schema::table('migrations', function (\$table) {\n"
            ."                \$table->boolean('smartmig_flag')->default(false);\n"
            ."            });\n"
            .'        }',
            "\\Illuminate\\Support\\Facades\\Schema::table('migrations', function (\$table) {\n"
            ."            \$table->dropColumn('smartmig_junk');\n"
            .'        });'
        );

        $this->writeMigration('2999_01_01_000003_smartmig_changes_something', $this->ran('changes')."\n        ".self::CHANGE_STATEMENT);

        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain("Table 'migrations' already exists — running the real up() anyway")
            ->expectsOutputToContain('no changes (already applied, or skipped by its own guard)')
            ->expectsOutputToContain('up() ran (1 change statement(s))')
            ->doesntExpectOutputToContain('WITHOUT running up()')
            ->assertExitCode(0);

        $this->assertSame(['create_guarded', 'add', 'changes'], self::$ran);

        foreach (['2999_01_01_000001_create_migrations_table', '2999_01_01_000002_add_smartmig_flag_to_migrations_table', '2999_01_01_000003_smartmig_changes_something'] as $name) {
            $this->assertDatabaseHas('migrations', ['migration' => $name, 'batch' => $nextBatch]);
        }

        // ไม่มีคอลัมน์ "ชนิดเดา" งอกขึ้นมา (รวมคอลัมน์ขยะที่ชื่อมาจาก dropColumn() ใน down())
        $this->assertFalse(Schema::hasColumn('migrations', 'smartmig_flag'));
        $this->assertFalse(Schema::hasColumn('migrations', 'smartmig_junk'));
    }

    public function test_failure_after_a_change_is_remembered_as_half_applied(): void
    {
        $this->writeMigration('2999_01_01_000001_smartmig_half_applied', self::CHANGE_STATEMENT."\n        ".self::FAILING_STATEMENT);

        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain('Failed after 1 change statement(s)')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000001_smartmig_half_applied']);

        $failure = DB::table('smart_migrate_failures')->where('migration', '2999_01_01_000001_smartmig_half_applied')->first();
        $this->assertNotNull($failure);
        $this->assertSame(1, (int) $failure->attempts);
        $this->assertStringContainsString('[after 1 change statement(s)]', $failure->error);
        $this->assertStringContainsString('smartmig_missing_table', $failure->error);
    }

    public function test_retry_of_half_applied_migration_that_changes_nothing_is_refused(): void
    {
        // รอบก่อน: สร้างตารางแล้วล้มก่อนเพิ่ม FK · รอบนี้: guard hasTable → return ⇒ ห้ามบันทึกว่ารันแล้ว
        $this->rememberHalfApplied('2999_01_01_000001_create_migrations_table');

        $this->writeMigration(
            '2999_01_01_000001_create_migrations_table',
            "if (\\Illuminate\\Support\\Facades\\Schema::hasTable('migrations')) {\n"
            ."            return;\n"
            ."        }\n\n"
            ."        throw new \\RuntimeException('guard must stop here, the migrations table exists');"
        );

        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain('Failed on an earlier run (1x')
            ->expectsOutputToContain('Refusing to record')
            ->expectsOutputToContain('SMART_MIGRATE_FAILED=2999_01_01_000001_create_migrations_table')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000001_create_migrations_table']);

        // error ตัวแรก (ต้นเหตุ) ต้องไม่ถูกเขียนทับด้วยข้อความปฏิเสธ — เพิ่มแค่ attempts
        $failure = DB::table('smart_migrate_failures')->where('migration', '2999_01_01_000001_create_migrations_table')->first();
        $this->assertSame(2, (int) $failure->attempts);
        $this->assertStringContainsString('Cannot add foreign key', $failure->error);
    }

    public function test_retry_of_half_applied_migration_that_changes_something_is_recorded_and_forgotten(): void
    {
        // up() ถูกแก้ให้ทำส่วนที่ขาดต่อได้ (เช็คทีละคอลัมน์/FK) ⇒ รอบนี้มีการเปลี่ยนแปลงจริง ⇒ บันทึก + ลบความจำ
        $this->rememberHalfApplied('2999_01_01_000001_smartmig_fixed_forward');

        $this->writeMigration('2999_01_01_000001_smartmig_fixed_forward', self::CHANGE_STATEMENT);

        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->assertExitCode(0);

        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000001_smartmig_fixed_forward']);
        $this->assertDatabaseMissing('smart_migrate_failures', ['migration' => '2999_01_01_000001_smartmig_fixed_forward']);
    }

    private function ran(string $label): string
    {
        return "\\Tests\\Feature\\SmartMigrateTest::\$ran[] = '{$label}';";
    }

    private function rememberHalfApplied(string $migration): void
    {
        DB::table('smart_migrate_failures')->insert([
            'migration' => $migration,
            'error' => '[after 1 change statement(s)] Illuminate\\Database\\QueryException: SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint',
            'attempts' => 1,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }

    /**
     * เขียนไฟล์ migration ปลอม (anonymous class) ที่ up()/down() รันโค้ดที่ให้มา
     */
    private function writeMigration(string $name, string $upBody, string $downBody = ''): void
    {
        File::put($this->dir.DIRECTORY_SEPARATOR.$name.'.php', <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;

return new class extends Migration
{
    public function up(): void
    {
        {$upBody}
    }

    public function down(): void
    {
        {$downBody}
    }
};

PHP);
    }
}
