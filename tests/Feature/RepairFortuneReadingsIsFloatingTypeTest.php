<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * repair migration ของ fortune_readings.is_floating / sms_notification_id (ต้องใช้ MySQL — รันบน CI)
 *
 * prod: is_floating เป็น varchar NULL ทุกแถว ⇒ where('is_floating', false) คืน 0 แถว
 * เทสต์จำลอง drift นั้นด้วย DDL จึงปิดทรานแซกชันของ RefreshDatabase ในคลาสนี้
 * (DDL ทำ implicit commit) แล้วคืน schema + ลบแถวทดสอบเองใน tearDown ทุกครั้ง แม้เทสต์ล้ม
 */
class RepairFortuneReadingsIsFloatingTypeTest extends TestCase
{
    use RefreshDatabase;

    /** ไม่ห่อทรานแซกชัน — เทสต์นี้รัน ALTER TABLE */
    protected array $connectionsToTransact = [];

    private const MARKER = 'repair_is_floating_test_';

    private const MIGRATION = 'database/migrations/2026_09_26_170000_repair_fortune_readings_is_floating_type.php';

    protected function tearDown(): void
    {
        DB::table('fortune_readings')->where('facebook_user_id', 'like', self::MARKER.'%')->delete();

        // คืนชนิดตาม repo เผื่อเทสต์ล้มกลางทาง — ไม่งั้นเทสต์คลาสอื่นใน process เดียวกันจะเจอ schema เพี้ยน
        if ($this->column('is_floating')->data_type !== 'tinyint') {
            DB::update("UPDATE fortune_readings SET is_floating = '0' WHERE is_floating IS NULL OR is_floating NOT IN ('0', '1')");
            DB::statement("ALTER TABLE fortune_readings MODIFY is_floating TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'บิลลอย = ยังไม่ระบุตัวตนลูกค้า'");
        }
        if ($this->column('sms_notification_id')->data_type !== 'bigint') {
            DB::update("UPDATE fortune_readings SET sms_notification_id = NULL WHERE sms_notification_id NOT REGEXP '^[0-9]+\$'");
            DB::statement('ALTER TABLE fortune_readings MODIFY sms_notification_id BIGINT UNSIGNED NULL DEFAULT NULL');
        }

        parent::tearDown();
    }

    public function test_repairs_prod_varchar_drift_and_paid_filter_matches_again(): void
    {
        $this->simulateProdDrift();

        $ids = [
            'null_paid' => $this->insertReading(null, true, null),
            'empty_paid' => $this->insertReading('', true, ''),
            'zero_paid' => $this->insertReading('0', true, '42'),
            'one_floating' => $this->insertReading('1', true, null),
            'true_floating' => $this->insertReading('true', false, null),
            'false_unpaid' => $this->insertReading('false', false, null),
        ];

        // สภาพเดียวกับ prod: แถว NULL หลุดจากตัวกรอง "ชำระแล้ว" (prod เป็น NULL ทุกแถว ⇒ ได้ 0)
        // ('' กับ '0' ยังตรงเพราะ MySQL แปลงสตริงเป็น 0 ตอนเทียบกับตัวเลข)
        $this->assertSame(2, $this->paidNotFloatingCount());

        $this->runRepair();

        $isFloating = $this->column('is_floating');
        $this->assertSame('tinyint', $isFloating->data_type);
        $this->assertSame('NO', $isFloating->is_nullable);
        $this->assertSame('0', (string) $isFloating->column_default);

        $sms = $this->column('sms_notification_id');
        $this->assertSame('bigint', $sms->data_type);
        $this->assertStringContainsString('unsigned', $sms->column_type);
        $this->assertSame('YES', $sms->is_nullable);

        $values = DB::table('fortune_readings')->whereIn('id', $ids)->pluck('is_floating', 'id');
        $this->assertSame(0, (int) $values[$ids['null_paid']]);
        $this->assertSame(0, (int) $values[$ids['empty_paid']]);
        $this->assertSame(0, (int) $values[$ids['zero_paid']]);
        $this->assertSame(1, (int) $values[$ids['one_floating']]);
        $this->assertSame(1, (int) $values[$ids['true_floating']]);
        $this->assertSame(0, (int) $values[$ids['false_unpaid']]);

        $smsValues = DB::table('fortune_readings')->whereIn('id', $ids)->pluck('sms_notification_id', 'id');
        $this->assertNull($smsValues[$ids['empty_paid']]);
        $this->assertSame(42, (int) $smsValues[$ids['zero_paid']]);

        // ตัวกรองแบบใน FortuneBillingController:45 กลับมาเห็นบิลที่จ่ายแล้วและไม่ใช่บิลลอยครบ 3 ใบ
        $this->assertSame(3, $this->paidNotFloatingCount());

        // รันซ้ำ = ไม่ทำอะไร
        $this->runRepair();
        $this->assertSame(3, $this->paidNotFloatingCount());
    }

    public function test_unknown_value_stops_the_migration_instead_of_guessing(): void
    {
        $this->simulateProdDrift();
        $this->insertReading('maybe', true, null);

        try {
            $this->runRepair();
            $this->fail('Expected the repair to refuse a non-boolean is_floating value');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('maybe', $e->getMessage());
        }

        // ยังไม่ได้ ALTER — ชนิดเดิมค้างไว้ให้คนแก้ข้อมูลก่อน
        $this->assertSame('varchar', $this->column('is_floating')->data_type);
    }

    /**
     * ทำให้ schema เหมือน prod: ทั้งสองคอลัมน์เป็น varchar(255) NULL
     */
    private function simulateProdDrift(): void
    {
        DB::statement('ALTER TABLE fortune_readings MODIFY is_floating VARCHAR(255) NULL DEFAULT NULL, MODIFY sms_notification_id VARCHAR(255) NULL DEFAULT NULL');
    }

    private function insertReading(?string $isFloating, bool $isPaid, ?string $smsNotificationId): int
    {
        return DB::table('fortune_readings')->insertGetId([
            'facebook_user_id' => self::MARKER.uniqid(),
            'questions' => '[]',
            'is_paid' => $isPaid,
            'is_floating' => $isFloating,
            'sms_notification_id' => $smsNotificationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runRepair(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    private function paidNotFloatingCount(): int
    {
        return DB::table('fortune_readings')
            ->where('facebook_user_id', 'like', self::MARKER.'%')
            ->where('is_paid', true)
            ->where('is_floating', false)
            ->count();
    }

    private function column(string $name): object
    {
        return DB::selectOne(
            'SELECT DATA_TYPE AS data_type, COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default'
            .' FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['fortune_readings', $name]
        );
    }
}
