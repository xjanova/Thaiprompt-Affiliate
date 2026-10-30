<?php

namespace Tests\Feature;

use App\Models\ChatbotPlatformIntegration;
use App\Models\FoodCertification;
use App\Models\FoodProduct;
use App\Models\KycVerification;
use App\Models\LineBroadcastMessage;
use App\Models\LineRegistrationSession;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceBookingDispute;
use App\Models\ServiceBookingPenalty;
use App\Models\ServiceCategory;
use App\Models\SoftwareQuotation;
use App\Models\TpixConfiguration;
use App\Models\TPIXStake;
use App\Models\TPIXToken;
use App\Models\User;
use App\Notifications\TPIX\AddressFrozenNotification;
use App\Notifications\TPIX\StakeUnlockedNotification;
use App\Notifications\TPIX\TokenRejectedNotification;
use App\Services\NotificationService;
use App\Services\TPIX\TpixDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ตรวจปลายทางของจุดที่เคยเรียก route ที่ไม่มีอยู่จริง ด้วยข้อมูลจำลองที่ตรงเงื่อนไขจริง
 *
 * 🚨 กิ่งพวกนี้ไม่ถูกรันบน DB ว่าง (ปุ่มค่าปรับจะโผล่เมื่อมีค่าปรับค้าง, Step 5 ต้องมี config ฯลฯ)
 *    เทสต์ทุกตัวจึงสร้างแถวข้อมูลที่ทำให้กิ่งนั้นทำงานก่อนเสมอ
 *
 * รายการชื่อทั้งหมดตรวจโดย NamedRouteCallersTest — ไฟล์นี้ตรวจว่าเปลี่ยนชื่อแล้วใช้งานได้จริง
 */
class NamedRouteDestinationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
    }

    /**
     * การจองบริการ 1 แถว สำหรับข้อพิพาทและค่าปรับ
     */
    private function makeBooking(User $customer): int
    {
        $categoryId = DB::table('service_categories')->insertGetId([
            'name' => 'ทำความสะอาด', 'slug' => 'cleaning-'.Str::random(6), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceId = DB::table('services')->insertGetId([
            'category_id' => $categoryId, 'owner_id' => User::factory()->create()->id, 'name' => 'ทำความสะอาดบ้าน',
            'slug' => 'home-'.Str::random(6), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('service_bookings')->insertGetId([
            'booking_number' => 'SB-'.Str::random(8),
            'user_id' => $customer->id,
            'service_id' => $serviceId,
            'scheduled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makePenalty(User $customer): ServiceBookingPenalty
    {
        return ServiceBookingPenalty::create([
            'service_booking_id' => $this->makeBooking($customer),
            'user_id' => $customer->id,
            'penalized_type' => 'user',
            'penalty_type' => 'no_show',
            'penalty_amount' => 150,
            'status' => 'pending',
            'reason' => 'ลูกค้าไม่อยู่ตามนัด',
        ]);
    }

    private function makeDispute(User $reporter): ServiceBookingDispute
    {
        return ServiceBookingDispute::create([
            'dispute_number' => 'DSP-'.Str::random(8),
            'service_booking_id' => $this->makeBooking($reporter),
            'reporter_user_id' => $reporter->id,
            'reporter_type' => 'user',
            'accused_type' => 'provider',
            'dispute_type' => 'no_show',
            'title' => 'ผู้ให้บริการไม่มาตามนัด',
            'description' => 'รอตั้งแต่เช้าแต่ไม่มีใครมา',
            'status' => 'pending',
            'priority' => 'medium',
        ]);
    }

    private function makeTpixConfig(User $owner, int $step): TpixConfiguration
    {
        return TpixConfiguration::create([
            'user_id' => $owner->id,
            'name' => 'เหรียญทดสอบ',
            'slug' => 'test-coin-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'current_step' => $step,
        ]);
    }

    /**
     * สมัครผ่าน LINE เสร็จ ต้องล็อกอินแล้วไปหน้าโปรไฟล์ (เดิม 500 ทันทีหลังสมัคร)
     */
    public function test_line_signup_completion_redirects_to_the_profile_page(): void
    {
        $user = User::factory()->create();
        $payload = json_encode(['user_id' => $user->id, 'session_token' => 'tok', 'expires_at' => now()->addMinutes(15)->timestamp]);
        $token = base64_encode($payload.'.'.hash_hmac('sha256', $payload, config('app.key')));

        $this->get(route('line.registration.complete', $token))
            ->assertRedirect(route('user.profile'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * หน้า polling ของการสมัครผ่าน LINE ต้องได้ลิงก์ไปหน้าโปรไฟล์
     */
    public function test_completed_line_session_reports_the_profile_url(): void
    {
        $session = LineRegistrationSession::createSession();
        $session->forceFill(['status' => LineRegistrationSession::STATUS_COMPLETED, 'user_id' => User::factory()->create()->id])->save();

        $this->assertSame(route('user.profile'), $session->fresh()->toApiResponse()['redirect_url']);
    }

    /**
     * แจ้งเตือนอนุมัติ KYC ต้องถูกบันทึกจริง (เดิม observer กลืน exception ไว้ ลูกค้าไม่เคยได้แจ้งเตือน)
     */
    public function test_approving_kyc_creates_the_notification(): void
    {
        $user = User::factory()->create();
        $kyc = KycVerification::create(['user_id' => $user->id, 'status' => 'pending']);

        app(NotificationService::class)->notifyKycApproved($user, $kyc);

        $this->assertSame(route('user.profile'), Notification::where('user_id', $user->id)->latest('id')->value('action_url'));
    }

    /**
     * แดชบอร์ดกันกลั่นแกล้งที่มีค่าปรับค้าง ต้องเปิดได้ และปุ่มเรียกเก็บใช้งานได้
     */
    public function test_anti_abuse_dashboard_with_a_pending_penalty_renders_and_charges(): void
    {
        $admin = $this->admin();
        $penalty = $this->makePenalty(User::factory()->create());

        $this->actingAs($admin)->get(route('admin.anti-abuse.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.anti-abuse.penalties.charge', $penalty), false)
            ->assertSee(route('admin.anti-abuse.penalties', ['status' => 'pending']), false);

        $this->post(route('admin.anti-abuse.penalties.charge', $penalty))
            ->assertRedirect(route('admin.anti-abuse.penalties'));

        $this->assertSame('charged', $penalty->fresh()->status);
    }

    /**
     * หน้าข้อพิพาทที่ยังเปิดอยู่ต้องแสดงฟอร์ม และฟอร์มต้องส่งเป็น POST ตาม route จริง
     */
    public function test_open_dispute_page_renders_and_status_form_submits(): void
    {
        $admin = $this->admin();
        $dispute = $this->makeDispute(User::factory()->create());

        $response = $this->actingAs($admin)->get(route('admin.anti-abuse.disputes.show', $dispute));

        $response->assertOk()->assertSee(route('admin.anti-abuse.disputes.status', $dispute), false);
        $this->assertStringNotContainsString('name="_method"', $response->getContent());

        $this->post(route('admin.anti-abuse.disputes.status', $dispute), ['status' => 'investigating'])
            ->assertSessionHasNoErrors();

        $this->assertSame('investigating', $dispute->fresh()->status);
    }

    /**
     * Broadcast ฉบับร่างเปิดหน้ารายละเอียดได้ (ปุ่ม Edit เดิมเรียก route ที่ไม่มี)
     */
    public function test_draft_broadcast_page_renders(): void
    {
        $admin = $this->admin();
        $broadcast = LineBroadcastMessage::create([
            'name' => 'โปรต้นเดือน', 'message_type' => 'text', 'content' => 'สวัสดีค่ะ',
            'target_type' => 'all', 'status' => 'draft', 'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.line-bot.broadcast.show', $broadcast->id))
            ->assertOk()
            ->assertDontSee('Edit Broadcast');
    }

    /**
     * Step 5 ใช้ค่า gas ที่คำนวณฝั่งเซิร์ฟเวอร์ ไม่ยิง endpoint ที่ไม่มี
     */
    public function test_tpix_step5_uses_the_server_side_gas_estimate(): void
    {
        $admin = $this->admin();
        $config = $this->makeTpixConfig($admin, 5);

        $this->mock(TpixDeploymentService::class)->shouldReceive('estimateDeploymentCost')->andReturn([
            'gas_price_gwei' => 7.5,
            'estimated_gas' => 2500000,
            'estimated_cost_tpix' => 0.01875,
            'estimated_cost_formatted' => '0.01875000 TPIX',
        ]);

        $this->actingAs($admin)->get(route('admin.tpix.deployment.step5', $config->slug))
            ->assertOk()
            ->assertSee('2,500,000')
            ->assertDontSee('estimate-gas')
            ->assertDontSee('estimateGas()', false);
    }

    /**
     * คำนวณ gas ไม่ได้ ต้องบอกผู้ใช้ และปุ่ม Deploy ต้องถูกปิดไว้
     */
    public function test_tpix_step5_keeps_deploy_disabled_when_the_estimate_fails(): void
    {
        $admin = $this->admin();
        $config = $this->makeTpixConfig($admin, 5);

        $this->mock(TpixDeploymentService::class)->shouldReceive('estimateDeploymentCost')->andReturn(['error' => 'RPC down']);

        $this->actingAs($admin)->get(route('admin.tpix.deployment.step5', $config->slug))
            ->assertOk()
            ->assertSee('คำนวณค่า Gas ไม่สำเร็จ')
            ->assertSee('estimatedGas: null', false)
            ->assertDontSee('RPC down');
    }

    /**
     * Step 6, Step 7 และหน้ายืนยันชำระเงินของ wizard ต้องเปิดได้ และ Step 7 ปิดงานได้จริง
     */
    public function test_tpix_wizard_pages_render_and_step7_completes(): void
    {
        $admin = $this->admin();
        $config = $this->makeTpixConfig($admin, 7);

        $this->actingAs($admin);
        $this->get(route('admin.tpix.deployment.step6', $config->slug))->assertOk()->assertDontSee('addLiquidity');
        $this->get(route('admin.tpix.deployment.step7', $config->slug))
            ->assertOk()
            ->assertSee(route('admin.tpix.deployment.step7.complete', $config->slug), false);
        $this->get(route('admin.tpix.deployment.payment', $config->slug))
            ->assertOk()
            ->assertSee(route('admin.tpix.deployment.step5', $config->slug), false)
            ->assertSee(route('admin.tpix.deployment.step4', $config->slug), false);

        $this->post(route('admin.tpix.deployment.step7.complete', $config->slug))
            ->assertRedirect(route('admin.tpix.deployment.index'));

        $this->assertSame('listed', $config->fresh()->status);
    }

    /**
     * ข้อมูล Food Passport ต้อง serialize ได้ (เดิม passport_url อยู่ใน $appends = พังทุกครั้ง)
     */
    public function test_food_passport_urls_point_at_the_public_api(): void
    {
        $product = (new FoodProduct)->forceFill(['id' => 7, 'food_passport_id' => 'FP-0001']);
        $certification = (new FoodCertification)->forceFill(['certificate_number' => 'CERT-001']);

        $this->assertSame(route('api.food-passport.products.show', 7), $product->toArray()['passport_url']);
        $this->assertSame(route('api.food-passport.scan', 'FP-0001'), $product->toArray()['qr_scan_url']);
        $this->assertSame(route('api.food-passport.certifications.verify', 'CERT-001'), $certification->verification_url);
    }

    /**
     * webhook ของ chatbot ที่ยังไม่ตั้งค่า URL เอง ต้องได้ URL ของ route จริง
     */
    public function test_chatbot_integration_falls_back_to_the_real_webhook_route(): void
    {
        $integration = (new ChatbotPlatformIntegration)->forceFill(['id' => 9, 'platform_type' => 'line']);

        $this->assertStringEndsWith('/api/webhook/chatbot/line/9', $integration->getWebhookUrl());
    }

    public function test_service_urls_use_the_id_bound_user_routes(): void
    {
        $service = (new Service)->forceFill(['id' => 3, 'slug' => 'home-cleaning']);
        $category = (new ServiceCategory)->forceFill(['id' => 4, 'slug' => 'cleaning']);

        $this->assertStringEndsWith('/user/services/3', $service->getUrl());
        $this->assertStringEndsWith('/user/services/category/4', $category->getUrl());
    }

    /**
     * อีเมล TPIX ต้องสร้างได้ — StakeUnlocked ถูกส่งจาก job จริงเมื่อ stake ปลดล็อก
     */
    public function test_tpix_mail_notifications_build_with_real_links(): void
    {
        $user = User::factory()->make(['name' => 'สมชาย']);
        $token = (new TPIXToken)->forceFill(['id' => 5, 'name' => 'Test', 'symbol' => 'TST']);
        $stake = $this->createPartialMock(TPIXStake::class, ['getUnclaimedRewards']);
        $stake->method('getUnclaimedRewards')->willReturn('1.50000000');
        $stake->forceFill(['amount' => 10]);
        $stake->setRelation('pool', (object) ['name' => 'Flexible']);

        $this->assertSame(route('contact'), (new AddressFrozenNotification($token, '0xabc', 'ตรวจสอบ'))->toMail($user)->actionUrl);
        $this->assertSame(route('user.tokens.show', 5), (new TokenRejectedNotification($token, 'ไม่ผ่าน'))->toMail($user)->actionUrl);
        $this->assertSame(route('user.staking.index'), (new StakeUnlockedNotification($stake))->toMail($user)->actionUrl);
    }

    /**
     * เมนูมือถือแบบแอดมินต้องเรนเดอร์ได้
     */
    public function test_admin_mobile_navigation_links_to_unified_reports(): void
    {
        $this->actingAs($this->admin());

        foreach (['mobile-bottom-navigation', 'mobile-quick-actions'] as $component) {
            $html = Blade::render('<x-'.$component.' type="admin" />');

            $this->assertStringContainsString(route('admin.unified-reports.index'), $html);
        }
    }

    /**
     * หน้ารายการของระบบโรงแรมต้องเปิดได้ และลิงก์สร้างโรงแรมพาไปฟอร์มแอดมินที่รับข้อมูลได้จริง
     */
    public function test_hotel_admin_lists_render_without_the_dead_create_forms(): void
    {
        // ต้องตั้ง is_hotel_admin ด้วย: HotelAdminMiddleware เช็ค $user->is_admin ซึ่งไม่มีอยู่จริงใน users
        $this->actingAs(User::factory()->create([
            'role' => 'admin', 'is_super_admin' => true, 'is_hotel_admin' => true, 'managed_hotel_id' => 1,
        ]));

        $this->get(route('hotel-admin.hotels.index'))->assertOk()->assertSee(route('admin.hotels.create'), false);
        $this->get(route('hotel-admin.bookings.index'))->assertOk()->assertDontSee('New Booking');
    }

    /**
     * แปลงใบเสนอราคาเป็นออเดอร์แล้วต้องไปหน้าออเดอร์ที่เพิ่งสร้าง
     *
     * 🚨 convertToOrder ส่ง payment_method = 'pending' ซึ่งไม่อยู่ใน enum ของ orders จึงพังก่อนถึง redirect
     *    (ยังไม่แก้ เพราะเป็นเรื่องการจ่ายเงิน รายงานไว้ใน PR) เทสต์นี้ล้างค่าให้เพื่อไปถึงบรรทัด redirect
     */
    public function test_converting_a_quotation_redirects_to_the_new_order(): void
    {
        Order::creating(fn (Order $order) => $order->payment_method = null);

        $user = User::factory()->create();
        $quotation = SoftwareQuotation::create([
            'quotation_number' => 'QT-'.Str::random(6),
            'user_id' => $user->id,
            'customer_name' => 'สมชาย',
            'customer_email' => 'somchai@example.com',
            'subtotal' => 1000,
            'tax_amount' => 70,
            'total_amount' => 1070,
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user)->post(route('quotations.convert', $quotation));

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect(route('orders.show', $order->id));
    }
}
