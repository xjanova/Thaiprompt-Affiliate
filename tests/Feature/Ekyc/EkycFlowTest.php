<?php

namespace Tests\Feature\Ekyc;

use App\Models\KycVerification;
use App\Models\Notification;
use App\Models\User;
use App\Services\Ekyc\EkycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 AI eKYC — ขั้นตอนของแอปทั้งเส้น (บริการ AI ปลอมด้วย Http::fake · ต้องใช้ MySQL)
 *
 * ครอบคลุม: อนุมัติอัตโนมัติ · ส่งแอดมินตรวจ (ใบหน้าก้ำกึ่ง / AI ล่ม / บัตรซ้ำ / ผู้ใช้แก้ข้อมูล)
 *   ถ่ายใหม่ (checksum / บัตรหมดอายุ / ทำท่าไม่ผ่าน / ของปลอม) + เพดานต่อวัน → แอดมินตรวจ
 *   รอบหมดอายุ · ลำดับท่าไม่ตรง/ส่งซ้ำ · ต้องยินยอม · ยืนยันแล้ว · แตะรอบของคนอื่นไม่ได้ · ตรวจไฟล์รูป
 */
#[Group('ekyc')]
class EkycFlowTest extends TestCase
{
    use BuildsEkycFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Cache::flush();
        $this->configureEkyc();
    }

    // =====================================================
    // อนุมัติอัตโนมัติ
    // =====================================================

    public function test_happy_path_is_auto_approved_and_user_is_verified(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();

        $this->getJson('/api/v1/ekyc/status')
            ->assertOk()
            ->assertJsonPath('data.kyc_status', 'none')
            ->assertJsonPath('data.verified', false)
            ->assertJsonPath('data.can_start', true)
            ->assertJsonPath('data.attempts_left', 3)
            ->assertJsonPath('data.required_for', ['order', 'rider', 'seller']);

        $start = $this->startSession()->assertCreated()->assertJsonPath('success', true);
        $sid = (string) $start->json('data.session_id');
        $challenges = (array) $start->json('data.challenges');
        $this->assertCount(3, $challenges);
        $this->assertCount(3, array_unique($challenges), 'คำสั่งต้องไม่ซ้ำกัน');
        $this->assertEmpty(array_diff($challenges, EkycService::CHALLENGES));
        $this->assertNotNull($start->json('data.expires_at'));

        $card = $this->postCard($sid)->assertOk();
        $card->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.fields.id_number_masked', '1 2345 ••••• 12 1')
            ->assertJsonPath('data.fields.name_th', 'นาย ณัฐ ใจงาม')
            ->assertJsonPath('data.fields.birth_date', '1995-01-12')
            ->assertJsonPath('data.checks.checksum', true)
            ->assertJsonPath('data.checks.not_expired', true)
            ->assertJsonPath('data.checks.card_real', true)
            ->assertJsonPath('data.checks.quality_ok', true);
        $this->assertStringNotContainsString(self::VALID_ID, $card->getContent(), 'ห้ามคืนเลขบัตรเต็ม');

        $face = $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk();
        $face->assertJsonPath('data.decision', 'approved')
            ->assertJsonPath('data.kyc_status', 'approved')
            ->assertJsonPath('data.reasons', [])
            ->assertJsonPath('data.attempts_left', 3);
        $this->assertEqualsWithDelta(0.86, (float) $face->json('data.scores.face_match'), 0.001);
        $this->assertEqualsWithDelta(0.97, (float) $face->json('data.scores.liveness'), 0.001);

        $user->refresh();
        $this->assertSame('approved', $user->kyc_status);
        $this->assertNotNull($user->kyc_verified_at);
        $this->assertSame('ณัฐ', $user->thai_first_name);
        $this->assertSame('ใจงาม', $user->thai_last_name);

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame('approved', $kyc->status);
        $this->assertSame('approved', $kyc->ai_decision);
        $this->assertSame(KycVerification::METHOD_EKYC, $kyc->method);
        $this->assertSame('2026-10-04', $kyc->consent_version);
        $this->assertSame(self::VALID_ID, $kyc->id_number_encrypted);
        $this->assertSame('0121', $kyc->id_last4);
        $this->assertSame(EkycService::idHash(self::VALID_ID), $kyc->id_number_hash);
        $raw = \Illuminate\Support\Facades\DB::table('kyc_verifications')->where('id', $kyc->id)->value('id_number_encrypted');
        $this->assertStringNotContainsString(self::VALID_ID, (string) $raw, 'เลขบัตรในฐานข้อมูลต้องเข้ารหัส');

        // บริการ AI ได้ header กุญแจ + ป้ายตามลำดับ
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/id-card') && $r->hasHeader('X-Ekyc-Key', self::AI_KEY));
        Http::assertSent(function (HttpRequest $r) use ($challenges) {
            if (! str_ends_with($r->url(), '/v1/face/verify') || ! $r->hasHeader('X-Ekyc-Key', self::AI_KEY)) {
                return false;
            }
            $labels = collect($r->data())->where('name', 'labels[]')->pluck('contents')->all();
            $frames = collect($r->data())->where('name', 'frames[]')->count();
            $card = collect($r->data())->where('name', 'card_image')->count();

            return $labels === array_merge(['neutral'], $challenges) && $frames === 4 && $card === 1;
        });

        // แจ้งผลผู้ใช้ด้วย type kyc_result
        $note = Notification::where('user_id', $user->id)->where('type', 'kyc_result')->first();
        $this->assertNotNull($note);
        $this->assertSame('approved', $note->data['decision'] ?? null);
        $this->assertSame('ekyc', $note->data['screen'] ?? null);

        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.kyc_status', 'approved')
            ->assertJsonPath('data.method', 'ekyc')
            ->assertJsonPath('data.can_start', false)
            ->assertJsonPath('data.last_decision', 'approved');
    }

    // =====================================================
    // ส่งให้แอดมินตรวจ
    // =====================================================

    public function test_borderline_face_match_goes_to_review_and_admins_are_notified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.40, 'score' => 0.62]]));
        $user = $this->ekycUser();

        $this->runFullFlow()->assertOk()
            ->assertJsonPath('data.decision', 'review')
            ->assertJsonPath('data.kyc_status', 'pending');

        $kyc = KycVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame('pending', $kyc->status);
        $this->assertContains('BORDERLINE_MATCH', $kyc->ai_reasons);
        $this->assertSame('pending', $user->fresh()->kyc_status);

        // แก้บั๊กแจ้งแอดมิน (เดิม query users.is_admin ที่ไม่มีอยู่)
        $this->assertTrue(Notification::where('user_id', $admin->id)->where('type', 'kyc')->exists(), 'แอดมินต้องได้แจ้งเตือน');

        // ระหว่างรอตรวจ เริ่มรอบใหม่ไม่ได้
        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.can_start', false)
            ->assertJsonPath('data.last_decision', 'review');
        $this->startSession()->assertStatus(409)->assertJsonPath('code', 'EKYC_PENDING_REVIEW');
    }

    public function test_ai_down_or_timeout_sends_case_to_review(): void
    {
        // AI ล่มทั้งตอนอ่านบัตร (500) และตอนตรวจใบหน้า (หมดเวลา)
        $this->fakeAi(
            fn () => Http::response(['detail' => 'boom'], 500),
            fn ($request) => (Http::failedConnection('cURL error 28: Operation timed out'))($request),
        );
        $user = $this->ekycUser();

        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.reasons', ['AI_UNAVAILABLE']);

        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'review');

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame('pending', $kyc->status);
        $this->assertContains('AI_UNAVAILABLE', $kyc->ai_reasons);
        // รูปยังอยู่ให้แอดมินตรวจ (เฟรม neutral เป็นเฟรมที่เก็บ)
        $this->assertNotNull($kyc->best_frame_path);
        Storage::disk('local')->assertExists($kyc->best_frame_path);
        Storage::disk('local')->assertExists($kyc->id_card_image);
        $this->assertSame('pending', $user->fresh()->kyc_status);
    }

    public function test_ai_key_missing_means_review_not_crash(): void
    {
        config(['ekyc.ai_key' => '']);
        Http::fake(['*' => Http::response([], 200)]);
        $this->ekycUser();

        $this->runFullFlow()->assertOk()->assertJsonPath('data.decision', 'review');
        Http::assertNothingSent();
    }

    public function test_duplicate_id_already_approved_on_another_account_goes_to_review(): void
    {
        $other = User::factory()->create();
        $other->forceFill(['kyc_status' => 'approved'])->save();
        KycVerification::create([
            'user_id' => $other->id,
            'method' => 'ekyc',
            'status' => 'approved',
            'ekyc_session_id' => (string) \Illuminate\Support\Str::uuid(),
            'id_number_hash' => EkycService::idHash(self::VALID_ID),
        ]);

        $this->fakeAi();
        $user = $this->ekycUser();

        $this->runFullFlow()->assertOk()->assertJsonPath('data.decision', 'review');
        $kyc = KycVerification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertContains('DUPLICATE_ID', $kyc->ai_reasons);
        $this->assertNotSame('approved', $user->fresh()->kyc_status);
    }

    public function test_user_corrections_force_review(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        // แก้เป็นค่าเดิม = ไม่นับว่าแก้
        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_th' => 'นาย ณัฐ ใจงาม'])
            ->assertOk()->assertJsonPath('data.corrected', false);

        // รูปแบบผิด
        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_th' => 'Nat Jaingam'])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR');
        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['birth_date' => '2999-01-01'])
            ->assertStatus(422);

        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_th' => 'นาย ณัฐพล ใจงาม', 'birth_date' => '1995-01-13'])
            ->assertOk()
            ->assertJsonPath('data.corrected', true)
            ->assertJsonPath('data.fields.name_th', 'นาย ณัฐพล ใจงาม')
            ->assertJsonPath('data.fields.birth_date', '1995-01-13');

        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'review');

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertContains('USER_CORRECTED', $kyc->ai_reasons);
        $this->assertSame('นาย ณัฐ ใจงาม', $kyc->extracted_data['corrections']['name_th']['ocr']);
        $this->assertSame('1995-01-12', $kyc->extracted_data['corrections']['birth_date']['ocr']);
        $this->assertNotSame('approved', $user->fresh()->kyc_status);
    }

    // =====================================================
    // ถ่ายใหม่
    // =====================================================

    public function test_checksum_failure_asks_for_retake_and_counts_an_attempt(): void
    {
        $this->fakeAi($this->goodCard(['id_checksum_ok' => false, 'reasons' => ['ID_CHECKSUM_FAIL']], ['id_number' => self::BAD_ID]));
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCard($sid)->assertOk()
            ->assertJsonPath('data.status', 'retake')
            ->assertJsonPath('data.checks.checksum', false)
            ->assertJsonPath('data.reasons', ['ID_CHECKSUM_FAIL']);

        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'retake')
            ->assertJsonPath('data.attempts_left', 2);

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame(KycVerification::STATUS_RETAKE, $kyc->status);
        $this->assertNotSame('pending', $user->fresh()->kyc_status, 'ถ่ายใหม่ไม่ใช่รอตรวจ');
        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.can_start', true)
            ->assertJsonPath('data.last_decision', 'retake')
            ->assertJsonPath('data.attempts_left', 2);
    }

    public function test_server_side_checksum_catches_a_lying_ai(): void
    {
        // AI บอกว่าผ่าน แต่เลขบัตรไม่ผ่านสูตรจริง → เซิร์ฟเวอร์เชื่อสูตรของตัวเอง
        $this->fakeAi($this->goodCard([], ['id_number' => self::BAD_ID]));
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCard($sid)->assertOk()->assertJsonPath('data.status', 'retake')->assertJsonPath('data.checks.checksum', false);
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertJsonPath('data.decision', 'retake');
    }

    public function test_expired_card_asks_for_retake(): void
    {
        $this->fakeAi($this->goodCard([], ['expiry_date' => now()->subDay()->toDateString()]));
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCard($sid)->assertOk()
            ->assertJsonPath('data.status', 'retake')
            ->assertJsonPath('data.checks.not_expired', false);
        $this->assertContains('EXPIRED', (array) $this->postFace($sid, array_merge(['neutral'], $challenges))
            ->assertJsonPath('data.decision', 'retake')->json('data.reasons'));
    }

    public function test_lifelong_card_is_not_expired(): void
    {
        $this->fakeAi($this->goodCard(['reasons' => ['EXPIRY_LIFELONG']], ['expiry_date' => null]));
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCard($sid)->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.checks.not_expired', true)
            ->assertJsonPath('data.fields.expiry_lifelong', true);
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertJsonPath('data.decision', 'approved');
    }

    public function test_failed_challenge_asks_for_retake(): void
    {
        $this->fakeAi(null, $this->goodFace([
            'liveness' => ['passed' => false, 'score' => 0.4, 'challenges' => ['blink' => false, 'turn_left' => true, 'turn_right' => true, 'smile' => true, 'nod' => true]],
            'reasons' => ['CHALLENGE_FAILED:blink'],
        ]));
        $this->ekycUser();

        $res = $this->runFullFlow()->assertOk()->assertJsonPath('data.decision', 'retake');
        $this->assertContains('CHALLENGE_FAILED:blink', (array) $res->json('data.reasons'));
        $this->assertContains('ทำท่า "กะพริบตา" ไม่ผ่าน', (array) $res->json('data.reason_texts'));
    }

    public function test_clear_spoof_asks_for_retake_but_mild_spoof_goes_to_review(): void
    {
        $this->fakeAi(null, $this->goodFace(['anti_spoof' => ['real_score' => 0.2], 'reasons' => ['SPOOF_SUSPECTED']]));
        $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'retake');

        $this->fakeAi(null, $this->goodFace(['anti_spoof' => ['real_score' => 0.5], 'reasons' => ['SPOOF_SUSPECTED']]));
        $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');
    }

    public function test_attempt_limit_turns_the_last_failure_into_review_then_blocks(): void
    {
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.05, 'score' => 0.1], 'reasons' => ['LOW_MATCH']]));
        $user = $this->ekycUser();

        $this->runFullFlow()->assertJsonPath('data.decision', 'retake')->assertJsonPath('data.attempts_left', 2);
        $this->runFullFlow()->assertJsonPath('data.decision', 'retake')->assertJsonPath('data.attempts_left', 1);

        // ครั้งที่ 3 (ถึงเพดาน) → แอดมินตรวจแทน ไม่ให้ผู้ใช้ติดตาย
        $res = $this->runFullFlow()->assertJsonPath('data.decision', 'review');
        $this->assertContains('ATTEMPTS_EXHAUSTED', (array) $res->json('data.reasons'));
        $this->assertSame('pending', $user->fresh()->kyc_status);

        // วันใหม่ ถ้ายังมีรอบ retake ครบเพดาน → 429
        KycVerification::where('user_id', $user->id)->update(['status' => 'retake', 'ai_decision' => 'retake']);
        $this->startSession()->assertStatus(429)->assertJsonPath('code', 'EKYC_TOO_MANY_ATTEMPTS');
        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.attempts_left', 0)->assertJsonPath('data.can_start', false);

        $this->travel(1)->days();
        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.attempts_left', 3);
    }

    // =====================================================
    // รอบยืนยัน: หมดอายุ / ลำดับท่า / ส่งซ้ำ / ยินยอม / ยืนยันแล้ว / ของคนอื่น
    // =====================================================

    public function test_session_expires_after_ttl(): void
    {
        $this->fakeAi();
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        $this->travel(21)->minutes();

        $this->postCard($sid)->assertStatus(410)->assertJsonPath('code', 'EKYC_SESSION_EXPIRED');
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertStatus(410)->assertJsonPath('code', 'EKYC_SESSION_EXPIRED');
    }

    public function test_challenge_mismatch_and_replay_are_rejected(): void
    {
        $this->fakeAi();
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        // ยังไม่ส่งบัตร
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertStatus(409)->assertJsonPath('code', 'EKYC_CARD_REQUIRED');

        $this->postCard($sid)->assertOk();

        // สลับลำดับ / ไม่มี neutral / ท่าที่ไม่ได้สุ่มให้
        $this->postFace($sid, array_merge(['neutral'], array_reverse($challenges)))->assertStatus(422)->assertJsonPath('code', 'EKYC_CHALLENGE_MISMATCH');
        $this->postFace($sid, $challenges)->assertStatus(422)->assertJsonPath('code', 'EKYC_CHALLENGE_MISMATCH');
        $other = array_values(array_diff(EkycService::CHALLENGES, $challenges));
        $this->postFace($sid, ['neutral', $other[0], $challenges[1], $challenges[2]])->assertStatus(422)->assertJsonPath('code', 'EKYC_CHALLENGE_MISMATCH');
        // ไม่มีการเรียก AI ตรวจใบหน้าเลยระหว่างนี้
        Http::assertNotSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/face/verify'));

        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()->assertJsonPath('data.decision', 'approved');

        // ส่งซ้ำ (replay) รอบที่ตัดสินแล้ว
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertStatus(410)->assertJsonPath('code', 'EKYC_SESSION_EXPIRED');
        $this->postCard($sid)->assertStatus(410);
        $this->assertSame(1, KycVerification::where('ekyc_session_id', $sid)->count());
    }

    public function test_new_session_discards_the_unfinished_one(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        [$first] = $this->openSession();
        $this->postCard($first)->assertOk();
        $oldCard = KycVerification::where('ekyc_session_id', $first)->value('id_card_image');
        Storage::disk('local')->assertExists($oldCard);

        [$second] = $this->openSession();

        $this->assertNotSame($first, $second);
        $this->assertFalse(KycVerification::where('ekyc_session_id', $first)->exists());
        Storage::disk('local')->assertMissing($oldCard);
        $this->postCard($first)->assertStatus(404)->assertJsonPath('code', 'EKYC_SESSION_NOT_FOUND');
        $this->assertSame(1, KycVerification::where('user_id', $user->id)->count());
    }

    public function test_consent_is_required(): void
    {
        $this->fakeAi();
        $this->ekycUser();

        foreach ([[], ['consent' => false, 'consent_version' => '2026-10-04'], ['consent' => true], ['consent' => true, 'consent_version' => '<script>']] as $body) {
            $this->postJson('/api/v1/ekyc/sessions', $body)
                ->assertStatus(422)
                ->assertJsonPath('code', 'EKYC_CONSENT_REQUIRED');
        }

        $this->assertSame(0, KycVerification::count());
    }

    public function test_already_verified_user_cannot_start(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        $user->forceFill(['kyc_status' => 'approved', 'kyc_verified_at' => now()])->save();

        $this->startSession()->assertStatus(409)->assertJsonPath('code', 'EKYC_ALREADY_VERIFIED');
        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.verified', true)->assertJsonPath('data.can_start', false);
    }

    public function test_another_user_cannot_touch_my_session(): void
    {
        $this->fakeAi();
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->postCard($sid)->assertStatus(404)->assertJsonPath('code', 'EKYC_SESSION_NOT_FOUND');
        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_th' => 'นาย ขโมย ตัวตน'])->assertStatus(404);
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertStatus(404);
        $this->postCard('not-a-uuid')->assertStatus(404);

        $this->assertSame('นาย ณัฐ ใจงาม', KycVerification::where('ekyc_session_id', $sid)->value('name_th'));
    }

    public function test_image_type_and_size_are_validated(): void
    {
        $this->fakeAi();
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $url = '/api/v1/ekyc/sessions/'.$sid.'/id-card';
        $json = ['Accept' => 'application/json'];

        $this->post($url, [], $json)->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');
        $this->post($url, ['image' => \Illuminate\Http\UploadedFile::fake()->createWithContent('x.jpg', str_repeat('not an image ', 100))], $json)
            ->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');
        $this->post($url, ['image' => \Illuminate\Http\UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')], $json)
            ->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');
        $this->post($url, ['image' => \Illuminate\Http\UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg')], $json)
            ->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');
        // รูปบัตรเล็กเกินไป (อ่านไม่ออก)
        $this->post($url, ['image' => \Illuminate\Http\UploadedFile::fake()->createWithContent('small.jpg', $this->jpeg(200, 120))], $json)
            ->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');

        Http::assertNothingSent();

        // เฟรมเสีย 1 เฟรม
        $this->postCard($sid)->assertOk();
        $frames = [$this->frameFile(0), \Illuminate\Http\UploadedFile::fake()->createWithContent('f.jpg', 'garbage'), $this->frameFile(2), $this->frameFile(3)];
        $this->post('/api/v1/ekyc/sessions/'.$sid.'/face', ['frames' => $frames, 'labels' => array_merge(['neutral'], $challenges)], $json)
            ->assertStatus(422)->assertJsonPath('code', 'EKYC_BAD_IMAGE');
        Http::assertNotSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/v1/face/verify'));
    }

    public function test_images_are_encrypted_at_rest_and_only_three_are_kept(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();
        // ถ่ายบัตรใหม่ในรอบเดียวกัน → ไฟล์ใบเก่าถูกลบ
        $this->postCard($sid)->assertOk();
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk();

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $files = Storage::disk('local')->allFiles('ekyc/'.$user->id);
        sort($files);
        $expected = [$kyc->id_card_image, $kyc->card_face_path, $kyc->best_frame_path];
        sort($expected);
        $this->assertSame($expected, $files, 'เก็บเฉพาะรูปบัตร + หน้าจากบัตร + เฟรมที่ดีที่สุด');
        $this->assertSame([], Storage::disk('public')->allFiles(), 'ห้ามมีรูป eKYC บน public disk');

        foreach ($expected as $path) {
            $stored = Storage::disk('local')->get($path);
            $this->assertNotSame("\xFF\xD8", substr($stored, 0, 2), 'ไฟล์บนดิสก์ต้องไม่ใช่ JPEG');
            $this->assertFalse(@getimagesizefromstring($stored), 'เปิดเป็นรูปตรงๆ ไม่ได้');
            $plain = \Illuminate\Support\Facades\Crypt::decryptString($stored);
            $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($plain)[2]);
        }

        // ไม่คืน path ไฟล์ให้แอป
        $this->getJson('/api/v1/ekyc/status')->assertDontSee('ekyc/'.$user->id, false);
    }

    public function test_purge_command_removes_stale_unfinished_sessions_only(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        [$sid] = $this->openSession();
        $this->postCard($sid)->assertOk();
        $card = KycVerification::where('ekyc_session_id', $sid)->value('id_card_image');

        $done = User::factory()->create();
        Sanctum::actingAs($done);
        $this->runFullFlow()->assertJsonPath('data.decision', 'approved');

        $this->travel(2)->hours();
        $this->artisan('ekyc:purge-stale')->assertSuccessful();

        $this->assertFalse(KycVerification::where('ekyc_session_id', $sid)->exists());
        Storage::disk('local')->assertMissing($card);
        $this->assertTrue(KycVerification::where('user_id', $done->id)->where('status', 'approved')->exists(), 'รอบที่ตัดสินแล้วไม่ถูกแตะ');
        $this->assertSame(0, KycVerification::where('user_id', $user->id)->count());
    }
}
