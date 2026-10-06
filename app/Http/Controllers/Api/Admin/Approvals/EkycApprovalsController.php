<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\KycAccessLog;
use App\Models\KycVerification;
use App\Services\Ekyc\EkycException;
use App\Services\Ekyc\EkycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 🪪 แอปแอดมิน: คิวตรวจ AI eKYC ที่ AI ไม่มั่นใจ (status = pending) — ตรรกะเดียวกับหลังบ้านเว็บ Admin\KycController
 *
 * - สิทธิ์: KycVerificationPolicy ตัวเดียวกับเว็บ (viewAny/view/viewImages/approve/reject/requestRetake)
 * - ตัดสิน: EkycService::adminDecide() (ล็อกเลขบัตร · เช็คบัตรซ้ำสด · อัปเดตผู้ใช้ · แจ้งผล) — ไม่มีตรรกะใหม่
 * - รูป: สตรีมผ่าน endpoint ที่ต้องแนบ Bearer token · Cache-Control: private, no-store
 *   · บันทึกการเปิดดูทุกครั้งใน kyc_access_logs (PDPA) ผ่าน KycAccessLog::record() ตัวเดียวกับเว็บ
 * - เฉพาะแถว eKYC (method = ekyc) — คำขอแบบเดิม (อัปโหลดเอกสารเอง) ยังตรวจที่หลังบ้านเว็บ
 * - ไม่ส่งเลขบัตรเต็มออกไป: รายการ = 4 ตัวท้าย · รายละเอียด = ปิดกลาง (EkycService::maskId)
 */
class EkycApprovalsController extends Controller
{
    use ApprovalResponses;

    public const STATUSES = ['pending', 'approved', 'rejected', KycVerification::STATUS_RETAKE, 'all'];

    public const IMAGE_KINDS = [
        'card' => 'รูปบัตรประชาชน',
        'card_face' => 'รูปหน้าบนบัตร',
        'best_frame' => 'ใบหน้าจากกล้อง (เฟรมที่ดีที่สุด)',
    ];

    /** รหัสเหตุผลที่แอปควรเน้นเป็นธงเตือน */
    private const FLAG_REASONS = [
        'duplicate_id' => 'DUPLICATE_ID',
        'replay_suspected' => 'REPLAY_SUSPECTED',
        'prior_rejected' => 'PRIOR_REJECTED',
        'attempts_exhausted' => 'ATTEMPTS_EXHAUSTED',
        'user_corrected' => 'USER_CORRECTED',
        'ai_unavailable' => 'AI_UNAVAILABLE',
        'card_not_real' => 'LOW_CARD_REAL',
    ];

    public function __construct(private readonly EkycService $ekyc) {}

    /**
     * GET /api/admin/approvals/ekyc?status=pending&search=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->can('viewAny', KycVerification::class)) {
            return $this->forbidden('ไม่มีสิทธิ์ดูรายการยืนยันตัวตน');
        }

        $data = $this->validateInput($request, [
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'search' => 'nullable|string|max:100',
        ] + $this->pagingRules(), [
            'status.in' => 'สถานะไม่ถูกต้อง',
        ]);

        $status = (string) ($data['status'] ?? 'pending');

        $query = KycVerification::query()
            ->ekyc()
            ->where('status', '!=', 'draft')
            ->with(['user:id,name,member_number']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($data['search'])) {
            $search = trim((string) $data['search']);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // คิวรอตรวจ = รอนานสุดก่อน (เหมือนคิวในหน้าเว็บ) · อื่น ๆ = ล่าสุดก่อน
        if ($status === 'pending') {
            $query->orderByRaw('COALESCE(processed_at, submitted_at, created_at) ASC')->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));

        return $this->paged($page, $page->getCollection()->map(fn (KycVerification $k) => $this->listItem($k))->all());
    }

    /**
     * GET /api/admin/approvals/ekyc/{kyc}
     */
    public function show(Request $request, KycVerification $kyc): JsonResponse
    {
        if (! $kyc->isEkyc() || $kyc->status === 'draft') {
            return $this->notFound('ไม่พบคำขอยืนยันตัวตนนี้');
        }
        if (! $request->user()->can('view', $kyc)) {
            return $this->forbidden('ไม่มีสิทธิ์ดูคำขอยืนยันตัวตนนี้');
        }

        $kyc->load(['user', 'reviewer:id,name']);
        $summary = $this->ekyc->adminSummary($kyc);
        $canDecide = $kyc->status === 'pending' && $request->user()->can('approve', $kyc);

        $images = [];
        foreach (self::IMAGE_KINDS as $kind => $label) {
            $available = (bool) ($summary['has_'.$kind] ?? false);
            $images[] = [
                'kind' => $kind,
                'label' => $label,
                'available' => $available,
                'url' => $available ? route('api.admin.approvals.ekyc.image', [$kyc->id, $kind]) : null,
            ];
        }

        $views = KycAccessLog::query()
            ->with('viewer:id,name')
            ->where('kyc_verification_id', $kyc->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (KycAccessLog $log) => [
                'viewer' => $log->viewer?->name,
                'kind' => $log->kind,
                'at' => $this->iso($log->created_at),
            ])
            ->all();

        return $this->ok($this->listItem($kyc) + [
            'card' => [
                'name_th' => $kyc->name_th,
                'name_en' => $kyc->name_en,
                'birth_date' => $kyc->birth_date?->toDateString(),
                'expiry_date' => $kyc->card_expiry?->toDateString(),
                'lifelong' => (bool) ($summary['lifelong'] ?? false),
                'id_masked' => $summary['id_masked'] ?? null,
                'checksum_ok' => $summary['checksum_ok'] ?? null,
            ],
            'account' => [
                'name' => $kyc->user?->name,
                'kyc_status' => $kyc->user?->kyc_status,
                'name_matches_card' => (bool) ($summary['name_matches_account'] ?? false),
                'created_at' => $this->iso($kyc->user?->created_at),
            ],
            'ai_detail' => [
                'match_score' => $summary['match_score'] ?? null,
                'liveness_passed' => $summary['liveness_passed'] ?? null,
                'challenges' => $summary['challenges'] ?? [],
                'thresholds' => $summary['thresholds'] ?? [],
                'model_version' => $kyc->ai_model_version,
            ],
            'corrections' => $summary['corrections'] ?? [],
            // เช็คสด ณ ตอนเปิดดู — อีกบัญชีอาจยืนยันด้วยบัตรนี้ระหว่างรอตรวจ (อนุมัติจะถูกปฏิเสธ EKYC_DUPLICATE_ID)
            'duplicate_now' => (bool) ($summary['duplicate_now'] ?? false),
            'images' => $images,
            'image_requires_auth' => true,
            'recent_views' => $views,
            'review' => [
                'reviewed_by' => $kyc->reviewer?->name,
                'reviewed_at' => $this->iso($kyc->reviewed_at),
                'note' => $kyc->rejection_reason,
            ],
            'actions' => [
                'can_approve' => $canDecide,
                'can_reject' => $canDecide,
                'can_request_retake' => $canDecide,
            ],
        ]);
    }

