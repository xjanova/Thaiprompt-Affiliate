<?php

namespace Tests\Feature;

use App\Models\FortuneTellingSetting;
use App\Models\UniquePaymentAmount;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🌙 /api/v1/juntra/server/amounts/* (CONTRACT §C1–C2) + ตัวจองยอด UniquePaymentAmount::generate()
 *
 * บัญชีรับเงินเดียวกัน + มือถือ SMS เครื่องเดียวกัน → ยอดทศนิยมของสองเว็บห้ามชนกันเด็ดขาด
 *   ถ้าชน = เงินก้อนเดียวถูกตัดบิลทั้งสองฝั่ง หรือเงินลูกค้าเราถูกมองเป็นเงินของจันทรา
 */
class JuntraServerAmountApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(
            null,
            'Juntra Chantra SSO',
            'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback',
            'oauth_users'
        );
        Passport::actingAsClient($client, [], 'api-oauth');
    }

    protected function tearDown(): void
    {
        FortuneTellingSetting::clearSettingsCache();

        parent::tearDown();
    }

    private function reserve(array $overrides = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/amounts/reserve', array_merge([
            'base_amount' => 100,
            'ref' => 'TUP-AB12CD34',
            'external_id' => 555,
            'ttl_minutes' => 60,
        ], $overrides));
    }

    private function release(array $body)
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/amounts/release', $body);
    }

    /** บิลของ Thaiprompt เอง (ดูดวง) ที่จองยอดไว้ */
    private function thaipromptBill(int $base, int $suffix, string $status = 'reserved', array $attrs = []): UniquePaymentAmount
    {
        return UniquePaymentAmount::create(array_merge([
            'base_amount' => $base,
            'unique_amount' => $base + $suffix / 100,
            'decimal_suffix' => $suffix,
            'transaction_id' => null,
            'transaction_type' => 'fortune_reading',
            'status' => $status,
            'expires_at' => now()->addMinutes(60),
        ], $attrs));
    }

    private function suffixOf(string $uniqueAmount): int
    {
        return (int) substr($uniqueAmount, -2);
    }

    // ─────────────────────────────────────────────────────────────────────
    // C1. reserve
    // ─────────────────────────────────────────────────────────────────────

    public function test_reserve_allocates_a_juntraweb_amount_through_the_shared_allocator(): void
    {
        $response = $this->reserve()->assertStatus(201);

        $response->assertJsonPath('data.base_amount', '100.00')
            ->assertJsonStructure(['data' => ['id', 'unique_amount', 'base_amount', 'expires_at']]);
        $this->assertMatchesRegularExpression('/^100\.\d{2}$/', $response->json('data.unique_amount'));
        $this->assertNotSame('100.00', $response->json('data.unique_amount'));

        $row = UniquePaymentAmount::findOrFail($response->json('data.id'));
        $this->assertSame(UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP, $row->transaction_type);
        $this->assertNull($row->transaction_id, 'ห้ามใส่ id ของจันทราลง transaction_id (FK ไป payment_transactions)');
        $this->assertSame('TUP-AB12CD34', $row->external_ref);
        $this->assertSame(555, (int) $row->external_id);
        $this->assertSame('reserved', $row->status);
        $this->assertEqualsWithDelta(now()->addMinutes(60)->timestamp, $row->expires_at->timestamp, 5);
        $this->assertSame($row->expires_at->toIso8601String(), $response->json('data.expires_at'));
    }

    public function test_reserve_never_reuses_a_suffix_thaiprompt_still_owns_or_just_used(): void
    {
        // บิลดูดวงของเรายังจองอยู่ 1..97 + suffix 98 เพิ่งจ่ายไป (SMS อาจมาซ้ำ/ช้าได้) → เหลือ 99 ที่เดียว
        foreach (range(1, 97) as $s) {
            $this->thaipromptBill(100, $s);
        }
        $this->thaipromptBill(100, 98, 'used', ['matched_at' => now()]);

        $this->reserve()->assertStatus(201)->assertJsonPath('data.unique_amount', '100.99');
    }

    public function test_reserve_returns_409_when_the_pool_is_exhausted(): void
    {
        foreach (range(1, 99) as $s) {
            $this->thaipromptBill(150, $s);
        }

        $this->reserve(['base_amount' => 150])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'pool_exhausted');

        $this->assertSame(0, UniquePaymentAmount::where('transaction_type', UniquePaymentAmount::TYPE_JUNTRAWEB_TOPUP)->count());
    }

    public function test_reserve_is_idempotent_on_ref(): void
    {
        $first = $this->reserve()->assertStatus(201);
        $again = $this->reserve()->assertOk()->assertJsonPath('data.idempotent', true);

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertSame($first->json('data.unique_amount'), $again->json('data.unique_amount'));
        $this->assertSame(1, UniquePaymentAmount::where('external_ref', 'TUP-AB12CD34')->count());
    }

    public function test_reserve_refuses_the_same_ref_with_a_different_price(): void
    {
        $this->reserve()->assertStatus(201);

        $this->reserve(['base_amount' => 200])
            ->assertStatus(409)
            ->assertJsonPath('reason_code', 'ref_conflict');
    }

    public function test_reserve_validates_input(): void
    {
        $this->reserve(['ttl_minutes' => 4])->assertStatus(422)->assertJsonValidationErrors(['ttl_minutes']);
        $this->reserve(['ttl_minutes' => 2881])->assertStatus(422)->assertJsonValidationErrors(['ttl_minutes']);
        $this->reserve(['base_amount' => 0.5])->assertStatus(422)->assertJsonValidationErrors(['base_amount']);
        $this->reserve(['ref' => str_repeat('X', 65)])->assertStatus(422)->assertJsonValidationErrors(['ref']);
    }

    public function test_thaiprompt_bills_never_get_a_suffix_juntraweb_can_still_claim(): void
    {
        $juntra = $this->reserve(['base_amount' => 300])->assertStatus(201);
        $juntraSuffix = $this->suffixOf($juntra->json('data.unique_amount'));

        // ทศนิยมอื่นทั้งหมดของราคา 300 ถูกบิลเราจองอยู่ (หมดอายุอีก 3 วัน) — เหลือแต่ของจันทรา
        foreach (range(1, 99) as $s) {
            if ($s !== $juntraSuffix) {
                $this->thaipromptBill(300, $s, 'reserved', ['expires_at' => now()->addDays(3)]);
            }
        }

        // แม้ด่านถอยกลับ (safety valve) ก็ห้ามแจกยอดของจันทราให้บิลเรา
        $this->assertNull(UniquePaymentAmount::generate(300, null, 'fortune_reading', 60));

        // จันทรายกเลิกแล้ว — ยอดยังเป็นของจันทราอีก 24 ชม. (ลูกค้าอาจโอนตามมาทีหลัง)
        $this->release(['ref' => 'TUP-AB12CD34', 'status' => 'cancelled'])->assertOk();
        $this->assertNull(UniquePaymentAmount::generate(300, null, 'fortune_reading', 60));

        // พ้นช่วงอ้างสิทธิ์แล้ว (หมดอายุ +24 ชม. = ชั่วโมงที่ 25) → กลับมาแจกให้บิลเราได้
        $this->travel(26)->hours();
        $upa = UniquePaymentAmount::generate(300, null, 'fortune_reading', 60);
        $this->assertNotNull($upa);
        $this->assertSame($juntraSuffix, (int) $upa->decimal_suffix);
    }

    // ─────────────────────────────────────────────────────────────────────
    // C2. release
    // ─────────────────────────────────────────────────────────────────────

    public function test_release_marks_used_by_id_and_is_idempotent(): void
    {
        $id = $this->reserve()->json('data.id');

        $this->release(['id' => $id, 'status' => 'used'])
            ->assertOk()
            ->assertJsonPath('data.released', true)
            ->assertJsonPath('data.status', 'used');

        $row = UniquePaymentAmount::find($id);
        $this->assertSame('used', $row->status);
        $this->assertNotNull($row->matched_at);

        $this->release(['id' => $id, 'status' => 'used'])->assertOk()->assertJsonPath('data.released', true);

        // ยกเลิกทีหลังก็ไม่ถอยสถานะ used (เงินเข้าจริงแล้ว)
        $this->release(['id' => $id, 'status' => 'cancelled'])->assertOk()->assertJsonPath('data.status', 'used');
        $this->assertSame('used', UniquePaymentAmount::find($id)->status);
    }

    public function test_release_cancels_by_ref(): void
    {
        $id = $this->reserve()->json('data.id');

        $this->release(['ref' => 'TUP-AB12CD34', 'status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.released', true)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame('cancelled', UniquePaymentAmount::find($id)->status);
    }

    public function test_release_returns_404_for_unknown_or_thaiprompt_reservations(): void
    {
        $this->release(['id' => 999999, 'status' => 'used'])
            ->assertStatus(404)
            ->assertJsonPath('reason_code', 'not_found');

        $ours = $this->thaipromptBill(100, 11);
        $this->release(['id' => $ours->id, 'status' => 'cancelled'])->assertStatus(404);
        $this->assertSame('reserved', $ours->fresh()->status, 'ห้ามจันทราไปยกเลิกบิลของเรา');

        $this->release(['ref' => 'TUP-NOPE', 'status' => 'used'])->assertStatus(404);
    }

    public function test_release_requires_id_or_ref_and_a_valid_status(): void
    {
        $this->release(['status' => 'used'])->assertStatus(422)->assertJsonValidationErrors(['id', 'ref']);
        $this->release(['id' => 1, 'status' => 'refunded'])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }
}
