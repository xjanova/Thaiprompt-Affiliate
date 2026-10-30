<?php

namespace Tests\Feature;

use App\Models\TpixConfiguration;
use App\Models\User;
use App\Services\TPIX\TpixDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ด่านของ TPIX Wizard
 *
 * เงินที่โอนบนเชนเรียกคืนไม่ได้ และยังไม่มีเครื่อง Deploy จริง
 * ด่านเหล่านี้จึงต้องปิดทางตั้งแต่ก่อนผู้ใช้โอนเงิน
 */
class TpixWizardGuardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->actingAs($this->admin);
    }

    private function openWizard(): void
    {
        config([
            'tpix-pricing.wizard_enabled' => true,
            'tpix-pricing.service_fee_wallet' => '0x'.str_repeat('1', 40),
        ]);
    }

    private function makeConfig(array $attributes = []): TpixConfiguration
    {
        return TpixConfiguration::create(array_merge([
            'user_id' => $this->admin->id,
            'name' => 'เหรียญทดสอบ',
            'slug' => 'test-coin-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'current_step' => 5,
        ], $attributes));
    }

    private function makeDeployedConfig(array $attributes = []): TpixConfiguration
    {
        return $this->makeConfig(array_merge([
            'current_step' => 7,
            'contract_address' => '0x'.str_repeat('a', 40),
            'deploy_tx_hash' => '0x'.str_repeat('b', 64),
            'deployed_at' => now(),
        ], $attributes));
    }

    private function paymentPayload(): array
    {
        return [
            'wallet_address' => '0x'.str_repeat('2', 40),
            'payment_tx_hash' => '0x'.str_repeat('3', 64),
            'payment_amount' => 1000,
            'pricing_breakdown' => ['deployment' => 1000],
            'subtotal_amount' => 1000,
        ];
    }

    /**
     * ปิด Wizard อยู่ ต้องไม่รับข้อมูลการชำระเงิน และไม่เปลี่ยนสถานะอะไรเลย
     */
    public function test_payment_is_refused_while_the_wizard_is_closed(): void
    {
        $config = $this->makeConfig();

        $this->postJson(route('admin.tpix.deployment.payment.process', $config->slug), $this->paymentPayload())
            ->assertStatus(409)
            ->assertJson(['success' => false]);

        $this->assertNull($config->fresh()->payment_tx_hash);
    }

    /**
     * เปิด flag แล้วแต่กระเป๋าเป็น address ศูนย์ ต้องยังปิดอยู่ ไม่งั้นเงินลูกค้าจะถูกเผาทิ้ง
     */
    public function test_a_zero_fee_wallet_keeps_payment_closed_even_with_the_flag_on(): void
    {
        config([
            'tpix-pricing.wizard_enabled' => true,
            'tpix-pricing.service_fee_wallet' => '0x'.str_repeat('0', 40),
        ]);

        $config = $this->makeConfig();

        $this->postJson(route('admin.tpix.deployment.payment.process', $config->slug), $this->paymentPayload())
            ->assertStatus(409)
            ->assertJsonFragment(['message' => 'ยังไม่ได้ตั้งกระเป๋ารับค่าบริการ จึงยังไม่รับชำระเงิน']);
    }

    /**
     * กระเป๋าที่รูปแบบไม่ถูกต้องต้องถูกกันเหมือน address ศูนย์
     */
    public function test_a_malformed_fee_wallet_keeps_payment_closed(): void
    {
        config([
            'tpix-pricing.wizard_enabled' => true,
            'tpix-pricing.service_fee_wallet' => 'not-a-wallet',
        ]);

        $this->assertNotNull(app(TpixDeploymentService::class)->deploymentBlockedReason());
    }

    /**
     * เปิดครบแล้วต้องรับชำระได้ตามเดิม ด่านนี้ต้องไม่ปิดตายถาวร
     */
    public function test_payment_is_accepted_once_the_wizard_is_open(): void
    {
        $this->openWizard();
        $config = $this->makeConfig();

        $this->postJson(route('admin.tpix.deployment.payment.process', $config->slug), $this->paymentPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('processing', $config->fresh()->payment_status);
    }

    /**
     * ปุ่มจ่ายต้องหายตั้งแต่หน้านี้ เพราะหน้าเว็บโอนเงินบนเชนก่อนแล้วค่อยแจ้งเซิร์ฟเวอร์
     */
    public function test_the_pay_button_is_absent_while_closed_and_present_once_open(): void
    {
        $config = $this->makeConfig();
        $page = route('admin.tpix.deployment.payment', $config->slug);

        $this->get($page)
            ->assertOk()
            ->assertSee('ระบบ Deploy เหรียญยังไม่เปิดใช้งาน')
            ->assertDontSee('@click="processPayment()"', false);

        $this->openWizard();

        $this->get($page)
            ->assertOk()
            ->assertSee('@click="processPayment()"', false);
    }

    /**
     * หน้าชำระเงินก่อนถึงขั้นตอน ต้อง redirect กลับ ไม่ใช่ 500 จากชนิดข้อมูลที่คืนผิด
     */
    public function test_the_payment_page_redirects_before_step_four(): void
    {
        $config = $this->makeConfig(['current_step' => 3]);

        $this->get(route('admin.tpix.deployment.payment', $config->slug))->assertRedirect();
    }

    /**
     * Step 5 ต้องซ่อนปุ่ม Deploy และบอกเหตุผล เมื่อ Wizard ยังปิด
     */
    public function test_step5_replaces_the_deploy_button_with_the_reason_while_closed(): void
    {
        $config = $this->makeConfig();

        $this->mock(TpixDeploymentService::class, function ($mock) {
            $mock->shouldReceive('deploymentBlockedReason')->andReturn('ระบบปิดอยู่');
            $mock->shouldReceive('estimateDeploymentCost')->andReturn([
                'gas_price_gwei' => 7.5,
                'estimated_gas' => 2500000,
                'estimated_cost_tpix' => 0.01875,
                'estimated_cost_formatted' => '0.01875000 TPIX',
            ]);
        });

        $this->get(route('admin.tpix.deployment.step5', $config->slug))
            ->assertOk()
            ->assertSee('ระบบปิดอยู่')
            ->assertDontSee('@click="deployContract()"', false);
    }

    /**
     * fetch ใน Wizard ต้องขอ JSON ไม่งั้นเซิร์ฟเวอร์ตอบ redirect แล้ว .json() ล้ม
     */
    public function test_wizard_fetches_ask_for_json(): void
    {
        $config = $this->makeDeployedConfig();

        foreach (['step6', 'step7'] as $step) {
            $this->get(route('admin.tpix.deployment.'.$step, $config->slug))
                ->assertOk()
                ->assertSee("'Accept': 'application/json'", false);
        }
    }

    /**
     * Deploy ตอนปิดอยู่ต้องตอบ JSON สำหรับ fetch และไม่แตะแถวข้อมูล
     */
    public function test_deploy_answers_json_with_the_reason_while_closed(): void
    {
        $config = $this->makeConfig();

        $this->postJson(route('admin.tpix.deployment.step5.deploy', $config->slug))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'ระบบ Deploy เหรียญยังไม่เปิดใช้งาน จึงยังไม่รับชำระเงิน']);

        $this->assertSame('draft', $config->fresh()->status);
    }

    /**
     * ฟอร์มปกติที่ไม่ได้ขอ JSON ต้องได้ redirect พร้อมข้อความ
     */
    public function test_deploy_redirects_with_the_reason_for_plain_forms(): void
    {
        $config = $this->makeConfig();

        $this->from(route('admin.tpix.deployment.index'))
            ->post(route('admin.tpix.deployment.step5.deploy', $config->slug))
            ->assertRedirect(route('admin.tpix.deployment.index'))
            ->assertSessionHas('error');
    }

    /**
     * เครื่อง Deploy พังแบบ Error ต้องตอบข้อความกลาง ห้ามส่งรายละเอียดภายในให้ผู้ใช้
     */
    public function test_a_deploy_engine_error_is_reported_without_internal_details(): void
    {
        $config = $this->makeConfig();

        $this->mock(TpixDeploymentService::class, function ($mock) {
            $mock->shouldReceive('deploymentBlockedReason')->andReturn(null);
            $mock->shouldReceive('deployContract')->andThrow(new \Error('Call to undefined method deployContract'));
        });

        $this->postJson(route('admin.tpix.deployment.step5.deploy', $config->slug))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Deploy ล้มเหลว กรุณาตรวจสอบ log ของระบบ']);
    }

    /**
     * ปุ่มอื่นใน Wizard ต้องตอบ JSON เหมือนกัน
     */
    public function test_other_wizard_actions_answer_json(): void
    {
        $config = $this->makeConfig();

        $this->postJson(route('admin.tpix.deployment.step5.verify', $config->slug))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'กรุณา Deploy Contract ก่อน']);

        $this->postJson(route('admin.tpix.deployment.step7.submit-cmc', $config->slug))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'กรุณา Deploy Contract ก่อน']);

        $this->postJson(route('admin.tpix.deployment.step7.submit-coingecko', $config->slug))
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'กรุณา Deploy Contract ก่อน']);

        $fresh = $config->fresh();
        $this->assertFalse((bool) $fresh->cmc_submitted);
        $this->assertFalse((bool) $fresh->coingecko_submitted);
    }

    /**
     * hash ที่ผู้ใช้ส่งมาเปล่า ๆ ต้องยืนยันการชำระไม่ได้ (เดิม mock ตอบ verified เสมอ)
     */
    public function test_a_bare_transaction_hash_no_longer_completes_payment(): void
    {
        $config = $this->makeConfig([
            'payment_tx_hash' => '0x'.str_repeat('3', 64),
            'payment_status' => 'processing',
        ]);

        $this->postJson(route('admin.tpix.deployment.payment.verify', $config->slug))
            ->assertJson(['success' => false, 'data' => ['verified' => false]]);

        $fresh = $config->fresh();
        $this->assertSame('processing', $fresh->payment_status);
        $this->assertNull($fresh->payment_verified_at);
    }

    /**
     * ยังไม่ Deploy ต้องจบขั้นตอนไม่ได้ ไม่งั้นรายการขึ้น listed ทั้งที่ไม่มี contract
     */
    public function test_completing_is_refused_until_the_contract_is_deployed(): void
    {
        $config = $this->makeConfig(['current_step' => 7]);

        $this->post(route('admin.tpix.deployment.step7.complete', $config->slug))
            ->assertRedirect(route('admin.tpix.deployment.step5', $config->slug))
            ->assertSessionHas('error');

        $this->assertSame('draft', $config->fresh()->status);
    }

    /**
     * จบขั้นตอนต้องบันทึก checklist แสดงกลับในหน้าเดิม และกดซ้ำไม่บันทึก log ซ้ำ
     */
    public function test_completing_saves_the_checklist_and_logs_once(): void
    {
        $config = $this->makeDeployedConfig(['marketing_materials' => ['logo' => 'logo.png']]);
        $url = route('admin.tpix.deployment.step7.complete', $config->slug);

        $this->post($url, ['marketing_checklist' => ['press_release', 'listing_sites']])
            ->assertRedirect(route('admin.tpix.deployment.index'));

        $fresh = $config->fresh();
        $this->assertSame('listed', $fresh->status);
        $this->assertSame('logo.png', $fresh->marketing_materials['logo']);
        $this->assertSame(['press_release', 'listing_sites'], $fresh->marketing_materials['checklist']);

        $html = $this->get(route('admin.tpix.deployment.step7', $config->slug))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/value="press_release"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="listing_sites"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="community_campaign"\s+checked/', $html);

        $this->post($url)->assertRedirect(route('admin.tpix.deployment.index'));

        $logged = collect($config->fresh()->deployment_logs)->where('action', 'deployment_completed');
        $this->assertCount(1, $logged);
    }

    /**
     * ค่าใน checklist ที่หน้าเว็บไม่มี ต้องถูกปฏิเสธ
     */
    public function test_completing_rejects_unknown_checklist_values(): void
    {
        $config = $this->makeDeployedConfig();

        $this->post(route('admin.tpix.deployment.step7.complete', $config->slug), ['marketing_checklist' => ['anything']])
            ->assertSessionHasErrors('marketing_checklist.0');

        $this->assertSame('draft', $config->fresh()->status);
    }
}
