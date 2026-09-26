<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\Seller\SellerPanelGate;
use App\Services\Seller\SellerStoreSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ตั้งค่าร้านในแอป — กติกาเดียวกับหน้าเว็บ /seller/store/settings (SellerStoreSettingsService)
 *
 * สิทธิ์: ผ่านด่าน SellerPanelGate และต้องมีร้านของตัวเอง — แก้ได้เฉพาะร้าน user_id = ผู้เรียก (ไม่มี id ใน URL เลย)
 *
 * GET  /api/v1/seller/store         ข้อมูลร้าน
 * PUT  /api/v1/seller/store         แก้เฉพาะช่องที่ส่งมา
 * POST /api/v1/seller/store/logo    อัปโหลดโลโก้ (store_logo ≤2MB)
 * POST /api/v1/seller/store/banner  อัปโหลดแบนเนอร์ (store_banner ≤4MB)
 */
class SellerStoreApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(
        private readonly SellerPanelGate $gate,
        private readonly SellerStoreSettingsService $settings,
    ) {}

    public function show(Request $request): JsonResponse
    {
        [, $store, $denied] = $this->ownStore($request);
        if ($denied) {
            return $denied;
        }

        return $this->ok($this->settings->present($store), 'ดึงข้อมูลร้านสำเร็จ');
    }

    public function update(Request $request): JsonResponse
    {
        [$user, $store, $denied] = $this->ownStore($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, $this->settings->rules(), $this->settings->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $crossErrors = $this->settings->crossFieldErrors($store, $data);
        if ($crossErrors !== []) {
            return $this->fail(
                'VALIDATION_ERROR',
                (string) reset($crossErrors),
                422,
                [],
                array_map(fn (string $m) => [$m], $crossErrors)
            );
        }

        try {
            $fresh = $this->settings->update($user, $store, $data, $request->ip());
        } catch (\Throwable $e) {
            Log::error('Seller store settings update failed (app)', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return $this->fail('SERVER_ERROR', 'บันทึกการตั้งค่าร้านไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($this->settings->present($fresh), 'บันทึกการตั้งค่าร้านแล้ว');
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        return $this->upload($request, 'logo', 'store_logo', 2048);
    }

    public function uploadBanner(Request $request): JsonResponse
    {
        return $this->upload($request, 'banner', 'store_banner', 4096);
    }

    /**
     * @param  'logo'|'banner'  $kind
     */
    private function upload(Request $request, string $kind, string $field, int $maxKb): JsonResponse
    {
        [, $store, $denied] = $this->ownStore($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, [
            $field => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:'.$maxKb,
        ], $this->settings->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            $fresh = $this->settings->uploadImage($store, $request->file($field), $kind);
        } catch (\Throwable $e) {
            Log::error('Seller store image upload failed (app)', ['store_id' => $store->id, 'kind' => $kind, 'error' => $e->getMessage()]);

            return $this->fail('UPLOAD_FAILED', 'อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($this->settings->present($fresh), $kind === 'logo' ? 'เปลี่ยนโลโก้ร้านแล้ว' : 'เปลี่ยนแบนเนอร์ร้านแล้ว');
    }

    /**
     * ร้านของผู้เรียก (ต้องผ่านด่านผู้ขายและมีร้าน)
     *
     * @return array{0: ?User, 1: ?VendorStore, 2: ?JsonResponse}
     */
    private function ownStore(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $gate = $this->gate->check($user, requireStore: true);

        if (! $gate['ok']) {
            return [null, null, $this->denied($gate)];
        }

        return [$user, $gate['store'], null];
    }
}
