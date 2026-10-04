<?php

namespace App\Services\Ekyc;

use App\Models\KycVerification;
use App\Models\User;
use App\Services\KycAutoCheckService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * 🪪 AI eKYC — ขั้นตอนยืนยันตัวตนทั้งหมดฝั่งเซิร์ฟเวอร์ (2026-10-04)
 *
 * รอบยืนยัน 1 รอบ = 1 แถว kyc_verifications (method = ekyc, status = draft ระหว่างทำ)
 *   1. startSession()  — ยินยอม PDPA → สุ่มคำสั่ง 3 ข้อ (เก็บใน DB) → อายุ 20 นาที
 *   2. submitIdCard()  — รูปบัตร → AI อ่านบัตร → เก็บรูปเข้ารหัส + ตัดรูปหน้าจากบัตร
 *   3. correctIdCard() — ผู้ใช้แก้ชื่อ/วันเกิด (แก้แล้ว = ต้องให้แอดมินตรวจเสมอ)
 *   4. submitFace()    — เฟรม neutral + คำสั่ง 3 ข้อ (ตามลำดับที่ให้) → AI ตรวจ → decide()
 *
 * ผลตัดสิน (decide):
 *   approved = ทุกข้อผ่านเกณฑ์ config/ekyc.php + ไม่มีเหตุผลค้าง + ไม่มีบัตรซ้ำ + ไม่ได้แก้ข้อมูล
 *   retake   = ไม่ผ่านชัดเจน (ไม่เจอหน้า · checksum ผิด · บัตรหมดอายุ · ทำท่าไม่ผ่าน · ของปลอมชัด · คนละคน · cosine ต่ำมาก)
 *              นับเป็น 1 ครั้ง — ครั้งที่ไปถึงเพดานต่อวันจะส่งแอดมินตรวจแทน (ผู้ใช้ไม่ติดตาย)
 *   review   = นอกนั้นทั้งหมด (รวม AI ล่ม/หมดเวลา · บัตรซ้ำ · ผู้ใช้แก้ข้อมูล) → status pending + แจ้งแอดมิน
 *
 * users.kyc_status / kyc_verified_at เป็นคอลัมน์ guarded → forceFill เท่านั้น
 * รูปที่เก็บหลังตัดสิน = รูปบัตร + หน้าจากบัตร + เฟรมที่ดีที่สุด (เฟรมอื่นไม่เคยถูกเขียนลงดิสก์)
 */
class EkycService
{
    /** คำสั่งที่สุ่มได้ */
    public const CHALLENGES = ['blink', 'turn_left', 'turn_right', 'smile', 'nod'];

    public const NEUTRAL = 'neutral';

    /** จำนวนคำสั่งต่อรอบ */
    public const CHALLENGE_COUNT = 3;

    /** ใช้กับอะไรบ้าง (แสดงในแอป) */
    public const REQUIRED_FOR = ['order', 'rider', 'seller'];

    public const STEP_CONSENTED = 'consented';

    public const STEP_CARD = 'card';

    public const STEP_PROCESSING = 'processing';

    public const STEP_DONE = 'done';

    /** แถวที่รอแอดมินตรวจ แต่ผู้ใช้ขอถ่ายใหม่เอง (status = superseded) — นับเป็น 1 สิทธิ์ถ่ายใหม่ */
    public const STEP_RETAKEN = 'retaken';

    /** เหตุผลจาก AI ที่ไม่ขวางการอนุมัติอัตโนมัติ */
    private const BENIGN_REASONS = ['EXPIRY_LIFELONG'];

    /**
     * เคสรอตรวจที่ผู้ใช้ขอถ่ายใหม่เองได้ — เฉพาะเหตุผลด้านคุณภาพภาพ/ความมั่นใจของ AI
     * (บัตรซ้ำ · ภาพซ้ำ · เคยถูกปฏิเสธ · บัตรจากจอ · ลองครบแล้ว = ต้องรอแอดมินเท่านั้น)
     */
    private const RETAKEABLE_REVIEW_REASONS = [
        'BORDERLINE_MATCH', 'LOW_MATCH', 'NO_MATCH_SCORE', 'LOW_LIVENESS', 'LOW_REAL', 'MULTIPLE_FACES', 'FACES_INCONSISTENT',
        'OCR_LOW', 'ID_UNREADABLE', 'EXPIRY_UNKNOWN', 'EXPIRY_LIFELONG', 'USER_CORRECTED', 'AI_UNAVAILABLE', 'CARD_IMAGE_MISSING',
    ];

    /** error จากบริการ AI ที่แปลว่า "คิวเต็ม/ตรวจไม่ทันชั่วคราว" → ให้แอปลองซ้ำ ไม่ใช่ส่งแอดมินตรวจ */
    private const AI_BUSY_ERRORS = ['HTTP_503', 'HTTP_504'];

    /** เหตุผลจากรอบอ่านบัตรที่ต้องถ่ายบัตรใหม่ */
    private const CARD_RETAKE_REASONS = ['NO_CARD', 'BLURRY', 'GLARE', 'CARD_INCOMPLETE', 'NO_CARD_FACE', 'ID_CHECKSUM_FAIL', 'EXPIRED'];

    /** รหัสเหตุผล → ข้อความไทย (แอดมิน + แอป) */
    public const REASON_LABELS = [
        'NO_CARD' => 'ไม่พบบัตรในรูป',
        'BLURRY' => 'รูปบัตรเบลอ',
        'GLARE' => 'มีแสงสะท้อนบนบัตร',
        'CARD_INCOMPLETE' => 'ถ่ายบัตรไม่ครบทั้งใบ',
        'OCR_LOW' => 'อ่านตัวอักษรบนบัตรได้ไม่ชัด',
        'ID_UNREADABLE' => 'อ่านเลขบัตรไม่ได้',
        'ID_CHECKSUM_FAIL' => 'เลขบัตรไม่ผ่านการตรวจสอบ',
        'EXPIRED' => 'บัตรหมดอายุแล้ว',
        'EXPIRY_LIFELONG' => 'บัตรตลอดชีพ',
        'EXPIRY_UNKNOWN' => 'อ่านวันหมดอายุไม่ได้',
        'NO_CARD_FACE' => 'ไม่พบรูปหน้าบนบัตร',
        'NO_FACE' => 'ไม่พบใบหน้าในกล้อง',
        'MULTIPLE_FACES' => 'พบหลายใบหน้าในกล้อง',
        'CHALLENGE_FAILED' => 'ทำท่าทางไม่ครบตามที่ระบบให้ทำ',
        'SPOOF_SUSPECTED' => 'สงสัยว่าไม่ใช่ใบหน้าจริง (รูปจากจอ/กระดาษ)',
        'DIFFERENT_PEOPLE' => 'ใบหน้าในแต่ละช่วงไม่ใช่คนเดียวกัน',
        'LOW_MATCH' => 'ใบหน้าไม่ค่อยตรงกับรูปบนบัตร',
        'BORDERLINE_MATCH' => 'ใบหน้าคล้ายรูปบนบัตรระดับกลาง',
        'NO_MATCH_SCORE' => 'เทียบใบหน้ากับบัตรไม่ได้',
        'LOW_LIVENESS' => 'การทำท่าทางยืนยันว่าเป็นคนจริงยังไม่ชัด',
        'LOW_REAL' => 'ความเป็นใบหน้าจริงต่ำกว่าเกณฑ์',
        'LOW_CARD_REAL' => 'สงสัยว่าเป็นบัตรจากจอหรือสำเนา',
        'USER_CORRECTED' => 'ผู้ใช้แก้ไขข้อมูลจากบัตรเอง',
        'DUPLICATE_ID' => 'เลขบัตรนี้ยืนยันกับบัญชีอื่นไว้แล้ว',
        'AI_UNAVAILABLE' => 'ระบบ AI ไม่พร้อม ส่งให้เจ้าหน้าที่ตรวจ',
        'ATTEMPTS_EXHAUSTED' => 'ลองครบจำนวนครั้งแล้ว ส่งให้เจ้าหน้าที่ตรวจ',
        'CARD_IMAGE_MISSING' => 'ไม่พบรูปบัตรของรอบนี้',
        'ADMIN_REJECTED' => 'เจ้าหน้าที่ปฏิเสธการยืนยันตัวตน',
        'ADMIN_RETAKE' => 'เจ้าหน้าที่ขอให้ถ่ายใหม่',
        'REPLAY_SUSPECTED' => 'ภาพใบหน้าซ้ำกับการยืนยันครั้งก่อน ต้องให้เจ้าหน้าที่ตรวจ',
        'PRIOR_REJECTED' => 'เคยถูกเจ้าหน้าที่ปฏิเสธการยืนยันตัวตน ต้องให้เจ้าหน้าที่ตรวจ',
        'FACES_INCONSISTENT' => 'ยืนยันไม่ได้ว่าใบหน้าทุกช่วงเป็นคนเดียวกัน',
    ];

    /** ป้ายคำสั่ง (ภาษาไทย) */
    public const CHALLENGE_LABELS = [
        'neutral' => 'มองตรง',
        'blink' => 'กะพริบตา',
        'turn_left' => 'หันซ้าย',
        'turn_right' => 'หันขวา',
        'smile' => 'ยิ้ม',
        'nod' => 'พยักหน้า',
    ];

    public function __construct(
        private readonly EkycAiClient $ai,
        private readonly EkycImages $images,
        private readonly NotificationService $notifications,
    ) {}

    // =====================================================
    // สถานะ
    // =====================================================

    /**
     * ป้าย "ยืนยันตัวตนแล้ว" ของผู้ใช้ (ใช้ใน PersonCard ทุกที่ — อ่านคอลัมน์เดียว ไม่ query เพิ่ม)
     */
    public static function badge(?User $user): bool
    {
        return $user !== null && $user->kyc_status === 'approved';
    }

    /**
     * สถานะ KYC แบบที่แอปใช้: none | pending | approved | rejected
     */
    public static function publicStatus(User $user): string
    {
        if ($user->kyc_status === 'approved') {
            return 'approved';
        }

        return match ($user->kyc_status) {
            'pending' => 'pending',
            'rejected' => 'rejected',
            default => 'none',
        };
    }

