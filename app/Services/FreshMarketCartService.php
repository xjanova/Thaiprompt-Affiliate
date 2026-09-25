<?php

namespace App\Services;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketCartItem;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FreshMarketCartService - ตะกร้าตลาดสด (ฝั่งเซิร์ฟเวอร์ = ข้อมูลหลักของแอปและเว็บ)
 *
 * - ตะกร้าแยกตามร้าน: ผู้ซื้อหยิบของได้หลายร้าน แต่ชำระทีละร้าน (1 ร้าน = 1 ออเดอร์)
 * - ไม่เก็บราคาในตะกร้า: ทุกครั้งที่ดูตะกร้าคำนวณราคาใหม่จากราคาสินค้า/ตัวเลือกปัจจุบัน
 * - รายการที่มีปัญหา (สินค้าปิด, ตัวเลือกหมด, ของไม่พอ) ยังแสดงอยู่พร้อม issue — ไม่นับรวมยอด และชำระไม่ได้จนกว่าจะแก้
 */
class FreshMarketCartService
{
    /** จำนวนบรรทัดสูงสุดในตะกร้าทั้งหมด */
    public const MAX_LINES = 100;

    public function __construct(
        protected FreshMarketOptionService $options,
        protected FreshMarketService $market,
    ) {}

    // ╔══════════════════════════════════════════╗
    // ║  แก้ตะกร้า                                ║
    // ╚══════════════════════════════════════════╝

    /**
     * หยิบสินค้าใส่ตะกร้า (สินค้าเดิม + ตัวเลือกชุดเดิม + โน้ตเดิม = เพิ่มจำนวนในบรรทัดเดิม)
     *
     * @throws FreshMarketException LISTING_NOT_FOUND | LISTING_UNAVAILABLE | SELF_PURCHASE | OUT_OF_STOCK | CART_FULL | OPTION_*
     */
    public function addItem(User $user, int $listingId, int $quantity, mixed $optionIds = [], ?string $note = null): FreshMarketCartItem
    {
        $listing = $this->purchasableListing($user, $listingId);
        $line = $this->options->resolveLine($listing, $optionIds, $quantity, $note);
        $key = FreshMarketCartItem::makeLineKey((int) $listing->id, $line['option_ids'], $line['note']);

        return DB::transaction(function () use ($user, $listing, $line, $key, $quantity) {
            // lock ตะกร้าของผู้ใช้ (กดเพิ่มรัวๆ พร้อมกัน → ไม่เกิดบรรทัดซ้ำ)
            $lines = FreshMarketCartItem::where('user_id', $user->id)->lockForUpdate()->get();
            $existing = $lines->firstWhere('line_key', $key);

            $newQty = ($existing ? (int) $existing->quantity : 0) + $quantity;

            if ($newQty > 999) {
                throw FreshMarketException::make('INVALID_QUANTITY', 'จำนวนต่อรายการสูงสุด 999', 422);
            }

            // จำนวนรวมของสินค้านี้ทุกบรรทัดในตะกร้าต้องไม่เกินสต็อก (สินค้าที่ตัดสต็อก)
            $this->assertStock($listing, $lines->where('listing_id', $listing->id)->sum('quantity') + $quantity);

            if ($existing) {
                $existing->quantity = $newQty;
                $existing->save();

                return $existing;
            }

            if ($lines->count() >= self::MAX_LINES) {
                throw FreshMarketException::make('CART_FULL', 'ตะกร้าเต็มแล้ว (สูงสุด '.self::MAX_LINES.' รายการ) กรุณาชำระหรือลบบางรายการก่อน', 422);
            }

            return FreshMarketCartItem::create([
                'user_id' => $user->id,
                'seller_id' => $listing->seller_id,
                'listing_id' => $listing->id,
                'quantity' => $quantity,
                'option_ids' => $line['option_ids'],
                'line_key' => $key,
                'note' => $line['note'],
            ]);
        });
    }

