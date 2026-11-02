<?php

namespace Tests\Feature\User;

use App\Models\NFCCard;
use App\Models\NFCReader;
use App\Models\NFCTransaction;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * หน้าบริการ · รีวิวบริการ · การ์ด NFC ของสมาชิกในธีมโนวาเต็ม
 * (/user/services*, /user/service-reviews*, /user/nfc*)
 */
class NovaServicesNfcPagesTest extends TestCase
{
    use RefreshDatabase;

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
     * หน้าต้องอยู่ในเปลือกโนวาเต็ม ไม่ใช่โหมดสะพาน V4
     */
    private function assertFullNova(string $html): void
    {
        $this->assertStringContainsString('nvm-body', $html);
        $this->assertStringContainsString('nova-member.css', $html);
        $this->assertStringContainsString('theme-nova/member/services-nfc.css', $html);
        $this->assertStringNotContainsString('class="nvm-v4"', $html, 'ยังเป็นโหมดสะพาน V4');
        $this->assertStringNotContainsString('tp-card', $html, 'ยังมีการ์ด V4 หลงอยู่');
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bfa[srb]?\s+fa-/', $this->contentOnly($html), 'ยังมีไอคอน Font Awesome ในเนื้อหา');
    }

    /**
     * ตัดเฉพาะเนื้อหาใน <main> (เปลือกโนวาเป็นของ layout ไม่ใช่ของหน้านี้)
     */
    private function contentOnly(string $html): string
    {
        if (preg_match('/<main class="nvm-main">(.*)<\/main>/s', $html, $m)) {
            return $m[1];
        }

        return $html;
    }

    private function category(array $attributes = []): ServiceCategory
    {
        return ServiceCategory::forceCreate(array_merge([
            'name' => 'นวดแผนไทย',
            'slug' => 'massage-'.Str::random(6),
            'color' => '#8B5CF6',
            'icon' => 'fas fa-spa',
            'is_active' => true,
            'sort_order' => 1,
        ], $attributes));
    }

    private function service(ServiceCategory $category, User $owner, array $attributes = []): Service
    {
        return Service::forceCreate(array_merge([
            'category_id' => $category->id,
            'owner_id' => $owner->id,
            'name' => 'นวดไทย 1 ชั่วโมง',
            'slug' => 'thai-massage-'.Str::random(6),
            'description' => 'นวดคลายเส้นโดยหมอนวดมืออาชีพ',
            'base_price' => 500,
            'duration_minutes' => 60,
            'is_active' => true,
            'is_featured' => true,
            'requires_location' => false,
            'options' => [['name' => 'น้ำมันอโรมา', 'price' => 100]],
        ], $attributes));
    }

    private function booking(User $user, Service $service, array $attributes = []): ServiceBooking
    {
        return ServiceBooking::forceCreate(array_merge([
            'booking_number' => 'SB-'.Str::upper(Str::random(8)),
            'user_id' => $user->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->subDay(),
            'status' => 'completed',
            'completed_at' => now()->subHours(20),
        ], $attributes));
    }

    private function card(User $user, array $attributes = []): NFCCard
    {
        return NFCCard::forceCreate(array_merge([
            'card_number' => '4000'.random_int(100000000000, 999999999999),
            'card_name' => "บัตรของ O'Neil",
            'user_id' => $user->id,
            'encrypted_data' => 'enc',
            'encryption_key_hash' => 'hash',
            'card_signature' => 'sig',
            'card_type' => 'standard',
            'balance' => 250,
            'daily_spending_limit' => 1000,
            'monthly_spending_limit' => 10000,
            'transaction_limit' => 500,
            'daily_spent' => 800,
            'monthly_spent' => 800,
            'last_reset_daily' => now()->toDateString(),
            'last_reset_monthly' => now()->startOfMonth()->toDateString(),
            'status' => 'active',
            'is_enabled' => true,
        ], $attributes));
    }