    /**
     * GET /ekyc/status
     *
     * @return array<string, mixed>
     */
    public function status(User $user): array
    {
        $user->refresh();
        $verified = $user->isKycVerified();
        $last = $this->lastDecidedRow($user);
        $attemptsLeft = $this->attemptsLeft($user);

        $reasons = [];
        $message = null;
        if ($last) {
            $reasons = $last->isEkyc() ? array_values((array) ($last->ai_reasons ?? [])) : [];
            if ($last->reviewed_by && $last->status === 'rejected') {
                $reasons[] = 'ADMIN_REJECTED';
                $message = $last->rejection_reason;
            } elseif ($last->reviewed_by && $last->status === KycVerification::STATUS_RETAKE) {
                $reasons[] = 'ADMIN_RETAKE';
                $message = $last->rejection_reason;
            }
            $reasons = array_values(array_unique($reasons));
        }

        $approvedRow = $verified
            ? KycVerification::where('user_id', $user->id)->where('status', 'approved')->latest('id')->first()
            : null;
        $approvedId = $approvedRow ? self::plainId($approvedRow) : null;
        $pending = $verified ? null : $this->pendingEkycReviewRow($user);

        return [
            'kyc_status' => $verified ? 'approved' : self::publicStatus($user),
            'verified' => $verified,
            'verified_at' => $verified ? ($user->kyc_verified_at ?? $approvedRow?->reviewed_at ?? $approvedRow?->processed_at)?->toIso8601String() : null,
            'method' => ($approvedRow ?? $last)?->method,
            'can_start' => $this->canStart($user, $verified, $attemptsLeft),
            // รอแอดมินตรวจด้วยเหตุผลด้านคุณภาพภาพ → ขอถ่ายใหม่เองได้ (รอบใหม่จะแทนที่เคสที่รอตรวจ)
            'can_retake' => $pending !== null && $attemptsLeft > 0 && $this->isRetakeableReview($pending),
            // มีรอบที่กำลังตรวจอยู่ (เช่นเน็ตหลุดระหว่างส่ง) — แอปถามสถานะซ้ำจนกว่าจะได้ผล
            'processing' => ! $verified && $this->hasProcessingSession($user),
            'attempts_left' => $attemptsLeft,
            'last_decision' => $last ? $this->effectiveDecision($last) : null,
            'reasons' => $reasons,
            'reason_texts' => self::reasonTexts($reasons),
            'message' => $message,
            'required_for' => $this->enforced() ? self::REQUIRED_FOR : [],
            // ข้อความยินยอม PDPA เวอร์ชันปัจจุบัน (แอปส่งกลับมาตอนเริ่มรอบ)
            'consent_version' => (string) config('ekyc.consent_version', ''),
            // หน้าโปรไฟล์ของเจ้าของบัญชี (เห็นเฉพาะตัวเอง) — ชื่อตามบัตร + เลขบัตรแบบปิดบางส่วน
            'name_th' => $approvedRow?->name_th,
            'id_number_masked' => $approvedRow
                ? ($approvedId !== null
                    ? self::maskId($approvedId)
                    : ($approvedRow->id_last4 ? '•••••••••'.$approvedRow->id_last4 : null))
                : null,
        ];
    }

    /**
     * ถ่ายใหม่ได้อีกกี่ครั้งวันนี้ (นับรอบที่ AI ให้ถ่ายใหม่ + เคสรอตรวจที่ผู้ใช้ขอถ่ายใหม่เอง ตั้งแต่เที่ยงคืนเวลาไทย)
     */
    public function attemptsLeft(User $user): int
    {
        $used = KycVerification::query()
            ->where('user_id', $user->id)
            ->where('method', KycVerification::METHOD_EKYC)
            ->where(function ($q) {
                $q->where('ai_decision', 'retake')
                    ->orWhere('ekyc_step', self::STEP_RETAKEN);
            })
            ->where('processed_at', '>=', now()->startOfDay())
            ->count();

        return max(0, $this->maxAttempts() - $used);
    }

    // =====================================================
    // 1) เริ่มรอบ
    // =====================================================

    /**
     * POST /ekyc/sessions
     *
     * @return array{session_id: string, challenges: array<int, string>, expires_at: string, attempts_left: int}
     *
     * @throws EkycException
     */
    public function startSession(User $user, mixed $consent, mixed $consentVersion): array
    {
        $user->refresh();

        if ($user->isKycVerified()) {
            throw EkycException::alreadyVerified();
        }

        $version = is_string($consentVersion) ? trim($consentVersion) : '';
        if (! self::isAccepted($consent) || $version === '' || ! preg_match('/^[\w.\-]{1,20}$/', $version)) {
            throw EkycException::consentRequired();
        }
        // ยินยอมกับข้อความเวอร์ชันที่ใช้อยู่เท่านั้น (แอปเก่าที่แสดงข้อความเดิม = ต้องอัปเดตก่อน)
        if ($version !== (string) config('ekyc.consent_version', '')) {
            throw EkycException::consentOutdated();
        }

        // รอบก่อนหน้ายังตรวจอยู่ (เน็ตหลุดแล้วกดเริ่มใหม่) — ห้ามลบทิ้งกลางทาง ให้แอปถามสถานะซ้ำ
        if ($this->hasProcessingSession($user)) {
            throw EkycException::processing();
        }

        $attemptsLeft = $this->attemptsLeft($user);

        // รอแอดมินตรวจ: ขอถ่ายใหม่เองได้เฉพาะเหตุผลด้านคุณภาพภาพ (รอบใหม่แทนเคสเดิม + นับ 1 สิทธิ์)
        $pending = $this->pendingEkycReviewRow($user);
        if ($pending !== null && (! $this->isRetakeableReview($pending) || $attemptsLeft <= 0)) {
            throw EkycException::pendingReview();
        }

        if ($attemptsLeft <= 0) {
            throw EkycException::tooManyAttempts(['attempts_left' => 0]);
        }

        $sessionsToday = KycVerification::query()
            ->where('user_id', $user->id)
            ->where('method', KycVerification::METHOD_EKYC)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($sessionsToday >= max(1, (int) config('ekyc.max_sessions_per_day', 15))) {
            throw EkycException::tooManyAttempts(['attempts_left' => $attemptsLeft]);
        }

        $challenges = $this->randomChallenges();
        $sessionId = (string) Str::uuid();
        $expiresAt = now()->addMinutes(max(1, (int) config('ekyc.session_ttl_minutes', 20)));

        $abandoned = DB::transaction(function () use ($user, $challenges, $sessionId, $expiresAt, $version, $pending) {
            // ล็อกแถวผู้ใช้ — กดเริ่มพร้อมกัน 2 ครั้งจะได้รอบที่ใช้งานได้รอบเดียว
            /** @var User $lockedUser */
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            // รอบที่กำลังตรวจ (ยังไม่ค้างนาน) ห้ามลบ — เช็คซ้ำในล็อก
            if ($this->hasProcessingSession($user)) {
                throw EkycException::processing();
            }

            if ($pending !== null) {
                /** @var KycVerification|null $review */
                $review = KycVerification::query()->whereKey($pending->id)->lockForUpdate()->first();
                // แอดมินตัดสินไปแล้วระหว่างนั้น → ใช้ผลของแอดมิน (ไม่เปิดรอบใหม่ทับ)
                if (! $review || $review->status !== 'pending' || $review->reviewed_by !== null) {
                    throw EkycException::pendingReview();
                }

                $review->forceFill([
                    'status' => KycVerification::STATUS_SUPERSEDED,
                    'ekyc_step' => self::STEP_RETAKEN,
                    'rejection_reason' => 'ผู้ใช้ขอถ่ายใหม่ก่อนเจ้าหน้าที่ตรวจ',
                ])->save();

                $stillPending = KycVerification::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->exists();
                if (! $stillPending && $lockedUser->kyc_status === 'pending') {
                    $lockedUser->forceFill(['kyc_status' => 'not_submitted', 'kyc_verified_at' => null])->save();
                }
            }

            // รอบเก่าที่ยังทำไม่จบ = ทิ้ง (ลบแถว + ไฟล์หลัง commit) — รอบที่ค้าง "กำลังตรวจ" นานเกินเวลาก็ทิ้งได้
            $old = KycVerification::query()
                ->where('user_id', $user->id)
                ->where('method', KycVerification::METHOD_EKYC)
                ->where('status', 'draft')
                ->get(['id', 'user_id', 'ekyc_session_id']);

            if ($old->isNotEmpty()) {
                KycVerification::query()->whereIn('id', $old->pluck('id'))->delete();
            }

            KycVerification::create([
                'user_id' => $user->id,
                'method' => KycVerification::METHOD_EKYC,
                'status' => 'draft',
                'ekyc_session_id' => $sessionId,
                'consent_at' => now(),
                'consent_version' => $version,
                'ekyc_challenges' => $challenges,
                'ekyc_expires_at' => $expiresAt,
                'ekyc_step' => self::STEP_CONSENTED,
            ]);

            return $old;
        });

        foreach ($abandoned as $row) {
            $this->images->deleteSessionDir((int) $row->user_id, $row->ekyc_session_id);
        }

        return [
            'session_id' => $sessionId,
            'challenges' => $challenges,
            'expires_at' => $expiresAt->toIso8601String(),
            'attempts_left' => $attemptsLeft,
        ];
    }

    // =====================================================
    // 2) รูปบัตร
    // =====================================================

