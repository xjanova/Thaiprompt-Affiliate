<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\FreshMarketSeller;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\RiderPay\RiderPayService;
use App\Services\Seller\SellerPanelGate;
use App\Support\SafeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 🛵 ค่าตอบแทนไรเดอร์ของร้าน (ไรเดอร์รอบ 2) — โบนัสที่ร้านเติม + ส่งฟรี + ผู้ช่วยแนะนำ
 *
 * GET /api/v1/seller/rider-pay                 ร้านค้า (ด่าน SellerPanelGate เหมือน /seller/store)
 * PUT /api/v1/seller/rider-pay
 * GET /api/v1/fresh-market/seller/rider-pay    ร้านตลาดสดของผู้เรียก (fresh_market_sellers.user_id)
 * PUT /api/v1/fresh-market/seller/rider-pay
 *
 * GET รับ query ทดลอง bonus, bonus_peak, free_delivery → คำนวณใหม่โดยไม่บันทึก (และไม่เรียก AI สด)
 * PUT {rider_bonus 0..100, rider_bonus_peak 0..100, rider_free_delivery bool} — ทศนิยมไม่เกิน 2 ตำแหน่ง
 * ไม่มี id ใน URL — แตะได้เฉพาะร้านของผู้เรียกเท่านั้น
 */
class RiderPayApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(
        private readonly SellerPanelGate $gate,
        private readonly RiderPayService $riderPay,
    ) {}

    // ===== ร้านค้า =====

    public function showShop(Request $request): JsonResponse
    {
        [$store, $denied] = $this->shopStore($request);

        return $denied ?? $this->show($request, $store);
    }

    public function updateShop(Request $request): JsonResponse
    {
        [$store, $denied] = $this->shopStore($request);

        return $denied ?? $this->update($request, $store);
    }

    // ===== ร้านตลาดสด =====

    public function showFreshMarket(Request $request): JsonResponse
    {
        $seller = $this->freshMarketSeller($request);
        if (! $seller) {
            return $this->fail('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        return $this->show($request, $seller);
    }

    public function updateFreshMarket(Request $request): JsonResponse
    {
        $seller = $this->freshMarketSeller($request);
        if (! $seller) {
            return $this->fail('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        if (! $seller->is_active || $seller->is_suspended) {
            return $this->fail('SELLER_SUSPENDED', 'ร้านของคุณถูกระงับหรือปิดอยู่ ไม่สามารถแก้การตั้งค่าได้', 403);
        }

        return $this->update($request, $seller);
    }

    // ===== ภายใน =====

    private function show(Request $request, Model $store): JsonResponse
    {
        $preview = $this->validateOrFail($request, [
            'bonus' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            'bonus_peak' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            'free_delivery' => ['nullable', Rule::in(['0', '1', 'true', 'false', 0, 1, true, false])],
        ], $this->messages());
        if ($preview instanceof JsonResponse) {
            return $preview;
        }

        $overrides = [];
        if (isset($preview['bonus']) && $preview['bonus'] !== '') {
            $overrides['rider_bonus'] = (float) $preview['bonus'];
        }
        if (isset($preview['bonus_peak']) && $preview['bonus_peak'] !== '') {
            $overrides['rider_bonus_peak'] = (float) $preview['bonus_peak'];
        }
        if (isset($preview['free_delivery']) && $preview['free_delivery'] !== '') {
            $overrides['rider_free_delivery'] = filter_var($preview['free_delivery'], FILTER_VALIDATE_BOOLEAN);
        }

        try {
            $payload = $this->riderPay->build($store, $overrides === [] ? null : $overrides);
        } catch (\Throwable $e) {
            Log::error('RiderPay: build failed', ['store' => $this->riderPay->storeKey($store), 'error' => SafeLog::exceptionMessage($e)]);

            return $this->fail('SERVER_ERROR', 'คำนวณค่าตอบแทนไรเดอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($payload, $overrides === [] ? 'ดึงค่าตอบแทนไรเดอร์สำเร็จ' : 'คำนวณตัวอย่างแล้ว (ยังไม่บันทึก)');
    }

    private function update(Request $request, Model $store): JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'rider_bonus' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'rider_bonus_peak' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'rider_free_delivery' => ['sometimes', 'required', 'boolean'],
        ], $this->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        if ($data === []) {
            return $this->fail('VALIDATION_ERROR', 'กรุณาระบุค่าที่ต้องการบันทึก', 422);
        }

        try {
            $fresh = $this->riderPay->save($store, $data);
            $payload = $this->riderPay->build($fresh);
        } catch (\Throwable $e) {
            Log::error('RiderPay: save failed', ['store' => $this->riderPay->storeKey($store), 'error' => SafeLog::exceptionMessage($e)]);

            return $this->fail('SERVER_ERROR', 'บันทึกค่าตอบแทนไรเดอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($payload, 'บันทึกค่าตอบแทนไรเดอร์แล้ว');
    }

    /**
     * ร้านค้าของผู้เรียก (ผ่านด่านผู้ขายเดียวกับ /seller/store)
     *
     * @return array{0: ?VendorStore, 1: ?JsonResponse}
     */
    private function shopStore(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $gate = $this->gate->check($user, requireStore: true);

        if (! $gate['ok']) {
            return [null, $this->denied($gate)];
        }

        return [$gate['store'], null];
    }

    private function freshMarketSeller(Request $request): ?FreshMarketSeller
    {
        return FreshMarketSeller::where('user_id', $request->user()->id)->orderBy('id')->first();
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            '*.numeric' => 'โบนัสต้องเป็นตัวเลข',
            '*.decimal' => 'โบนัสมีทศนิยมได้ไม่เกิน 2 ตำแหน่ง',
            '*.between' => 'โบนัสต้องอยู่ระหว่าง 0 ถึง 100 บาท',
            '*.required' => 'กรุณาระบุค่า',
            'rider_free_delivery.boolean' => 'ค่าส่งฟรีไม่ถูกต้อง',
            'free_delivery.in' => 'ค่าส่งฟรีไม่ถูกต้อง',
        ];
    }
}
