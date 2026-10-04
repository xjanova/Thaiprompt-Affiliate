<?php

namespace Tests\Feature\Ekyc;

use App\Models\KycVerification;
use App\Models\Notification;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\Ekyc\EkycException;
use App\Services\Ekyc\EkycImages;
use App\Services\Ekyc\EkycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 AI eKYC — รอบแก้ผลรีวิวความปลอดภัย (2026-10-04)
 *
 * AI ไม่ว่าง = ลองซ้ำ (ไม่ตกคิวแอดมิน) · เพดานส่งรูปบัตร · ค่า "ไม่ทราบ" เป็น null
 * ถ่ายใหม่จากหน้ารอตรวจ · ไม่ลบรอบที่กำลังตรวจ · แอดมินปฏิเสธแล้วมีผลต่อ · บัตรซ้ำตอนแอดมินอนุมัติ
 * ภาพชุดเดิมส่งซ้ำ · ไม่เจอหน้าบนบัตร ≠ คนละคน · ข้อความยินยอมต้องตรงเวอร์ชัน · ลบรูปตามกำหนด · แจ้งเตือนแอดมินถูกลบพร้อมบัญชี
 */
#[Group('ekyc')]
class EkycHardeningTest extends TestCase
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

    /**
     * เลขบัตรที่ checksum ถูกต้อง (12 หลักแรกกำหนดเอง)
     */
    private function validId(string $first12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $first12[$i] * (13 - $i);
        }

        return $first12.((11 - $sum % 11) % 10);
    }

    /**
     * เฟรมที่มีรายละเอียด (สุ่มสี่เหลี่ยมตาม seed) — seed เดียวกัน = ภาพเดียวกันเป๊ะ
     */
    private function patternJpeg(int $seed): string
    {
        mt_srand($seed);
        $img = imagecreatetruecolor(480, 640);
        for ($i = 0; $i < 60; $i++) {
            $color = imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            $x = mt_rand(0, 440);
            $y = mt_rand(0, 600);
            imagefilledrectangle($img, $x, $y, $x + mt_rand(20, 160), $y + mt_rand(20, 160), $color);
        }
        mt_srand();

        ob_start();
        imagejpeg($img, null, 90);

        return (string) ob_get_clean();
    }

    /**
     * ทำครบรอบด้วยเฟรมลายตาม seed (seed ซ้ำ = ภาพชุดเดิม)
     */
    private function runFlowWithFrames(int $seedBase): TestResponse
    {
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        $labels = array_merge(['neutral'], $challenges);
        $frames = [];
        foreach ($labels as $i => $label) {
            $frames[] = UploadedFile::fake()->createWithContent('f'.$i.'.jpg', $this->patternJpeg($seedBase + $i));
        }

        return $this->post('/api/v1/ekyc/sessions/'.$sid.'/face', ['frames' => $frames, 'labels' => $labels], ['Accept' => 'application/json']);
    }

    private function busyAi(): \Closure
    {
        return fn () => Http::response(['ok' => false, 'error' => 'BUSY'], 503);
    }

    // =====================================================
    // AI ไม่ว่าง = ลองซ้ำ
    // =====================================================

    public function test_ai_busy_on_card_asks_to_retry_and_keeps_the_session(): void
    {
        $this->fakeAi($this->busyAi());
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCard($sid)
            ->assertStatus(503)
            ->assertJsonPath('code', 'EKYC_AI_BUSY')
            ->assertJsonPath('data.retry_after_seconds', 15);

        // AI ว่างแล้ว → ส่งรูปเดิมซ้ำในรอบเดิมได้
        $this->fakeAi();
        $this->postCard($sid)->assertOk()->assertJsonPath('data.status', 'ok')->assertJsonPath('data.ai_available', true);
    }

    public function test_ai_busy_on_face_returns_the_session_without_using_an_attempt(): void
    {
        $this->fakeAi(null, $this->busyAi());
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        $this->postFace($sid, array_merge(['neutral'], $challenges))
            ->assertStatus(503)
            ->assertJsonPath('code', 'EKYC_AI_BUSY');

        $row = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame('draft', $row->status);
        $this->assertSame(EkycService::STEP_CARD, $row->ekyc_step, 'คืนรอบให้ส่งเฟรมเดิมซ้ำได้');
        $this->assertNull($row->best_frame_path);

        $this->fakeAi();
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()->assertJsonPath('data.decision', 'approved');
        $this->assertSame(3, app(EkycService::class)->attemptsLeft($user->fresh()));
    }

    // =====================================================
    // เพดานส่งรูปบัตร + ค่า "ไม่ทราบ"
    // =====================================================

    public function test_card_uploads_are_capped_per_session(): void
    {
        config(['ekyc.max_card_uploads_per_session' => 2]);
        $this->fakeAi();
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCard($sid)->assertOk();
        $this->postCard($sid)->assertOk();
        $this->postCard($sid)->assertStatus(429)->assertJsonPath('code', 'EKYC_CARD_LIMIT');

        // เริ่มรอบใหม่แล้วส่งได้อีก
        [$sid2] = $this->openSession();
        $this->postCard($sid2)->assertOk();
    }

    public function test_card_uploads_are_capped_per_day(): void
    {
        config(['ekyc.max_card_uploads_per_day' => 2, 'ekyc.max_card_uploads_per_session' => 10]);
        $this->fakeAi();
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCard($sid)->assertOk();
        $this->postCard($sid)->assertOk();
        $this->postCard($sid)->assertStatus(429)->assertJsonPath('code', 'EKYC_TOO_MANY_ATTEMPTS');
    }

    public function test_unknown_card_checks_are_null_not_false(): void
    {
        // อ่านเลขบัตร/วันหมดอายุ/ความเป็นบัตรจริงไม่ได้ → null (แอปแสดงเป็นกลาง ไม่ใช่ตัวแดง)
        $this->fakeAi($this->goodCard(['card_real_score' => null, 'reasons' => ['EXPIRY_UNKNOWN']], ['expiry_date' => null]));
        $this->ekycUser();
        [$sid] = $this->openSession();

        $res = $this->postCard($sid)->assertOk();
        $this->assertNull($res->json('data.checks.not_expired'));
        $this->assertNull($res->json('data.checks.card_real'));
        $this->assertTrue($res->json('data.checks.checksum'));

        // AI ล่มจริง (ไม่ใช่คิวเต็ม) → ไปต่อได้ + บอกแอปว่าตัวอ่านอัตโนมัติไม่พร้อม
        $this->fakeAi(fn () => Http::response('oops', 500));
        [$sid2] = $this->openSession();
        $down = $this->postCard($sid2)->assertOk();
        $this->assertFalse($down->json('data.ai_available'));
        $this->assertNull($down->json('data.checks.checksum'));
        $this->assertNull($down->json('data.checks.not_expired'));
    }

    public function test_lifelong_card_needs_an_owner_aged_seventy(): void
    {
        $this->fakeAi($this->goodCard(['reasons' => ['EXPIRY_LIFELONG']], ['expiry_date' => null, 'birth_date' => '1990-05-01']));
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->assertNull($this->postCard($sid)->assertOk()->json('data.checks.not_expired'));
        $face = $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk();
        $this->assertSame('review', $face->json('data.decision'));
        $this->assertContains('EXPIRY_UNKNOWN', $face->json('data.reasons'));
    }

    // =====================================================
    // ถ่ายใหม่จากหน้ารอตรวจ + รอบที่กำลังตรวจ
    // =====================================================

    public function test_borderline_review_can_be_retaken_and_costs_one_attempt(): void
    {
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.40, 'score' => 0.6]]));
        $user = $this->ekycUser();
        $this->runFullFlow()->assertOk()->assertJsonPath('data.decision', 'review');
        $this->assertSame('pending', $user->fresh()->kyc_status);

        $this->getJson('/api/v1/ekyc/status')
            ->assertJsonPath('data.can_start', false)
            ->assertJsonPath('data.can_retake', true)
            ->assertJsonPath('data.processing', false);

        $review = KycVerification::where('user_id', $user->id)->where('status', 'pending')->firstOrFail();

        $this->fakeAi();
        $this->runFullFlow()->assertOk()->assertJsonPath('data.decision', 'approved');

        $review->refresh();
        $this->assertSame(KycVerification::STATUS_SUPERSEDED, $review->status);
        $this->assertSame(EkycService::STEP_RETAKEN, $review->ekyc_step);
        $this->assertSame(2, app(EkycService::class)->attemptsLeft($user->fresh()), 'ขอถ่ายใหม่จากหน้ารอตรวจนับ 1 สิทธิ์');
        $this->assertSame('approved', $user->fresh()->kyc_status);
    }

    public function test_review_for_fraud_signals_cannot_be_retaken(): void
    {
        // บัญชีอื่นยืนยันด้วยบัตรนี้แล้ว → DUPLICATE_ID → ต้องรอแอดมินเท่านั้น
        $this->fakeAi();
        $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'approved');

        $this->ekycUser();
        $result = $this->runFullFlow()->assertOk();
        $this->assertSame('review', $result->json('data.decision'));
        $this->assertContains('DUPLICATE_ID', $result->json('data.reasons'));

        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.can_retake', false);
        $this->startSession()->assertStatus(409)->assertJsonPath('code', 'EKYC_PENDING_REVIEW');
    }

    public function test_a_session_being_processed_is_never_deleted_by_a_new_start(): void
    {
        $this->fakeAi();
        $user = $this->ekycUser();
        [$sid] = $this->openSession();
        $this->postCard($sid)->assertOk();
        KycVerification::where('ekyc_session_id', $sid)->update(['ekyc_step' => EkycService::STEP_PROCESSING, 'updated_at' => now()]);

        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.processing', true);
        $this->startSession()->assertStatus(409)->assertJsonPath('code', 'EKYC_PROCESSING');
        $this->assertTrue(KycVerification::where('ekyc_session_id', $sid)->exists());

        // ส่งเฟรมซ้ำระหว่างกำลังตรวจ → 409 EKYC_PROCESSING (ไม่ใช่ 410 ให้เริ่มใหม่)
        $this->postFace($sid, ['neutral', 'blink', 'smile', 'nod'])->assertStatus(409)->assertJsonPath('code', 'EKYC_PROCESSING');

        // ค้างนานเกินเวลา = ถือว่าค้าง เริ่มใหม่ทับได้
        KycVerification::where('ekyc_session_id', $sid)->update(['updated_at' => now()->subMinutes(10)]);
        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.processing', false);
        $this->startSession()->assertCreated();
        $this->assertFalse(KycVerification::where('ekyc_session_id', $sid)->exists());
        $this->assertSame(1, KycVerification::where('user_id', $user->id)->count());
    }

    public function test_resubmitting_a_decided_session_says_done(): void
    {
        $this->fakeAi();
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();
        $labels = array_merge(['neutral'], $challenges);
        $this->postFace($sid, $labels)->assertOk();

        $this->postFace($sid, $labels)->assertStatus(409)->assertJsonPath('code', 'EKYC_SESSION_DONE');
    }

    // =====================================================
    // แอดมินปฏิเสธแล้วมีผลต่อ + บัตรซ้ำตอนแอดมินอนุมัติ
    // =====================================================

    public function test_admin_rejection_sticks_for_the_account_and_the_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(EkycService::class);

        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.40, 'score' => 0.6]]));
        $user = $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');
        $service->adminDecide(KycVerification::where('user_id', $user->id)->where('status', 'pending')->firstOrFail(), $admin, 'rejected', 'รูปจากจอ');

        // รอบใหม่ผลดีทุกข้อ → AI อนุมัติเองไม่ได้ ต้องให้คนตรวจ
        Sanctum::actingAs($user->fresh());
        $this->fakeAi();
        $again = $this->runFullFlow()->assertOk();
        $this->assertSame('review', $again->json('data.decision'));
        $this->assertContains('PRIOR_REJECTED', $again->json('data.reasons'));

        // บัญชีใหม่ที่ใช้บัตรใบเดียวกันก็เช่นกัน
        $this->ekycUser();
        $other = $this->runFullFlow()->assertOk();
        $this->assertSame('review', $other->json('data.decision'));
        $this->assertContains('PRIOR_REJECTED', $other->json('data.reasons'));
    }

    public function test_admin_cannot_approve_a_card_that_another_account_verified_meanwhile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $service = app(EkycService::class);

        // บัญชี A ค้างรอตรวจ (ใบหน้าก้ำกึ่ง)
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.40, 'score' => 0.6]]));
        $a = $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');
        $rowA = KycVerification::where('user_id', $a->id)->where('status', 'pending')->firstOrFail();

        // ระหว่างนั้นบัญชี B ใช้บัตรเดียวกัน AI อนุมัติเอง
        $this->fakeAi();
        $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'approved');

        $this->assertTrue($service->adminSummary($rowA->fresh('user'))['duplicate_now']);

        try {
            $service->adminDecide($rowA->fresh(), $admin, 'approved');
            $this->fail('ต้องอนุมัติซ้ำไม่ได้');
        } catch (EkycException $e) {
            $this->assertSame('EKYC_DUPLICATE_ID', $e->errorCode);
        }
        $this->assertSame('pending', $rowA->fresh()->status);
        $this->assertNotSame('approved', $a->fresh()->kyc_status);

        // ปฏิเสธ/ขอถ่ายใหม่ยังทำได้
        $service->adminDecide($rowA->fresh(), $admin, 'rejected');
        $this->assertSame('rejected', $rowA->fresh()->status);
    }

    // =====================================================
    // ภาพชุดเดิมส่งซ้ำ + ไม่เจอหน้าบนบัตร
    // =====================================================

    public function test_replaying_the_same_frames_goes_to_review(): void
    {
        $this->fakeAi(null, $this->goodFace());

        // A ยืนยันผ่านด้วยภาพชุดหนึ่ง
        $this->fakeAi($this->goodCard([], ['id_number' => $this->validId('110370001234')]));
        $this->ekycUser();
        $this->runFlowWithFrames(100)->assertOk()->assertJsonPath('data.decision', 'approved');

        // B (บัตรอีกใบ) ส่งภาพชุดเดิมเป๊ะ → สงสัยเอาภาพนิ่งมาเรียงใหม่
        $this->fakeAi($this->goodCard([], ['id_number' => $this->validId('310370005678')]));
        $this->ekycUser();
        $replay = $this->runFlowWithFrames(100)->assertOk();
        $this->assertSame('review', $replay->json('data.decision'));
        $this->assertContains('REPLAY_SUSPECTED', $replay->json('data.reasons'));
        $this->getJson('/api/v1/ekyc/status')->assertJsonPath('data.can_retake', false);

        // C (บัตรอีกใบ) ภาพใหม่ทั้งชุด → อนุมัติตามปกติ
        $this->fakeAi($this->goodCard([], ['id_number' => $this->validId('510370009012')]));
        $this->ekycUser();
        $this->runFlowWithFrames(900)->assertOk()->assertJsonPath('data.decision', 'approved');
    }

    public function test_frame_fingerprint_ignores_reencoding_and_flat_images(): void
    {
        $images = app(EkycImages::class);
        $a = $images->dhash($this->patternJpeg(7));
        $other = $images->dhash($this->patternJpeg(8));

        // เข้ารหัส JPEG ใหม่ที่คุณภาพต่ำลง = ภาพเดิม
        $src = imagecreatefromstring($this->patternJpeg(7));
        ob_start();
        imagejpeg($src, null, 55);
        $recoded = $images->dhash((string) ob_get_clean());

        $this->assertSame(128, strlen((string) $a));
        $this->assertSame(0, EkycImages::hashDistance($a, $images->dhash($this->patternJpeg(7))), 'ไฟล์เดิม = ลายนิ้วมือเดิม');
        $this->assertLessThanOrEqual(6, EkycImages::hashDistance($a, $recoded));
        $this->assertGreaterThan(30, EkycImages::hashDistance($a, $other));
        $this->assertTrue(EkycImages::hashInformative($a));
        // ภาพสีเรียบ/ไล่สีแนวนอน (เช่นปิดกล้อง) ไม่เอามาใช้จับภาพซ้ำ
        $this->assertFalse(EkycImages::hashInformative($images->dhash($this->jpeg(480, 640))));
    }

    public function test_missing_card_face_is_not_treated_as_a_different_person(): void
    {
        // AI 1.0.0 ส่ง cosine 0.0 เมื่อหาหน้าบนบัตรไม่เจอ — ต้องไม่กลายเป็น LOW_MATCH แล้วเสียสิทธิ์
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.0, 'score' => 0.0], 'reasons' => ['NO_CARD_FACE']]));
        $user = $this->ekycUser();

        $res = $this->runFullFlow()->assertOk();
        $this->assertSame('review', $res->json('data.decision'));
        $this->assertNotContains('LOW_MATCH', $res->json('data.reasons'));
        $this->assertContains('NO_MATCH_SCORE', $res->json('data.reasons'));
        $this->assertSame(3, app(EkycService::class)->attemptsLeft($user->fresh()));
        $this->assertNull(KycVerification::where('user_id', $user->id)->latest('id')->value('ai_face_match'));
    }

    public function test_same_person_false_without_ai_code_is_review_not_retake(): void
    {
        $this->fakeAi(null, $this->goodFace(['same_person_across_frames' => false, 'reasons' => ['MULTIPLE_FACES']]));
        $user = $this->ekycUser();

        $res = $this->runFullFlow()->assertOk();
        $this->assertSame('review', $res->json('data.decision'));
        $this->assertContains('MULTIPLE_FACES', $res->json('data.reasons'));
        $this->assertNotContains('DIFFERENT_PEOPLE', $res->json('data.reasons'));
        $this->assertSame(3, app(EkycService::class)->attemptsLeft($user->fresh()));
    }

    // =====================================================
    // ยินยอม · กุญแจ hash · ลบรูปตามกำหนด · ลบบัญชี
    // =====================================================

    public function test_consent_must_be_for_the_current_text(): void
    {
        $this->ekycUser();
        $this->postJson('/api/v1/ekyc/sessions', ['consent' => true, 'consent_version' => '2025-01-01'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'EKYC_CONSENT_OUTDATED');
        $this->startSession()->assertCreated();
    }

    public function test_id_hash_uses_its_own_key_when_set(): void
    {
        config(['ekyc.hash_key' => '']);
        $withAppKey = EkycService::idHash(self::VALID_ID);
        config(['ekyc.hash_key' => 'separate-ekyc-hash-key-for-tests']);
        $withOwnKey = EkycService::idHash(self::VALID_ID);

        $this->assertNotSame($withAppKey, $withOwnKey);
        $this->assertSame(hash_hmac('sha256', 'ekyc-id:'.self::VALID_ID, 'separate-ekyc-hash-key-for-tests'), $withOwnKey);
    }

    public function test_old_retake_and_rejected_images_are_purged_on_schedule(): void
    {
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.05, 'score' => 0.0]]));
        $user = $this->ekycUser();
        $this->runFullFlow()->assertJsonPath('data.decision', 'retake');
        $retake = KycVerification::where('user_id', $user->id)->where('status', KycVerification::STATUS_RETAKE)->firstOrFail();
        $paths = array_filter([$retake->id_card_image, $retake->card_face_path, $retake->best_frame_path]);
        $this->assertNotEmpty($paths);

        $service = app(EkycService::class);

        // ยังไม่ครบ 30 วัน → ไม่ลบ
        $this->assertSame(0, $service->purgeExpiredImages());

        KycVerification::whereKey($retake->id)->update(['updated_at' => now()->subDays(31)]);
        $this->assertSame(1, $service->purgeExpiredImages());
        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
        $retake->refresh();
        $this->assertNull($retake->id_card_image);
        $this->assertNull($retake->best_frame_path);
        $this->assertSame(KycVerification::STATUS_RETAKE, $retake->status, 'แถวยังอยู่ (นับสิทธิ์/กันโกง)');

        // รอบที่อนุมัติไม่ถูกแตะ
        $this->fakeAi();
        $this->runFullFlow()->assertJsonPath('data.decision', 'approved');
        $approved = KycVerification::where('user_id', $user->id)->where('status', 'approved')->firstOrFail();
        KycVerification::whereKey($approved->id)->update(['updated_at' => now()->subDays(400)]);
        $this->assertSame(0, $service->purgeExpiredImages());
        Storage::disk('local')->assertExists($approved->id_card_image);
    }

    public function test_account_deletion_also_removes_admin_notifications_naming_the_user(): void
    {
        User::factory()->create(['role' => 'admin']);
        $this->fakeAi(null, $this->goodFace(['match' => ['cosine' => 0.40, 'score' => 0.6]]));
        $user = $this->ekycUser(['password' => Hash::make('secret-pass-123')]);
        $this->runFullFlow()->assertJsonPath('data.decision', 'review');

        $kycId = KycVerification::where('user_id', $user->id)->where('status', 'pending')->value('id');
        $this->assertGreaterThan(0, Notification::withTrashed()->where('notifiable_type', KycVerification::class)->where('notifiable_id', $kycId)->count());

        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/v1/account', [
            'confirm_text' => AccountDeletionService::CONFIRM_TEXT,
            'password' => 'secret-pass-123',
        ])->assertOk();

        $this->assertSame(0, Notification::withTrashed()->where('notifiable_type', KycVerification::class)->where('notifiable_id', $kycId)->count());
    }
}