    /**
     * GET /api/admin/approvals/ekyc/{kyc}/image/{kind}
     *
     * สตรีมรูปที่ถอดรหัสแล้ว — บันทึกการเปิดดูทุกครั้ง (PDPA) เหมือนหลังบ้านเว็บ
     */
    public function image(Request $request, KycVerification $kyc, string $kind): Response
    {
        if (! $request->user()->can('viewImages', $kyc)) {
            return $this->forbidden('ไม่มีสิทธิ์เปิดดูรูปยืนยันตัวตน');
        }

        if (! $kyc->isEkyc() || ! array_key_exists($kind, self::IMAGE_KINDS)) {
            return $this->notFound('ไม่พบรูปนี้');
        }

        $bytes = $this->ekyc->decryptImage($kyc, $kind);
        if ($bytes === null) {
            return $this->fail('ไม่พบรูปนี้ (อาจถูกลบตามรอบเก็บข้อมูลแล้ว)', 'IMAGE_NOT_FOUND', 404);
        }

        KycAccessLog::record($kyc, $request->user(), $kind, $request);

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($bytes),
            'Content-Disposition' => 'inline; filename="kyc-'.$kyc->id.'-'.$kind.'.jpg"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    /**
     * POST /api/admin/approvals/ekyc/{kyc}/approve
     */
    public function approve(Request $request, KycVerification $kyc): JsonResponse
    {
        if (! $request->user()->can('approve', $kyc)) {
            return $this->forbidden('ไม่มีสิทธิ์อนุมัติการยืนยันตัวตน');
        }

        return $this->decide($request, $kyc, 'approved', null, 'อนุมัติการยืนยันตัวตนเรียบร้อยแล้ว');
    }

    /**
     * POST /api/admin/approvals/ekyc/{kyc}/reject  body: { reason }
     */
    public function reject(Request $request, KycVerification $kyc): JsonResponse
    {
        if (! $request->user()->can('reject', $kyc)) {
            return $this->forbidden('ไม่มีสิทธิ์ปฏิเสธการยืนยันตัวตน');
        }

        $data = $this->validateInput($request, [
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลในการปฏิเสธ',
            'reason.max' => 'เหตุผลต้องไม่เกิน 1000 ตัวอักษร',
        ]);

        return $this->decide($request, $kyc, 'rejected', (string) $data['reason'], 'ปฏิเสธการยืนยันตัวตนเรียบร้อยแล้ว');
    }

    /**
     * POST /api/admin/approvals/ekyc/{kyc}/request-retake  body: { reason }
     */
    public function requestRetake(Request $request, KycVerification $kyc): JsonResponse
    {
        if (! $request->user()->can('requestRetake', $kyc)) {
            return $this->forbidden('ไม่มีสิทธิ์ขอให้ผู้ใช้ถ่ายใหม่');
        }

        $data = $this->validateInput($request, [
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุสิ่งที่ต้องการให้ผู้ใช้แก้ (ผู้ใช้จะเห็นข้อความนี้)',
            'reason.max' => 'หมายเหตุต้องไม่เกิน 500 ตัวอักษร',
        ]);

        return $this->decide($request, $kyc, 'retake', (string) $data['reason'], 'ส่งคำขอให้ผู้ใช้ถ่ายใหม่เรียบร้อยแล้ว');
    }

    /**
     * ตัดสินผ่าน EkycService::adminDecide — กดซ้ำด้วยผลเดิม = สำเร็จ (already_decided) · ผลอื่นไปแล้ว = 409
     */
    private function decide(Request $request, KycVerification $kyc, string $decision, ?string $note, string $success): JsonResponse
    {
        if (! $kyc->isEkyc() || $kyc->status === 'draft') {
            return $this->notFound('ไม่พบคำขอยืนยันตัวตนนี้');
        }

        $target = match ($decision) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => KycVerification::STATUS_RETAKE,
        };

        if ($kyc->status !== 'pending') {
            return $this->alreadyDecided($kyc, $target);
        }

        try {
            $kyc = $this->ekyc->adminDecide($kyc, $request->user(), $decision, $note);
        } catch (EkycException $e) {
            // แอดมินอีกคนตัดสินไปพร้อมกัน → ผลเดียวกัน = สำเร็จ
            if ($e->errorCode === 'EKYC_ALREADY_DECIDED') {
                return $this->alreadyDecided($kyc->fresh(), $target);
            }

            return $this->fail($e->getMessage(), $e->errorCode, $e->status);
        } catch (\Throwable $e) {
            return $this->serverError('ekyc_'.$decision, $e, ['kyc_id' => $kyc->id]);
        }

        return $this->ok([
            'id' => (int) $kyc->id,
            'status' => (string) $kyc->status,
            'already_decided' => false,
        ], $success);
    }

