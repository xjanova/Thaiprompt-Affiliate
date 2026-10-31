<?php

namespace Tests\Feature\Console;

use App\Models\FortuneTellingSetting;
use App\Models\FreshMarketSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * คำสั่ง security:scan — ตรวจเฉพาะส่วนในเครื่อง ไม่ยิง HTTP/port/audit ระหว่างเทสต์
 */
class SecurityScanCommandTest extends TestCase
{
    // การไล่ route สร้าง controller ทุกตัว และ constructor ของ webhook จะสร้างแถว settings ว่างถ้ายังไม่มี
    // ต้องอยู่ใน transaction ไม่งั้นแถวนั้นค้างเป็น first() ให้เทสต์อื่นทั้งชุด
    use RefreshDatabase;

    private const LOCAL_ONLY = ['--skip-http' => true, '--skip-ports' => true, '--skip-deps' => true, '--json' => true];

    /**
     * constructor ตัวเดียวกันจำ settings ไว้ใน static memo ซึ่ง rollback ไม่ล้างให้
     * ถ้าไม่ล้างเอง เทสต์ถัดไปจะได้แถวที่ถูก rollback ไปแล้ว
     */
    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();
        FreshMarketSetting::clearCache();

        parent::tearDown();
    }

    private function scan(): array
    {
        $exitCode = Artisan::call('security:scan', self::LOCAL_ONLY);
        $report = json_decode(Artisan::output(), true);

        $this->assertIsArray($report);

        return [$exitCode, $report];
    }

    private function titles(array $report): array
    {
        return array_column($report['findings'], 'title');
    }

    public function test_report_has_a_summary_for_every_severity(): void
    {
        [, $report] = $this->scan();

        $this->assertSame(['critical', 'high', 'medium', 'low', 'info'], array_keys($report['summary']));
    }

    public function test_debug_mode_is_reported_and_fails_the_command(): void
    {
        config(['app.debug' => true]);

        [$exitCode, $report] = $this->scan();

        $this->assertSame(1, $exitCode);
        $this->assertContains('APP_DEBUG=true — เปิดเผย stack trace/env', $this->titles($report));
    }

    public function test_findings_are_sorted_most_severe_first(): void
    {
        config(['app.debug' => true, 'app.env' => 'staging']);

        [, $report] = $this->scan();

        $ranks = array_map(
            fn (array $finding) => array_search($finding['sev'], ['critical', 'high', 'medium', 'low', 'info'], true),
            $report['findings']
        );
        $sorted = $ranks;
        sort($sorted);

        $this->assertSame($sorted, $ranks);
    }
}