    /**
     * POST /ekyc/sessions/{id}/id-card
     *
     * @return array<string, mixed>
     *
     * @throws EkycException
     */
    public function submitIdCard(User $user, string $sessionId, UploadedFile $image): array
    {
        $kyc = $this->findSession($user, $sessionId);
        $this->assertOpen($kyc, [self::STEP_CONSENTED, self::STEP_CARD]);
        $this->assertNotVerified($user);

        // เพดานส่งรูปบัตร: ต่อรอบ (เริ่มใหม่ได้) + ต่อวัน — อ่านบัตร 1 ใบใช้ AI ~10 วิ กันคนเดียวยิงจนคิว AI เต็ม
        $uploads = (int) (((array) ($kyc->extracted_data ?? []))['card_uploads'] ?? 0);
        if ($uploads >= max(1, (int) config('ekyc.max_card_uploads_per_session', 6))) {
            throw EkycException::cardLimit();
        }
        $dayKey = 'ekyc-card-day:'.$user->id.':'.now()->toDateString();
        if (RateLimiter::tooManyAttempts($dayKey, max(1, (int) config('ekyc.max_card_uploads_per_day', 20)))) {
            throw EkycException::tooManyAttempts(['attempts_left' => $this->attemptsLeft($user)]);
        }

        $jpeg = $this->images->normalize($image, EkycImages::CARD_MAX_EDGE, EkycImages::CARD_MIN_EDGE);

        $result = $this->ai->idCard($jpeg);
        // คิว AI เต็ม/ตรวจไม่ทัน = ให้ส่งใหม่ (ไม่ใช่ผ่านขั้นบัตรไปแบบไม่มีข้อมูลแล้วตกไปคิวแอดมิน) — ไม่นับเพดาน
        if (self::isAiBusy($result)) {
            throw EkycException::aiBusy();
        }
        RateLimiter::hit($dayKey, 86400);

        $card = $this->parseCard($result);

        $cardPath = $this->images->newPath((int) $user->id, $sessionId, 'card');
        $this->images->putEncrypted($cardPath, $jpeg);

        // รูปหน้าจากบัตร: AI เจอหน้า (หรือ AI ล่ม → ตัดตามตำแหน่งมาตรฐานไว้ให้แอดมินดู)
        $facePath = null;
        if (! $card['available'] || $card['card_face_found']) {
            $faceJpeg = $this->images->cropCardFace($jpeg, $card['card_face_box']);
            if ($faceJpeg !== null) {
                $facePath = $this->images->newPath((int) $user->id, $sessionId, 'card_face');
                $this->images->putEncrypted($facePath, $faceJpeg);
            }
        }

        $idNumber = $card['fields']['id_number'];

        try {
            $previous = DB::transaction(function () use ($kyc, $card, $cardPath, $facePath, $idNumber) {
                /** @var KycVerification $locked */
                $locked = KycVerification::query()->whereKey($kyc->id)->lockForUpdate()->firstOrFail();
                $this->assertOpen($locked, [self::STEP_CONSENTED, self::STEP_CARD]);

                $old = [$locked->id_card_image, $locked->card_face_path];

                $extracted = (array) ($locked->extracted_data ?? []);
                $extracted['source'] = 'ekyc';
                $extracted['card'] = $this->cardSummary($card);
                $extracted['card_uploads'] = (int) ($extracted['card_uploads'] ?? 0) + 1;
                // บัตรใบใหม่ = ผลอ่านใหม่ — การแก้ไขของใบเดิมไม่ใช้แล้ว
                $extracted['corrections'] = [];

                $locked->forceFill([
                    'id_card_image' => $cardPath,
                    'card_face_path' => $facePath,
                    'id_number_encrypted' => $idNumber,
                    'id_number_hash' => $idNumber !== null ? self::idHash($idNumber) : null,
                    'id_last4' => $idNumber !== null ? substr($idNumber, -4) : null,
                    'name_th' => $card['fields']['name_th'],
                    'name_en' => $card['fields']['name_en'],
                    'birth_date' => $card['fields']['birth_date'],
                    'card_expiry' => $card['fields']['expiry_date'],
                    'ai_ocr_confidence' => $card['ocr_confidence'],
                    'ai_card_real' => $card['card_real_score'],
                    'ai_model_version' => $card['model_version'] !== null ? mb_substr('card:'.$card['model_version'], 0, 120) : null,
                    'extracted_data' => $extracted,
                    'ekyc_step' => self::STEP_CARD,
                ])->save();

                return $old;
            });
        } catch (\Throwable $e) {
            $this->images->deleteQuietly([$cardPath, $facePath]);

            throw $e;
        }

        // ไฟล์ของบัตรใบก่อนหน้าในรอบเดียวกัน (ลบหลัง commit เท่านั้น)
        $this->images->deleteQuietly(array_values(array_diff(array_filter($previous), [$cardPath, $facePath])));

        $kyc->refresh();

        $reasons = $card['reasons'];
        if (! $card['available']) {
            $reasons[] = 'AI_UNAVAILABLE';
        }
        $reasons = array_values(array_unique($reasons));

        return [
            'status' => $this->cardNeedsRetake($card) ? 'retake' : 'ok',
            // false = ตัวอ่านบัตรอัตโนมัติไม่พร้อม (ผู้ใช้กรอกเอง → เจ้าหน้าที่ตรวจ) — แอปไม่ต้องโชว์ "อ่านสำเร็จ 0%"
            'ai_available' => $card['available'],
            'fields' => $this->fieldsPayload($kyc),
            // true / false / null (null = อ่านไม่ได้/ไม่ทราบ → แอปแสดงเป็นกลาง ไม่ใช่ตัวแดง)
            'checks' => [
                'checksum' => $card['checksum_ok'],
                'not_expired' => $this->expiryState($card['fields']['expiry_date'], $card['lifelong'], $card['fields']['birth_date']),
                'card_real' => $card['card_real_score'] !== null ? $card['card_real_score'] >= $this->threshold('min_card_real') : null,
                'quality_ok' => $card['available'] && $this->cardQualityOk($card),
            ],
            'ocr_confidence' => $card['ocr_confidence'] ?? 0.0,
            'reasons' => $reasons,
            'reason_texts' => self::reasonTexts($reasons),
        ];
    }

    /**
     * PATCH /ekyc/sessions/{id}/id-card — ผู้ใช้แก้ชื่อไทย/วันเกิด (แก้จริง = ผลสุดท้ายอย่างน้อย review)
     *
     * @param  array{name_th?: string|null, birth_date?: string|null}  $data  (ตรวจรูปแบบที่ controller แล้ว)
     * @return array{corrected: bool, fields: array<string, mixed>}
     *
     * @throws EkycException
     */
    public function correctIdCard(User $user, string $sessionId, array $data): array
    {
        $kyc = $this->findSession($user, $sessionId);
        // ยังไม่ส่งรูปบัตร = EKYC_CARD_REQUIRED (assertOpen เช็คหมดอายุก่อน)
        $this->assertOpen($kyc, [self::STEP_CARD]);

        $kyc = DB::transaction(function () use ($kyc, $data) {
            /** @var KycVerification $locked */
            $locked = KycVerification::query()->whereKey($kyc->id)->lockForUpdate()->firstOrFail();
            $this->assertOpen($locked, [self::STEP_CARD]);

            $extracted = (array) ($locked->extracted_data ?? []);
            $corrections = (array) ($extracted['corrections'] ?? []);

            foreach (['name_th', 'birth_date'] as $field) {
                if (! array_key_exists($field, $data) || $data[$field] === null) {
                    continue;
                }

                $new = $field === 'birth_date'
                    ? Carbon::parse((string) $data[$field])->toDateString()
                    : self::cleanName((string) $data[$field]);
                $current = $field === 'birth_date' ? $locked->birth_date?->toDateString() : $locked->name_th;
                $original = array_key_exists($field, $corrections) ? ($corrections[$field]['ocr'] ?? null) : $current;

                if ($new === $original) {
                    // แก้กลับเป็นค่าที่ AI อ่านได้ = ไม่นับว่าแก้
                    unset($corrections[$field]);
                } elseif ($new !== $current || isset($corrections[$field])) {
                    $corrections[$field] = ['ocr' => $original, 'user' => $new];
                }

                $locked->{$field} = $new;
            }

            $extracted['corrections'] = $corrections;
            $locked->extracted_data = $extracted;
            $locked->save();

            return $locked;
        });

        return [
            'corrected' => ! empty($kyc->extracted_data['corrections'] ?? []),
            'fields' => $this->fieldsPayload($kyc),
        ];
    }

    // =====================================================
    // 3) ใบหน้า + ตัดสิน
    // =====================================================

