<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Admin\RiderController as WebRiderController;
use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Services\RiderAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * 🛵 แอปแอดมิน: ใบสมัครไรเดอร์ + ตรวจเอกสารที่ไรเดอร์เปลี่ยนหลังอนุมัติ
 *
 * ทุกการกระทำเรียกเมธอดของหลังบ้านเว็บ Admin\RiderController ตัวเดิม (ตอบ JSON ได้อยู่แล้ว)
 * → validation / เงื่อนไขสถานะ / เอกสารต้องครบ / ข้อความไทย / log ชุดเดียวกับเว็บทุกตัว
 *
 * เอกสารอยู่ private disk → สตรีมผ่าน endpoint ที่ต้องแนบ Bearer token (Cache-Control: private, no-store)
 * สิทธิ์: แอดมิน (เว็บใช้ role:admin,super_admin เหมือนกัน — ไม่มีสิทธิ์ย่อย)
 */
class RiderApplicationsController extends Controller
{
    use ApprovalResponses;

    /** pending = ใบสมัครรอตรวจ · documents_changed = อนุมัติแล้วแต่เปลี่ยนเอกสารสำคัญ รอตรวจซ้ำ */
    public const STATUSES = ['pending', 'documents_changed', 'rejected', 'all'];

    public function __construct(private readonly RiderAccountService $accounts) {}

    /**
     * GET /api/admin/approvals/riders?status=pending&search=&page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->validateInput($request, [
            'status' => 'nullable|in:'.implode(',', self::STATUSES),
            'search' => 'nullable|string|max:100',
        ] + $this->pagingRules(), [
            'status.in' => 'สถานะไม่ถูกต้อง',
        ]);

        $status = (string) ($data['status'] ?? 'pending');
        $query = Rider::query()->with(['user:id,name,member_number,kyc_status']);

        match ($status) {
            'pending' => $query->where('status', 'pending')->orderBy('created_at')->orderBy('id'),
            'documents_changed' => $query->whereNotNull('documents_changed_at')->orderBy('documents_changed_at')->orderBy('id'),
            'rejected' => $query->where('status', 'rejected')->orderByDesc('rejected_at')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        if (! empty($data['search'])) {
            $search = trim((string) $data['search']);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('vehicle_plate', 'like', '%'.$search.'%');
            });
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));

        return $this->paged($page, $page->getCollection()->map(fn (Rider $r) => $this->listItem($r))->all());
    }

    /**
     * GET /api/admin/approvals/riders/{rider}
     */
    public function show(Rider $rider): JsonResponse
    {
        $rider->load('user');
        $missing = $this->accounts->missingDocuments($rider);

        $documents = array_map(fn (array $doc) => $doc + ['requires_auth' => $doc['url'] !== null], $this->accounts->documentList(
            $rider,
            fn (Rider $r, string $type) => route('api.admin.approvals.riders.document', [$r->id, $type])
        ));

        return $this->ok($this->listItem($rider) + [
            'phone' => $rider->phone,
            'birth_date' => $rider->birth_date?->toDateString(),
            'address' => [
                'line' => $rider->address,
                'district' => $rider->district,
                'province' => $rider->province,
            ],
            'vehicle' => [
                'type' => $rider->vehicle_type,
                'type_text' => $rider->vehicle_type_text,
                'plate' => $rider->vehicle_plate,
                'brand' => $rider->vehicle_brand,
                'color' => $rider->vehicle_color,
            ],
            'rider_type' => $rider->rider_type,
            'documents' => $documents,
            'rejection_reason' => $rider->status === 'rejected' ? $rider->rejection_reason : null,
            'suspension_reason' => $rider->status === 'suspended' ? $rider->suspension_reason : null,
            'approved_at' => $this->iso($rider->approved_at),
            'actions' => [
                // ชุดเดียวกับปุ่มหน้าเว็บ (admin.riders.show: canApprove)
                'can_approve' => in_array($rider->status, ['pending', 'rejected', 'inactive'], true) && $missing === [],
                'can_reject' => in_array($rider->status, ['pending', 'inactive'], true),
                'can_mark_documents_reviewed' => $this->accounts->documentsChangedAt($rider) !== null && $missing === [],
            ],
        ]);
    }

    /**
     * GET /api/admin/approvals/riders/{rider}/document/{type}
     *
     * สตรีมเอกสารจาก private disk ผ่าน Admin\RiderController::document (log การเปิดดูแบบเดียวกับเว็บ)
     */
    public function document(Rider $rider, string $type): Response
    {
        try {
            return app(WebRiderController::class)->document($rider, $type);
        } catch (HttpExceptionInterface $e) {
            return $this->fail('ไม่พบเอกสารนี้', 'DOCUMENT_NOT_FOUND', 404);
        } catch (\Throwable $e) {
            return $this->serverError('rider_document', $e, ['rider_id' => $rider->id, 'type' => $type]);
        }
    }

    /**
     * POST /api/admin/approvals/riders/{rider}/approve
     */
    public function approve(Request $request, Rider $rider): JsonResponse
    {
        return $this->delegateToWeb($request, fn () => app(WebRiderController::class)->approve($request, $rider), 'rider_approve');
    }

    /**
     * POST /api/admin/approvals/riders/{rider}/reject  body: { reason }
     */
    public function reject(Request $request, Rider $rider): JsonResponse
    {
        // กดซ้ำหลังปฏิเสธไปแล้ว = สำเร็จเฉย ๆ (หน้าเว็บตอบ 409 — แอปลองซ้ำเมื่อเน็ตหลุดได้ปลอดภัย)
        if ($rider->status === 'rejected') {
            return $this->ok(['id' => (int) $rider->id, 'status' => 'rejected', 'already_decided' => true], 'ใบสมัครนี้ถูกปฏิเสธไปแล้ว');
        }

        return $this->delegateToWeb($request, fn () => app(WebRiderController::class)->reject($request, $rider), 'rider_reject');
    }

    /**
     * POST /api/admin/approvals/riders/{rider}/documents-reviewed
     */
    public function documentsReviewed(Request $request, Rider $rider): JsonResponse
    {
        return $this->delegateToWeb($request, fn () => app(WebRiderController::class)->markDocumentsReviewed($request, $rider), 'rider_documents_reviewed');
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(Rider $rider): array
    {
        $missing = $this->accounts->missingDocuments($rider);
        $changedAt = $this->accounts->documentsChangedAt($rider);
        $user = $rider->user;

        return [
            'id' => (int) $rider->id,
            'full_name' => (string) $rider->full_name,
            'phone_masked' => $this->maskPhone($rider->phone),
            'id_card_masked' => $this->accounts->maskIdCard($rider->id_card_number),
            'status' => (string) $rider->status,
            'status_text' => $rider->status_text,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_type_text' => $rider->vehicle_type_text,
            'vehicle_plate' => $rider->vehicle_plate,
            'province' => $rider->province,
            'user' => $this->personRef($user),
            'kyc_verified' => ($user?->kyc_status ?? null) === 'approved',
            'documents_complete' => $missing === [],
            'documents_missing' => $missing,
            'documents_missing_text' => $missing !== [] ? $this->accounts->documentLabels($missing) : null,
            'documents_changed_at' => $this->iso($changedAt),
            'submitted_at' => $this->iso($rider->created_at),
            'waiting_minutes' => match (true) {
                $rider->status === 'pending' => $this->minutesSince($rider->created_at),
                $changedAt !== null => $this->minutesSince($changedAt),
                default => null,
            },
        ];
    }
}
