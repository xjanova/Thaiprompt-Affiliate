<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FreshMarketListing;
use App\Models\FreshMarketListingOption;
use App\Models\FreshMarketListingOptionGroup;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Services\FreshMarketOptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ร้านจัดการสินค้าตลาดสดของตัวเอง (API แอปฝั่งร้าน)
 *
 * - รายการสินค้าของร้าน / รายละเอียด / ลบ / รูปสินค้า
 * - กลุ่มตัวเลือก + ตัวเลือก (สร้าง/แก้/ลบ/รูป) — ส่งทั้งชุด (sync) หรือทีละรายการก็ได้
 *
 * ทุกคำสั่งที่แก้ตัวเลือกตอบ {listing_id, option_groups[]} ชุดล่าสุดทั้งหมดของสินค้านั้น
 */
class FreshMarketSellerListingApiController extends FreshMarketApiController
{
    protected function optionService(): FreshMarketOptionService
    {
        return app(FreshMarketOptionService::class);
    }

    // ===== สินค้าของร้าน =====

    /**
     * GET /api/v1/fresh-market/seller/listings?status=&q=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403);
        }

        $data = $this->validateRequest($request, [
            'status' => 'nullable|in:active,sold_out,draft,expired,suspended',
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $listings = FreshMarketListing::where('seller_id', $seller->id)
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['q'] ?? null, fn ($q, $keyword) => $q->search($keyword))
            ->with(['category:id,name,icon', 'optionGroups.options'])
            ->latest()
            ->paginate((int) ($data['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'message' => 'ดึงรายการสินค้าของร้านสำเร็จ',
            'data' => collect($listings->items())->map(fn (FreshMarketListing $l) => $this->ownerListing($l))->values(),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/fresh-market/seller/listings/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        $listing->load(['category:id,name,icon', 'optionGroups.options']);

        return $this->ok($this->ownerListing($listing));
    }

    /**
     * DELETE /api/v1/fresh-market/listings/{id} — ลบสินค้า (ไม่ได้ถ้ายังมีออเดอร์ที่ค้างอยู่)
     */
    public function destroyListing(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        if ($this->hasActiveOrders($listing)) {
            return $this->error('LISTING_HAS_ACTIVE_ORDERS', 'สินค้านี้มีออเดอร์ที่ยังไม่เสร็จ กรุณาจัดการออเดอร์ให้เสร็จก่อนลบ', 409);
        }

        $listing->delete();
        $seller->refreshStats();

        return $this->ok(['id' => (int) $listing->id], 'ลบสินค้าสำเร็จ');
    }

    /**
     * POST /api/v1/fresh-market/listings/{id}/images (multipart)
     * body: images[] (รูป ≤5MB, รวมแล้วไม่เกิน 5 รูป), replace? (true = แทนรูปเดิมทั้งหมด)
     */
    public function addImages(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        $data = $this->validateRequest($request, [
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|max:5120',
            'replace' => 'nullable|boolean',
        ], [
            'images.required' => 'กรุณาเลือกรูปสินค้า',
            'images.*.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'images.*.max' => 'รูปต้องไม่เกิน 5MB',
        ]);

        $previous = array_values(array_filter(array_merge((array) ($listing->images ?? []), [$listing->main_image_url])));
        $current = $request->boolean('replace') ? [] : array_values(array_filter((array) ($listing->images ?? [])));

        if (count($current) + count($data['images']) > 5) {
            return $this->error('TOO_MANY_IMAGES', 'รูปสินค้ารวมกันได้ไม่เกิน 5 รูป', 422);
        }

        foreach ($request->file('images', []) as $image) {
            $current[] = '/storage/'.$image->store('fresh-market', 'public');
        }

        $main = $listing->main_image_url && in_array($listing->main_image_url, $current, true)
            ? $listing->main_image_url
            : ($current[0] ?? null);

        $listing->update(['images' => $current, 'main_image_url' => $main]);

        // แทนรูปทั้งชุด → ลบไฟล์รูปเดิมที่ไม่มีสินค้า/ตัวเลือก/ออเดอร์ไหนใช้แล้ว (กันไฟล์ค้างบน disk)
        foreach (array_diff($previous, $current) as $url) {
            $this->optionService()->deleteListingImageIfUnused($url);
        }

        return $this->ok($this->ownerListing($listing->fresh(['category:id,name,icon', 'optionGroups.options'])), 'อัปโหลดรูปสินค้าสำเร็จ');
    }

