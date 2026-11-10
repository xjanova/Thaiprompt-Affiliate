<?php

namespace Tests\Feature\Ekyc;

use App\Models\KycVerification;
use App\Models\User;
use App\Services\Ekyc\EkycService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use Tests\Feature\Ekyc\Concerns\BuildsEkycFixtures;
use Tests\TestCase;

/**
 * 🪪 eKYC หลายเอกสาร (2026-11-10)
 *
 * ใบขับขี่ไทย = เลข 13 หลักเดียวกับบัตร (จับบัญชีซ้ำข้ามเอกสารได้) · หนังสือเดินทางทุกประเทศ = MRZ + check digit
 * เอกสารที่ยังอ่านอัตโนมัติไม่ได้ = ไม่ต้องถ่ายวน ไปรอแอดมินตรวจ · แก้ชื่อเพี้ยนเล็กน้อยยังอนุมัติอัตโนมัติได้
 */
#[Group('ekyc')]
class EkycDocumentTypesTest extends TestCase
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

    private function postCardAs(string $sessionId, string $docType): TestResponse
    {
        return $this->post('/api/v1/ekyc/sessions/'.$sessionId.'/id-card', [
            'image' => $this->cardFile(),
            'doc_type' => $docType,
        ], ['Accept' => 'application/json']);
    }

    /**
     * ผล AI ของหนังสือเดินทางที่อ่าน MRZ ผ่าน
     *
     * @return array<string, mixed>
     */
    private function passportCard(array $mrz = [], string $number = 'TK1234567'): array
    {
        return $this->goodCard([
            'doc_type' => 'passport',
            'document' => [
                'type' => 'passport', 'country' => 'JPN', 'nationality' => 'JPN', 'sex' => 'F', 'number' => $number,
                'mrz' => array_merge(['format' => 'TD3', 'valid' => true], $mrz),
            ],
            'field_confidence' => ['id_number' => 1.0, 'name_th' => 1.0, 'birth_date' => 1.0, 'expiry_date' => 1.0],
            'ocr_confidence' => 1.0,
            'id_checksum_ok' => ($mrz['valid'] ?? true) === true,
        ], ['id_number' => $number, 'name_th' => null, 'name_en' => 'HANAKO TANAKA']);
    }

    public function test_status_lists_documents_for_the_app(): void
    {
        $this->ekycUser();
        $docs = collect($this->getJson('/api/v1/ekyc/status')->assertOk()->json('data.documents'));

        $this->assertSame(['thai_id', 'thai_dl', 'passport', 'foreign_id', 'foreign_dl'], $docs->pluck('type')->all());
        $this->assertTrue($docs->firstWhere('type', 'passport')['auto']);
        $this->assertFalse($docs->firstWhere('type', 'foreign_dl')['auto']);
    }

    public function test_doc_type_is_sent_to_the_ai_and_unknown_types_are_rejected(): void
    {
        $sent = null;
        $this->fakeAi(function (HttpRequest $request) use (&$sent) {
            foreach ($request->data() as $part) {
                if (($part['name'] ?? null) === 'doc_type') {
                    $sent = $part['contents'];
                }
            }

            return Http::response($this->goodCard(), 200);
        });
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCardAs($sid, 'library_card')->assertStatus(422);
        $this->postCardAs($sid, 'thai_dl')->assertOk()->assertJsonPath('data.document.type', 'thai_dl');
        $this->assertSame('thai_dl', $sent);

        // แอปรุ่นเก่า (ไม่ส่ง doc_type) = บัตรประชาชน
        $this->postCard($sid)->assertOk()->assertJsonPath('data.document.type', 'thai_id');
    }

    public function test_thai_driving_licence_auto_approves_and_shares_the_id_hash_with_the_id_card(): void
    {
        $this->fakeAi($this->goodCard(['doc_type' => 'thai_dl', 'document' => ['type' => 'thai_dl', 'country' => 'THA']]));
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCardAs($sid, 'thai_dl')->assertOk()->assertJsonPath('data.status', 'ok');
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'approved');

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame('thai_dl', $kyc->document_type);
        $this->assertSame('THA', $kyc->document_country);
        // บัตรประชาชนใบจริงของคนเดียวกัน = hash เดียวกัน → บัญชีอื่นเอาบัตรมาใช้ซ้ำ ระบบจับได้
        $this->assertSame(EkycService::idHash(self::VALID_ID), $kyc->id_number_hash);
        $this->assertSame('approved', $user->fresh()->kyc_status);
    }

    public function test_passport_with_valid_mrz_auto_approves(): void
    {
        $this->fakeAi($this->passportCard());
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCardAs($sid, 'passport')->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.document.country', 'JPN')
            ->assertJsonPath('data.checks.checksum', true)
            ->assertJsonPath('data.fields.id_number_masked', '••••••567');
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'approved');

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame(['passport', 'JPN'], [$kyc->document_type, $kyc->document_country]);
        $this->assertSame(EkycService::documentHash('passport', 'JPN', 'TK1234567'), $kyc->id_number_hash);
        $this->assertNotSame(EkycService::documentHash('passport', 'USA', 'TK1234567'), $kyc->id_number_hash);
        $this->assertSame('approved', $user->fresh()->kyc_status);
        $this->assertSame('HANAKO', $user->fresh()->english_first_name);
    }

    public function test_passport_used_by_another_account_goes_to_review(): void
    {
        $this->fakeAi($this->passportCard());
        $other = User::factory()->create();
        KycVerification::create([
            'user_id' => $other->id, 'method' => KycVerification::METHOD_EKYC, 'status' => 'approved',
            'id_number_hash' => EkycService::documentHash('passport', 'JPN', 'TK1234567'),
        ]);
        $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        $this->postCardAs($sid, 'passport')->assertOk();
        $res = $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'review');
        $this->assertContains('DUPLICATE_ID', $res->json('data.reasons'));
    }

    public function test_passport_mrz_check_fail_asks_for_retake_first(): void
    {
        $card = $this->passportCard(['valid' => false]);
        $card['reasons'] = ['MRZ_CHECK_FAIL'];
        $this->fakeAi($card);
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCardAs($sid, 'passport')->assertOk()->assertJsonPath('data.status', 'retake');
        // ครั้งที่สอง = ไปต่อแบบกรอกเอง รอแอดมิน (เหมือนบัตรไทยที่อ่านไม่ได้)
        $this->postCardAs($sid, 'passport')->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.manual_review', true);
    }

    public function test_unsupported_foreign_licence_skips_straight_to_admin_review(): void
    {
        $this->fakeAi($this->goodCard([
            'doc_type' => 'foreign_dl', 'document' => ['type' => 'foreign_dl', 'country' => null, 'mrz' => null],
            'reasons' => ['DOC_MANUAL_REVIEW'], 'id_checksum_ok' => false, 'ocr_confidence' => 0.0,
        ], ['id_number' => null, 'name_th' => null, 'name_en' => null, 'birth_date' => null, 'expiry_date' => null]));
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();

        // ครั้งแรกเลย ไม่ต้องถ่ายวน
        $this->postCardAs($sid, 'foreign_dl')->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.ai_available', false)
            ->assertJsonPath('data.manual_review', true);

        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_en' => 'John Smith', 'birth_date' => '1990-05-01'])
            ->assertOk();
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'review');

        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertSame('pending', $kyc->status);
        $this->assertContains('DOC_MANUAL_REVIEW', $kyc->ai_reasons);
        $this->assertSame('John Smith', $kyc->name_en);
        $this->assertSame('pending', $user->fresh()->kyc_status);
    }

    public function test_unsupported_document_with_blurry_photo_still_asks_for_retake(): void
    {
        $this->fakeAi($this->goodCard([
            'doc_type' => 'foreign_dl', 'quality' => ['blur' => 0.9],
            'reasons' => ['DOC_MANUAL_REVIEW', 'BLURRY'],
        ], ['id_number' => null]));
        $this->ekycUser();
        [$sid] = $this->openSession();

        $this->postCardAs($sid, 'foreign_dl')->assertOk()->assertJsonPath('data.status', 'retake');
    }

    public function test_minor_thai_name_fix_still_auto_approves_but_a_different_name_does_not(): void
    {
        $this->fakeAi($this->goodCard([], ['name_th' => 'นาย ณัฐ ใจคาม']));   // AI อ่าน ง เป็น ค
        $user = $this->ekycUser();
        [$sid, $challenges] = $this->openSession();
        $this->postCard($sid)->assertOk();

        $this->patchJson('/api/v1/ekyc/sessions/'.$sid.'/id-card', ['name_th' => 'นาย ณัฐ ใจงาม'])
            ->assertOk()->assertJsonPath('data.corrected', true);
        $this->postFace($sid, array_merge(['neutral'], $challenges))->assertOk()
            ->assertJsonPath('data.decision', 'approved');
        $kyc = KycVerification::where('ekyc_session_id', $sid)->firstOrFail();
        $this->assertTrue($kyc->extracted_data['corrections']['name_th']['minor']);
        $this->assertSame('นาย ณัฐ ใจคาม', $kyc->extracted_data['corrections']['name_th']['ocr']);
        $this->assertSame('ใจงาม', $user->fresh()->thai_last_name);

        $this->assertFalse(EkycService::isMinorNameFix('นาย ณัฐ ใจงาม', 'นาย สมชาย ใจดี'));
        $this->assertFalse(EkycService::isMinorNameFix(null, 'นาย ณัฐ ใจงาม'));
        $this->assertFalse(EkycService::hasMajorCorrection(['name_th' => ['minor' => true]]));
        $this->assertTrue(EkycService::hasMajorCorrection(['birth_date' => ['ocr' => '1995-01-12', 'user' => '1995-01-13']]));
    }
}
