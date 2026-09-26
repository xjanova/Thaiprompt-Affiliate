<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\Seller\SellerPanelGate;
use App\Services\Seller\SellerProductService;
use App\Services\Shop\ShopPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * จัดการสินค้าของร้านในแอป — กติกาเดียวกับหลังร้านเว็บ /seller/products (SellerProductService)
 *
 * สิทธิ์: ผ่านด่าน SellerPanelGate (ผู้ขาย + KYC + ร้านเปิดอยู่ + มีแพ็กเกจ) และแตะได้เฉพาะสินค้า seller_id = ตัวเอง
 *        (สินค้าของคนอื่น = 404 เหมือนไม่มีอยู่ — กัน IDOR)
 *
 * GET    /seller/products                    รายการ (?filter=all|active|hidden|out_of_stock|low_stock|blocked&search=&page=&per_page=)
 * GET    /seller/products/meta               หมวดหมู่ + GP/VAT + เพดาน
 * POST   /seller/products/quote              คำนวณเงินที่ร้านได้รับ {price, cost?, product_id?}
 * POST   /seller/products                    สร้าง (multipart: ช่องข้อมูล + main_image + images[]) · header Idempotency-Key
 * GET    /seller/products/{id}               รายละเอียด
 * PUT    /seller/products/{id}               แก้ข้อมูล (JSON — รูปใช้ endpoint รูป)
 * POST   /seller/products/{id}/active        เปิด/ปิดการขาย {is_active}
 * POST   /seller/products/{id}/stock         ปรับสต็อก {stock_quantity}
 * DELETE /seller/products/{id}               ลบ (soft delete)
 * POST   /seller/products/{id}/images        เพิ่มรูป (multipart images[])
 * DELETE /seller/products/{id}/images/{img}  ลบรูป (0 = รูปหลัก)
 * POST   /seller/products/{id}/images/main   ตั้งรูปหลัก {image_id}
 * POST   /seller/products/{id}/images/order  เรียงรูปเพิ่มเติม {order: [id...]}
 */
class SellerProductApiController extends Controller
{
    use SellerAppResponses;

    /** เวลาที่จำผลการสร้างสินค้าต่อ Idempotency-Key (กันสร้างซ้ำตอนเน็ตหลุดแล้วกดใหม่) */
    private const IDEMPOTENCY_TTL_HOURS = 24;

    /** เวลาล็อกระหว่างสร้าง (ครอบเวลาอัปโหลดรูปหลายรูปบนเน็ตช้า) */
    private const CREATE_LOCK_SECONDS = 120;

    public function __construct(
        private readonly SellerPanelGate $gate,
        private readonly SellerProductService $products,
    ) {}