    /**
     * DELETE /api/v1/fresh-market/listings/{id}/images  body: url (รูปที่จะลบ), หรือ POST .../images/main {url} ตั้งรูปหลัก
     */
    public function removeImage(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        $data = $this->validateRequest($request, ['url' => 'required|string|max:255']);
        $ownedImage = in_array($data['url'], (array) ($listing->images ?? []), true) || $listing->main_image_url === $data['url'];
        $images = array_values(array_filter((array) ($listing->images ?? []), fn ($u) => $u !== $data['url']));

        $main = $listing->main_image_url === $data['url'] ? ($images[0] ?? null) : $listing->main_image_url;
        $listing->update(['images' => $images, 'main_image_url' => $main]);

        // ลบไฟล์เฉพาะรูปของสินค้านี้ และเฉพาะเมื่อไม่มีสินค้า/ตัวเลือก/ออเดอร์ไหนใช้แล้ว (รูปในออเดอร์เก่ายังอยู่)
        if ($ownedImage) {
            $this->optionService()->deleteListingImageIfUnused($data['url']);
        }

        return $this->ok($this->ownerListing($listing->fresh(['category:id,name,icon', 'optionGroups.options'])), 'ลบรูปแล้ว');
    }

    /**
     * POST /api/v1/fresh-market/listings/{id}/images/main  body: url (ต้องเป็นหนึ่งในรูปของสินค้า)
     */
    public function setMainImage(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        $data = $this->validateRequest($request, ['url' => 'required|string|max:255']);

        if (! in_array($data['url'], (array) ($listing->images ?? []), true)) {
            return $this->error('IMAGE_NOT_FOUND', 'ไม่พบรูปนี้ในสินค้า', 404);
        }

        $listing->update(['main_image_url' => $data['url']]);

        return $this->ok($this->ownerListing($listing->fresh(['category:id,name,icon', 'optionGroups.options'])), 'ตั้งรูปหลักแล้ว');
    }

    // ===== กลุ่มตัวเลือก =====

    /**
     * GET /api/v1/fresh-market/listings/{id}/option-groups
     */
    public function optionGroups(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        return $this->ok($this->groupsPayload($listing));
    }

    /**
     * PUT /api/v1/fresh-market/listings/{id}/option-groups (JSON)
     * POST /api/v1/fresh-market/listings/{id}/option-groups/sync (multipart — แนบรูป option_groups[i][options][j][image])
     * body: option_groups[] ทั้งชุด (มี id = แก้, ไม่มี id = สร้าง, ไม่ได้ส่ง = ลบ) · [] = ลบทั้งหมด
     */
    public function syncOptionGroups(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        FreshMarketOptionService::normalizeGroupsInput($request);

        if (! $request->has('option_groups')) {
            $request->merge(['option_groups' => []]);
        }

        $data = $this->validateRequest(
            $request,
            FreshMarketOptionService::groupsRules(),
            FreshMarketOptionService::validationMessages()
        );

        return $this->handle(function () use ($request, $listing, $data) {
            $this->optionService()->syncGroups($listing, $data['option_groups'] ?? [], $request);

            return $this->ok($this->groupsPayload($listing), 'บันทึกตัวเลือกสินค้าแล้ว');
        });
    }

    /**
     * POST /api/v1/fresh-market/listings/{id}/option-groups — สร้างกลุ่มเดียว
     * body: name, selection_type (single|multi), is_required, min_select?, max_select?, sort_order?, options[] {name, price_delta, is_available?, sort_order?, image? (ไฟล์), image_url?}
     */
    public function storeOptionGroup(Request $request, int $id): JsonResponse
    {
        [$seller, $listing, $error] = $this->ownedListing($request, $id);

        if ($error) {
            return $error;
        }

        $this->decodeJsonField($request, 'options');

        $data = $this->validateRequest(
            $request,
            array_merge(FreshMarketOptionService::singleGroupRules(), ['options' => 'required|array|min:1|max:'.FreshMarketOptionService::MAX_OPTIONS_PER_GROUP]),
            FreshMarketOptionService::validationMessages()
        );

        return $this->handle(function () use ($request, $listing, $data) {
            $this->optionService()->saveGroup($listing, $data, null, $request);

            return $this->ok($this->groupsPayload($listing), 'เพิ่มกลุ่มตัวเลือกแล้ว', 201);
        });
    }

    /**
     * PUT /api/v1/fresh-market/option-groups/{groupId}
     * body: name?, selection_type?, is_required?, min_select?, max_select?, sort_order?, options[]? (ส่ง = แทนที่ตัวเลือกทั้งกลุ่ม)
     */
    public function updateOptionGroup(Request $request, int $groupId): JsonResponse
    {
        [$group, $listing, $error] = $this->ownedGroup($request, $groupId);

        if ($error) {
            return $error;
        }

        $this->decodeJsonField($request, 'options');

        $data = $this->validateRequest(
            $request,
            FreshMarketOptionService::singleGroupRules(true),
            FreshMarketOptionService::validationMessages()
        );

        return $this->handle(function () use ($request, $listing, $group, $data) {
            $this->optionService()->saveGroup($listing, $data, $group, $request);

            return $this->ok($this->groupsPayload($listing), 'บันทึกกลุ่มตัวเลือกแล้ว');
        });
    }