    /**
     * POST /ekyc/sessions/{id}/face
     *
     * @param  array<int, UploadedFile>  $frames
     * @param  array<int, mixed>  $labels
     * @return array<string, mixed>
     *
     * @throws EkycException
     */
    public function submitFace(User $user, string $sessionId, array $frames, array $labels): array
    {
        $kyc = $this->findSession($user, $sessionId);
        // ยังไม่ส่งรูปบัตร = EKYC_CARD_REQUIRED (assertOpen เช็คหมดอายุก่อน)
        $this->assertOpen($kyc, [self::STEP_CARD]);
        if (blank($kyc->id_card_image)) {
            throw EkycException::cardRequired();
        }
        $this->assertNotVerified($user);

        // ต้องเป็น neutral ตามด้วยคำสั่งที่สุ่มให้ ตามลำดับเป๊ะ
        $expected = array_merge([self::NEUTRAL], array_values((array) $kyc->ekyc_challenges));
        $labels = array_map(fn ($l) => is_string($l) ? trim($l) : $l, array_values($labels));
        if ($labels !== $expected || count($frames) !== count($expected)) {
            throw EkycException::challengeMismatch();
        }

        $jpegs = [];
        foreach (array_values($frames) as $frame) {
            if (! $frame instanceof UploadedFile) {
                throw EkycException::badImage();
            }
            $jpegs[] = $this->images->normalize($frame, EkycImages::FRAME_MAX_EDGE, EkycImages::FRAME_MIN_EDGE);
        }

        $retakesToday = $this->maxAttempts() - $this->attemptsLeft($user);
        if ($retakesToday >= $this->maxAttempts()) {
            throw EkycException::tooManyAttempts(['attempts_left' => 0]);
        }

        // จองรอบแบบอะตอมมิก — กดส่งซ้ำ/ส่งพร้อมกันได้ประมวลผลครั้งเดียว
        $claimed = KycVerification::query()
            ->whereKey($kyc->id)
            ->where('status', 'draft')
            ->where('ekyc_step', self::STEP_CARD)
            ->where('ekyc_expires_at', '>', now())
            ->update(['ekyc_step' => self::STEP_PROCESSING, 'updated_at' => now()]);
        if ($claimed === 0) {
            $kyc->refresh();
            if ($kyc->status === 'draft' && $kyc->ekyc_step === self::STEP_PROCESSING) {
                throw EkycException::processing();
            }
            if ($kyc->status !== 'draft' || $kyc->ekyc_step === self::STEP_DONE) {
                throw EkycException::sessionDone();
            }

            throw EkycException::sessionExpired();
        }

        $bestPath = null;

        try {
            $kyc->refresh();
            $cardJpeg = $this->images->getDecrypted($kyc->id_card_image);

            $faceResult = $cardJpeg !== null
                ? $this->ai->verifyFace($cardJpeg, $jpegs, $labels)
                : ['available' => false, 'data' => [], 'error' => 'CARD_IMAGE_MISSING', 'ms' => 0];
            // คิว AI เต็ม/ตรวจไม่ทัน → คืนรอบ (catch ข้างล่าง) ให้แอปส่งเฟรมเดิมซ้ำได้ ไม่เสียสิทธิ์
            if (self::isAiBusy($faceResult)) {
                throw EkycException::aiBusy();
            }
            $face = $this->parseFace($faceResult, count($jpegs));

            $cardInfo = (array) (($kyc->extracted_data ?? [])['card'] ?? []);
            $corrected = ! empty(($kyc->extracted_data ?? [])['corrections'] ?? []);
            $idNumber = self::plainId($kyc);

            // ลายนิ้วมือภาพของทุกเฟรม — ซ้ำกับรอบก่อนๆ (บัญชีไหนก็ได้) = เอาภาพนิ่งชุดเดิมมาเรียงตามคำสั่งใหม่
            $frameHashes = array_map(fn (string $jpeg) => $this->images->dhash($jpeg), $jpegs);

            $baseInput = [
                'card_available' => (bool) ($cardInfo['available'] ?? false),
                'face_available' => $face['available'],
                'id_present' => $idNumber !== null,
                'checksum_ok' => $idNumber !== null ? KycAutoCheckService::validThaiIdChecksum($idNumber) && ($cardInfo['id_checksum_ok'] ?? true) !== false : null,
                'expiry' => $kyc->card_expiry,
                'lifelong' => (bool) ($cardInfo['lifelong'] ?? false),
                'birth_date' => $kyc->birth_date,
                'ocr' => $kyc->ai_ocr_confidence,
                'card_real' => $kyc->ai_card_real,
                'liveness_passed' => $face['liveness_passed'],
                'liveness' => $face['liveness'],
                'challenges' => $face['challenges'],
                'real' => $face['real'],
                'cosine' => $face['cosine'],
                'same_person' => $face['same_person'],
                'reasons' => array_merge((array) ($cardInfo['reasons'] ?? []), $face['reasons'], $cardJpeg === null ? ['CARD_IMAGE_MISSING'] : []),
                'corrected' => $corrected,
                'replay' => $this->replaySuspected((int) $kyc->id, $frameHashes),
                'prior_rejected' => $this->priorAdminRejection($user, $kyc->id_number_hash),
                'retakes_today' => $retakesToday,
            ];

            // เก็บเฉพาะเฟรมที่ดีที่สุด (AI ล่ม = เฟรม neutral) — เฟรมอื่นไม่ถูกเขียนลงดิสก์เลย
            $bestIndex = $face['best_frame_index'] ?? 0;
            $bestPath = $this->images->newPath((int) $user->id, $sessionId, 'best_frame');
            $this->images->putEncrypted($bestPath, $jpegs[$bestIndex] ?? $jpegs[0]);

            // ล็อกตามเลขบัตร: เช็คบัตรซ้ำ + ตัดสิน + บันทึก ต้องเป็นก้อนเดียว
            // (2 บัญชีใช้บัตรใบเดียวกันส่งพร้อมกัน → คนที่สองเห็นแถว approved ของคนแรก → ส่งแอดมินตรวจ)
            [$kyc, $verdict] = $this->withIdLock($kyc->id_number_hash, function () use ($kyc, $user, $face, $baseInput, $idNumber, $bestPath, $frameHashes) {
                $verdict = $this->decide($baseInput + [
                    'duplicate' => $this->isDuplicateId($user, $kyc->id_number_hash, $idNumber),
                ]);
                $decision = $verdict['decision'];
                $status = match ($decision) {
                    'approved' => 'approved',
                    'review' => 'pending',
                    default => KycVerification::STATUS_RETAKE,
                };

                $saved = DB::transaction(function () use ($kyc, $user, $face, $verdict, $decision, $status, $bestPath, $frameHashes) {
                    /** @var User $lockedUser */
                    $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                    /** @var KycVerification $locked */
                    $locked = KycVerification::query()->whereKey($kyc->id)->lockForUpdate()->firstOrFail();

                    $extracted = (array) ($locked->extracted_data ?? []);
                    $extracted['face'] = $this->faceSummary($face);

                    $modelVersion = trim(implode(';', array_filter([
                        $locked->ai_model_version,
                        $face['model_version'] !== null ? 'face:'.$face['model_version'] : null,
                    ])));

                    $locked->forceFill([
                        'status' => $status,
                        'submitted_at' => now(),
                        'processed_at' => now(),
                        'ai_decision' => $decision,
                        'ai_reasons' => $verdict['reasons'],
                        'ai_face_match' => $face['cosine'],
                        'ai_liveness' => $face['liveness'],
                        'ai_real' => $face['real'],
                        'ai_model_version' => $modelVersion !== '' ? mb_substr($modelVersion, 0, 120) : null,
                        'best_frame_path' => $bestPath,
                        'extracted_data' => $extracted,
                        'ekyc_frame_hashes' => array_values(array_filter($frameHashes, 'is_string')),
                        'ekyc_step' => self::STEP_DONE,
                    ])->save();

                    if ($decision === 'approved') {
                        $this->markUserApproved($lockedUser, $locked);
                    } elseif ($decision === 'review' && $lockedUser->kyc_status !== 'approved') {
                        $lockedUser->forceFill(['kyc_status' => 'pending', 'kyc_verified_at' => null])->save();
                    }

                    return $locked;
                });

                return [$saved, $verdict];
            });
        } catch (\Throwable $e) {
            // พังกลางทาง / คิว AI เต็ม (ไม่ใช่ผลตัดสิน) → คืนรอบให้ส่งใหม่ได้ ไม่เสียสิทธิ์
            KycVerification::query()
                ->whereKey($kyc->id)
                ->where('ekyc_step', self::STEP_PROCESSING)
                ->update(['ekyc_step' => self::STEP_CARD, 'updated_at' => now()]);
            $this->images->deleteQuietly([$bestPath]);

            if (! $e instanceof EkycException) {
                Log::error('eKYC: face step failed', ['kyc_id' => $kyc->id, 'error' => class_basename($e)]);
            }

            throw $e;
        }

        $decision = (string) $kyc->ai_decision;

        Log::info('eKYC: decided', [
            'kyc_id' => $kyc->id,
            'user_id' => $user->id,
            'decision' => $decision,
            'reasons' => $kyc->ai_reasons,
            'cosine' => $kyc->ai_face_match,
            'liveness' => $kyc->ai_liveness,
            'real' => $kyc->ai_real,
        ]);

        if ($decision === 'review') {
            $this->notifyAdmins($kyc);
        } elseif ($decision === 'approved') {
            $this->notifyUser($kyc, 'approved');
        }

        $user->refresh();
        $reasons = array_values((array) ($kyc->ai_reasons ?? []));

        return [
            'decision' => $decision,
            'scores' => [
                'face_match' => $face['match_score'] ?? 0.0,
                'liveness' => $face['liveness'] ?? 0.0,
                'ocr' => (float) ($kyc->ai_ocr_confidence ?? 0.0),
                'card_real' => (float) ($kyc->ai_card_real ?? 0.0),
            ],
            'reasons' => $reasons,
            'reason_texts' => self::reasonTexts($reasons),
            'kyc_status' => $user->isKycVerified() ? 'approved' : self::publicStatus($user),
            'attempts_left' => $this->attemptsLeft($user),
        ];
    }

