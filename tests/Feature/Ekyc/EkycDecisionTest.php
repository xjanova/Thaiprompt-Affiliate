<?php

namespace Tests\Feature\Ekyc;

use App\Services\Ekyc\EkycService;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * 🪪 กติกาตัดสิน eKYC (EkycService::decide) — ไม่แตะฐานข้อมูล
 */
#[Group('ekyc')]
class EkycDecisionTest extends TestCase
{
    private EkycService $ekyc;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ekyc.auto_approve_match' => 0.50,
            'ekyc.review_match' => 0.30,
            'ekyc.retake_match' => 0.15,
            'ekyc.min_liveness' => 0.80,
            'ekyc.min_real' => 0.70,
            'ekyc.spoof_retake_real' => 0.30,
            'ekyc.min_card_real' => 0.60,
            'ekyc.min_ocr' => 0.80,
            'ekyc.max_attempts_per_day' => 3,
        ]);
        $this->ekyc = app(EkycService::class);
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array{decision: string, reasons: array<int, string>}
     */
    private function decide(array $override = []): array
    {
        return $this->ekyc->decide(array_merge([
            'card_available' => true,
            'face_available' => true,
            'id_present' => true,
            'checksum_ok' => true,
            'expiry' => now()->addYears(3)->toDateString(),
            'lifelong' => false,
            'ocr' => 0.95,
            'card_real' => 0.9,
            'liveness_passed' => true,
            'liveness' => 0.95,
            'challenges' => ['blink' => true, 'smile' => true, 'nod' => true],
            'real' => 0.92,
            'cosine' => 0.62,
            'same_person' => true,
            'reasons' => [],
            'corrected' => false,
            'duplicate' => false,
            'retakes_today' => 0,
        ], $override));
    }

    public function test_all_green_is_approved(): void
    {
        $this->assertSame(['decision' => 'approved', 'reasons' => []], $this->decide());
        // บัตรตลอดชีพ: ผ่านเฉพาะเจ้าของบัตรอายุ 70 ขึ้นไป (ไม่รู้วันเกิด/อายุไม่ถึง = ส่งตรวจ)
        $this->assertSame('approved', $this->decide(['expiry' => null, 'lifelong' => true, 'birth_date' => now()->subYears(72)->toDateString(), 'reasons' => ['EXPIRY_LIFELONG']])['decision']);
        foreach ([null, now()->subYears(45)->toDateString()] as $birth) {
            $young = $this->decide(['expiry' => null, 'lifelong' => true, 'birth_date' => $birth, 'reasons' => ['EXPIRY_LIFELONG']]);
            $this->assertSame('review', $young['decision'], (string) $birth);
            $this->assertContains('EXPIRY_UNKNOWN', $young['reasons']);
        }
        // ตรงเกณฑ์พอดี
        $this->assertSame('approved', $this->decide(['cosine' => 0.50, 'liveness' => 0.80, 'real' => 0.70, 'card_real' => 0.60, 'ocr' => 0.80])['decision']);
    }

    public function test_each_threshold_below_minimum_goes_to_review(): void
    {
        foreach ([
            [['cosine' => 0.49], 'BORDERLINE_MATCH'],
            [['cosine' => 0.20], 'LOW_MATCH'],
            [['liveness' => 0.79], 'LOW_LIVENESS'],
            [['liveness_passed' => false], 'LOW_LIVENESS'],
            [['real' => 0.69], 'LOW_REAL'],
            [['card_real' => 0.59], 'LOW_CARD_REAL'],
            [['ocr' => 0.79], 'OCR_LOW'],
            [['expiry' => null], 'EXPIRY_UNKNOWN'],
            [['corrected' => true], 'USER_CORRECTED'],
            [['duplicate' => true], 'DUPLICATE_ID'],
            [['face_available' => false, 'cosine' => null, 'liveness' => null, 'real' => null, 'liveness_passed' => null], 'AI_UNAVAILABLE'],
            [['card_available' => false, 'id_present' => false, 'checksum_ok' => null, 'ocr' => null, 'card_real' => null], 'AI_UNAVAILABLE'],
            [['reasons' => ['MULTIPLE_FACES']], 'MULTIPLE_FACES'],
            [['reasons' => ['SPOOF_SUSPECTED'], 'real' => 0.5], 'SPOOF_SUSPECTED'],
            [['reasons' => ['NO_CARD_FACE'], 'cosine' => null], 'NO_CARD_FACE'],
            // same_person = false ที่ AI ไม่ได้ยืนยันว่าเป็นคนละคน (เช่นเจอหลายหน้า) = ส่งตรวจ ไม่ใช่ให้ถ่ายใหม่
            [['same_person' => false], 'FACES_INCONSISTENT'],
            [['same_person' => false, 'reasons' => ['MULTIPLE_FACES']], 'MULTIPLE_FACES'],
            [['replay' => true], 'REPLAY_SUSPECTED'],
            [['prior_rejected' => true], 'PRIOR_REJECTED'],
        ] as [$override, $reason]) {
            $result = $this->decide($override);
            $this->assertSame('review', $result['decision'], json_encode($override));
            $this->assertContains($reason, $result['reasons'], json_encode($override));
        }
    }

    public function test_clear_failures_ask_for_retake(): void
    {
        foreach ([
            [['reasons' => ['NO_FACE'], 'cosine' => null], 'NO_FACE'],
            [['checksum_ok' => false], 'ID_CHECKSUM_FAIL'],
            [['expiry' => now()->subDay()->toDateString()], 'EXPIRED'],
            [['challenges' => ['blink' => false, 'smile' => true, 'nod' => true]], 'CHALLENGE_FAILED:blink'],
            [['reasons' => ['SPOOF_SUSPECTED'], 'real' => 0.29], 'SPOOF_SUSPECTED'],
            [['same_person' => false, 'reasons' => ['DIFFERENT_PEOPLE']], 'DIFFERENT_PEOPLE'],
            [['cosine' => 0.14], 'LOW_MATCH'],
        ] as [$override, $reason]) {
            $result = $this->decide($override);
            $this->assertSame('retake', $result['decision'], json_encode($override));
            $this->assertContains($reason, $result['reasons'], json_encode($override));
        }

        // ไม่ผ่านชัดเจนชนะ AI ล่ม (รู้แน่แล้วว่าไม่ผ่าน)
        $this->assertSame('retake', $this->decide(['face_available' => false, 'cosine' => null, 'checksum_ok' => false])['decision']);
    }

    public function test_last_allowed_failure_goes_to_review_instead_of_retake(): void
    {
        $this->assertSame('retake', $this->decide(['cosine' => 0.05, 'retakes_today' => 1])['decision']);

        $result = $this->decide(['cosine' => 0.05, 'retakes_today' => 2]);
        $this->assertSame('review', $result['decision']);
        $this->assertContains('ATTEMPTS_EXHAUSTED', $result['reasons']);
    }

    public function test_masking_and_reason_texts(): void
    {
        $this->assertSame('1 2345 ••••• 12 1', EkycService::maskId('1234567890121'));
        $this->assertSame('1 2345 ••••• 12 1', EkycService::maskId('1-2345-67890-12-1'));
        $this->assertSame(['ทำท่า "หันซ้าย" ไม่ผ่าน', 'บัตรหมดอายุแล้ว', 'UNKNOWN_CODE'], EkycService::reasonTexts(['CHALLENGE_FAILED:turn_left', 'EXPIRED', 'UNKNOWN_CODE']));
        $this->assertSame(EkycService::idHash('1234567890121'), EkycService::idHash('1-2345-67890-12-1'));
        $this->assertSame(64, strlen(EkycService::idHash('1234567890121')));
    }
}
