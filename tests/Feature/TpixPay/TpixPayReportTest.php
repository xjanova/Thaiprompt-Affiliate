<?php

namespace Tests\Feature\TpixPay;

use App\Models\TpixPayment;
use App\Models\User;
use App\Services\TpixPay\TpixPayReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 📊 รายงาน TPIX ในหลังบ้าน — ตัวเลขสรุป / กราฟ / ส่งออก CSV / สิทธิ์แอดมิน (ต้องใช้ MySQL)
 */
class TpixPayReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $payer;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->payer = User::factory()->create(['name' => 'ผู้จ่ายทดสอบ']);
    }

    public function test_report_page_shows_correct_totals_and_charts(): void
    {
        $now = now();
        // ได้รับเงินวันนี้: 1,000 + 250.50 (เติมเข้ากระเป๋า)
        $this->pay(['amount_thb' => 1000, 'amount_tpix' => '307.69240001', 'status' => 'paid', 'paid_at' => $now->copy()]);
        $this->pay(['amount_thb' => 250.50, 'amount_tpix' => '77.07692300', 'status' => 'credited_wallet', 'paid_at' => $now->copy(), 'purpose' => 'wallet_topup']);
        // 3 วันก่อน: 500
        $this->pay(['amount_thb' => 500, 'amount_tpix' => '150', 'status' => 'paid', 'paid_at' => $now->copy()->subDays(3), 'created_at' => $now->copy()->subDays(3)]);
        // 20 วันก่อน: 2,000
        $this->pay(['amount_thb' => 2000, 'amount_tpix' => '600', 'status' => 'paid', 'paid_at' => $now->copy()->subDays(20), 'created_at' => $now->copy()->subDays(20)]);
        // เก่ากว่า 30 วัน — ไม่นับ
        $this->pay(['amount_thb' => 9999, 'amount_tpix' => '3000', 'status' => 'paid', 'paid_at' => $now->copy()->subDays(40), 'created_at' => $now->copy()->subDays(40)]);
        // ไม่ได้รับเงิน: หมดเวลา 1, เปิดอยู่ 1, รออนุมัติ 1
        $this->pay(['status' => 'expired', 'expires_at' => $now->copy()->subMinutes(10)]);
        $this->pay(['status' => 'pending', 'expires_at' => $now->copy()->addMinutes(10)]);
        $late = $this->pay(['status' => 'late', 'meta' => ['needs_review' => true, 'late' => true]]);

        // ราคา 2 ชั่วโมงล่าสุด นาทีละจุด (3.00 → 3.12 บาท)
        $rows = [];
        for ($i = 120; $i >= 1; $i--) {
            $rows[] = [
                'price_usd' => '0.1', 'usd_thb' => '32.5', 'price_thb' => (string) (3 + (120 - $i) / 1000),
                'reserve_tpix' => '200000', 'reserve_usdt' => '20000', 'source' => 'dex',
                'created_at' => $now->copy()->subMinutes($i)->format('Y-m-d H:i:s'),
            ];
        }
        DB::table('tpix_price_snapshots')->insert($rows);

        $report = app(TpixPayReportService::class)->build();
        $k = $report['kpi'];

        $this->assertSame(2, $k['today']['count']);
        $this->assertEqualsWithDelta(1250.50, $k['today']['thb'], 0.001);
        $this->assertSame(3, $k['d7']['count']);
        $this->assertEqualsWithDelta(1750.50, $k['d7']['thb'], 0.001);
        $this->assertSame(4, $k['d30']['count']);
        $this->assertEqualsWithDelta(3750.50, $k['d30']['thb'], 0.001);
        $this->assertSame(0, bccomp('1134.76932301', $k['d30']['tpix'], 8), 'ยอดเหรียญรวมต้องแม่นทุกหลัก');
        $this->assertEqualsWithDelta(937.63, $k['avg_ticket_thb'], 0.001);
        // ใบที่ออกใน 30 วัน = 7 (ไม่รวมใบ 40 วันก่อน) ได้รับเงิน 4 → 57.1%
        $this->assertSame(7, $k['quotes_issued']);
        $this->assertSame(4, $k['quotes_settled']);
        $this->assertEqualsWithDelta(57.1, $k['conversion_pct'], 0.001);
        $this->assertSame(1, $k['open_count']);
        $this->assertSame(1, $k['review_count']);
        $this->assertSame(1, $k['credited_wallet']['count']);
        $this->assertEqualsWithDelta(250.50, $k['credited_wallet']['thb'], 0.001);

        // กราฟรายวัน: 30 วัน วันนี้ = 1,000 (คำสั่งซื้อ) + 250.50 (เติมกระเป๋า)
        $this->assertCount(30, $report['daily']['dates']);
        $this->assertEqualsWithDelta(1000, end($report['daily']['thb']['order']), 0.001);
        $this->assertEqualsWithDelta(250.5, end($report['daily']['thb']['wallet_topup']), 0.001);
        $this->assertSame(2, end($report['daily']['count']));

        // กราฟราคา: ≤ 300 จุด, มี TWAP, สภาพคล่องเข้ามา
        $this->assertNotEmpty($report['price']['labels']);
        $this->assertLessThanOrEqual(TpixPayReportService::MAX_PRICE_POINTS, count($report['price']['labels']));
        $this->assertNotNull(end($report['price']['twap']));
        $this->assertLessThan(end($report['price']['thb']), end($report['price']['twap']), 'ราคาขาขึ้น → ค่าเฉลี่ย 30 นาทีต้องต่ำกว่าราคาล่าสุด');
        $this->assertEqualsWithDelta(20000, end($report['price']['reserve_usdt']), 0.01);

        $this->assertSame(3, $report['statuses']['paid']);
        $this->assertSame(1, $report['statuses']['credited_wallet']);
        $this->assertSame(1, $report['statuses']['late']);
        $this->assertSame($this->payer->id, $report['top_payers'][0]['user_id']);
        $this->assertEqualsWithDelta(3750.50, $report['top_payers'][0]['thb'], 0.001);
        $this->assertSame($late->reference, $report['action_items'][0]['reference']);

        $this->actingAs($this->admin)->get('/admin/tpix-pay/reports')
            ->assertOk()
            ->assertSee('รายงาน TPIX')
            ->assertSee('฿3,750.50')
            ->assertSee('฿1,250.50')
            ->assertSee('57.1%')
            ->assertSee('ผู้จ่ายทดสอบ')
            ->assertSee($late->reference)
            ->assertSee('chart.js@4.4.1', false)
            ->assertSee('id="trpDaily"', false)
            ->assertSee('id="trpPrice"', false);
    }

    public function test_report_page_renders_when_empty(): void
    {
        $this->actingAs($this->admin)->get('/admin/tpix-pay/reports')
            ->assertOk()
            ->assertSee('ยังไม่มีรายการที่ได้รับเงินใน 30 วัน')
            ->assertSee('ยังไม่มีข้อมูลราคาย้อนหลัง')
            ->assertDontSee('id="trpDaily"', false);

        $this->actingAs($this->admin)->get('/admin/tpix-pay')->assertOk()->assertSee(route('admin.tpix-pay.reports'), false);
    }

    public function test_csv_export_has_bom_thai_headers_and_rows_in_range(): void
    {
        $inRange = $this->pay(['amount_thb' => 1234.5, 'amount_tpix' => '379.84615385', 'status' => 'paid', 'paid_at' => now(), 'meta' => ['order_number' => 'ORD-777']]);
        $evil = $this->pay(['status' => 'failed', 'failure_reason' => '=HYPERLINK("http://x")']);
        $old = $this->pay(['status' => 'paid', 'paid_at' => now()->subDays(60), 'created_at' => now()->subDays(60)]);

        $res = $this->actingAs($this->admin)->get('/admin/tpix-pay/export?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString());
        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $res->headers->get('Content-Disposition'));

        $csv = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('รหัสอ้างอิง', $csv);
        $this->assertStringContainsString('ยอดบาท', $csv);
        $this->assertStringContainsString($inRange->reference, $csv);
        $this->assertStringContainsString('1234.50', $csv);
        $this->assertStringContainsString('ORD-777', $csv);
        $this->assertStringContainsString('ชำระแล้ว', $csv);
        $this->assertStringNotContainsString($old->reference, $csv);
        // กันสูตร Excel
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString($evil->reference, $csv);

        // กรองเฉพาะที่ได้รับเงิน
        $settled = $this->actingAs($this->admin)
            ->get('/admin/tpix-pay/export?status=settled&from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString())
            ->streamedContent();
        $this->assertStringContainsString($inRange->reference, $settled);
        $this->assertStringNotContainsString($evil->reference, $settled);
    }

    public function test_csv_export_validates_range(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/tpix-pay/export?from=2026-10-10&to=2026-10-01')
            ->assertSessionHasErrors('to');

        $this->actingAs($this->admin)
            ->get('/admin/tpix-pay/export?from=2024-01-01&to=2026-01-01')
            ->assertSessionHasErrors('to');
    }

    public function test_non_admin_cannot_open_report_or_export(): void
    {
        $this->pay(['status' => 'paid', 'paid_at' => now()]);
        $member = User::factory()->create(['role' => 'user']);

        $report = $this->actingAs($member)->get('/admin/tpix-pay/reports');
        $this->assertContains($report->status(), [302, 403]);
        $report->assertDontSee('รายงาน TPIX');

        $export = $this->actingAs($member)->get('/admin/tpix-pay/export?from='.now()->toDateString().'&to='.now()->toDateString());
        $this->assertContains($export->status(), [302, 403]);
        $this->assertStringNotContainsString('text/csv', (string) $export->headers->get('Content-Type'));

        auth()->logout();
        $this->get('/admin/tpix-pay/reports')->assertRedirect();
    }

    /**
     * สร้างใบชำระ TPIX (กำหนด created_at ได้)
     *
     * @param  array<string, mixed>  $attrs
     */
    private function pay(array $attrs): TpixPayment
    {
        $createdAt = $attrs['created_at'] ?? now()->subMinutes(30);
        unset($attrs['created_at']);

        $p = new TpixPayment(array_merge([
            'reference' => 'TPX'.strtoupper(Str::random(10)),
            'user_id' => $this->payer->id,
            'purpose' => 'order',
            'amount_thb' => 100,
            'rate_thb' => '3.25',
            'amount_wei' => (string) random_int(10 ** 15, 10 ** 17),
            'amount_tpix' => '30.76923077',
            'receive_address' => '0x1111111111111111111111111111111111111111',
            'status' => 'pending',
            'expires_at' => now()->subMinutes(5),
        ], $attrs));
        $p->created_at = $createdAt;
        $p->updated_at = $attrs['paid_at'] ?? $createdAt;
        $p->save();

        return $p;
    }
}
