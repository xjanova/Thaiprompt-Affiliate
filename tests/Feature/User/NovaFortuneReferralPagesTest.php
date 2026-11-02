<?php

namespace Tests\Feature\User;

use App\Models\FortuneCommission;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\MlmPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * หน้าค่าแนะนำดูดวงของสมาชิกธีมโนวาเต็ม
 * (/user/fortune-referral/commissions, /recruit, /tree และ JSON /tree-data)
 */
class NovaFortuneReferralPagesTest extends TestCase
{
    use RefreshDatabase;

    private ?MlmPlan $plan = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(\App\Http\Middleware\RequireTwoFactor::class);
        Http::fake();
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'user', 'kyc_status' => 'approved'], $attributes));
    }

    /**
     * สมาชิก MLM ของ user (สร้างแผนให้ครั้งแรก)
     */
    private function mlm(User $user, ?MlmMember $sponsor = null): MlmMember
    {
        $this->plan ??= MlmPlan::create([
            'name' => 'Test Plan',
            'name_th' => 'แผนทดสอบ',
            'slug' => 'test-plan-'.uniqid(),
            'type' => 'unilevel',
            'is_active' => true,
            'is_default' => true,
        ]);

        return MlmMember::create([
            'user_id' => $user->id,
            'mlm_plan_id' => $this->plan->id,
            'member_code' => 'M'.str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
            'original_sponsor_id' => $sponsor?->id,
            'status' => 'active',
        ]);
    }

    private function commission(User $owner, User $from, array $attributes = []): FortuneCommission
    {
        static $reading = 1000;

        return FortuneCommission::create(array_merge([
            'user_id' => $owner->id,
            'from_user_id' => $from->id,
            'fortune_reading_id' => ++$reading,
            'level' => 1,
            'commission_type' => 'fixed',
            'commission_rate' => 10,
            'amount' => 10,
            'reading_price' => 39,
            'status' => 'paid',
        ], $attributes));
    }

    private function assertNovaShell($response): void
    {
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/fortune-referral.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html);
        $this->assertStringNotContainsString('tp-card', $html);
        $this->assertStringNotContainsString('class="fas ', $html);
    }

    public function test_commissions_page_renders_in_full_nova_shell_empty_state(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get(route('user.fortune-referral.commissions'));

        $this->assertNovaShell($response);
        $response->assertSee('ยังไม่มีคอมมิชชั่นดูดวง');
    }

    public function test_commissions_page_lists_only_my_rows_and_filters_by_level(): void
    {
        $user = $this->member();
        $friend = $this->member(['name' => 'เพื่อนสายตรง']);
        $grandchild = $this->member(['name' => 'หลานดูดวง']);
        $stranger = $this->member(['name' => 'คนอื่นไม่เกี่ยว']);

        $this->commission($user, $friend, ['amount' => 12.5]);
        $this->commission($user, $grandchild, ['level' => 2, 'amount' => 4, 'status' => 'pending']);
        $this->commission($stranger, $friend, ['amount' => 999]);

        $all = $this->actingAs($user)->get(route('user.fortune-referral.commissions'));
        $this->assertNovaShell($all);
        $all->assertSee('เพื่อนสายตรง')->assertSee('หลานดูดวง')->assertSee('16.50')->assertDontSee('999.00');

        $l2 = $this->actingAs($user)->get(route('user.fortune-referral.commissions', ['level' => '2']));
        $this->assertNovaShell($l2);
        $l2->assertSee('หลานดูดวง')->assertDontSee('เพื่อนสายตรง');
    }

    public function test_recruit_page_renders_with_and_without_mlm_membership(): void
    {
        $user = $this->member();

        $this->assertNovaShell($this->actingAs($user)->get(route('user.fortune-referral.recruit')));

        $other = $this->member();
        $this->mlm($other);
        $response = $this->actingAs($other)->get(route('user.fortune-referral.recruit'));
        $this->assertNovaShell($response);
        $response->assertSee('/fortune/invite/', false);
    }

    public function test_commissions_page_ignores_crafted_level_query(): void
    {
        $user = $this->member();
        $this->commission($user, $this->member(['name' => 'เพื่อนสายตรง']));

        // ?level[]=1 (อาร์เรย์) เคยทำให้ view พังจาก Array to string conversion → ต้องแสดงทุกชั้นตามปกติ
        $array = $this->actingAs($user)->get(route('user.fortune-referral.commissions').'?level[]=1');
        $this->assertNovaShell($array);
        $array->assertSee('เพื่อนสายตรง');

        // ค่าที่ไม่รู้จักถือเป็น "ทั้งหมด" — แท็บทั้งหมดถูกเลือก และไม่ขึ้นข้อความ "ไม่มีรายการในชั้นนี้"
        $unknown = $this->actingAs($this->member())->get(route('user.fortune-referral.commissions', ['level' => 'foo']));
        $this->assertNovaShell($unknown);
        $unknown->assertSee('ชวนเพื่อนมาดูดวง แล้วค่าแนะนำจะแสดงที่นี่')->assertDontSee('ไม่มีรายการในชั้นนี้');
        $this->assertSame(1, substr_count($unknown->getContent(), 'aria-current="page"'));
    }

    public function test_recruit_page_shows_fallback_when_invite_link_cannot_be_generated(): void
    {
        $this->mock(\App\Services\FortuneAffiliateService::class, function ($mock) {
            $mock->shouldReceive('generateReferralLink')->andThrow(new \RuntimeException('สร้างลิงก์ไม่ได้'));
        });

        $response = $this->actingAs($this->member())->get(route('user.fortune-referral.recruit'));

        $this->assertNovaShell($response);
        $response->assertSee('ยังสร้างลิงก์เชิญให้ไม่ได้');
        $response->assertDontSee('คัดลอกลิงก์เชิญแล้ว')->assertDontSee('/fortune/invite/', false);
    }

    public function test_recruit_page_shows_package_rates_when_enabled(): void
    {
        FortuneTellingSetting::create([
            'facebook_app_id' => 'test-app-'.uniqid(),
            'facebook_page_id' => 'test-page-'.uniqid(),
            'is_enabled' => true,
            'deep_reading_price' => 39,
            'celtic_cross_price' => 99,
            'fortune_affiliate_enabled' => true,
            'fortune_level1_commission_type' => 'fixed',
            'fortune_level1_commission_amount' => 10,
            'fortune_level2_enabled' => true,
            'fortune_level2_commission_type' => 'fixed',
            'fortune_level2_commission_amount' => 5,
            'fortune_pkg_rates_enabled' => true,
            'fortune_deep_l1_amount' => 5,
            'fortune_deep_l2_enabled' => false,
            'fortune_celtic_l1_amount' => 10,
            'fortune_celtic_l2_enabled' => true,
            'fortune_celtic_l2_amount' => 2,
        ]);
        FortuneTellingSetting::clearSettingsCache();

        $response = $this->actingAs($this->member())->get(route('user.fortune-referral.recruit'));

        $this->assertNovaShell($response);
        $response->assertSee('Celtic Cross')->assertSee('แพคเกจนี้ไม่มี')->assertSee('จำลองรายได้');
        // Celtic: 10×10×3 + 2×10×5×3 = 300 + 300 = 600 บาท/เดือน
        $response->assertSee('600 บาท');
    }

    public function test_tree_page_renders_without_mlm_membership(): void
    {
        $response = $this->actingAs($this->member())->get(route('user.fortune-referral.tree'));

        $this->assertNovaShell($response);
        $response->assertSee('ยังไม่มีข้อมูลสายงาน');
        $response->assertDontSee('org-chart-viewer.js', false);
    }

    public function test_tree_page_and_data_for_mlm_member(): void
    {
        $user = $this->member(['name' => 'หัวสาย']);
        $root = $this->mlm($user);
        $child = $this->mlm($this->member(['name' => 'ลูกทีม']), $root);
        $this->mlm($this->member(['name' => 'หลานทีม']), $child);
        $this->mlm($this->member(['name' => 'สายอื่น']));

        $page = $this->actingAs($user)->get(route('user.fortune-referral.tree'));
        $this->assertNovaShell($page);
        $page->assertSee('org-chart-viewer.js', false)->assertSee($root->member_code);
        // ข้อความว่าง/ผิดพลาดของผังต้องเป็นคลาสโนวา (เดิมเป็นคลาส Tailwind ที่เชลล์โนวาไม่โหลด → ข้อความไม่มีสไตล์)
        $page->assertSee('id="nvm-fr-tree-empty"', false)->assertSee('id="nvm-fr-tree-error"', false);
        $this->assertStringNotContainsString('text-gray-500 dark:text-gray-400', $page->getContent());
        // ข้อความว่าง/ผิดพลาดแสดงในกล่องแยก ไม่แทนที่ DOM ของผัง → ใช้ viewer ตัวเดียว ไม่สร้างใหม่จนตัวฟังเหตุการณ์ค้างสะสม
        $html = $page->getContent();
        $page->assertSee('id="fortune-tree-message"', false);
        $this->assertStringContainsString('messageBox.replaceChildren(tpl.content.cloneNode(true))', $html);
        $this->assertStringNotContainsString('container.replaceChildren(', $html);
        // สถานะ HTTP ผิดพลาด (เช่น 500) ต้องขึ้นข้อความผิดพลาด ไม่ใช่ "ยังไม่มีข้อมูลสายงาน"
        $this->assertStringContainsString('if (!response.ok)', $html);
        $this->assertStringContainsString("response.status === 404 ? 'nvm-fr-tree-empty' : 'nvm-fr-tree-error'", $html);
        // เปลี่ยนความลึกต้องโหลดใหม่เสมอ (แม้รอบก่อนผิดพลาด) และปิดปุ่มรีเฟรชระหว่างโหลด
        $this->assertStringContainsString("depthSelector.addEventListener('change', loadFortuneTree)", $html);
        $this->assertStringContainsString('btnReload.disabled = true', $html);
        $this->assertStringContainsString('if (seq !== loadSeq) return;', $html);

        $json = $this->actingAs($user)->getJson(route('user.fortune-referral.tree-data', ['depth' => 3]));
        $json->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'หัวสาย')
            ->assertJsonPath('data.children.0.name', 'ลูกทีม')
            ->assertJsonPath('data.children.0.children.0.name', 'หลานทีม');
        $this->assertStringNotContainsString('สายอื่น', $json->getContent());
    }

    public function test_tree_data_returns_404_json_without_mlm_membership(): void
    {
        $this->actingAs($this->member())
            ->getJson(route('user.fortune-referral.tree-data'))
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('user.fortune-referral.commissions'))->assertRedirect();
        $this->get(route('user.fortune-referral.recruit'))->assertRedirect();
        $this->get(route('user.fortune-referral.tree'))->assertRedirect();
    }
}
