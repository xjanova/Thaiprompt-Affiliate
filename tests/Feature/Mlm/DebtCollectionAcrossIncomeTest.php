<?php

namespace Tests\Feature\Mlm;

use App\Models\FortuneReading;
use App\Models\PlatformWallet;
use App\Models\WalletDebt;
use App\Services\FortuneCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Fortune\BuildsFortuneCommissionChain;
use Tests\TestCase;

/**
 * M7 (review money SHOULD): หนี้ค้างต้องถูกหักจากรายได้ทุกทางที่เงินเข้ากระเป๋า — อย่างน้อยค่าแนะนำดูดวง
 * และ migration ค่าตั้ง binary_pair_unit_pv ต้องไม่ใส่แถวในตารางว่าง (review regression #1)
 */
class DebtCollectionAcrossIncomeTest extends TestCase
{
    use BuildsFortuneCommissionChain;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    /**
     * ผู้แนะนำมีหนี้ดึงคืนคอม MLM 7 บาท → ค่าแนะนำดูดวง 10 บาทที่เพิ่งเข้า ถูกหักหนี้ทันที เหลือ 3 · กระเป๋าไม่ติดลบ
     */
    public function test_fortune_commission_income_collects_active_mlm_debt(): void
    {
        $this->setUpFortuneChain();
        [, $grand] = $this->makeChainMember();
        [$sponsorUser, $sponsor] = $this->makeChainMember($grand);
        [$buyerUser, $buyer] = $this->makeChainMember($sponsor);
        $debt = WalletDebt::createDebt($sponsorUser->id, 7, 'MlmClawback', 1, 'ทดสอบหนี้ดึงคืนคอม MLM');
        $refundPoolBefore = (float) PlatformWallet::getRefundPoolWallet()->fresh()->balance;

        $bill = $this->makeBill($buyerUser->id, FortuneReading::READING_TYPE_CELTIC_CROSS, 99.83);
        app(FortuneCommissionService::class)->distributeCommissions($bill, $buyer, $this->settings);

        $this->assertSame(WalletDebt::STATUS_PAID, $debt->fresh()->status, 'หนี้ต้องถูกหักจากค่าแนะนำที่เพิ่งเข้า');
        $this->assertEqualsWithDelta(3.0, $this->balanceOf($sponsorUser->id), 0.001, 'ค่าแนะนำ 10 − หนี้ 7');
        $this->assertEqualsWithDelta($refundPoolBefore + 7, (float) PlatformWallet::getRefundPoolWallet()->fresh()->balance, 0.001);
    }

    /**
     * ฐานใหม่ (ตารางค่าตั้งว่าง) → migration ไม่ใส่แถว (ปล่อยให้ seeder ใส่) · ฐานที่มีค่าตั้งอยู่แล้ว → ใส่เฉพาะที่ขาด
     */
    public function test_pair_unit_migration_skips_empty_settings_table(): void
    {
        $migration = require database_path('migrations/2026_11_03_120000_add_binary_pair_unit_pv_setting.php');

        DB::table('mlm_global_settings')->delete();
        $migration->up();
        $this->assertSame(0, DB::table('mlm_global_settings')->count(), 'ตารางว่างต้องว่างต่อ — แถวนี้ห้ามกลายเป็น id แรก');

        DB::table('mlm_global_settings')->insert(['key' => 'vat_rate', 'value' => '7', 'type' => 'decimal']);
        $migration->up();
        $this->assertSame('100', DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->value('value'));

        DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->update(['value' => '50']);
        $migration->up();
        $this->assertSame('50', DB::table('mlm_global_settings')->where('key', 'binary_pair_unit_pv')->value('value'), 'ห้ามเขียนทับค่าที่แอดมินตั้ง');
    }
}