    /**
     * แก้บรรทัดในตะกร้า (จำนวน / ตัวเลือก / โน้ต) — จำนวน 0 = ลบบรรทัด
     *
     * @param  array  $data  quantity?, option_ids?, note?
     * @return FreshMarketCartItem|null null = ถูกลบ
     *
     * @throws FreshMarketException CART_ITEM_NOT_FOUND | OPTION_* | OUT_OF_STOCK
     */
    public function updateItem(User $user, int $itemId, array $data): ?FreshMarketCartItem
    {
        $item = FreshMarketCartItem::where('user_id', $user->id)->find($itemId);

        if (! $item) {
            throw FreshMarketException::make('CART_ITEM_NOT_FOUND', 'ไม่พบรายการนี้ในตะกร้า', 404);
        }

        $quantity = array_key_exists('quantity', $data) && $data['quantity'] !== null ? (int) $data['quantity'] : (int) $item->quantity;

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $optionIds = array_key_exists('option_ids', $data) ? ($data['option_ids'] ?? []) : $item->normalizedOptionIds();
        $note = array_key_exists('note', $data) ? $data['note'] : $item->note;

        $listing = $this->purchasableListing($user, (int) $item->listing_id);
        $line = $this->options->resolveLine($listing, $optionIds, $quantity, $note !== null ? (string) $note : null);
        $key = FreshMarketCartItem::makeLineKey((int) $listing->id, $line['option_ids'], $line['note']);

        return DB::transaction(function () use ($user, $item, $listing, $line, $key, $quantity) {
            $lines = FreshMarketCartItem::where('user_id', $user->id)->lockForUpdate()->get();
            $current = $lines->firstWhere('id', $item->id);

            if (! $current) {
                throw FreshMarketException::make('CART_ITEM_NOT_FOUND', 'ไม่พบรายการนี้ในตะกร้า', 404);
            }

            $otherQty = $lines->where('listing_id', $listing->id)->where('id', '!=', $current->id)->sum('quantity');
            $this->assertStock($listing, $otherQty + $quantity);

            // เปลี่ยนตัวเลือกแล้วไปซ้ำกับอีกบรรทัด → รวมเป็นบรรทัดเดียว
            $twin = $lines->first(fn ($l) => $l->line_key === $key && (int) $l->id !== (int) $current->id);

            if ($twin) {
                $merged = (int) $twin->quantity + $quantity;

                if ($merged > 999) {
                    throw FreshMarketException::make('INVALID_QUANTITY', 'จำนวนต่อรายการสูงสุด 999', 422);
                }

                $twin->quantity = $merged;
                $twin->save();
                $current->delete();

                return $twin;
            }

            $current->fill([
                'quantity' => $quantity,
                'option_ids' => $line['option_ids'],
                'line_key' => $key,
                'note' => $line['note'],
            ])->save();

            return $current;
        });
    }

    /**
     * ลบบรรทัดในตะกร้า
     *
     * @throws FreshMarketException CART_ITEM_NOT_FOUND
     */
    public function removeItem(User $user, int $itemId): void
    {
        $deleted = FreshMarketCartItem::where('user_id', $user->id)->whereKey($itemId)->delete();

        if ($deleted === 0) {
            throw FreshMarketException::make('CART_ITEM_NOT_FOUND', 'ไม่พบรายการนี้ในตะกร้า', 404);
        }
    }

    /**
     * ล้างตะกร้า (ระบุร้าน = ล้างเฉพาะร้านนั้น)
     *
     * @return int จำนวนบรรทัดที่ลบ
     */
    public function clear(User $user, ?int $sellerId = null): int
    {
        return FreshMarketCartItem::where('user_id', $user->id)
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->delete();
    }

    // ╔══════════════════════════════════════════╗
    // ║  อ่านตะกร้า                               ║
    // ╚══════════════════════════════════════════╝

    /**
     * ตะกร้าทั้งใบ (จัดกลุ่มตามร้าน) พร้อมราคาที่คำนวณใหม่
     *
     * @return array{shops: array, shops_count: int, lines_count: int, items_count: int, subtotal: float, has_issues: bool}
     */
    public function cartFor(User $user, ?int $sellerId = null): array
    {
        $items = FreshMarketCartItem::where('user_id', $user->id)
            ->when($sellerId, fn ($q) => $q->where('seller_id', $sellerId))
            ->with(['listing.optionGroups.options', 'seller'])
            ->orderBy('id')
            ->get();

        $shops = $items->groupBy('seller_id')
            ->map(fn (Collection $rows) => $this->shopPayload($rows))
            ->values()
            ->all();

        return [
            'shops' => $shops,
            'shops_count' => count($shops),
            'lines_count' => (int) array_sum(array_column($shops, 'lines_count')),
            'items_count' => (int) array_sum(array_column($shops, 'items_count')),
            'subtotal' => round((float) array_sum(array_column($shops, 'subtotal')), 2),
            'has_issues' => collect($shops)->contains(fn ($s) => $s['issues_count'] > 0),
        ];
    }

    /**
     * ตะกร้าของร้านเดียว (null = ไม่มีของของร้านนี้ในตะกร้า)
     */
    public function shopCart(User $user, int $sellerId): ?array
    {
        return $this->cartFor($user, $sellerId)['shops'][0] ?? null;
    }