    private function alreadyDecided(KycVerification $kyc, string $target): JsonResponse
    {
        if ($kyc->status === $target) {
            return $this->ok([
                'id' => (int) $kyc->id,
                'status' => (string) $kyc->status,
                'already_decided' => true,
            ], 'รายการนี้ถูกดำเนินการแบบเดียวกันไปแล้ว');
        }

        return $this->fail('การยืนยันตัวตนนี้ได้ถูกดำเนินการไปแล้ว', 'EKYC_ALREADY_DECIDED', 409, [
            'id' => (int) $kyc->id,
            'status' => (string) $kyc->status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(KycVerification $kyc): array
    {
        $reasons = array_values(array_filter((array) ($kyc->ai_reasons ?? []), 'is_string'));
        $flags = [];
        foreach (self::FLAG_REASONS as $flag => $code) {
            $flags[$flag] = in_array($code, $reasons, true);
        }
        $flags['lifelong_card'] = in_array('EXPIRY_LIFELONG', $reasons, true);

        $submittedAt = $kyc->processed_at ?? $kyc->submitted_at ?? $kyc->created_at;

        return [
            'id' => (int) $kyc->id,
            'user' => $this->personRef($kyc->user),
            'status' => (string) $kyc->status,
            'status_label' => match ($kyc->status) {
                'pending' => 'รอเจ้าหน้าที่ตรวจ',
                'approved' => 'อนุมัติแล้ว',
                'rejected' => 'ปฏิเสธแล้ว',
                KycVerification::STATUS_RETAKE => 'ขอให้ถ่ายใหม่',
                KycVerification::STATUS_SUPERSEDED => 'ถูกแทนด้วยการยืนยันที่ผ่านแล้ว',
                default => (string) $kyc->status,
            },
            'method' => 'ekyc',
            'document_type' => 'thai_national_id',
            'document_type_label' => 'บัตรประชาชนไทย',
            'id_last4' => $kyc->id_last4 ? '•••••••••'.$kyc->id_last4 : null,
            'submitted_at' => $this->iso($submittedAt),
            'waiting_minutes' => $kyc->status === 'pending' ? $this->minutesSince($submittedAt) : null,
            'ai' => [
                'decision' => $kyc->ai_decision,
                'face_match' => $kyc->ai_face_match !== null ? round((float) $kyc->ai_face_match, 4) : null,
                'liveness' => $kyc->ai_liveness !== null ? round((float) $kyc->ai_liveness, 4) : null,
                'real' => $kyc->ai_real !== null ? round((float) $kyc->ai_real, 4) : null,
                'card_real' => $kyc->ai_card_real !== null ? round((float) $kyc->ai_card_real, 4) : null,
                'ocr' => $kyc->ai_ocr_confidence !== null ? round((float) $kyc->ai_ocr_confidence, 4) : null,
            ],
            'reasons' => $reasons,
            'reason_texts' => EkycService::reasonTexts($reasons),
            'flags' => $flags,
        ];
    }
}
