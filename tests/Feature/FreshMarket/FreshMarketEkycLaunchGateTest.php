<?php

namespace Tests\Feature\FreshMarket;

use App\Exceptions\FreshMarketException;
use App\Http\Middleware\EnsureEkycVerified;
use App\Models\FreshMarketConversation;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\PosDevice;
use App\Models\PosSession;
use App\Models\PosTransaction;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Ekyc\EkycGate;
use App\Services\FreshMarketCartService;
use App\Services\FreshMarketChannelManager;
use App\Services\FreshMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Shop\Concerns\BuildsShopFixtures;
use Tests\TestCase;

/**
 * 🪪 ด่านเปิดตัว (2026-10-13) — ต้องใช้ MySQL (RefreshDatabase)
 *
 * 1. ผู้ซื้อต้องยืนยันตัวตน (eKYC) ก่อนสั่งซื้อทุกช่องทาง: เว็บ / ตะกร้าเว็บ / API (ไม่ส่ง header ก็โดน) / LINE
 *    ยกเว้น POS หน้าร้าน (ลูกค้า walk-in)
 * 2. ร้านตลาดสดสมัครได้ทุกช่องทางโดยไม่ต้องยืนยันตัวตนก่อน แต่ผู้ซื้อมองไม่เห็น/สั่งไม่ได้จนเจ้าของร้านยืนยันตัวตน
 * 3. ร้านที่ถูกระงับ: รับ/จัดการออเดอร์ + แก้สินค้าไม่ได้ · ผู้ซื้อยืนยันรับของ/ระบบปิดเอง = ไม่โอนเงินให้ร้าน (รอแอดมิน)
 */
class FreshMarketEkycLaunchGateTest extends TestCase
{
    use BuildsShopFixtures;
    use RefreshDatabase;

    protected FreshMarketService $service;

    /** ผู้ซื้อที่ยังไม่ยืนยันตัวตน */
    protected User $buyer;

    /** ผู้ซื้อที่ยืนยันตัวตนแล้ว */
    protected User $verifiedBuyer;

    protected User $sellerUser;

    protected FreshMarketSeller $seller;

    protected FreshMarketListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Cache::flush();
        FreshMarketSetting::clearCache();

        config([
            'ekyc.enforce' => true,
            'ekyc.min_build' => 44,
            'profile_photo.required' => false,
            'turnstile.enabled' => false,
        ]);

        FreshMarketSetting::create([
            'brand_name' => 'ตลาดสดทดสอบ',
            'ai_provider' => 'groq',
            'ai_model' => 'llama-3.3-70b-versatile',
            'platform_fee_percentage' => 10,
            'fee_mode' => 'percentage',
            'escrow_enabled' => true,
            'cod_enabled' => true,
            'rider_enabled' => false,
            'cashback_enabled' => false,
            'mlm_commission_enabled' => false,
        ]);
        FreshMarketSetting::clearCache();

        Setting::set('pricing.fresh_market_gp_rate', '10', 'float', 'pricing');
        Setting::set('pricing.gp_free', '0', 'boolean', 'pricing');
        Setting::set('fresh_market.auto_approve_sellers', '1', 'boolean', 'fresh_market');
        Setting::set('fresh_market.auto_complete_hours', '24', 'integer', 'fresh_market');

        $this->buyer = User::factory()->create(['name' => 'ผู้ซื้อยังไม่ยืนยัน']);
        $this->verifiedBuyer = $this->kycApproved(User::factory()->create(['name' => 'ผู้ซื้อยืนยันแล้ว']));
        $this->sellerUser = $this->kycApproved(User::factory()->create(['name' => 'เจ้าของร้าน']));

        foreach ([$this->buyer, $this->verifiedBuyer] as $user) {
            Wallet::create(['user_id' => $user->id, 'balance' => 1000, 'currency' => 'THB', 'status' => 'active']);
        }
        Wallet::create(['user_id' => $this->sellerUser->id, 'balance' => 0, 'currency' => 'THB', 'status' => 'active']);

        $this->seller = FreshMarketSeller::create([
            'user_id' => $this->sellerUser->id,
            'shop_name' => 'ร้านผักยืนยันแล้ว',
            'phone' => '0812345678',
            'address' => 'ตลาดทดสอบ',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'is_active' => true,
            'is_suspended' => false,
            'is_verified' => true,
            'subscription_type' => 'free',
        ]);