    /**
     * คำนวณค่าส่งไรเดอร์ของตะกร้าร้านเดียว (ใช้แสดงก่อนกดยืนยัน)
     *
     * @return array{available: bool, code: ?string, message: ?string, distance_km: ?float, total_fee: float, estimated_duration_minutes: ?int, max_distance_km: float, subtotal: float, grand_total: float, items_count: int}
     *
     * @throws FreshMarketException CART_EMPTY
     */
    public function quote(User $user, int $sellerId, float $lat, float $lng): array
    {
        $shop = $this->shopCart($user, $sellerId);

        if (! $shop || $shop['lines_count'] === 0) {
            throw FreshMarketException::make('CART_EMPTY', 'ยังไม่มีสินค้าของร้านนี้ในตะกร้า', 422);
        }

        $listing = FreshMarketListing::with('seller')->find($shop['items'][0]['listing_id']);

        if (! $listing) {
            throw FreshMarketException::make('LISTING_UNAVAILABLE', 'สินค้าในตะกร้าไม่พร้อมขายแล้ว', 409);
        }

        $quote = $this->market->quoteDelivery($listing, $lat, $lng);
        $subtotal = (float) $shop['subtotal'];

        return array_merge($quote, [
            'subtotal' => $subtotal,
            'grand_total' => round($subtotal + ($quote['available'] ? (float) $quote['total_fee'] : 0), 2),
            'items_count' => (int) $shop['items_count'],
        ]);
    }

    /**
     * ชำระตะกร้าของร้านเดียว → ออเดอร์เดียว (ล้างบรรทัดที่สั่งในธุรกรรมเดียวกัน)
     *
     * @param  array  $data  delivery_type, payment_method, buyer_latitude, buyer_longitude, delivery_address, delivery_notes, channel
     *
     * @throws FreshMarketException CART_EMPTY + ข้อผิดพลาดของ createOrderFromItems
     */
    public function checkout(User $user, int $sellerId, array $data): FreshMarketOrder
    {
        $rows = FreshMarketCartItem::where('user_id', $user->id)
            ->where('seller_id', $sellerId)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            throw FreshMarketException::make('CART_EMPTY', 'ยังไม่มีสินค้าของร้านนี้ในตะกร้า', 422);
        }

        $items = $rows->map(fn (FreshMarketCartItem $row) => [
            'listing_id' => (int) $row->listing_id,
            'quantity' => (int) $row->quantity,
            'option_ids' => $row->normalizedOptionIds(),
            'note' => $row->note,
        ])->all();

        unset($data['items'], $data['apply_default_options']);