    public function index(Request $request): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, [
            'filter' => 'nullable|string',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ], ['search.max' => 'คำค้นต้องไม่เกิน 100 ตัวอักษร']);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $filter = in_array($data['filter'] ?? 'all', SellerProductService::FILTERS, true) ? ($data['filter'] ?? 'all') : 'all';
        $paginator = $this->products
            ->listQuery($user, $filter, $data['search'] ?? null)
            ->paginate((int) ($data['per_page'] ?? 20));

        return $this->ok([
            'products' => collect($paginator->items())->map(fn (Product $p) => $this->products->present($p))->values(),
            'pagination' => ShopPresenter::pagination($paginator),
            'counts' => $this->products->counts($user),
            'filter' => $filter,
        ], 'ดึงรายการสินค้าสำเร็จ');
    }

    public function meta(Request $request): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        return $this->ok($this->products->formMeta($user), 'ดึงข้อมูลฟอร์มสินค้าสำเร็จ');
    }

    public function quote(Request $request): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, [
            'price' => 'required|numeric|min:0|max:'.SellerProductService::MAX_PRICE,
            'cost' => 'nullable|numeric|min:0|max:'.SellerProductService::MAX_PRICE,
            'product_id' => 'nullable|integer|min:1',
        ], [
            'price.required' => 'กรุณากรอกราคาขาย',
            'price.numeric' => 'ราคาขายต้องเป็นตัวเลข',
            'price.min' => 'ราคาขายต้องไม่ติดลบ',
            'price.max' => 'ราคาขายสูงเกินไป',
            'cost.numeric' => 'ต้นทุนต้องเป็นตัวเลข',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        // สินค้าของคนอื่น = ถือว่าไม่ได้เลือก (คำนวณแบบสินค้าใหม่ของร้านตัวเอง)
        $product = isset($data['product_id']) ? $this->products->findOwned($user, (int) $data['product_id']) : null;

        try {
            return $this->ok(
                $this->products->quote($user, (float) $data['price'], isset($data['cost']) ? (float) $data['cost'] : null, $product),
                'คำนวณแล้ว'
            );
        } catch (\Throwable $e) {
            Log::error('Seller product quote failed (app)', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->fail('QUOTE_FAILED', 'คำนวณราคาไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        $product = $this->products->findOwned($user, $id);
        if (! $product) {
            return $this->notFound();
        }

        return $this->ok($this->products->present($product, true), 'ดึงข้อมูลสินค้าสำเร็จ');
    }

    public function store(Request $request): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, $this->products->rules(), $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        // กันสร้างซ้ำ: key เดียวกัน = ผลเดิม (เน็ตหลุดหลังบันทึกแล้วกดใหม่ จะไม่ได้สินค้าซ้อน)
        $key = $this->idempotencyKey($request);
        $cacheKey = $key ? 'seller-product-create:'.$user->id.':'.$key : null;

        if ($cacheKey && ($replayed = $this->replay($user, $cacheKey))) {
            return $replayed;
        }

        $lock = $cacheKey ? Cache::lock($cacheKey.':lock', self::CREATE_LOCK_SECONDS) : null;
        if ($lock && ! $lock->get()) {
            return $this->fail('REQUEST_IN_PROGRESS', 'กำลังบันทึกสินค้านี้อยู่ รอสักครู่นะ', 409);
        }

        try {
            // เช็คซ้ำหลังได้ล็อก (อีกคำขอเพิ่งบันทึกเสร็จระหว่างรอ)
            if ($cacheKey && ($replayed = $this->replay($user, $cacheKey))) {
                return $replayed;
            }

            $main = $request->file('main_image');
            $gallery = array_values(array_filter(
                (array) $request->file('images', []),
                fn ($f) => $f instanceof UploadedFile
            ));

            $product = $this->products->create($user, $data, $main instanceof UploadedFile ? $main : null, $gallery);

            if ($cacheKey) {
                Cache::put($cacheKey, (int) $product->id, now()->addHours(self::IDEMPOTENCY_TTL_HOURS));
            }

            $fresh = $this->products->findOwned($user, (int) $product->id) ?? $product;

            return $this->ok($this->products->present($fresh, true), 'เพิ่มสินค้าเรียบร้อยแล้ว', 201);
        } catch (\Throwable $e) {
            Log::error('Seller product create failed (app)', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->fail('SERVER_ERROR', 'บันทึกสินค้าไม่สำเร็จ กรุณาตรวจสอบข้อมูลและรูปภาพ แล้วลองใหม่อีกครั้ง', 500);
        } finally {
            $lock?->release();
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, $this->products->rules($product), $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        try {
            $this->products->update($product, $data);
        } catch (\Throwable $e) {
            Log::error('Seller product update failed (app)', ['user_id' => $user->id, 'product_id' => $product->id, 'error' => $e->getMessage()]);

            return $this->fail('SERVER_ERROR', 'บันทึกการแก้ไขสินค้าไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        return $this->ok($this->presentFresh($user, $product), 'บันทึกสินค้าเรียบร้อยแล้ว');
    }

    public function setActive(Request $request, int $id): JsonResponse
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return $denied;
        }

        $product = $this->products->findOwned($user, $id);
        if (! $product) {
            return $this->notFound();
        }
        // สินค้าที่ทีมงานระงับ → เปิด/ปิดเองไม่ได้ (สินค้ามีตัวเลือกย่อยยังเปิด/ปิดได้ ไม่กระทบตัวเลือก)
        if ($product->is_blocked) {
            $reason = $this->products->readOnlyReason($product);

            return $this->fail('PRODUCT_BLOCKED', $reason['message'] ?? 'สินค้านี้ถูกระงับโดยทีมงาน', 423);
        }

        $data = $this->validateOrFail($request, ['is_active' => 'required|boolean'], $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $this->products->setActive($product, (bool) $data['is_active']);

        return $this->ok(
            $this->presentFresh($user, $product),
            $data['is_active'] ? 'เปิดขายสินค้าแล้ว' : 'ปิดการขายสินค้าแล้ว'
        );
    }

    public function updateStock(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, ['stock_quantity' => 'required|integer|min:0|max:1000000'], $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $this->products->setStock($product, (int) $data['stock_quantity']);

        return $this->ok($this->presentFresh($user, $product), 'อัปเดตสต็อกเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $this->products->delete($product);

        return $this->ok(['id' => (int) $product->id], 'ลบสินค้าเรียบร้อยแล้ว');
    }

    public function addImages(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, $this->products->imageRules(), $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $files = array_values(array_filter((array) $request->file('images', []), fn ($f) => $f instanceof UploadedFile));

        try {
            $result = $this->products->addImages($product, $files);
        } catch (\Throwable $e) {
            Log::error('Seller product add images failed (app)', ['product_id' => $product->id, 'error' => $e->getMessage()]);

            return $this->fail('UPLOAD_FAILED', 'อัปโหลดรูปไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 500);
        }

        if (! $result['ok']) {
            return $this->fail($result['code'], $result['message'], 422);
        }

        return $this->ok($this->presentFresh($user, $product), 'อัปโหลดรูปสินค้าแล้ว');
    }

    public function deleteImage(Request $request, int $id, int $imageId): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $result = $this->products->deleteImage($product, $imageId);
        if (! $result['ok']) {
            return $this->fail($result['code'], $result['message'], $result['status']);
        }

        return $this->ok($this->presentFresh($user, $product), 'ลบรูปแล้ว');
    }

    public function setMainImage(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, ['image_id' => 'required|integer|min:1'], $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $result = $this->products->setMainImage($product, (int) $data['image_id']);
        if (! $result['ok']) {
            return $this->fail($result['code'], $result['message'], $result['status']);
        }

        return $this->ok($this->presentFresh($user, $product), 'ตั้งรูปหลักแล้ว');
    }

    public function reorderImages(Request $request, int $id): JsonResponse
    {
        [$user, $product, $denied] = $this->editable($request, $id);
        if ($denied) {
            return $denied;
        }

        $data = $this->validateOrFail($request, [
            'order' => 'required|array|max:'.SellerProductService::MAX_GALLERY_IMAGES,
            'order.*' => 'integer|min:1',
        ], $this->products->messages());
        if ($data instanceof JsonResponse) {
            return $data;
        }

        $result = $this->products->reorderImages($product, $data['order']);
        if (! $result['ok']) {
            return $this->fail($result['code'], $result['message'], $result['status']);
        }

        return $this->ok($this->presentFresh($user, $product), 'เรียงรูปใหม่แล้ว');
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * @return array{0: ?User, 1: ?JsonResponse}
     */
    private function seller(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $gate = $this->gate->check($user);

        return $gate['ok'] ? [$user, null] : [null, $this->denied($gate)];
    }

    /**
     * สินค้าของผู้เรียกที่แก้จากแอปได้ (ระงับ/มีตัวเลือกย่อย = 423 พร้อมเหตุผล)
     *
     * @return array{0: ?User, 1: ?Product, 2: ?JsonResponse}
     */
    private function editable(Request $request, int $id): array
    {
        [$user, $denied] = $this->seller($request);
        if ($denied) {
            return [null, null, $denied];
        }

        $product = $this->products->findOwned($user, $id);
        if (! $product) {
            return [null, null, $this->notFound()];
        }

        $readOnly = $this->products->readOnlyReason($product);
        if ($readOnly) {
            return [null, null, $this->fail($readOnly['code'], $readOnly['message'], 423, [
                'web_edit_path' => '/seller/products/'.$product->id.'/edit',
            ])];
        }

        return [$user, $product, null];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentFresh(User $user, Product $product): array
    {
        $fresh = $this->products->findOwned($user, (int) $product->id) ?? $product->fresh(['images', 'category:id,name']);

        return $this->products->present($fresh, true);
    }

    /**
     * Idempotency-Key จาก header (รับเฉพาะตัวอักษร/ตัวเลข/ขีด ยาวไม่เกิน 100)
     */
    private function idempotencyKey(Request $request): ?string
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));

        return $key !== '' && preg_match('/^[A-Za-z0-9\-_]{8,100}$/', $key) ? $key : null;
    }

    /**
     * สินค้าที่เคยสร้างด้วย key นี้ (ยังเป็นของผู้ใช้และยังไม่ถูกลบ) → ตอบผลเดิม
     */
    private function replay(User $user, string $cacheKey): ?JsonResponse
    {
        $existingId = Cache::get($cacheKey);
        if (! $existingId) {
            return null;
        }

        $existing = $this->products->findOwned($user, (int) $existingId);

        return $existing
            ? $this->ok($this->products->present($existing, true), 'เพิ่มสินค้าเรียบร้อยแล้ว', 200, ['replayed' => true])
            : null;
    }

    private function notFound(): JsonResponse
    {
        return $this->fail('PRODUCT_NOT_FOUND', 'ไม่พบสินค้านี้ หรือคุณไม่มีสิทธิ์แก้ไข', 404);
    }
}
