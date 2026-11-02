<?php

namespace Tests\Feature\User;

use App\Models\TwoFactorUserSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ชุดหน้าสมาชิก "user-misc" ย้ายจากเปลือก V4 (user-v4) มาเป็นธีมโนวาเต็ม (layouts.user-nova)
 *
 * - ทุกหน้าต้องเปิดได้ 200 ในเปลือกโนวา (nvm-body + nova-member.css) ไม่หลุดกลับไปโหมดสะพาน V4 / คลาส tp-card เดิม
 * - เทสต์ถดถอยสำหรับบั๊กที่แก้ไปพร้อมกัน (ตัวเรียงสินค้า 500, หน้าตั้งค่าบันทึกหลอก, ลิงก์แนะนำ YOUR_CODE ฯลฯ)
 */
class NovaUserMiscPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attrs));
    }

    /** ตรวจว่าเป็นเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4 */
    private function assertNovaPage($res): void
    {
        $res->assertOk();
        $html = (string) $res->getContent();
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/user-misc.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html);
        $this->assertStringNotContainsString('tp-card', $html);
    }

    public static function pages(): array
    {
        return [
            'home' => ['user.home'],
            'wealth-guide' => ['user.wealth-guide'],
            'wealth-guide-pro' => ['user.wealth-guide-pro'],
            'virtual-id-card' => ['user.ranks.id-card'],
            'settings' => ['user.settings'],
            'line-required' => ['user.line-required'],
            'seller-apply' => ['user.seller-apply.index'],
            'marketplace' => ['user.marketplace.products'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_in_full_nova_theme(string $route): void
    {
        $this->assertNovaPage($this->actingAs($this->member())->get(route($route)));
    }

    public function test_seller_apply_renders_for_member_without_kyc(): void
    {
        $user = $this->member(['kyc_status' => 'pending']);

        $this->assertNovaPage($this->actingAs($user)->get(route('user.seller-apply.index')));
    }

    public function test_wealth_guide_keeps_all_ten_chapter_anchors(): void
    {
        $html = (string) $this->actingAs($this->member())->get(route('user.wealth-guide'))->getContent();

        for ($i = 1; $i <= 10; $i++) {
            $this->assertStringContainsString('id="chapter-'.$i.'"', $html);
            $this->assertStringContainsString('href="#chapter-'.$i.'"', $html);
        }
    }

    // ── บั๊ก: ตัวเรียงสินค้า marketplace ──────────────────────

    public function test_marketplace_rejects_unknown_sort_direction_instead_of_500(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('user.marketplace.products', ['sort_by' => 'price', 'sort_dir' => 'sideways']))->assertOk();
        $this->actingAs($user)->get(route('user.marketplace.products', ['sort_by' => 'not_a_column']))->assertOk();
        $this->actingAs($user)->get(route('user.marketplace.products', ['sort_by' => ['x'], 'category' => 'abc', 'min_price' => 'cheap', 'search' => ['a']]))->assertOk();
    }

    // ── บั๊ก: หน้าตั้งค่า ─────────────────────────────────────

    public function test_settings_update_saves_preferred_language(): void
    {
        $user = $this->member(['preferred_language' => 'th']);

        $this->actingAs($user)
            ->put(route('user.settings.update'), ['preferred_language' => 'en', 'timezone' => 'UTC', 'theme' => 'dark'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('en', $user->fresh()->preferred_language);
        $this->assertSame('en', session('locale'));
    }

    public function test_settings_update_rejects_unknown_language(): void
    {
        $user = $this->member(['preferred_language' => 'th']);

        $this->actingAs($user)
            ->put(route('user.settings.update'), ['preferred_language' => 'xx'])
            ->assertSessionHasErrors('preferred_language');

        $this->assertSame('th', $user->fresh()->preferred_language);
    }

    public function test_settings_shows_two_factor_enabled_from_two_factor_settings(): void
    {
        $user = $this->member();
        TwoFactorUserSetting::create(['user_id' => $user->id, 'enabled' => true, 'preferred_method' => 'authenticator']);

        $this->actingAs($user)->get(route('user.settings'))
            ->assertOk()
            ->assertSee('เปิดใช้งานแล้ว')
            ->assertDontSee('ยังไม่เปิด 2FA');
    }

    public function test_settings_shows_two_factor_off_by_default(): void
    {
        $this->actingAs($this->member())->get(route('user.settings'))
            ->assertOk()
            ->assertSee('ยังไม่ได้เปิดใช้งาน');
    }

    // ── บั๊ก: ลิงก์แนะนำในคู่มือ PRO ───────────────────────────

    public function test_wealth_guide_pro_referral_link_uses_member_code(): void
    {
        $user = $this->member();
        $planId = DB::table('mlm_plans')->insertGetId(['name' => 'แผนทดสอบ', 'slug' => 'test-plan', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('mlm_members')->insert(['user_id' => $user->id, 'mlm_plan_id' => $planId, 'member_code' => 'TPM12345', 'created_at' => now(), 'updated_at' => now()]);

        $html = (string) $this->actingAs($user)->get(route('user.wealth-guide-pro'))->assertOk()->getContent();

        $this->assertStringNotContainsString('YOUR_CODE', $html);
        $this->assertStringContainsString('ref=TPM12345', $html);
    }

    public function test_wealth_guide_pro_without_mlm_member_has_no_fake_link(): void
    {
        $this->actingAs($this->member())->get(route('user.wealth-guide-pro'))
            ->assertOk()
            ->assertDontSee('YOUR_CODE')
            ->assertSee('ยังไม่มีรหัสสมาชิก MLM');
    }

    public function test_wealth_guide_pro_keeps_3d_mount_points_and_calculator_ids(): void
    {
        $html = (string) $this->actingAs($this->member())->get(route('user.wealth-guide-pro'))->getContent();

        foreach (['wealth-3d-mindmap', 'wealth-3d-income-flow', 'toggle-rotate', 'reset-camera', 'calc-direct-sales', 'calc-binary-pairs', 'result-total', 'result-yearly'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
    }

    // ── บั๊ก: บัตรสมาชิก QR ไม่เคยขึ้น / หน้าแรก "สแกน QR" หลอก ─────────

    public function test_id_card_uses_qrcodejs_and_has_qr_slot(): void
    {
        $html = (string) $this->actingAs($this->member())->get(route('user.ranks.id-card'))->assertOk()->getContent();

        $this->assertStringContainsString('qrcodejs@1.0.0', $html);
        $this->assertStringContainsString('x-ref="qr"', $html);
        $this->assertStringContainsString('id="virtual-id-card"', $html);
    }

    public function test_home_quick_scan_links_to_real_qr_transfer_page(): void
    {
        $this->actingAs($this->member())->get(route('user.home'))
            ->assertOk()
            ->assertSee(route('user.wallet.qr-transfer'), false);
    }
}