    // ───────────────────────── บริการ ─────────────────────────

    public function test_services_index_renders_full_nova_with_services(): void
    {
        $member = $this->member();
        $category = $this->category();
        $this->service($category, $member, ['name' => 'ล้างแอร์บ้าน', 'images' => ['services/air.jpg']]);

        $html = $this->actingAs($member)->get(route('user.services.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ล้างแอร์บ้าน', $html);
        $this->assertStringContainsString('storage/services/air.jpg', $html, 'รูปบริการต้องมาจากคอลัมน์ images');
        // ช่องแนะนำคำค้นเดิมยิงไป API ที่ไม่มีอยู่จริง
        $this->assertStringNotContainsString('search-suggestions', $html);
    }

    public function test_services_index_filters_and_empty_state(): void
    {
        $member = $this->member();
        $category = $this->category();
        $this->service($category, $member, ['name' => 'ตัดผมชาย']);

        $this->actingAs($member)->get(route('user.services.index', ['q' => 'ตัดผม', 'sort' => 'price_low']))
            ->assertOk()->assertSee('ตัดผมชาย');

        $html = $this->actingAs($member)->get(route('user.services.index', ['q' => 'ไม่มีแน่นอน']))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('ไม่พบบริการที่ค้นหา', $html);
    }

    public function test_category_page_renders_and_sorts_on_the_server(): void
    {
        $member = $this->member();
        $category = $this->category();
        $this->service($category, $member, ['name' => 'บริการราคาแพง', 'base_price' => 900, 'is_featured' => false]);
        $this->service($category, $member, ['name' => 'บริการราคาถูก', 'base_price' => 100, 'is_featured' => false]);
        $this->category(['name' => 'หมวดอื่นที่เปิดอยู่']);

        $html = $this->actingAs($member)->get(route('user.services.category', [$category, 'sort' => 'price_asc']))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertLessThan(strpos($html, 'บริการราคาแพง'), strpos($html, 'บริการราคาถูก'), 'เรียงราคาต่ำ → สูงต้องได้ผลจริง');
        $this->assertStringContainsString('หมวดอื่นที่เปิดอยู่', $html, 'ต้องแสดงหมวดหมู่อื่น');
    }

    public function test_inactive_category_is_not_found(): void
    {
        $member = $this->member();
        $category = $this->category(['is_active' => false]);

        $this->actingAs($member)->get(route('user.services.category', $category))->assertNotFound();
    }

    public function test_service_show_renders_booking_form_with_named_options_and_vat_total(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member, ['cashback_percentage' => 5, 'pv_value' => 50]);

        $html = $this->actingAs($member)->get(route('user.services.show', $service))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('action="'.route('user.services.store-booking', $service).'"', $html);
        // ออปชันต้องอยู่ในฟอร์มและมีชื่อช่อง (เดิมไม่เคยถูกส่ง)
        $this->assertMatchesRegularExpression('/name="selected_options\[\]"\s+value="น้ำมันอโรมา"/u', $html);
        $this->assertStringContainsString('name="payment_method" value="wallet"', $html);
        // ยอดรวมต้องรวม VAT ตามสูตรของระบบ (500 + 7%)
        $this->assertStringContainsString('฿535.00', $html);
        $this->assertStringContainsString('ภาษีมูลค่าเพิ่ม', $html);
    }

    public function test_inactive_service_cannot_be_viewed_or_booked(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member, ['is_active' => false]);

        $this->actingAs($member)->get(route('user.services.show', $service))->assertNotFound();
        $this->actingAs($member)->get(route('user.services.book', $service))->assertNotFound();
        $this->actingAs($member)->post(route('user.services.store-booking', $service), [
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
            'payment_method' => 'wallet',
        ])->assertNotFound();
        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_book_route_redirects_to_the_booking_form_instead_of_500(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member);