    /**
     * DELETE /api/v1/fresh-market/option-groups/{groupId}
     */
    public function destroyOptionGroup(Request $request, int $groupId): JsonResponse
    {
        [$group, $listing, $error] = $this->ownedGroup($request, $groupId);

        if ($error) {
            return $error;
        }

        $imageUrls = FreshMarketListingOption::where('group_id', $group->id)->pluck('image_url')->filter()->all();
        $group->delete();

        // ลบไฟล์เฉพาะของสินค้านี้ที่ไม่มีตัวเลือก/ออเดอร์ไหนใช้แล้ว
        foreach ($imageUrls as $url) {
            $this->optionService()->deleteStoredImage($url, (int) $listing->id);
        }

        return $this->ok($this->groupsPayload($listing), 'ลบกลุ่มตัวเลือกแล้ว');
    }

    // ===== ตัวเลือก =====

    /**
     * POST /api/v1/fresh-market/option-groups/{groupId}/options (multipart ได้)
     * body: name, price_delta?, is_available?, sort_order?, image? (ไฟล์), image_url?
     */
    public function storeOption(Request $request, int $groupId): JsonResponse
    {
        [$group, $listing, $error] = $this->ownedGroup($request, $groupId);

        if ($error) {
            return $error;
        }

        $data = $this->validateRequest($request, FreshMarketOptionService::optionRules(), FreshMarketOptionService::validationMessages());

        return $this->handle(function () use ($request, $listing, $group, $data) {
            $this->optionService()->saveOption($group, $data, null, $request->file('image'), (int) ($data['sort_order'] ?? 0));

            return $this->ok($this->groupsPayload($listing), 'เพิ่มตัวเลือกแล้ว', 201);
        });
    }

    /**
     * PUT /api/v1/fresh-market/options/{optionId}
     * body: name?, price_delta?, is_available?, sort_order?, image_url?, remove_image?
     */
    public function updateOption(Request $request, int $optionId): JsonResponse
    {
        [$option, $group, $listing, $error] = $this->ownedOption($request, $optionId);

        if ($error) {
            return $error;
        }

        $data = $this->validateRequest($request, FreshMarketOptionService::optionRules('', true), FreshMarketOptionService::validationMessages());

        return $this->handle(function () use ($request, $listing, $group, $option, $data) {
            // เปลี่ยน/เอารูปออก → saveOption ลบไฟล์เดิมให้เอง (เฉพาะไฟล์ที่ไม่มีตัวเลือก/ออเดอร์ไหนใช้แล้ว)
            $this->optionService()->saveOption($group, $data, $option, $request->file('image'));

            return $this->ok($this->groupsPayload($listing), 'บันทึกตัวเลือกแล้ว');
        });
    }

    /**
     * POST /api/v1/fresh-market/options/{optionId}/image (multipart) body: image
     */
    public function uploadOptionImage(Request $request, int $optionId): JsonResponse
    {
        [$option, $group, $listing, $error] = $this->ownedOption($request, $optionId);

        if ($error) {
            return $error;
        }

        $this->validateRequest($request, ['image' => 'required|image|max:5120'], [
            'image.required' => 'กรุณาเลือกรูป',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'image.max' => 'รูปต้องไม่เกิน 5MB',
        ]);

        return $this->handle(function () use ($request, $listing, $group, $option) {
            // รูปเดิมถูกลบโดย saveOption เมื่อไม่มีใครใช้แล้ว (รูปในออเดอร์เก่ายังอยู่)
            $this->optionService()->saveOption($group, [], $option, $request->file('image'));

            return $this->ok($this->groupsPayload($listing), 'อัปโหลดรูปตัวเลือกแล้ว');
        });
    }

    /**
     * DELETE /api/v1/fresh-market/options/{optionId}
     */
    public function destroyOption(Request $request, int $optionId): JsonResponse
    {
        [$option, $group, $listing, $error] = $this->ownedOption($request, $optionId);

        if ($error) {
            return $error;
        }

        if (FreshMarketListingOption::where('group_id', $group->id)->count() <= 1) {
            return $this->error('LAST_OPTION', 'กลุ่มต้องมีตัวเลือกอย่างน้อย 1 รายการ (ลบทั้งกลุ่มแทนได้)', 422);
        }

        $url = $option->image_url;
        $option->delete();
        // ลบไฟล์เฉพาะของสินค้านี้ที่ไม่มีตัวเลือก/ออเดอร์ไหนใช้แล้ว (รูปในออเดอร์เก่าไม่หาย)
        $this->optionService()->deleteStoredImage($url, (int) $listing->id);

        return $this->ok($this->groupsPayload($listing), 'ลบตัวเลือกแล้ว');
    }

