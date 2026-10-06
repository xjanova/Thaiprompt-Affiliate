<?php

namespace App\Http\Controllers\Api\Admin\Approvals;

use App\Http\Controllers\Api\Admin\Approvals\Concerns\ApprovalResponses;
use App\Http\Controllers\Controller;
use App\Models\VendorStore;
use App\Services\Seller\SellerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 🏪 แอปแอดมิน: คำขอเปิดร้านค้า (VendorStore status = pending) — ตรรกะเดียวกับหลังบ้านเว็บ Admin\SellerApplicationController
 *
 * อนุมัติ/ปฏิเสธผ่าน SellerApplicationService::approve()/reject() ตัวเดียวกับเว็บ (ล็อกแถว · เปลี่ยน role · แจ้งผู้สมัคร)
 * สิทธิ์: แอดมิน (เว็บใช้ role:admin,super_admin เหมือนกัน — ไม่มีสิทธิ์ย่อย)
 */
class SellerApplicationsController extends Controller
{
    use ApprovalResponses;

    public const STATUSES = ['pending', 'active', 'closed', 'all'];

    public function __construct(private readonly SellerApplicationService $applications) {}

    /**
     * GET /api/admin/approvals/seller-applications?status=pending&search=&page=&per_page=
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

        $query = VendorStore::query()->with(['user' => fn ($q) => $q->withTrashed()]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($data['search'])) {
            $search = trim((string) $data['search']);
            $query->where(function ($q) use ($search) {
                $q->where('store_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'));
            });
        }

        // คิวรออนุมัติ = รอนานสุดก่อน (เหมือนหน้าเว็บ — ยื่นใหม่ = updated_at ใหม่) · อื่น ๆ = ล่าสุดก่อน
        if ($status === 'pending') {
            $query->orderBy('updated_at')->orderBy('id');
        } else {
            $query->orderByDesc('updated_at')->orderByDesc('id');
        }

        $page = $query->paginate((int) ($data['per_page'] ?? 20));

        return $this->paged($page, $page->getCollection()->map(fn (VendorStore $s) => $this->listItem($s))->all());
    }

    /**
     * GET /api/admin/approvals/seller-applications/{store}
     */
    public function show(VendorStore $store): JsonResponse
    {
        $store->load(['user' => fn ($q) => $q->withTrashed()]);
        $owner = $store->user;

        return $this->ok($this->listItem($store) + [
            'store_description' => $store->store_description,
            'store_phone' => $store->store_phone,
            'store_email' => $store->store_email,
            'address' => [
                'line' => $store->store_address,
                'city' => $store->store_city,
                'state' => $store->store_state,
                'postal_code' => $store->store_postal_code,
            ],
            'owner_detail' => $owner ? [
                'role' => (string) $owner->role,
                'kyc_status' => $owner->kyc_status ?? 'not_submitted',
                'kyc_verified' => $owner->kyc_status === 'approved',
                'suspended' => $owner->blocked_at !== null,
                'deleted' => method_exists($owner, 'trashed') && $owner->trashed(),
                'joined_at' => $this->iso($owner->created_at),
            ] : null,
            'actions' => [
                'can_approve' => $store->status === 'pending',
                'can_reject' => $store->status === 'pending',
            ],
        ]);
    }

    /**
     * POST /api/admin/approvals/seller-applications/{store}/approve
     */
    public function approve(Request $request, VendorStore $store): JsonResponse
    {
        if ($store->status === 'active') {
            return $this->ok($this->result($store, true), 'ร้านนี้อนุมัติไปแล้ว');
        }

        try {
            $result = $this->applications->approve($store, $request->user());
        } catch (\Throwable $e) {
            return $this->serverError('seller_application_approve', $e, ['store_id' => $store->id]);
        }

        if (! $result['ok']) {
            $fresh = $store->fresh();
            if ($fresh && $fresh->status === 'active') {
                return $this->ok($this->result($fresh, true), 'ร้านนี้อนุมัติไปแล้ว');
            }

            return $this->fail($result['message'], $result['code'], 409, $fresh ? $this->result($fresh, false) : null);
        }

        return $this->ok($this->result($result['store'], false), $result['message']);
    }

    /**
     * POST /api/admin/approvals/seller-applications/{store}/reject  body: { reason }
     */
    public function reject(Request $request, VendorStore $store): JsonResponse
    {
        $data = $this->validateInput($request, [
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลที่ปฏิเสธ (ผู้สมัครจะเห็นข้อความนี้)',
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ]);

        if ($store->status === 'closed') {
            return $this->ok($this->result($store, true), 'คำขอนี้ถูกปฏิเสธไปแล้ว');
        }

        try {
            $result = $this->applications->reject($store, (string) $data['reason'], $request->user());
        } catch (\Throwable $e) {
            return $this->serverError('seller_application_reject', $e, ['store_id' => $store->id]);
        }

        $fresh = $store->fresh();

        if (! $result['ok']) {
            if ($fresh && $fresh->status === 'closed') {
                return $this->ok($this->result($fresh, true), 'คำขอนี้ถูกปฏิเสธไปแล้ว');
            }

            return $this->fail($result['message'], $result['code'], 409, $fresh ? $this->result($fresh, false) : null);
        }

        return $this->ok($this->result($fresh ?? $store, false), $result['message']);
    }

    /**
     * @return array<string, mixed>
     */
    private function result(VendorStore $store, bool $already): array
    {
        return [
            'id' => (int) $store->id,
            'status' => (string) $store->status,
            'already_decided' => $already,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listItem(VendorStore $store): array
    {
        $owner = $store->user;
        $taxId = preg_replace('/\D+/', '', (string) $store->tax_id) ?? '';
        $submittedAt = $store->updated_at ?? $store->created_at;

        return [
            'id' => (int) $store->id,
            'store_name' => (string) $store->store_name,
            'status' => (string) $store->status,
            'status_label' => match ($store->status) {
                'pending' => 'รออนุมัติ',
                'active' => 'อนุมัติแล้ว',
                'closed' => 'ไม่ผ่านการอนุมัติ',
                default => (string) $store->status,
            },
            'business_type' => $store->business_type,
            'business_type_label' => match ($store->business_type) {
                'company' => 'นิติบุคคล',
                'individual' => 'บุคคลธรรมดา',
                default => null,
            },
            'company_name' => $store->company_name,
            // เลขผู้เสียภาษี/เลขบัตร — ปิดกลาง เห็นแค่ 3 ตัวหน้า 3 ตัวท้าย
            'tax_id_masked' => $taxId !== '' ? (strlen($taxId) > 6 ? substr($taxId, 0, 3).str_repeat('•', strlen($taxId) - 6).substr($taxId, -3) : str_repeat('•', strlen($taxId))) : null,
            'phone_masked' => $this->maskPhone($store->store_phone),
            'province' => $store->store_state,
            'owner' => $this->personRef($owner),
            'rejection_reason' => $store->status === 'closed' ? $store->suspension_reason : null,
            'submitted_at' => $this->iso($submittedAt),
            'waiting_minutes' => $store->status === 'pending' ? $this->minutesSince($submittedAt) : null,
        ];
    }
}
