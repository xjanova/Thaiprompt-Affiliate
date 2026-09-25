<?php

namespace Tests\Feature\Launch;

use App\Models\AccountingActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Pricing\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * หน้าแอดมิน "ส่วนแบ่งรายได้ & GP" (admin.pricing.settings) — ใช้ MySQL
 *
 * ครอบคลุม: หน้า V4 เปิดได้, บันทึกค่า + ประวัติ (ใคร/ค่าเดิม → ค่าใหม่), ตรวจช่วงค่า,
 * ตัวจำลองใช้ PricingEngine/DeliveryFeeCalculator จริง, ผู้ใช้ทั่วไปเข้าไม่ได้
 */
class PricingSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_page_renders_with_v4_layout(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.pricing.settings'))
            ->assertOk()
            ->assertSee('tp-card', false)
            ->assertSee('ส่วนแบ่งรายได้')
            ->assertSee('โปรโมชันเปิดตัว')
            ->assertSee(route('admin.riders.settings'), false);
    }

    public function test_member_cannot_open_the_page(): void
    {
        $member = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($member)->get(route('admin.pricing.settings'));

        $this->assertNotSame(200, $response->getStatusCode());
    }

    public function test_launch_defaults_are_gp_free(): void
    {
        $engine = new PricingEngine;

        $this->assertTrue($engine->gpPromoActive());
        $this->assertSame(0.0, $engine->defaultGpRate());
    }

    public function test_save_updates_settings_and_writes_audit_rows(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.pricing.settings.update'), [
            'settings' => [
                'gp_free' => 0,
                'gp_free_until' => '',
                'default_gp_rate' => 12,
                'min_gp_rate' => 5,
                'fresh_market_gp_rate' => 3,
                'referral_pool_percent' => 0,
                'vat_rate' => 7,
                'official_shop_vat_registered' => 1,
                'rider_share_percent' => 85,
                'shipped_auto_release_days' => 10,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.settings.default_gp_rate', 12)
            ->assertJsonPath('data.settings.gp_free', false);

        $this->assertFalse(Setting::get('pricing.gp_free'));
        $this->assertSame(12.0, Setting::get('pricing.default_gp_rate'));
        $this->assertSame(5.0, Setting::get('pricing.min_gp_rate'));
        $this->assertSame(3.0, Setting::get('pricing.fresh_market_gp_rate'));
        $this->assertSame(85.0, Setting::get('rider.rider_share_percent'));
        $this->assertSame(10, Setting::get('money.shipped_auto_release_days'));

        // เครื่องคำนวณราคาอ่านค่าใหม่ทันที
        $engine = new PricingEngine;
        $this->assertFalse($engine->gpPromoActive());
        $this->assertSame(12.0, $engine->defaultGpRate());

        // ประวัติ: 1 แถวต่อค่าที่เปลี่ยน พร้อมผู้แก้และค่าเดิม → ค่าใหม่
        $changed = collect($response->json('data.changed'))->pluck('field')->all();
        $this->assertContains('default_gp_rate', $changed);
        $this->assertContains('gp_free', $changed);
        $this->assertNotContains('vat_rate', $changed, 'ค่าที่ไม่เปลี่ยนต้องไม่ถูกบันทึกประวัติ');

        $logs = AccountingActivityLog::where('loggable_type', Setting::class)->where('action', 'setting.updated')->get();
        $this->assertCount(count($changed), $logs);

        $gpLog = $logs->first(fn ($log) => ($log->new_values['key'] ?? null) === 'pricing.default_gp_rate');
        $this->assertNotNull($gpLog);
        $this->assertSame($this->admin->id, (int) $gpLog->user_id);
        $this->assertEquals(0, $gpLog->old_values['value']);
        $this->assertEquals(12, $gpLog->new_values['value']);

        // บันทึกซ้ำค่าเดิม → ไม่มีประวัติเพิ่ม
        $this->actingAs($this->admin)->postJson(route('admin.pricing.settings.update'), [
            'settings' => ['default_gp_rate' => 12, 'rider_share_percent' => 85],
        ])->assertOk()->assertJsonPath('message', 'ไม่มีค่าที่เปลี่ยนแปลง');

        $this->assertSame($logs->count(), AccountingActivityLog::where('action', 'setting.updated')->count());

        // หน้าแสดงประวัติ
        $this->actingAs($this->admin)->get(route('admin.pricing.settings'))
            ->assertOk()
            ->assertSee('อัตรา GP มาตรฐาน (อีคอมเมิร์ซ)');
    }

    public function test_validation_rejects_out_of_range_and_inconsistent_values(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.settings.update'), ['settings' => ['default_gp_rate' => 60]])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['settings.default_gp_rate']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.settings.update'), ['settings' => ['default_gp_rate' => 5, 'min_gp_rate' => 8]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings.min_gp_rate']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.settings.update'), ['settings' => ['rider_share_percent' => 120]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings.rider_share_percent']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.settings.update'), ['settings' => ['gp_free' => 1, 'gp_free_until' => now()->subDays(3)->format('Y-m-d')]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings.gp_free_until']);

        $this->actingAs($this->admin)
            ->postJson(route('admin.pricing.settings.update'), ['settings' => ['gp_free_until' => '25/12/2026']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings.gp_free_until']);

        // ไม่มีอะไรถูกบันทึก
        $this->assertSame(0.0, Setting::get('pricing.default_gp_rate'));
        $this->assertSame(0, AccountingActivityLog::where('action', 'setting.updated')->count());
    }

    public function test_plain_form_post_redirects_with_flash(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.pricing.settings'))
            ->post(route('admin.pricing.settings.update'), [
                'settings' => [
                    'gp_free' => '1',
                    'gp_free_until' => now()->addDays(30)->format('Y-m-d'),
                    'default_gp_rate' => '10',
                ],
            ])
            ->assertRedirect(route('admin.pricing.settings'))
            ->assertSessionHas('success');

        $this->assertSame(now()->addDays(30)->format('Y-m-d'), Setting::get('pricing.gp_free_until'));
        $this->assertTrue((new PricingEngine)->gpPromoActive());
    }

    public function test_simulator_uses_draft_values_and_real_engines(): void
    {
        // ร่าง: ปิดโปรฯ GP 10% ไรเดอร์ 80%
        $response = $this->actingAs($this->admin)->postJson(route('admin.pricing.simulate'), [
            'price' => 100,
            'quantity' => 1,
            'distance_km' => 3,
            'settings' => [
                'gp_free' => false,
                'default_gp_rate' => 10,
                'min_gp_rate' => 0,
                'fresh_market_gp_rate' => 2,
                'rider_share_percent' => 80,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.effective.promo_active', false)
            ->assertJsonPath('data.shop.gp_amount', 10)
            ->assertJsonPath('data.shop.seller_net', 90)
            ->assertJsonPath('data.fresh.gp_amount', 2)
            ->assertJsonPath('data.fresh.seller_net', 98);

        $rider = $response->json('data.rider');
        $this->assertEqualsWithDelta($rider['total_fee'] * 0.8, $rider['rider_earnings'], 0.011);
        $this->assertEqualsWithDelta($rider['total_fee'], $rider['rider_earnings'] + $rider['platform_fee'], 0.011);
        $this->assertNotEmpty($response->json('data.advice.items'));

        // ค่าที่บันทึกจริงยังไม่เปลี่ยน (ตัวจำลองไม่เขียน DB)
        $this->assertTrue((new PricingEngine)->gpPromoActive());

        // โปรฯ GP ฟรี → GP 0 ทั้งสองฝั่ง
        $this->actingAs($this->admin)->postJson(route('admin.pricing.simulate'), [
            'price' => 100,
            'settings' => ['gp_free' => true, 'default_gp_rate' => 10],
        ])->assertOk()
            ->assertJsonPath('data.effective.promo_active', true)
            ->assertJsonPath('data.shop.gp_amount', 0)
            ->assertJsonPath('data.fresh.gp_amount', 0);

        $this->actingAs($this->admin)->postJson(route('admin.pricing.simulate'), ['price' => -5])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }
}