    /**
     * กติกาตัดสิน (ไม่มี side effect — เทสต์ตรงได้)
     *
     * @param  array{card_available: bool, face_available: bool, id_present: bool, checksum_ok: bool|null,
     *               expiry: CarbonInterface|string|null, lifelong: bool, birth_date?: CarbonInterface|string|null,
     *               ocr: float|null, card_real: float|null,
     *               liveness_passed: bool|null, liveness: float|null, challenges: array<string, bool>,
     *               real: float|null, cosine: float|null, same_person: bool|null, reasons: array<int, string>,
     *               corrected: bool, duplicate: bool, replay?: bool, prior_rejected?: bool, retakes_today: int}  $in
     * @return array{decision: string, reasons: array<int, string>}
     */
    public function decide(array $in): array
    {
        $reasons = array_values(array_unique(array_filter((array) ($in['reasons'] ?? []), 'is_string')));
        $add = function (string $code) use (&$reasons): void {
            if (! in_array($code, $reasons, true)) {
                $reasons[] = $code;
            }
        };

        $expiry = $in['expiry'] ?? null;
        if (is_string($expiry) && $expiry !== '') {
            try {
                $expiry = Carbon::parse($expiry);
            } catch (\Throwable) {
                $expiry = null;
            }
        }
        $expired = $expiry instanceof CarbonInterface && $expiry->copy()->endOfDay()->isPast();
        $cosine = $in['cosine'] ?? null;
        $real = $in['real'] ?? null;

        // ----- ไม่ผ่านชัดเจน → ถ่ายใหม่ -----
        $clear = false;
        if (in_array('NO_FACE', $reasons, true)) {
            $clear = true;
        }
        if (in_array('ID_CHECKSUM_FAIL', $reasons, true) || (($in['id_present'] ?? false) && ($in['checksum_ok'] ?? null) === false)) {
            $add('ID_CHECKSUM_FAIL');
            $clear = true;
        }
        if (in_array('EXPIRED', $reasons, true) || $expired) {
            $add('EXPIRED');
            $clear = true;
        }
        foreach ((array) ($in['challenges'] ?? []) as $label => $passed) {
            if ($passed === false) {
                $add('CHALLENGE_FAILED:'.$label);
            }
        }
        foreach ($reasons as $code) {
            if (str_starts_with($code, 'CHALLENGE_FAILED')) {
                $clear = true;
            }
        }
        if (in_array('SPOOF_SUSPECTED', $reasons, true) && $real !== null && $real < $this->threshold('spoof_retake_real')) {
            $clear = true;
        }
        // คนละคน = AI ยืนยันด้วยรหัส DIFFERENT_PEOPLE เท่านั้น
        // (same_person = false เกิดจากไม่เจอหน้า/เจอหลายหน้าได้ด้วย — กรณีนั้นส่งตรวจ ไม่ใช่ให้ถ่ายใหม่)
        if (in_array('DIFFERENT_PEOPLE', $reasons, true)) {
            $clear = true;
        }
        if ($cosine !== null && $cosine < $this->threshold('retake_match')) {
            $add('LOW_MATCH');
            $clear = true;
        }

        if ($clear) {
            // ครั้งที่ไปถึงเพดานต่อวัน → ส่งแอดมินตรวจแทน (ไม่ให้ผู้ใช้ติดตาย)
            if ((int) ($in['retakes_today'] ?? 0) + 1 >= $this->maxAttempts()) {
                $add('ATTEMPTS_EXHAUSTED');

                return ['decision' => 'review', 'reasons' => $reasons];
            }

            return ['decision' => 'retake', 'reasons' => $reasons];
        }

        // ----- อนุมัติอัตโนมัติ: ต้องผ่านทุกข้อ -----
        $blockers = [];
        if (! ($in['card_available'] ?? false) || ! ($in['face_available'] ?? false)) {
            $blockers[] = 'AI_UNAVAILABLE';
        }
        if (($in['checksum_ok'] ?? null) !== true) {
            $blockers[] = 'ID_UNREADABLE';
        }
        $birth = $in['birth_date'] ?? null;
        $birth = $birth instanceof CarbonInterface ? $birth->toDateString() : (is_string($birth) ? $birth : null);
        if ($this->expiryState($expiry instanceof CarbonInterface ? $expiry->toDateString() : null, (bool) ($in['lifelong'] ?? false), $birth) !== true) {
            $blockers[] = 'EXPIRY_UNKNOWN';
        }
        if (($in['ocr'] ?? null) === null || $in['ocr'] < $this->threshold('min_ocr')) {
            $blockers[] = 'OCR_LOW';
        }
        if (($in['card_real'] ?? null) === null || $in['card_real'] < $this->threshold('min_card_real')) {
            $blockers[] = 'LOW_CARD_REAL';
        }
        if (($in['liveness_passed'] ?? null) !== true || ($in['liveness'] ?? null) === null || $in['liveness'] < $this->threshold('min_liveness')) {
            $blockers[] = 'LOW_LIVENESS';
        }
        if ($real === null || $real < $this->threshold('min_real')) {
            $blockers[] = 'LOW_REAL';
        }
        if ($cosine === null) {
            $blockers[] = 'NO_MATCH_SCORE';
        } elseif ($cosine < $this->threshold('auto_approve_match')) {
            $blockers[] = $cosine < $this->threshold('review_match') ? 'LOW_MATCH' : 'BORDERLINE_MATCH';
        }
        if ($in['corrected'] ?? false) {
            $blockers[] = 'USER_CORRECTED';
        }
        if ($in['duplicate'] ?? false) {
            $blockers[] = 'DUPLICATE_ID';
        }
        if (($in['same_person'] ?? null) === false && ! in_array('DIFFERENT_PEOPLE', $reasons, true)) {
            $blockers[] = 'FACES_INCONSISTENT';
        }
        if ($in['replay'] ?? false) {
            $blockers[] = 'REPLAY_SUSPECTED';
        }
        // แอดมินเคยปฏิเสธ (บัญชีนี้หรือบัตรใบนี้) — ผลของคนต้องไม่ถูก AI ทับ
        if ($in['prior_rejected'] ?? false) {
            $blockers[] = 'PRIOR_REJECTED';
        }
        foreach ($reasons as $code) {
            if (! in_array($code, self::BENIGN_REASONS, true)) {
                $blockers[] = $code;
            }
        }

        // ไม่มีข้อมูลพอจะเช็คก็ไม่ต้องติดป้ายซ้ำซ้อนตอน AI ล่ม (เหลือ AI_UNAVAILABLE เป็นตัวหลัก)
        if (in_array('AI_UNAVAILABLE', $blockers, true)) {
            $blockers = array_values(array_diff($blockers, ['ID_UNREADABLE', 'EXPIRY_UNKNOWN', 'OCR_LOW', 'LOW_CARD_REAL', 'LOW_LIVENESS', 'LOW_REAL', 'NO_MATCH_SCORE']));
            $blockers[] = 'AI_UNAVAILABLE';
        }

        foreach ($blockers as $code) {
            $add($code);
        }

        if ($blockers === []) {
            return ['decision' => 'approved', 'reasons' => $reasons];
        }

        return ['decision' => 'review', 'reasons' => $reasons];
    }

    // =====================================================
    // แอดมิน
    // =====================================================