        // เดิม render view user.services.book ที่ไม่มีอยู่จริง → 500
        $this->actingAs($member)->get(route('user.services.book', $service))
            ->assertRedirect(route('user.services.show', $service).'#nvm-sn-book');
    }

    public function test_calculate_price_endpoint_accepts_what_the_page_sends(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member);

        $this->actingAs($member)->postJson(route('user.services.calculate-price'), [
            'service_id' => $service->id,
            'distance_km' => 0,
            'selected_options' => ['น้ำมันอโรมา'],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pricing.options_price', 100)
            ->assertJsonPath('pricing.total_amount', 642);
    }

    // ───────────────────────── รีวิวบริการ ─────────────────────────

    public function test_review_index_renders_rated_bookings_without_crashing(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member, ['name' => 'ทำความสะอาดบ้าน']);
        $this->booking($member, $service, ['rating' => 4, 'review' => 'บริการดีมาก', 'rated_at' => now()->subHour()]);

        // เดิม rated_at ไม่มี cast → ->format() บน string = 500
        $html = $this->actingAs($member)->get(route('user.service-reviews.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ทำความสะอาดบ้าน', $html);
        $this->assertStringContainsString('บริการดีมาก', $html);
        $this->assertStringContainsString('4/5', $html);
        $this->assertStringContainsString('action="'.route('user.service-reviews.destroy', ServiceBooking::first()).'"', $html);
    }

    public function test_review_index_empty_state_and_only_own_reviews(): void
    {
        $member = $this->member();
        $other = $this->member();
        $service = $this->service($this->category(), $other);
        $this->booking($other, $service, ['rating' => 5, 'review' => 'รีวิวของคนอื่น', 'rated_at' => now()]);

        $html = $this->actingAs($member)->get(route('user.service-reviews.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ยังไม่มีรีวิว', $html);
        $this->assertStringNotContainsString('รีวิวของคนอื่น', $html);
    }

    public function test_review_edit_page_and_update(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member);
        $booking = $this->booking($member, $service, ['rating' => 3, 'review' => 'พอใช้', 'rated_at' => now()]);

        $html = $this->actingAs($member)->get(route('user.service-reviews.edit', $booking))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('name="rating" value="3"', $html);
        $this->assertStringContainsString('พอใช้', $html);

        $this->actingAs($member)->put(route('user.service-reviews.update', $booking), ['rating' => 5, 'review' => 'ดีขึ้นมาก'])
            ->assertRedirect(route('user.service-reviews.index'));
        // เดิม rating/review ไม่อยู่ใน fillable → update() ทิ้งค่าเงียบ ๆ
        $this->assertSame(5, (int) $booking->fresh()->rating);
        $this->assertSame('ดีขึ้นมาก', $booking->fresh()->review);
    }

    public function test_review_can_be_stored_and_deleted(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member);
        $booking = $this->booking($member, $service);

        $this->actingAs($member)->from(route('user.bookings.show', $booking))
            ->post(route('user.service-reviews.store', $booking), ['rating' => 4, 'review' => 'ตรงเวลา'])
            ->assertSessionHas('success');
        $fresh = $booking->fresh();
        $this->assertSame(4, (int) $fresh->rating);
        $this->assertNotNull($fresh->rated_at);

        $this->actingAs($member)->delete(route('user.service-reviews.destroy', $booking))
            ->assertRedirect(route('user.service-reviews.index'));
        $this->assertNull($booking->fresh()->rating);
        $this->assertNull($booking->fresh()->review);
    }

    public function test_review_of_another_member_is_forbidden(): void
    {
        $member = $this->member();
        $other = $this->member();
        $service = $this->service($this->category(), $other);
        $booking = $this->booking($other, $service, ['rating' => 4, 'rated_at' => now()]);

        $this->actingAs($member)->get(route('user.service-reviews.edit', $booking))->assertForbidden();
        $this->actingAs($member)->put(route('user.service-reviews.update', $booking), ['rating' => 1])->assertForbidden();
        $this->actingAs($member)->delete(route('user.service-reviews.destroy', $booking))->assertForbidden();
        $this->assertSame(4, (int) $booking->fresh()->rating);
    }

    public function test_unrated_booking_cannot_be_rated_through_the_edit_route(): void
    {
        $member = $this->member();
        $service = $this->service($this->category(), $member);
        // การจองที่ยังไม่เสร็จและยังไม่เคยรีวิว — เดิม PUT ให้คะแนนได้ ข้ามด่านสถานะของ storeReview
        $booking = $this->booking($member, $service, ['status' => 'pending', 'completed_at' => null]);

        $this->actingAs($member)->get(route('user.service-reviews.edit', $booking))->assertNotFound();
        $this->actingAs($member)->put(route('user.service-reviews.update', $booking), ['rating' => 5])->assertNotFound();
        $this->assertNull($booking->fresh()->rating);
    }

    // ───────────────────────── การ์ด NFC ─────────────────────────

    public function test_nfc_index_renders_cards_and_escapes_card_name_into_alpine(): void
    {
        $member = $this->member();
        $wallet = app(WalletService::class)->getOrCreateWallet($member);
        $card = $this->card($member, ['wallet_id' => $wallet->id]);
        NFCTransaction::forceCreate([
            'nfc_card_id' => $card->id,
            'user_id' => $member->id,
            'type' => 'payment',
            'amount' => 120,
            'balance_before' => 370,
            'balance_after' => 250,
            'status' => 'completed',
            'card_data_hash' => 'h',
        ]);

        $html = $this->actingAs($member)->get(route('user.nfc.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString($card->masked_card_number, $html);
        $this->assertStringContainsString('-฿120.00', $html);
        $this->assertStringContainsString('style="--w: 80%"', $html, 'แถบวงเงินต้องส่งความกว้างผ่านตัวแปร --w');
        // ชื่อการ์ดที่มี ' ต้องถูก encode ก่อนเข้า Alpine (เดิมทำให้ปุ่มเติมเงินพัง)
        $this->assertStringNotContainsString("'บัตรของ O'Neil'", $html);
        $this->assertStringContainsString('O\u0027Neil', $html);
        $this->assertStringContainsString(route('user.nfc.topup', $card), str_replace('\/', '/', $html));
    }

    public function test_nfc_index_empty_state_shows_no_other_members_cards(): void
    {
        $member = $this->member();
        $this->card($this->member(), ['card_name' => 'การ์ดคนอื่น']);

        $html = $this->actingAs($member)->get(route('user.nfc.index'))->assertOk()->getContent();

        $this->assertFullNova($html);
        $this->assertStringContainsString('ยังไม่มีการ์ด NFC', $html);
        $this->assertStringNotContainsString('การ์ดคนอื่น', $html);
    }

    public function test_nfc_show_and_transactions_render_for_the_owner(): void
    {
        $member = $this->member();
        $card = $this->card($member);
        NFCTransaction::forceCreate([
            'nfc_card_id' => $card->id,
            'user_id' => $member->id,
            'type' => 'topup',
            'amount' => 300,
            'balance_before' => 0,
            'balance_after' => 300,
            'status' => 'completed',
            'card_data_hash' => 'h',
            'location' => 'ร้านกาแฟหน้าตึก',
        ]);

        $html = $this->actingAs($member)->get(route('user.nfc.show', $card))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('action="'.route('user.nfc.limits.update', $card).'"', $html);
        $this->assertStringContainsString('action="'.route('user.nfc.auto-topup.configure', $card).'"', $html);
        // ติ๊กปิดเติมเงินอัตโนมัติแล้วต้องยังส่ง enabled=0 ได้
        $this->assertStringContainsString('<input type="hidden" name="enabled" value="0">', $html);
        $this->assertStringContainsString('+฿300.00', $html, 'ธุรกรรมล่าสุดต้องแสดงบนหน้ารายละเอียด');

        $html = $this->actingAs($member)->get(route('user.nfc.transactions', $card))->assertOk()->getContent();
        $this->assertFullNova($html);
        $this->assertStringContainsString('ร้านกาแฟหน้าตึก', $html);
        $this->assertStringContainsString('nvm-table-wrap', $html);
    }

    public function test_nfc_pages_of_another_member_are_forbidden(): void
    {
        $member = $this->member();
        $card = $this->card($this->member());

        $this->actingAs($member)->get(route('user.nfc.show', $card))->assertForbidden();
        $this->actingAs($member)->get(route('user.nfc.transactions', $card))->assertForbidden();
        $this->actingAs($member)->post(route('user.nfc.disable', $card))->assertForbidden();
        $this->assertTrue($card->fresh()->is_enabled);
    }

    public function test_auto_topup_can_be_turned_off_with_the_hidden_field(): void
    {
        $member = $this->member();
        $card = $this->card($member, ['auto_topup_enabled' => true, 'auto_topup_threshold' => 50, 'auto_topup_amount' => 200]);

        // ฟอร์มส่ง enabled=0 (hidden) เมื่อไม่ได้ติ๊ก และช่องจำนวนเงินถูก disable จึงไม่ถูกส่ง
        $this->actingAs($member)->from(route('user.nfc.show', $card))
            ->put(route('user.nfc.auto-topup.configure', $card), ['enabled' => '0'])
            ->assertRedirect(route('user.nfc.show', $card))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $card->fresh()->auto_topup_enabled);
    }

    public function test_topup_amount_field_focuses_itself_instead_of_an_unreachable_ref(): void
    {
        $member = $this->member();
        $wallet = app(WalletService::class)->getOrCreateWallet($member);
        $card = $this->card($member, ['wallet_id' => $wallet->id]);

        foreach ([route('user.nfc.index'), route('user.nfc.show', $card)] as $url) {
            $html = $this->actingAs($member)->get($url)->assertOk()->getContent();
            // x-ref ในฟอร์มลงทะเบียนกับ x-data ของฟอร์ม ปุ่มชั้นนอกจึงหาไม่เจอ (เดิม TypeError ทุกครั้งที่กด)
            $this->assertStringNotContainsString('$refs.topUpAmount', $html, $url);
            $this->assertStringContainsString('x-effect="if (topUp) $nextTick(() => $el.focus())"', $html, $url);
        }
    }

    public function test_nfc_transaction_rows_eager_load_their_readers(): void
    {
        $member = $this->member();
        $card = $this->card($member);
        foreach (['เครื่องอ่าน A', 'เครื่องอ่าน B', 'เครื่องอ่าน C'] as $i => $name) {
            $reader = NFCReader::forceCreate([
                'name' => $name,
                'reader_id' => 'RD-'.$i.'-'.Str::random(6),
                'serial_number' => 'SN-'.$i.'-'.Str::random(6),
                'status' => 'active',
            ]);
            NFCTransaction::forceCreate([
                'nfc_card_id' => $card->id,
                'user_id' => $member->id,
                'nfc_reader_id' => $reader->id,
                'type' => 'payment',
                'amount' => 10,
                'balance_before' => 500,
                'balance_after' => 490,
                'status' => 'completed',
                'card_data_hash' => 'h',
            ]);
        }

        foreach ([route('user.nfc.index'), route('user.nfc.show', $card)] as $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $html = $this->actingAs($member)->get($url)->assertOk()->getContent();
            $readerQueries = collect(DB::getQueryLog())
                ->filter(fn ($q) => str_contains($q['query'], 'nfc_readers'))
                ->count();
            DB::disableQueryLog();

            $this->assertStringContainsString('เครื่องอ่าน B', $html, $url);
            $this->assertLessThanOrEqual(1, $readerQueries, $url.' โหลดเครื่องอ่านทีละแถว (N+1)');
        }
    }
}