    // ===== Helpers =====

    /**
     * ข้อมูลสินค้าสำหรับเจ้าของร้าน (รวมสถานะ + กลุ่มตัวเลือกทั้งหมด รวมที่ปิดขาย)
     */
    protected function ownerListing(FreshMarketListing $listing): array
    {
        return array_merge($this->listingSummary($listing), [
            'status' => $listing->status,
            'is_available' => (bool) $listing->is_available,
            'category_id' => $listing->category_id !== null ? (int) $listing->category_id : null,
            'tags' => $listing->tags ?? [],
            'cashback_percentage' => (float) $listing->cashback_percentage,
            'view_count' => (int) $listing->view_count,
            'order_count' => (int) $listing->order_count,
            'option_groups' => $this->optionService()->groupsForApi($listing, true),
            'updated_at' => $listing->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * {listing_id, option_groups[]} ชุดล่าสุด
     */
    protected function groupsPayload(FreshMarketListing $listing): array
    {
        $listing->unsetRelation('optionGroups');

        return [
            'listing_id' => (int) $listing->id,
            'option_groups' => $this->optionService()->groupsForApi($listing, true),
        ];
    }

    /**
     * สินค้าของร้านผู้ใช้ปัจจุบัน
     *
     * @return array{0: ?FreshMarketSeller, 1: ?FreshMarketListing, 2: ?JsonResponse}
     */
    protected function ownedListing(Request $request, int $id): array
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return [null, null, $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403)];
        }

        $listing = FreshMarketListing::where('id', $id)->where('seller_id', $seller->id)->first();

        if (! $listing) {
            return [$seller, null, $this->error('LISTING_NOT_FOUND', 'ไม่พบสินค้า หรือคุณไม่มีสิทธิ์แก้ไข', 404)];
        }

        return [$seller, $listing, null];
    }

    /**
     * กลุ่มตัวเลือกที่เป็นของสินค้าในร้านผู้ใช้ปัจจุบัน
     *
     * @return array{0: ?FreshMarketListingOptionGroup, 1: ?FreshMarketListing, 2: ?JsonResponse}
     */
    protected function ownedGroup(Request $request, int $groupId): array
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return [null, null, $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403)];
        }

        $group = FreshMarketListingOptionGroup::find($groupId);
        $listing = $group ? FreshMarketListing::where('id', $group->listing_id)->where('seller_id', $seller->id)->first() : null;

        if (! $group || ! $listing) {
            return [null, null, $this->error('OPTION_GROUP_NOT_FOUND', 'ไม่พบกลุ่มตัวเลือก หรือคุณไม่มีสิทธิ์แก้ไข', 404)];
        }

        return [$group, $listing, null];
    }

    /**
     * ตัวเลือกที่เป็นของสินค้าในร้านผู้ใช้ปัจจุบัน
     *
     * @return array{0: ?FreshMarketListingOption, 1: ?FreshMarketListingOptionGroup, 2: ?FreshMarketListing, 3: ?JsonResponse}
     */
    protected function ownedOption(Request $request, int $optionId): array
    {
        $seller = $this->sellerOf($request);

        if (! $seller) {
            return [null, null, null, $this->error('NOT_SELLER', 'คุณยังไม่ได้สมัครเป็นผู้ขาย', 403)];
        }

        $option = FreshMarketListingOption::with('group')->find($optionId);
        $listing = $option ? FreshMarketListing::where('id', $option->listing_id)->where('seller_id', $seller->id)->first() : null;

        if (! $option || ! $option->group || ! $listing) {
            return [null, null, null, $this->error('OPTION_NOT_FOUND', 'ไม่พบตัวเลือก หรือคุณไม่มีสิทธิ์แก้ไข', 404)];
        }

        return [$option, $option->group, $listing, null];
    }

    /**
     * สินค้ายังมีออเดอร์ค้าง (รวมออเดอร์หลายรายการ)
     */
    protected function hasActiveOrders(FreshMarketListing $listing): bool
    {
        return FreshMarketOrder::active()
            ->where(function ($q) use ($listing) {
                $q->where('listing_id', $listing->id)
                    ->orWhereIn('id', DB::table('fresh_market_order_items')->select('order_id')->where('listing_id', $listing->id));
            })
            ->exists();
    }

    /**
     * ช่องที่แอปส่งเป็น JSON string ใน multipart → array
     */
    protected function decodeJsonField(Request $request, string $key): void
    {
        $value = $request->input($key);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $request->merge([$key => is_array($decoded) ? $decoded : []]);
        }
    }
}