    /**
     * แอดมินตัดสินแถว eKYC: approved | rejected | retake
     *
     * @throws EkycException แถวนี้ไม่ได้รอตรวจแล้ว
     */
    public function adminDecide(KycVerification $kyc, User $admin, string $decision, ?string $note = null): KycVerification
    {
        if (! in_array($decision, ['approved', 'rejected', 'retake'], true)) {
            throw new \InvalidArgumentException('Unknown decision');
        }

        // ล็อกตามเลขบัตรแบบเดียวกับตอน AI ตัดสิน — แอดมินอนุมัติพร้อมกับอีกบัญชีที่ใช้บัตรเดียวกันไม่ได้
        $kyc = $this->withIdLock($kyc->id_number_hash, fn () => DB::transaction(function () use ($kyc, $admin, $decision, $note) {
            /** @var KycVerification $locked */
            $locked = KycVerification::query()->whereKey($kyc->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new EkycException('EKYC_ALREADY_DECIDED', 'การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว', 409);
            }

            /** @var User|null $user */
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->first();

            // เช็คบัตรซ้ำสดๆ ตอนกดอนุมัติ (ป้ายที่ AI ติดไว้อาจเก่า — อีกบัญชีอาจผ่านด้วยบัตรนี้ระหว่างรอตรวจ)
            if ($decision === 'approved' && $user && $this->isDuplicateId($user, $locked->id_number_hash, self::plainId($locked))) {
                throw new EkycException('EKYC_DUPLICATE_ID', 'เลขบัตรนี้ยืนยันตัวตนกับบัญชีอื่นไว้แล้ว อนุมัติซ้ำไม่ได้', 409);
            }

            $locked->forceFill([
                'status' => match ($decision) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    default => KycVerification::STATUS_RETAKE,
                },
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $decision === 'approved' ? null : (trim((string) $note) !== '' ? trim((string) $note) : null),
            ])->save();

            if ($user) {
                $approvedElsewhere = KycVerification::query()
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $locked->id)
                    ->where('status', 'approved')
                    ->exists();

                if ($decision === 'approved') {
                    $this->markUserApproved($user, $locked);
                } elseif (! $approvedElsewhere) {
                    // ปฏิเสธ = rejected · ขอให้ถ่ายใหม่ = กลับไปเป็น "ยังไม่ยืนยัน" ให้เริ่มรอบใหม่ได้
                    $user->forceFill([
                        'kyc_status' => $decision === 'rejected' ? 'rejected' : 'not_submitted',
                        'kyc_verified_at' => null,
                    ])->save();
                }
            }

            return $locked;
        }));

        $this->notifyUser($kyc, $decision, $kyc->rejection_reason);

        return $kyc;
    }

    /**
     * ไบต์รูปที่ถอดรหัสแล้ว (เฉพาะแถว eKYC) — card | card_face | best_frame
     */
    public function decryptImage(KycVerification $kyc, string $kind): ?string
    {
        if (! $kyc->isEkyc()) {
            return null;
        }

        $path = match ($kind) {
            'card' => $kyc->id_card_image,
            'card_face' => $kyc->card_face_path,
            'best_frame' => $kyc->best_frame_path,
            default => null,
        };

        return $this->images->getDecrypted($path);
    }

    /**
     * ข้อมูลเพิ่มสำหรับหน้าแอดมิน (คะแนน · เกณฑ์ · เหตุผลภาษาไทย · การแก้ไขของผู้ใช้ · ชื่อตรงกับบัญชีไหม)
     *
     * @return array<string, mixed>
     */
    public function adminSummary(KycVerification $kyc): array
    {
        $extracted = (array) ($kyc->extracted_data ?? []);
        $card = (array) ($extracted['card'] ?? []);
        $face = (array) ($extracted['face'] ?? []);
        $reasons = array_values((array) ($kyc->ai_reasons ?? $card['reasons'] ?? []));
        $accountName = (string) ($kyc->user?->name ?? '');
        $cardName = (string) ($kyc->name_th ?? '');
        $plainId = self::plainId($kyc);

        return [
            'match_score' => isset($face['match_score']) ? (float) $face['match_score'] : null,
            'cosine' => $kyc->ai_face_match,
            'liveness' => $kyc->ai_liveness,
            'liveness_passed' => $face['liveness_passed'] ?? null,
            'challenges' => (array) ($face['challenges'] ?? []),
            'real' => $kyc->ai_real,
            'ocr' => $kyc->ai_ocr_confidence,
            'card_real' => $kyc->ai_card_real,
            'checksum_ok' => $card['id_checksum_ok'] ?? null,
            'lifelong' => (bool) ($card['lifelong'] ?? false),
            'reasons' => $reasons,
            'reason_texts' => self::reasonTexts($reasons),
            'corrections' => (array) ($extracted['corrections'] ?? []),
            'id_masked' => $plainId !== null ? self::maskId($plainId) : ($kyc->id_last4 ? '•••••••••'.$kyc->id_last4 : null),
            // เช็คสด ณ ตอนเปิดหน้า (ไม่ใช่ป้ายที่ AI ติดไว้ตอนตัดสิน)
            'duplicate_now' => $kyc->user !== null && $this->isDuplicateId($kyc->user, $kyc->id_number_hash, $plainId),
            'name_matches_account' => $cardName !== '' && $accountName !== ''
                && KycAutoCheckService::normalizeName($cardName) === KycAutoCheckService::normalizeName($accountName),
            'thresholds' => [
                'auto_approve_match' => $this->threshold('auto_approve_match'),
                'review_match' => $this->threshold('review_match'),
                'retake_match' => $this->threshold('retake_match'),
                'min_liveness' => $this->threshold('min_liveness'),
                'min_real' => $this->threshold('min_real'),
                'min_card_real' => $this->threshold('min_card_real'),
                'min_ocr' => $this->threshold('min_ocr'),
            ],
            'has_card' => $this->images->safePath($kyc->id_card_image),
            'has_card_face' => $this->images->safePath($kyc->card_face_path),
            'has_best_frame' => $this->images->safePath($kyc->best_frame_path),
        ];
    }

    // =====================================================
    // ล้างข้อมูล
    // =====================================================

    /**
     * ลบรอบที่ทำค้างแล้วหมดอายุเกิน 1 ชั่วโมง (แถว + ไฟล์) — คำสั่ง ekyc:purge-stale
     *
     * @return int จำนวนรอบที่ลบ
     */
    public function purgeStaleSessions(): int
    {
        $count = 0;

        KycVerification::query()
            ->where('method', KycVerification::METHOD_EKYC)
            ->where('status', 'draft')
            ->where('ekyc_expires_at', '<', now()->subHour())
            ->select(['id', 'user_id', 'ekyc_session_id'])
            ->chunkById(200, function ($rows) use (&$count) {
                KycVerification::query()->whereIn('id', $rows->pluck('id'))->delete();
                foreach ($rows as $row) {
                    $this->images->deleteSessionDir((int) $row->user_id, $row->ekyc_session_id);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * ลบรูป (ไม่ลบแถว) ของรอบที่ไม่ได้ใช้เป็นหลักฐานแล้ว — คำสั่ง ekyc:purge-stale
     *   ถ่ายใหม่ / ถูกแทนที่ → เก็บ retention_retake_days (30) · แอดมินปฏิเสธ → retention_rejected_days (180)
     * แถวยังอยู่ (นับสิทธิ์ถ่ายใหม่ · กันบัตรซ้ำ/ภาพซ้ำ/เคยถูกปฏิเสธ) · รอบที่อนุมัติ/รอตรวจไม่ถูกแตะ
     *
     * @return int จำนวนแถวที่ลบรูป
     */
    public function purgeExpiredImages(): int
    {
        $count = 0;
        $rules = [
            [[KycVerification::STATUS_RETAKE, KycVerification::STATUS_SUPERSEDED], (int) config('ekyc.retention_retake_days', 30)],
            [['rejected'], (int) config('ekyc.retention_rejected_days', 180)],
        ];

        foreach ($rules as [$statuses, $days]) {
            KycVerification::query()
                ->where('method', KycVerification::METHOD_EKYC)
                ->whereIn('status', $statuses)
                ->where('updated_at', '<', now()->subDays(max(1, $days)))
                ->where(function ($q) {
                    $q->whereNotNull('id_card_image')
                        ->orWhereNotNull('card_face_path')
                        ->orWhereNotNull('best_frame_path');
                })
                ->select(['id', 'user_id', 'ekyc_session_id', 'id_card_image', 'card_face_path', 'best_frame_path'])
                ->chunkById(200, function ($rows) use (&$count) {
                    foreach ($rows as $row) {
                        $this->images->deleteQuietly([$row->id_card_image, $row->card_face_path, $row->best_frame_path]);
                        $this->images->deleteSessionDir((int) $row->user_id, $row->ekyc_session_id);
                        KycVerification::query()->whereKey($row->id)->update([
                            'id_card_image' => null,
                            'card_face_path' => null,
                            'best_frame_path' => null,
                        ]);
                        $count++;
                    }
                });
        }

        return $count;
    }

    /**
     * ลบไฟล์รูปของแถว eKYC (เรียกจาก observer ตอนลบแถว)
     */
    public function deleteFilesFor(KycVerification $kyc): void
    {
        if (! $kyc->isEkyc()) {
            return;
        }

        $this->images->deleteQuietly([$kyc->id_card_image, $kyc->card_face_path, $kyc->best_frame_path]);
        $this->images->deleteSessionDir((int) $kyc->user_id, $kyc->ekyc_session_id);
    }

    // =====================================================
    // ตัวช่วย (public static — ใช้ในเทสต์/หน้าแอดมินได้)
    // =====================================================

    /**
     * HMAC ของเลขบัตร (กุญแจ = app key) — ไว้หาบัตรซ้ำโดยไม่ต้องเก็บเลขเปล่า
     */
    public static function idHash(string $idNumber): string
    {
        // กุญแจแยก (EKYC_HASH_KEY) — หมุน APP_KEY แล้ว hash เดิมยังใช้หาบัตรซ้ำได้ · ไม่ตั้ง = APP_KEY (แบบเดิม)
        $key = trim((string) config('ekyc.hash_key', ''));

        return hash_hmac('sha256', 'ekyc-id:'.preg_replace('/\D/', '', $idNumber), $key !== '' ? $key : (string) config('app.key'));
    }

    /**
     * เลขบัตรแบบปิดบางส่วน: "1 1037 ••••• 12 3"
     */
    public static function maskId(string $idNumber): string
    {
        $d = preg_replace('/\D/', '', $idNumber);
        if (strlen($d) !== 13) {
            return strlen($d) >= 4 ? '•••••••••'.substr($d, -4) : '•••••';
        }

        return $d[0].' '.substr($d, 1, 4).' ••••• '.substr($d, 10, 2).' '.$d[12];
    }

    /**
     * รหัสเหตุผล → ข้อความไทย (ตำแหน่งตรงกับรหัส · ข้ามเฉพาะค่าที่ไม่ใช่ string)
     *
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public static function reasonTexts(array $codes): array
    {
        $texts = [];
        foreach ($codes as $code) {
            if (! is_string($code)) {
                continue;
            }
            if (str_starts_with($code, 'CHALLENGE_FAILED:')) {
                $label = substr($code, strlen('CHALLENGE_FAILED:'));
                $texts[] = 'ทำท่า "'.(self::CHALLENGE_LABELS[$label] ?? $label).'" ไม่ผ่าน';

                continue;
            }
            $texts[] = self::REASON_LABELS[$code] ?? $code;
        }

        // เรียงตรงกับ $codes ตัวต่อตัว (แอปจับคู่รหัส ↔ ข้อความตามตำแหน่ง) — ห้าม unique ทิ้ง
        return $texts;
    }

    // =====================================================
    // ภายใน
    // =====================================================

    /**
     * หารอบของผู้ใช้คนนี้ (ของคนอื่น = ไม่พบ — ไม่บอกว่ามีอยู่)
     *
     * @throws EkycException
     */
    private function findSession(User $user, string $sessionId): KycVerification
    {
        if (! Str::isUuid($sessionId)) {
            throw EkycException::sessionNotFound();
        }

        $kyc = KycVerification::query()
            ->where('ekyc_session_id', $sessionId)
            ->where('method', KycVerification::METHOD_EKYC)
            ->first();

        if (! $kyc || (int) $kyc->user_id !== (int) $user->id) {
            throw EkycException::sessionNotFound();
        }

        return $kyc;
    }

    /**
     * รอบยังเปิดอยู่และอยู่ในขั้นที่อนุญาต
     *
     * @param  array<int, string>  $steps
     *
     * @throws EkycException
     */
    private function assertOpen(KycVerification $kyc, array $steps): void
    {
        // ตัดสินแล้ว → แอปดูผลจาก /ekyc/status (ไม่ใช่ให้เริ่มใหม่)
        if ($kyc->status !== 'draft' || $kyc->ekyc_step === self::STEP_DONE) {
            throw EkycException::sessionDone();
        }

        // กำลังตรวจ (เช่นส่งซ้ำหลังเน็ตหลุด) → แอปถามสถานะซ้ำ — เช็คก่อนหมดอายุ ผลกำลังจะออก
        if ($kyc->ekyc_step === self::STEP_PROCESSING) {
            throw EkycException::processing();
        }

        if (! $kyc->ekyc_expires_at || $kyc->ekyc_expires_at->isPast()) {
            throw EkycException::sessionExpired();
        }

        if (! in_array($kyc->ekyc_step, $steps, true)) {
            throw EkycException::cardRequired();
        }
    }

    /**
     * @throws EkycException
     */
    private function assertNotVerified(User $user): void
    {
        $user->refresh();
        if ($user->isKycVerified()) {
            throw EkycException::alreadyVerified();
        }
    }

    /**
     * ผลอ่านบัตรจาก AI → รูปแบบเดียว (ค่าแปลก/ไม่มี = null)
     *
     * @param  array{available: bool, data: array<string, mixed>, error: string|null, ms: int}  $result
     * @return array<string, mixed>
     */
    private function parseCard(array $result): array
    {
        $d = $result['available'] ? (array) $result['data'] : [];
        $fields = (array) ($d['fields'] ?? []);

        $id = preg_replace('/\D/', '', (string) ($fields['id_number'] ?? ''));
        $id = strlen($id) === 13 ? $id : null;

        $reasons = self::cleanReasons($d['reasons'] ?? []);
        $lifelong = in_array('EXPIRY_LIFELONG', $reasons, true);

        $checksumOk = $id !== null ? KycAutoCheckService::validThaiIdChecksum($id) && ($d['id_checksum_ok'] ?? true) !== false : null;
        if ($id !== null && $checksumOk === false && ! in_array('ID_CHECKSUM_FAIL', $reasons, true)) {
            $reasons[] = 'ID_CHECKSUM_FAIL';
        }

        $expiry = self::cleanDate($fields['expiry_date'] ?? null);
        if ($expiry !== null && Carbon::parse($expiry)->endOfDay()->isPast() && ! in_array('EXPIRED', $reasons, true)) {
            $reasons[] = 'EXPIRED';
        }

        $quality = (array) ($d['quality'] ?? []);

        return [
            'available' => (bool) $result['available'],
            'error' => $result['error'] ?? null,
            'card_detected' => (bool) ($d['card_detected'] ?? false),
            'quality' => [
                'blur' => self::score($quality['blur'] ?? null),
                'glare' => self::score($quality['glare'] ?? null),
                'complete' => isset($quality['complete']) ? (bool) $quality['complete'] : null,
            ],
            'fields' => [
                'id_number' => $id,
                'name_th' => self::cleanName($fields['name_th'] ?? null),
                'name_en' => self::cleanName($fields['name_en'] ?? null),
                'birth_date' => self::cleanDate($fields['birth_date'] ?? null),
                'expiry_date' => $expiry,
                'issue_date' => self::cleanDate($fields['issue_date'] ?? null),
            ],
            'field_confidence' => array_map(fn ($v) => self::score($v), array_filter((array) ($d['field_confidence'] ?? []), 'is_numeric')),
            'ocr_confidence' => self::score($d['ocr_confidence'] ?? null),
            'checksum_ok' => $checksumOk,
            'card_face_found' => (bool) ($d['card_face_found'] ?? false),
            'card_face_box' => $d['card_face_box'] ?? null,
            'card_real_score' => self::score($d['card_real_score'] ?? null),
            'lifelong' => $lifelong,
            'reasons' => $reasons,
            'model_version' => is_string($d['model_version'] ?? null) ? mb_substr($d['model_version'], 0, 60) : null,
        ];
    }

    /**
     * ส่วนของผลอ่านบัตรที่เก็บใน extracted_data — ไม่มีเลขบัตร/ชื่อ (อยู่ในคอลัมน์ของมันเอง)
     *
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function cardSummary(array $card): array
    {
        return [
            'available' => $card['available'],
            'error' => $card['error'],
            'card_detected' => $card['card_detected'],
            'quality' => $card['quality'],
            'field_confidence' => $card['field_confidence'],
            'ocr_confidence' => $card['ocr_confidence'],
            'id_checksum_ok' => $card['checksum_ok'],
            'card_face_found' => $card['card_face_found'],
            'card_real_score' => $card['card_real_score'],
            'lifelong' => $card['lifelong'],
            'issue_date' => $card['fields']['issue_date'],
            'reasons' => $card['reasons'],
            'model_version' => $card['model_version'],
        ];
    }

    /**
     * ผลตรวจใบหน้าจาก AI → รูปแบบเดียว
     *
     * @param  array{available: bool, data: array<string, mixed>, error: string|null, ms: int}  $result
     * @return array<string, mixed>
     */
    private function parseFace(array $result, int $frameCount): array
    {
        $d = $result['available'] ? (array) $result['data'] : [];
        $liveness = (array) ($d['liveness'] ?? []);
        $match = (array) ($d['match'] ?? []);

        $challenges = [];
        foreach ((array) ($liveness['challenges'] ?? []) as $label => $passed) {
            if (is_string($label) && preg_match('/^[a-z_]{1,20}$/', $label)) {
                $challenges[$label] = (bool) $passed;
            }
        }

        $rawBest = $d['best_frame_index'] ?? null;
        $hasBest = is_int($rawBest) && $rawBest >= 0 && $rawBest < $frameCount;
        $best = $hasBest ? $rawBest : 0;
        $reasons = self::cleanReasons($d['reasons'] ?? []);

        // AI 1.0.0 ส่ง cosine 0.0 เมื่อไม่มีหน้าให้เทียบ (ไม่เจอหน้าบนบัตร / ไม่มีเฟรมที่มีหน้าเดียว)
        // = "เทียบไม่ได้" ไม่ใช่ "คนละคนชัดเจน" → null (ไม่ให้ไปเข้ากฎ LOW_MATCH แล้วเสียสิทธิ์ถ่ายใหม่)
        $cosine = is_numeric($match['cosine'] ?? null) && $hasBest && ! in_array('NO_CARD_FACE', $reasons, true)
            ? max(-1.0, min(1.0, (float) $match['cosine']))
            : null;

        return [
            'available' => (bool) $result['available'],
            'error' => $result['error'] ?? null,
            'faces_found' => is_numeric($d['faces_found'] ?? null) ? (int) $d['faces_found'] : null,
            'same_person' => isset($d['same_person_across_frames']) ? (bool) $d['same_person_across_frames'] : null,
            'liveness_passed' => isset($liveness['passed']) ? (bool) $liveness['passed'] : null,
            'liveness' => self::score($liveness['score'] ?? null),
            'challenges' => $challenges,
            'real' => self::score(($d['anti_spoof'] ?? [])['real_score'] ?? null),
            'cosine' => $cosine,
            'match_score' => $cosine !== null ? self::score($match['score'] ?? null) : null,
            'best_frame_index' => $best,
            'reasons' => $reasons,
            'model_version' => is_string($d['model_version'] ?? null) ? mb_substr($d['model_version'], 0, 60) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $face
     * @return array<string, mixed>
     */
    private function faceSummary(array $face): array
    {
        return [
            'available' => $face['available'],
            'error' => $face['error'],
            'faces_found' => $face['faces_found'],
            'same_person' => $face['same_person'],
            'liveness_passed' => $face['liveness_passed'],
            'challenges' => $face['challenges'],
            'match_score' => $face['match_score'],
            'best_frame_index' => $face['best_frame_index'],
            'reasons' => $face['reasons'],
            'model_version' => $face['model_version'],
        ];
    }

    /**
     * รอบอ่านบัตรต้องถ่ายบัตรใหม่ไหม (AI ล่ม = ไม่ต้อง ให้ไปต่อแล้วแอดมินตรวจ)
     *
     * @param  array<string, mixed>  $card
     */
    private function cardNeedsRetake(array $card): bool
    {
        if (! $card['available']) {
            return false;
        }

        if (! $card['card_detected'] || $card['fields']['id_number'] === null) {
            return true;
        }

        return array_intersect(self::CARD_RETAKE_REASONS, $card['reasons']) !== [] || ! $this->cardQualityOk($card);
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function cardQualityOk(array $card): bool
    {
        return $card['card_detected']
            && $card['quality']['complete'] !== false
            && array_intersect(['NO_CARD', 'BLURRY', 'GLARE', 'CARD_INCOMPLETE'], $card['reasons']) === [];
    }

    /**
     * สถานะวันหมดอายุ: true = ยังไม่หมด · false = หมดแล้ว · null = ไม่ทราบ (อ่านไม่ได้)
     *
     * บัตรตลอดชีพนับว่าผ่านเฉพาะเจ้าของบัตรอายุถึงเกณฑ์ (config ekyc.lifelong_min_age = 70)
     * — AI เห็นคำว่า "ตลอด" บนบัตรที่อ่านวันหมดอายุไม่ออก ไม่พอจะเชื่อว่าเป็นบัตรตลอดชีพ
     */
    private function expiryState(?string $expiry, bool $lifelong, ?string $birthDate): ?bool
    {
        if ($expiry === null || $expiry === '') {
            if (! $lifelong || $birthDate === null || $birthDate === '') {
                return null;
            }

            try {
                return Carbon::parse($birthDate)->age >= (int) config('ekyc.lifelong_min_age', 70) ? true : null;
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return ! Carbon::parse($expiry)->endOfDay()->isPast();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * ฟิลด์จากบัตรที่ส่งให้แอป (เลขบัตรปิดบางส่วนเสมอ)
     *
     * @return array<string, mixed>
     */
    private function fieldsPayload(KycVerification $kyc): array
    {
        $card = (array) (($kyc->extracted_data ?? [])['card'] ?? []);
        $plainId = self::plainId($kyc);

        return [
            'id_number_masked' => $plainId !== null ? self::maskId($plainId) : null,
            'name_th' => $kyc->name_th,
            'name_en' => $kyc->name_en,
            'birth_date' => $kyc->birth_date?->toDateString(),
            'expiry_date' => $kyc->card_expiry?->toDateString(),
            'expiry_lifelong' => (bool) ($card['lifelong'] ?? false),
        ];
    }

    /**
     * อนุมัติผู้ใช้ + เติมชื่อ/วันเกิดจากบัตรลงโปรไฟล์ + ปิดคำขอที่ค้างอยู่ (เรียกใน transaction)
     */
    private function markUserApproved(User $user, KycVerification $kyc): void
    {
        $data = [
            'kyc_status' => 'approved',
            'kyc_verified_at' => now(),
        ];

        $thai = KycAutoCheckService::normalizeName((string) $kyc->name_th);
        if ($thai !== '') {
            $parts = explode(' ', $thai, 2);
            $data['thai_first_name'] = $parts[0];
            $data['thai_last_name'] = $parts[1] ?? null;
        }

        $english = KycAutoCheckService::normalizeName((string) $kyc->name_en);
        if ($english !== '') {
            $parts = explode(' ', $english, 2);
            $data['english_first_name'] = $parts[0];
            $data['english_last_name'] = $parts[1] ?? null;
        }

        if ($kyc->birth_date) {
            $data['id_card_birth_date'] = $kyc->birth_date->toDateString();
            if (empty($user->date_of_birth)) {
                $data['date_of_birth'] = $kyc->birth_date->toDateString();
            }
        }
        if ($kyc->card_expiry) {
            $data['id_card_expiry_date'] = $kyc->card_expiry->toDateString();
        }

        $user->forceFill($data)->save();

        // คำขอเก่าที่ค้างอยู่ (เช่นอัปโหลดรูปให้แอดมินตรวจเมื่อก่อน) — ปิดเงียบๆ กันแอดมินไปกดปฏิเสธทีหลังแล้วสถานะถอยกลับ
        KycVerification::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $kyc->id)
            ->whereIn('status', ['pending', 'draft'])
            ->update([
                'status' => KycVerification::STATUS_SUPERSEDED,
                'rejection_reason' => 'ยืนยันตัวตนผ่าน eKYC แล้ว',
                'updated_at' => now(),
            ]);
    }

    /**
     * เลขบัตรนี้ยืนยันผ่านกับบัญชีอื่นแล้วหรือยัง (eKYC ผ่าน hash · แบบเดิมผ่าน users.id_card_number)
     */
    private function isDuplicateId(User $user, ?string $hash, ?string $idNumber): bool
    {
        if ($hash !== null && $hash !== '') {
            $dup = KycVerification::query()
                ->where('id_number_hash', $hash)
                ->where('status', 'approved')
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($dup) {
                return true;
            }
        }

        if ($idNumber !== null && $idNumber !== '') {
            try {
                return User::query()
                    ->where('id', '!=', $user->id)
                    ->where('kyc_status', 'approved')
                    ->where('id_card_number', $idNumber)
                    ->exists();
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    /**
     * แถวล่าสุดที่ตัดสินแล้ว (ไม่นับรอบที่ยังทำค้าง / คำขอที่ถูกแทนแล้ว)
     */
    private function lastDecidedRow(User $user): ?KycVerification
    {
        return KycVerification::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'rejected', KycVerification::STATUS_RETAKE])
            ->latest('id')
            ->first();
    }

    /**
     * ผลตัดสินของแถว (สถานะสุดท้าย — แอดมินตัดสินทับ AI ได้)
     */
    private function effectiveDecision(KycVerification $row): string
    {
        return match ($row->status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            KycVerification::STATUS_RETAKE => 'retake',
            default => 'review',
        };
    }

    private function canStart(User $user, bool $verified, int $attemptsLeft): bool
    {
        return ! $verified && $attemptsLeft > 0 && ! $this->hasPendingEkycReview($user);
    }

    private function hasPendingEkycReview(User $user): bool
    {
        return $this->pendingEkycReviewRow($user) !== null;
    }

    /**
     * เคส eKYC ล่าสุดที่รอแอดมินตรวจ (ถ้ามี)
     */
    private function pendingEkycReviewRow(User $user): ?KycVerification
    {
        return KycVerification::query()
            ->where('user_id', $user->id)
            ->where('method', KycVerification::METHOD_EKYC)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    /**
     * เคสรอตรวจนี้ผู้ใช้ขอถ่ายใหม่เองได้ไหม — AI ส่งตรวจเพราะความมั่นใจ/คุณภาพภาพเท่านั้น และแอดมินยังไม่แตะ
     */
    private function isRetakeableReview(KycVerification $row): bool
    {
        if (! $row->isEkyc() || $row->status !== 'pending' || $row->reviewed_by !== null || $row->ai_decision !== 'review') {
            return false;
        }

        foreach ((array) ($row->ai_reasons ?? []) as $code) {
            if (! in_array($code, self::RETAKEABLE_REVIEW_REASONS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * มีรอบที่กำลังตรวจอยู่จริงไหม (ค้าง "กำลังตรวจ" นานเกิน processing_stale_minutes = ถือว่าค้าง ไม่นับ)
     */
    private function hasProcessingSession(User $user): bool
    {
        return KycVerification::query()
            ->where('user_id', $user->id)
            ->where('method', KycVerification::METHOD_EKYC)
            ->where('status', 'draft')
            ->where('ekyc_step', self::STEP_PROCESSING)
            ->where('updated_at', '>=', now()->subMinutes(max(1, (int) config('ekyc.processing_stale_minutes', 5))))
            ->exists();
    }

    /**
     * แอดมินเคยปฏิเสธบัญชีนี้ หรือบัตรใบนี้ (บัญชีไหนก็ได้) ภายใน prior_rejection_days
     */
    private function priorAdminRejection(User $user, ?string $idHash): bool
    {
        $base = fn () => KycVerification::query()
            ->where('status', 'rejected')
            ->whereNotNull('reviewed_by')
            ->where('reviewed_at', '>=', now()->subDays(max(1, (int) config('ekyc.prior_rejection_days', 365))));

        if ($base()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $idHash !== null && $idHash !== '' && $base()->where('id_number_hash', $idHash)->exists();
    }

    /**
     * เฟรมชุดนี้ซ้ำกับรอบก่อนๆ ไหม (ภาพเดียวกัน ≥ replay_min_frames เฟรมกับรอบใดรอบหนึ่ง — บัญชีไหนก็ได้)
     *
     * @param  array<int, string|null>  $hashes
     */
    private function replaySuspected(int $currentId, array $hashes): bool
    {
        // ภาพสีเรียบ/มืด (ปิดกล้อง) ซ้ำกันเองได้ — ใช้เฉพาะเฟรมที่มีรายละเอียด
        $hashes = array_values(array_filter($hashes, [EkycImages::class, 'hashInformative']));
        $minFrames = max(1, (int) config('ekyc.replay_min_frames', 2));
        if (count($hashes) < $minFrames) {
            return false;
        }

        $maxDistance = max(0, (int) config('ekyc.replay_max_distance', 6));
        $found = false;

        KycVerification::query()
            ->where('method', KycVerification::METHOD_EKYC)
            ->where('id', '!=', $currentId)
            ->whereNotNull('ekyc_frame_hashes')
            ->where('created_at', '>=', now()->subDays(max(1, (int) config('ekyc.replay_lookback_days', 90))))
            ->select(['id', 'ekyc_frame_hashes'])
            ->chunkById(500, function ($rows) use ($hashes, $minFrames, $maxDistance, &$found) {
                foreach ($rows as $row) {
                    $previous = array_values(array_filter((array) $row->ekyc_frame_hashes, [EkycImages::class, 'hashInformative']));
                    $same = 0;
                    foreach ($hashes as $hash) {
                        foreach ($previous as $old) {
                            $distance = EkycImages::hashDistance($hash, $old);
                            if ($distance !== null && $distance <= $maxDistance) {
                                $same++;

                                break;
                            }
                        }
                    }
                    if ($same >= $minFrames) {
                        $found = true;

                        return false;
                    }
                }

                return true;
            });

        return $found;
    }

    /**
     * ทำงานภายใต้ล็อกตามเลขบัตร (MySQL/MariaDB GET_LOCK — ข้าม process ได้) · ไม่มีเลขบัตร = ไม่ต้องล็อก
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withIdLock(?string $idHash, callable $callback): mixed
    {
        if ($idHash === null || $idHash === '' || ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return $callback();
        }

        $name = 'ekyc-id:'.substr($idHash, 0, 48);
        $got = (int) (DB::selectOne('SELECT GET_LOCK(?, 15) AS l', [$name])->l ?? 0);
        if ($got !== 1) {
            Log::warning('eKYC: id lock timeout', ['hash' => substr($idHash, 0, 8)]);
        }

        try {
            return $callback();
        } finally {
            if ($got === 1) {
                DB::selectOne('SELECT RELEASE_LOCK(?) AS r', [$name]);
            }
        }
    }

    /**
     * ผลจากบริการ AI = คิวเต็ม/ตรวจไม่ทัน (ให้ลองซ้ำ)
     *
     * @param  array{available: bool, error?: string|null}  $result
     */
    private static function isAiBusy(array $result): bool
    {
        return ! $result['available'] && in_array($result['error'] ?? null, self::AI_BUSY_ERRORS, true);
    }

    /**
     * เลขบัตรแบบถอดรหัสแล้ว — ถอดไม่ได้ (เช่นหมุน APP_KEY โดยไม่ตั้ง previous keys) = null แทน 500
     */
    private static function plainId(KycVerification $row): ?string
    {
        try {
            $value = $row->id_number_encrypted;
        } catch (\Throwable) {
            return null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function enforced(): bool
    {
        return filter_var(config('ekyc.enforce', true), FILTER_VALIDATE_BOOL);
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('ekyc.max_attempts_per_day', 3));
    }

    private function threshold(string $key): float
    {
        return (float) config('ekyc.'.$key);
    }

    /**
     * สุ่มคำสั่ง 3 ข้อไม่ซ้ำ (random_int — เดาลำดับไม่ได้)
     *
     * @return array<int, string>
     */
    private function randomChallenges(): array
    {
        $pool = self::CHALLENGES;
        for ($i = count($pool) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$pool[$i], $pool[$j]] = [$pool[$j], $pool[$i]];
        }

        return array_slice($pool, 0, self::CHALLENGE_COUNT);
    }

    /**
     * แจ้งแอดมินว่ามีเคสรอตรวจ (ล้มได้ ไม่กระทบผลของผู้ใช้)
     */
    private function notifyAdmins(KycVerification $kyc): void
    {
        try {
            $this->notifications->notifyAdminNewKyc($kyc->loadMissing('user'));
        } catch (\Throwable $e) {
            Log::warning('eKYC: notify admins failed', ['kyc_id' => $kyc->id, 'error' => class_basename($e)]);
        }
    }

    /**
     * แจ้งผู้ใช้ผลการยืนยัน (กล่องแจ้งเตือนในแอป + push type kyc_result)
     */
    private function notifyUser(KycVerification $kyc, string $decision, ?string $note = null): void
    {
        $user = $kyc->user()->first();
        if (! $user) {
            return;
        }

        [$title, $message] = match ($decision) {
            'approved' => ['ยืนยันตัวตนสำเร็จ', 'บัญชีของคุณได้รับป้าย "ยืนยันตัวตนแล้ว" ใช้งานสั่งซื้อ รับงานไรเดอร์ และเปิดร้านได้เต็มที่'],
            'rejected' => ['การยืนยันตัวตนไม่ผ่าน', 'เจ้าหน้าที่ไม่อนุมัติการยืนยันตัวตน'.($note ? ' เหตุผล: '.$note : '').' กรุณาติดต่อทีมงานหากมีข้อสงสัย'],
            default => ['ขอให้ยืนยันตัวตนอีกครั้ง', 'เจ้าหน้าที่ขอให้ถ่ายบัตรและใบหน้าใหม่'.($note ? ' ('.$note.')' : '').' ใช้เวลาประมาณ 1 นาที'],
        };

        try {
            $this->notifications->create(
                $user,
                'kyc_result',
                $title,
                $message,
                [
                    'decision' => $decision,
                    'kyc_status' => $decision === 'approved' ? 'approved' : ($decision === 'rejected' ? 'rejected' : 'none'),
                    'role' => 'user',
                    'screen' => 'ekyc',
                ],
                null,
                null,
                'high',
                true,
            );
        } catch (\Throwable $e) {
            Log::warning('eKYC: notify user failed', ['kyc_id' => $kyc->id, 'error' => class_basename($e)]);
        }
    }

    private static function isAccepted(mixed $value): bool
    {
        return $value === true || $value === 1 || (is_string($value) && in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true));
    }

    /**
     * @return array<int, string>
     */
    private static function cleanReasons(mixed $reasons): array
    {
        $out = [];
        foreach ((array) $reasons as $code) {
            if (is_string($code) && preg_match('/^[A-Z_]{2,40}(:[a-z_]{1,20})?$/', $code)) {
                $out[] = $code;
            }
        }

        return array_values(array_unique($out));
    }

    private static function cleanName(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : mb_substr($value, 0, 191);
    }

    /**
     * วันที่ YYYY-MM-DD (ค.ศ.) ที่ใช้ได้จริง — ผิดรูป = null
     */
    private static function cleanDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));
        if (! checkdate($m, $d, $y) || $y < 1900 || $y > 2200) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    private static function score(mixed $value): ?float
    {
        return is_numeric($value) ? max(0.0, min(1.0, (float) $value)) : null;
    }
}
