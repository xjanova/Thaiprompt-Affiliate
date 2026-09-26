<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Seller\SellerApplicationService;
use App\Services\Seller\SellerPanelGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🏪 สมัครเปิดร้านค้าจากแอป — ตรรกะเดียวกับเว็บ /user/seller-apply (SellerApplicationService)
 *
 * คำขอใหม่ไปรอแอดมินที่ /admin/seller-applications เหมือนยื่นจากเว็บทุกอย่าง
 *
 * GET  /api/v1/seller/application  สถานะคำขอ + ค่าเดิมสำหรับเติมฟอร์ม (ยื่นใหม่หลังถูกปฏิเสธ)
 * POST /api/v1/seller/application  ยื่นคำขอ (หรือยื่นใหม่)
 */
class SellerApplicationApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(
        private readonly SellerApplicationService $applications,
        private readonly SellerPanelGate $gate,
    ) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->ok($this->payload($user), 'ดึงสถานะคำขอเปิดร้านสำเร็จ');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // เช็คสถานะก่อนตรวจฟอร์ม — คนที่ยื่นไม่ได้ (รออนุมัติ/เป็นผู้ขายแล้ว) ไม่ต้องเห็น error รายช่อง
        $state = $this->applications->stateFor($user, $this->applications->latestStore($user));
        if (! in_array($state, SellerApplicationService::SUBMITTABLE_STATES, true)) {
            return $this->fail('APPLICATION_NOT_ALLOWED', $this->applications->stateMessage($state), 409, $this->payload($user));
        }

        $validated = $this->validateOrFail($request, $this->applications->rules(), $this->applications->messages());
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        try {
            $result = $this->applications->submit($user, $validated);
        } catch (\Throwable $e) {
            Log::error('Seller application submit failed (app)', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->fail('SERVER_ERROR', 'ส่งคำขอไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        if (! $result['ok']) {
            return $this->fail('APPLICATION_NOT_ALLOWED', $this->applications->stateMessage($result['state']), 409, $this->payload($user));
        }

        return $this->ok(
            $this->payload($user->fresh()),
            $result['resubmitted']
                ? 'ส่งคำขอเปิดร้านใหม่เรียบร้อย ทีมงานจะตรวจสอบโดยเร็ว'
                : 'ส่งคำขอเปิดร้านเรียบร้อย ทีมงานจะตรวจสอบและแจ้งผลทางการแจ้งเตือน',
            $result['resubmitted'] ? 200 : 201
        );
    }

    /**
     * สถานะ + ข้อมูลคำขอล่าสุด (เฉพาะร้านของผู้ใช้เอง)
     *
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        $store = $this->applications->latestStore($user);
        $state = $this->applications->stateFor($user, $store);

        return [
            'state' => $state,
            'can_submit' => in_array($state, SellerApplicationService::SUBMITTABLE_STATES, true),
            'kyc_approved' => $this->gate->kycApproved($user),
            'rejection_reason' => $state === 'rejected' ? $store?->suspension_reason : null,
            'application' => $store ? $this->presentStore($store) : null,
            'contact_email' => $user->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentStore(VendorStore $store): array
    {
        return [
            'id' => (int) $store->id,
            'status' => $store->status,
            'store_name' => (string) $store->store_name,
            'business_type' => $store->business_type ?: 'individual',
            'store_phone' => $store->store_phone,
            'store_description' => $store->store_description,
            'store_address' => $store->store_address,
            'store_city' => $store->store_city,
            'store_state' => $store->store_state,
            'store_postal_code' => $store->store_postal_code,
            'company_name' => $store->company_name,
            'tax_id' => $store->tax_id,
            'submitted_at' => $store->updated_at?->toIso8601String(),
        ];
    }
}