        return $this->market->createOrderFromItems($user, $sellerId, $items, array_merge($data, [
            'cart_item_ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]));
    }

    // ╔══════════════════════════════════════════╗
    // ║  Helpers                                 ║
    // ╚══════════════════════════════════════════╝

    /**
     * สินค้าที่หยิบใส่ตะกร้าได้ (ผู้ซื้อเห็นได้ + เปิดขาย + ไม่ใช่ของร้านตัวเอง)
     */
    protected function purchasableListing(User $user, int $listingId): FreshMarketListing
    {
        $listing = FreshMarketListing::with(['seller', 'optionGroups.options'])->find($listingId);

        if (! $listing || ! $listing->sellerIsVisible()) {
            throw FreshMarketException::make('LISTING_NOT_FOUND', 'ไม่พบสินค้า', 404);
        }

        if (! $listing->isAvailableForPurchase()) {
            throw FreshMarketException::make('LISTING_UNAVAILABLE', 'สินค้า "'.$listing->title.'" ไม่พร้อมขายในขณะนี้', 409);
        }

        if ((int) $listing->seller?->user_id === (int) $user->id) {
            throw FreshMarketException::make('SELF_PURCHASE', 'ไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้', 403);
        }

        return $listing;
    }

    /**
     * จำนวนรวมที่อยู่ในตะกร้าต้องไม่เกินสต็อก (เฉพาะสินค้าที่ตัดสต็อก)
     */
    protected function assertStock(FreshMarketListing $listing, int $totalQty): void
    {
        if ($listing->tracksStock() && $totalQty > (int) $listing->quantity_available) {
            throw FreshMarketException::make(
                'OUT_OF_STOCK',
                'สินค้า "'.$listing->title.'" เหลือ '.(int) $listing->quantity_available.' '.$listing->unit,
                409
            );
        }
    }

    /**
     * ข้อมูลตะกร้าของร้านเดียว
     */
    protected function shopPayload(Collection $rows): array
    {
        /** @var FreshMarketSeller|null $seller */
        $seller = $rows->first()->seller;
        $shopVisible = $seller && $seller->isVisibleToBuyers();

        // จำนวนรวมต่อสินค้า (ตรวจสต็อกรวมทุกบรรทัดของสินค้าเดียวกัน)
        $qtyByListing = $rows->groupBy('listing_id')->map(fn ($g) => (int) $g->sum('quantity'));

        $lines = $rows->map(function (FreshMarketCartItem $row) use ($shopVisible, $qtyByListing) {
            return $this->linePayload($row, $shopVisible, (int) ($qtyByListing[$row->listing_id] ?? 0));
        })->values();

        $valid = $lines->where('is_valid', true);
        $issues = $lines->where('is_valid', false)->count();

        // ร้านปิดอยู่ (รถเข็น/ตลาดนัดยังไม่เปิดวันนี้ / กดปิดร้าน) → เก็บของไว้ในตะกร้าได้ แต่ชำระไม่ได้จนกว่าร้านเปิด
        $shopOpen = $seller && $seller->isOpenNow();

        return [
            'seller' => $seller ? [
                'id' => (int) $seller->id,
                'shop_name' => $seller->shop_name,
                'shop_image' => $seller->shop_image,
                'rating_average' => (float) $seller->rating_average,
                'is_available' => $shopVisible,
                'is_mobile' => $seller->isMobileShop(),
                'is_open' => $shopOpen,
                'closed_message' => $shopOpen ? null : FreshMarketSeller::CLOSED_MESSAGE,
            ] : null,
            'seller_id' => (int) $rows->first()->seller_id,
            'items' => $lines->all(),
            'lines_count' => $lines->count(),
            'items_count' => (int) $lines->sum('quantity'),
            'subtotal' => round((float) $valid->sum('line_total'), 2),
            'issues_count' => $issues,
            'is_open' => $shopOpen,
            'can_checkout' => $shopVisible && $shopOpen && $issues === 0 && $lines->isNotEmpty(),
        ];
    }

    /**
     * ข้อมูลหนึ่งบรรทัดในตะกร้า (ราคาปัจจุบัน + ปัญหาถ้ามี)
     */
    protected function linePayload(FreshMarketCartItem $row, bool $shopVisible, int $listingQtyInCart): array
    {
        $listing = $row->listing;
        $issue = null;
        $priced = null;

        if (! $listing) {
            $issue = ['code' => 'LISTING_UNAVAILABLE', 'message' => 'สินค้านี้ถูกลบแล้ว กรุณาลบออกจากตะกร้า'];
        } elseif (! $shopVisible) {
            $issue = ['code' => 'SHOP_UNAVAILABLE', 'message' => 'ร้านนี้ปิดรับออเดอร์ชั่วคราว'];
        } elseif (! $listing->isAvailableForPurchase()) {
            $issue = ['code' => 'LISTING_UNAVAILABLE', 'message' => 'สินค้านี้ไม่พร้อมขายในขณะนี้'];
        } elseif ($listing->tracksStock() && $listingQtyInCart > (int) $listing->quantity_available) {
            $issue = [
                'code' => 'OUT_OF_STOCK',
                'message' => 'สินค้าเหลือ '.(int) $listing->quantity_available.' '.$listing->unit.' กรุณาลดจำนวน',
            ];
        }

        if ($listing) {
            try {
                $priced = $this->options->resolveLine($listing, $row->normalizedOptionIds(), max(1, min(999, (int) $row->quantity)), $row->note);
            } catch (FreshMarketException $e) {
                $issue ??= ['code' => $e->errorCode(), 'message' => $e->getMessage()];
            }
        }

        $basePrice = $listing ? round((float) $listing->price, 2) : 0.0;
        $unitPrice = $priced['unit_price'] ?? $basePrice;
        $quantity = (int) $row->quantity;

        return [
            'id' => (int) $row->id,
            'seller_id' => (int) $row->seller_id,
            'listing_id' => (int) $row->listing_id,
            'slug' => $listing?->slug,
            'title' => $listing?->title ?? 'สินค้าที่ถูกลบ',
            'unit' => $listing?->unit,
            'image_url' => $priced['image_url'] ?? $listing?->primary_image,
            'quantity' => $quantity,
            'option_ids' => $priced['option_ids'] ?? $row->normalizedOptionIds(),
            'selected_options' => $priced['selected_options'] ?? [],
            'options_label' => implode(', ', array_column($priced['selected_options'] ?? [], 'name')),
            'note' => $row->note,
            'base_price' => $basePrice,
            'options_price' => $priced['options_price'] ?? 0.0,
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
            'track_stock' => $listing ? $listing->tracksStock() : true,
            'max_order_quantity' => $listing ? (int) $listing->max_order_quantity : 0,
            'is_valid' => $issue === null,
            'issue' => $issue,
        ];
    }
}
