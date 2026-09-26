<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * migrate:smart ต้องไม่บันทึก migration ที่ล้มลงตาราง migrations (ต้องใช้ MySQL — รันบน CI)
 *
 * เคสจริง: 2025_12_21_000001_fix_mobile_banner_images และ 2026_09_26_133100_seed_launch_app_campaign_banners
 * ล้มบน prod ("Data truncated for column 'position'") แต่ถูกบันทึกว่ารันแล้ว + คำสั่ง exit 0
 * ⇒ deploy.sh เข้าใจว่าสำเร็จ และ mobile_banners.position ค้าง enum เก่า 9 เดือน
 *
 * เทสต์นี้ไม่รัน DDL (กัน implicit commit ทำลายทรานแซกชันของ RefreshDatabase) —
 * migration ปลอมเขียนลงโฟลเดอร์ชั่วคราวแล้วส่งให้คำสั่งผ่าน --path
 */
class SmartMigrateTest extends TestCase
{
    use RefreshDatabase;

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
        $this->writeMigration('2999_01_01_000001_smartmig_first_ok', "\\Tests\\Feature\\SmartMigrateTest::\$ran[] = 'first';");
        // SQL error จริงจาก MySQL (ตารางไม่มีอยู่) — แทน UPDATE ที่ล้มด้วย Data truncated บน prod
        $this->writeMigration('2999_01_01_000002_smartmig_breaks', "\\Illuminate\\Support\\Facades\\DB::update('UPDATE smartmig_missing_table SET position = ?', ['home']);");
        $this->writeMigration('2999_01_01_000003_smartmig_after_failure', "\\Tests\\Feature\\SmartMigrateTest::\$ran[] = 'after';");

        // exit 1 (ไม่ใช่ 2) = ล้มใน up() ⇒ deploy.sh ต้องหยุด ไม่ fallback ไป migrate --force
        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain('smartmig_missing_table')
            ->expectsOutputToContain('Failed migrations (NOT marked as ran')
            ->expectsOutputToContain('SMART_MIGRATE_FAILED=2999_01_01_000002_smartmig_breaks')
            ->assertExitCode(1);

        // ตัวที่สำเร็จก่อนหน้าถูกบันทึก · ตัวที่ล้มและตัวหลังจากนั้นยังค้าง pending
        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000001_smartmig_first_ok']);
        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000002_smartmig_breaks']);
        $this->assertDatabaseMissing('migrations', ['migration' => '2999_01_01_000003_smartmig_after_failure']);

        // หยุดที่ตัวแรกที่ล้ม — ตัวถัดไปอาจพึ่งตัวที่ล้ม จึงต้องไม่ถูกรัน
        $this->assertSame(['first'], self::$ran);
    }

    public function test_successful_and_already_applied_migrations_are_marked_in_one_batch(): void
    {
        $nextBatch = (int) DB::table('migrations')->max('batch') + 1;

        $this->writeMigration('2999_01_01_000001_smartmig_ok', "\\Tests\\Feature\\SmartMigrateTest::\$ran[] = 'ok';");
        // ชื่อ create_migrations_table + ตาราง/คอลัมน์มีครบ ⇒ ข้ามโดยตั้งใจ: บันทึกโดยไม่เรียก up()
        $this->writeMigration(
            '2999_01_01_000002_create_migrations_table',
            "throw new \\RuntimeException('up() must not run for an already existing table');\n"
            ."        \\Illuminate\\Support\\Facades\\Schema::create('migrations', function (\$table) {\n"
            ."            \$table->id();\n"
            ."            \$table->string('migration');\n"
            ."            \$table->integer('batch');\n"
            .'        });'
        );
        // ชื่อ add_..._to_migrations_table แต่ parse คอลัมน์ไม่ได้ (เช่น ใช้ DB::statement) ⇒ ต้องรัน up() จริง
        // (เดิม "skipping" แล้วบันทึกว่ารันแล้วโดยไม่ได้ทำอะไรเลย)
        $this->writeMigration('2999_01_01_000003_add_nothing_to_migrations_table', "\\Tests\\Feature\\SmartMigrateTest::\$ran[] = 'no_columns';");

        $this->artisan('migrate:smart', ['--force' => true, '--path' => $this->dir])
            ->expectsOutputToContain('marking as ran WITHOUT running up()')
            ->assertExitCode(0);

        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000001_smartmig_ok', 'batch' => $nextBatch]);
        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000002_create_migrations_table', 'batch' => $nextBatch]);
        $this->assertDatabaseHas('migrations', ['migration' => '2999_01_01_000003_add_nothing_to_migrations_table', 'batch' => $nextBatch]);
        $this->assertSame(['ok', 'no_columns'], self::$ran);
    }

    /**
     * เขียนไฟล์ migration ปลอม (anonymous class) ที่ up() รันโค้ดที่ให้มา
     */
    private function writeMigration(string $name, string $upBody): void
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
};

PHP);
    }
}