        $this->listing = $this->makeListing($this->seller, 'ผักบุ้งจีน');

        $this->service = new FreshMarketService;
    }

    // =====================================================
    // 1. ผู้ซื้อ: eKYC ทุกช่องทาง
    // =====================================================

    public function test_web_fresh_market_order_requires_buyer_ekyc(): void
    {
        $payload = [
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ];

        // ยังไม่ยืนยัน → ไม่สร้างออเดอร์ + ข้อความไทยพร้อมลิงก์ดาวน์โหลดแอป + ธงแสดงแถบปุ่ม
        $this->actingAs($this->buyer)
            ->from(route('taladsod.listing', $this->listing->slug))
            ->post(route('taladsod.order.store'), $payload)
            ->assertRedirect(route('taladsod.listing', $this->listing->slug))
            ->assertSessionHas(EkycGate::FLASH_KEY, true)
            ->assertSessionHas('error', EkycGate::buyerWebMessage());
        $this->assertStringContainsString('/app/download', EkycGate::buyerWebMessage());
        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());
        $this->assertSame(1000.0, $this->balanceOf($this->buyer));

        // ยืนยันแล้ว → สั่งได้ตามปกติ
        $this->actingAs($this->verifiedBuyer)
            ->post(route('taladsod.order.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionMissing(EkycGate::FLASH_KEY);
        $this->assertSame(1, FreshMarketOrder::where('buyer_id', $this->verifiedBuyer->id)->count());

        // ปิดสวิตช์ ekyc.enforce (ฉุกเฉิน) → กลับเป็นแบบเดิม
        config(['ekyc.enforce' => false]);
        $this->actingAs($this->buyer)->post(route('taladsod.order.store'), $payload);
        $this->assertSame(1, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());
    }

    public function test_web_cart_checkout_requires_buyer_ekyc(): void
    {
        app(FreshMarketCartService::class)->addItem($this->buyer, $this->listing->id, 1);

        $this->actingAs($this->buyer)
            ->postJson(route('taladsod.checkout.store'), [
                'seller_id' => $this->seller->id,
                'delivery_type' => 'pickup',
                'payment_method' => 'wallet',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'KYC_REQUIRED')
            ->assertJsonPath('app_download_url', EkycGate::downloadUrl());

        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());
    }

    public function test_api_orders_without_app_header_require_ekyc(): void
    {
        Sanctum::actingAs($this->buyer);
        $body = [
            'listing_id' => $this->listing->id,
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ];

        // ไม่ส่ง X-App-Build (เว็บ/สคริปต์/แอปเก่ามาก) → ถูกบล็อกเหมือนแอปใหม่ (เดิมหลุดด่าน)
        $this->postJson('/api/v1/fresh-market/orders', $body)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'KYC_REQUIRED')
            ->assertJsonPath('message', EnsureEkycVerified::MESSAGE)
            ->assertJsonPath('data.kyc_status', 'none');

        $this->postJson('/api/v1/cart/checkout', ['payment_method' => 'wallet'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'KYC_REQUIRED');

        // แอปรุ่นเก่ากว่า min_build (ยังไม่มีหน้า eKYC) → บอกให้อัปเดตแอปก่อน
        $this->postJson('/api/v1/fresh-market/orders', $body, ['X-App-Build' => '43'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'KYC_REQUIRED')
            ->assertJsonPath('message', EkycGate::UPDATE_APP_MESSAGE);

        $this->assertSame(0, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());

        // ยืนยันแล้ว → สั่งได้ (ไม่ต้องส่ง header)
        Sanctum::actingAs($this->verifiedBuyer);
        $this->postJson('/api/v1/fresh-market/orders', $body)
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_service_layer_refuses_unverified_buyer_on_every_channel(): void
    {
        foreach (['web', 'api', 'line'] as $channel) {
            try {
                $this->service->createOrder($this->buyer, $this->listing->fresh(), [
                    'quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'wallet', 'channel' => $channel,
                ]);
                $this->fail("ช่องทาง {$channel} ต้องถูกบล็อก");
            } catch (FreshMarketException $e) {
                $this->assertSame('KYC_REQUIRED', $e->errorCode());
                $this->assertSame(EkycGate::BUYER_MESSAGE, $e->getMessage());
            }
        }

        $this->assertSame(0, FreshMarketOrder::count());
        $this->assertSame(10, (int) $this->listing->fresh()->quantity_available, 'ไม่ตัดสต็อกเมื่อถูกบล็อก');
    }

    public function test_line_order_path_replies_with_app_download_link_for_unverified_buyer(): void
    {
        $lineUserId = 'U'.str_repeat('a', 32);
        $this->buyer->forceFill(['line_user_id' => $lineUserId])->save();

        $conversation = FreshMarketConversation::getOrCreate($lineUserId);
        $conversation->update(['user_id' => $this->buyer->id]);
        $manager = new FreshMarketChannelManager;

        // เลือกสินค้า (postback) → บอกตั้งแต่ขั้นแรก ไม่ให้เสียเวลากรอกจำนวน/ที่อยู่
        $reply = $this->invoke($manager, 'handleOrderSelectPostback', [$lineUserId, ['listing_id' => $this->listing->id], $conversation]);
        $this->assertStringContainsString(EkycGate::BUYER_MESSAGE, $reply['text']);
        $this->assertStringContainsString(EkycGate::downloadUrl(), $reply['text']);

        // ข้ามไปยืนยันคำสั่งซื้อตรงๆ (context ค้างจากก่อนเปิดด่าน) → ด่านในเซอร์วิสยังกันไว้
        $this->putInOrderReview($conversation);
        $reply = $this->invoke($manager, 'createOrderFromContext', [$conversation->fresh()]);
        $this->assertStringContainsString(EkycGate::BUYER_MESSAGE, $reply['text']);
        $this->assertStringContainsString('/app/download', $reply['text']);

        $this->assertSame(0, FreshMarketOrder::count());
        // ตอบผ่าน reply ของ event เท่านั้น — ระหว่างตัดสินไม่มีการยิง LINE API (ไม่มี push)
        Http::assertNothingSent();

        // ยืนยันตัวตนแล้ว → สั่งผ่าน LINE ได้
        $this->kycApproved($this->buyer);
        $this->putInOrderReview($conversation);
        $reply = $this->invoke($manager, 'createOrderFromContext', [$conversation->fresh()]);
        $this->assertStringContainsString('สั่งซื้อสำเร็จ', $reply['text']);
        $this->assertSame(1, FreshMarketOrder::where('buyer_id', $this->buyer->id)->count());
    }

    public function test_web_shop_checkout_requires_buyer_ekyc(): void
    {
        $this->actingAs($this->buyer)
            ->from(route('checkout.index'))
            ->post(route('checkout.process'), ['payment_method' => 'wallet'])
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHas(EkycGate::FLASH_KEY, true)
            ->assertSessionHas('error', EkycGate::buyerWebMessage());

        // เส้นเดิม (บัตร/โอน) ก็โดนด่านเดียวกัน
        $this->actingAs($this->buyer)
            ->post(route('checkout.process'), ['payment_method' => 'bank_transfer'])
            ->assertSessionHas(EkycGate::FLASH_KEY, true);
    }

    public function test_pos_cashier_checkout_is_not_gated_by_buyer_ekyc(): void
    {
        [$posSeller, $store] = $this->makeSellerWithStore();
        $this->assertFalse($posSeller->isKycVerified());

        $product = $this->makeProduct($posSeller, $store, ['price' => 100, 'stock_quantity' => 5]);
        $device = PosDevice::create([
            'store_id' => $store->id, 'device_name' => 'เครื่องหน้าร้าน', 'device_type' => 'web',
            'subscription_status' => 'active', 'is_active' => true, 'is_online' => true,
        ]);
        $session = PosSession::create([
            'pos_device_id' => $device->id, 'user_id' => $posSeller->id, 'status' => 'open',
            'opened_at' => now()->subHour(), 'opening_cash' => 500,
        ]);

        $this->actingAs($posSeller)->postJson(route('pos.checkout'), [
            'device_id' => $device->id,
            'session_id' => $session->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 100]],
            'payment_method' => 'cash',
            'amount_paid' => 100,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, PosTransaction::where('pos_device_id', $device->id)->count());
    }

    // =====================================================
    // 2. ร้านตลาดสด: สมัครได้ แต่ขายได้เมื่อยืนยันตัวตน
    // =====================================================

    public function test_seller_registered_without_ekyc_is_hidden_until_owner_passes_ekyc(): void
    {
        $owner = User::factory()->create(['name' => 'แม่ค้าใหม่']);

        // สมัครผ่านเว็บได้ทันที (ไม่ต้องยืนยันตัวตนก่อน)
        $this->actingAs($owner)->post(route('taladsod.register-seller.store'), [
            'shop_name' => 'ร้านแม่ค้าใหม่',
            'phone' => '0898765432',
            'address' => 'ตลาดนัด',
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'agree_terms' => '1',
        ])->assertRedirect(route('taladsod.seller.dashboard'));

        $shop = FreshMarketSeller::where('user_id', $owner->id)->firstOrFail();
        $this->assertFalse((bool) $shop->is_verified);
        $this->assertTrue($shop->needsOwnerKyc());
        $this->assertStringContainsString(EkycGate::SELLER_PENDING_KYC_MESSAGE, (string) $shop->sellingStatusMessage());
        $this->assertStringContainsString('ยืนยันตัวตน', (string) session('success'));

        // ลงสินค้าเตรียมไว้ได้ แต่ผู้ซื้อมองไม่เห็นและสั่งไม่ได้
        $listing = $this->service->createListing($shop, [
            'title' => 'มะม่วงน้ำดอกไม้', 'price' => 80, 'unit' => 'กก.', 'quantity_available' => 5,
        ]);
        $this->assertFalse($shop->fresh()->isVisibleToBuyers());
        $this->assertNull(FreshMarketListing::visibleToBuyers()->find($listing->id));
        $this->assertFalse($listing->fresh()->sellerIsVisible());

        try {
            $this->service->createOrder($this->verifiedBuyer, $listing->fresh(), ['quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'wallet']);
            $this->fail('ร้านที่เจ้าของยังไม่ยืนยันตัวตนต้องรับออเดอร์ไม่ได้');
        } catch (FreshMarketException $e) {
            $this->assertSame('LISTING_UNAVAILABLE', $e->errorCode());
        }

        // แอปเห็นสถานะร้าน (ฟิลด์เดิมยังอยู่ + ฟิลด์ใหม่)
        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/fresh-market/seller/profile')
            ->assertOk()
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.is_visible_to_buyers', false)
            ->assertJsonPath('data.status', 'unverified')
            ->assertJsonPath('data.kyc_required', true)
            ->assertJsonPath('data.can_operate', true);
        $this->assertStringContainsString(
            EkycGate::SELLER_PENDING_KYC_MESSAGE,
            (string) $this->getJson('/api/v1/fresh-market/seller/profile')->json('data.status_message')
        );

        // ยืนยันตัวตนผ่าน (ทางไหนก็ได้ที่ตั้ง kyc_status = approved) → ร้านเปิดขายเองอัตโนมัติ
        $this->kycApproved($owner);

        $shop->refresh();
        $this->assertTrue((bool) $shop->is_verified);
        $this->assertTrue($shop->isVisibleToBuyers());
        $this->assertNull($shop->sellingStatusMessage());
        $this->assertNotNull(FreshMarketListing::visibleToBuyers()->find($listing->id));

        $order = $this->service->createOrder($this->verifiedBuyer, $listing->fresh(), ['quantity' => 1, 'delivery_type' => 'pickup', 'payment_method' => 'wallet']);
        $this->assertSame((int) $shop->id, (int) $order->seller_id);
    }

    public function test_api_seller_registration_no_longer_requires_ekyc_and_reports_status(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/fresh-market/seller/register', [
            'shop_name' => 'ร้านจากแอป',
            'phone' => '0811111111',
            'address' => 'ตลาด',
            'latitude' => 13.75,
            'longitude' => 100.50,
            'agree_terms' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_verified', false)
            ->assertJsonPath('data.kyc_required', true);

        // เจ้าของที่ยืนยันตัวตนแล้วสมัคร → ขายได้ทันที (เปิดอนุมัติอัตโนมัติ)
        $verifiedOwner = $this->kycApproved(User::factory()->create());
        $shop = $this->service->registerSeller($verifiedOwner, ['shop_name' => 'ร้านพร้อมขาย']);
        $this->assertTrue((bool) $shop->is_verified);

        // ปิดอนุมัติอัตโนมัติ → ยืนยันตัวตนแล้วก็ยังต้องรอแอดมิน (แอดมินกดยืนยันร้านเองได้เสมอ)
        Setting::set('fresh_market.auto_approve_sellers', '0', 'boolean', 'fresh_market');
        Cache::flush();
        $other = $this->kycApproved(User::factory()->create());
        $pending = $this->service->registerSeller($other, ['shop_name' => 'ร้านรอแอดมิน']);
        $this->assertFalse((bool) $pending->is_verified);
        $this->assertFalse($pending->needsOwnerKyc());
    }

    // =====================================================
    // 3. ร้านถูกระงับ
    // =====================================================

    public function test_suspended_seller_cannot_act_and_settlement_is_held_for_admin(): void
    {
        // ออเดอร์ A: ส่งมอบแล้ว (ก่อนถูกระงับ) · ออเดอร์ B: ยังรอร้านรับ
        $delivered = $this->placeOrder();
        $this->service->applyAction($delivered, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($delivered->fresh(), 'ready', 'seller', $this->sellerUser);
        $this->service->applyAction($delivered->fresh(), 'handover', 'seller', $this->sellerUser);
        $pending = $this->placeOrder();

        // แอดมินระงับร้าน → ไม่ยกเลิกออเดอร์ค้างอัตโนมัติ แต่บอกแอดมินว่ามีค้างกี่รายการ
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.fresh-market.sellers.suspend', $this->seller), ['reason' => 'ตรวจสอบการโกง'])
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, '2 รายการ'));
        $this->assertTrue((bool) $this->seller->fresh()->is_suspended);
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $pending->fresh()->order_status);

        // ร้านกดรับ/ยกเลิกออเดอร์ไม่ได้ ทุกช่องทาง
        foreach (['accept', 'cancel'] as $action) {
            try {
                $this->service->applyAction($pending->fresh(), $action, 'seller', $this->sellerUser, ['reason' => 'ไม่สะดวก']);
                $this->fail("ร้านถูกระงับต้อง {$action} ไม่ได้");
            } catch (FreshMarketException $e) {
                $this->assertSame('SELLER_SUSPENDED', $e->errorCode());
            }
        }

        Sanctum::actingAs($this->sellerUser);
        $this->postJson("/api/v1/fresh-market/seller/orders/{$pending->id}/action", ['action' => 'accept'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'SELLER_SUSPENDED');
        $this->putJson("/api/v1/fresh-market/listings/{$this->listing->id}", ['price' => 1])
            ->assertStatus(403)
            ->assertJsonPath('code', 'SELLER_SUSPENDED');
        $this->assertSame(50.0, (float) $this->listing->fresh()->price);

        $this->actingAs($this->sellerUser)
            ->put(route('taladsod.seller.orders.status', $pending), ['action' => 'accept'])
            ->assertSessionHas('error', FreshMarketSeller::SUSPENDED_ACTION_MESSAGE);
        $this->assertSame(FreshMarketOrder::STATUS_PENDING, $pending->fresh()->order_status);

        // ผู้ซื้อสั่งใหม่ไม่ได้
        try {
            $this->placeOrder();
            $this->fail('ผู้ซื้อต้องสั่งร้านที่ถูกระงับไม่ได้');
        } catch (FreshMarketException $e) {
            $this->assertSame('LISTING_UNAVAILABLE', $e->errorCode());
        }

        // ผู้ซื้อยืนยันรับของ → ไม่ปิดออเดอร์/ไม่โอนเงินให้ร้าน (เงินยังพัก) แต่บันทึกว่าได้รับของแล้ว
        try {
            $this->service->completeOrder($delivered->fresh(), 'buyer', $this->verifiedBuyer);
            $this->fail('ร้านถูกระงับต้องไม่ปล่อยเงิน');
        } catch (FreshMarketException $e) {
            $this->assertSame('SETTLEMENT_ON_HOLD', $e->errorCode());
        }

        $held = $delivered->fresh();
        $this->assertSame(FreshMarketOrder::STATUS_DELIVERED, $held->order_status);
        $this->assertSame('held', $held->escrow_status);
        $this->assertNotNull($held->buyer_confirmed_at);
        $this->assertSame('settlement_held', collect($held->status_history)->last()['action'] ?? null);
        $this->assertSame(0.0, $this->balanceOf($this->sellerUser));
        $this->assertSame(0, WalletTransaction::where('reference_type', FreshMarketService::REF_PAYOUT)->count());

        // ตัวกวาดปิดออเดอร์อัตโนมัติข้ามร้านที่ถูกระงับ (ไม่จ่าย และไม่ลองซ้ำทุกรอบ)
        FreshMarketOrder::whereKey($held->id)->update(['delivered_at' => now()->subDays(3)]);
        $this->assertSame(0, $this->service->autoCompleteDeliveredOrders());
        $this->assertSame(FreshMarketOrder::STATUS_DELIVERED, $held->fresh()->order_status);
        $this->assertSame(0.0, $this->balanceOf($this->sellerUser));

        // แอดมินตัดสิน: ปิดออเดอร์ = จ่ายร้าน (ครั้งเดียว)
        $completed = $this->service->completeOrder($held->fresh(), 'admin', $admin);
        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $completed->order_status);
        $this->assertSame('released', $completed->escrow_status);
        $this->assertSame(45.0, $this->balanceOf($this->sellerUser));

        // แอดมินตัดสินอีกออเดอร์: ยกเลิก = คืนเงินผู้ซื้อ
        $cancelled = $this->service->cancelOrder($pending->fresh(), 'ร้านถูกระงับ', 'admin', $admin);
        $this->assertSame(FreshMarketOrder::STATUS_CANCELLED, $cancelled->order_status);
        $this->assertSame(50.0, (float) $cancelled->refunded_amount);
    }

    public function test_active_seller_settlement_is_unchanged(): void
    {
        $order = $this->placeOrder();
        $this->service->applyAction($order, 'accept', 'seller', $this->sellerUser);
        $this->service->applyAction($order->fresh(), 'ready', 'seller', $this->sellerUser);
        $this->service->applyAction($order->fresh(), 'handover', 'seller', $this->sellerUser);

        $completed = $this->service->completeOrder($order->fresh(), 'buyer', $this->verifiedBuyer);

        $this->assertSame(FreshMarketOrder::STATUS_COMPLETED, $completed->order_status);
        $this->assertSame(45.0, $this->balanceOf($this->sellerUser));
    }

    // =====================================================
    // Helpers
    // =====================================================

    protected function kycApproved(User $user): User
    {
        $user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();

        return $user->fresh();
    }

    protected function makeListing(FreshMarketSeller $seller, string $title): FreshMarketListing
    {
        return FreshMarketListing::create([
            'seller_id' => $seller->id,
            'title' => $title,
            'price' => 50,
            'unit' => 'กำ',
            'quantity_available' => 10,
            'latitude' => 13.7563,
            'longitude' => 100.5018,
            'status' => 'active',
            'is_available' => true,
            'created_via' => 'web',
        ]);
    }

    protected function placeOrder(): FreshMarketOrder
    {
        return $this->service->createOrder($this->verifiedBuyer, $this->listing->fresh(), [
            'quantity' => 1,
            'delivery_type' => 'pickup',
            'payment_method' => 'wallet',
        ]);
    }

    protected function balanceOf(User $user): float
    {
        return round((float) Wallet::where('user_id', $user->id)->value('balance'), 2);
    }

    /**
     * ตั้งบทสนทนา LINE ให้อยู่ขั้นยืนยันคำสั่งซื้อ (ข้ามขั้นจำนวน/ที่อยู่)
     */
    protected function putInOrderReview(FreshMarketConversation $conversation): void
    {
        // อ่านค่าล่าสุดก่อน (handler รีเซ็ตบทสนทนาผ่านอีก instance — ค่าเดิมเหมือนกันจะไม่ถูกบันทึกซ้ำ)
        $conversation->refresh()->forceFill([
            'conversation_state' => FreshMarketConversation::STATE_ORDER_REVIEW,
            'state_updated_at' => now(),
            'context' => ['order' => ['listing_id' => $this->listing->id, 'quantity' => 1, 'delivery_type' => 'pickup']],
        ])->save();
    }

    /**
     * เรียกเมธอด protected ของ handler LINE (เทสต์ระดับ handler — ไม่ผ่าน webhook)
     */
    protected function invoke(object $target, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($target, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($target, $args);
    }
}
