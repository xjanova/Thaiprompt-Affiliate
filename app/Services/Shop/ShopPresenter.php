<?php

namespace App\Services\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\RiderJob;
use App\Models\VendorStore;
use App\Support\Shop\PaymentMethod;
use Illuminate\Support\Str;

/**
 * รูปแบบ JSON ของระบบร้านค้าสำหรับแอปมือถือ (ตัวเดียวที่ API ร้านค้าใช้)
 *
 * กติกา:
 * - ตัวเลขเป็น number จริง (float/int) ไม่ใช่ string ทศนิยม
 * - 🚫 ไม่ส่ง PV / อัตราคอมมิชชั่น / ค่าคอมโดยประมาณ ให้แอป (SHOP-16 นโยบาย Google Play)
 * - 🚫 ไม่มีข้อมูลสมมติ (เรตติ้ง/รีวิว/อัตราตอบกลับปลอม) — ไม่มีข้อมูลจริงให้เป็น 0/null (SHOP-17)
 */
class ShopPresenter
{
    /**
     * แปลง path รูปเป็น URL เต็ม
     */
    public static function imageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return url($path);
        }

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'images/')) {
            return url('/'.$path);
        }

        return url('/storage/'.ltrim($path, '/'));
    }

    /**
     * สินค้า (รายการ) — shape เดียวกับ /mobile/premium-store/products เดิม (ตัด PV/คอมมิชชั่นออก)
     *
     * @return array<string, mixed>
     */
    public static function product(Product $product, ?VendorStore $store = null): array
    {
        $price = round((float) $product->price, 2);
        $compare = $product->compare_at_price !== null ? round((float) $product->compare_at_price, 2) : null;
        $onSale = $compare !== null && $compare > $price;
        $stock = $product->track_inventory ? max(0, (int) $product->stock_quantity) : null;
        $inStock = $product->isInStock();
        $store = $store ?? ($product->relationLoaded('store') ? $product->getRelation('store') : null);

        $images = self::productImages($product);

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'description' => $product->short_description ?: Str::limit(strip_tags((string) $product->description), 160),
            'image' => $images[0] ?? null,
            'images' => $images,
            'price' => $price,
            'original_price' => $onSale ? $compare : null,
            // ค่าเดิมที่แอปอ่าน: ราคาขายหลังลด (มีเมื่อมีราคาก่อนลด) — คงไว้ให้แอปรุ่นเก่า
            'discount_price' => $onSale ? $price : null,
            'discount_percent' => $onSale && $compare > 0 ? (int) round(($compare - $price) / $compare * 100) : null,
            'category' => $product->relationLoaded('category') ? $product->category?->name : null,
            'category_id' => $product->category_id ? (int) $product->category_id : null,
            'categoryId' => $product->category_id ? (int) $product->category_id : null,
            'rating' => round((float) ($product->rating_average ?? 0), 2),
            'review_count' => (int) ($product->rating_count ?? 0),
            'sales_count' => (int) ($product->sales_count ?? 0),
            'is_featured' => (bool) $product->is_featured,
            'stock_status' => (string) ($product->stock_status ?? 'in_stock'),
            'stock' => $stock,
            'in_stock' => $inStock,
            'brand' => $product->brand,
            'is_virtual' => (bool) $product->is_virtual,
            'is_affiliate' => (bool) $product->is_affiliate,
            'affiliate_url' => $product->is_affiliate ? $product->affiliate_url : null,
            'external_platform' => $product->is_affiliate ? $product->external_platform : null,
            'can_add_to_cart' => ! $product->is_affiliate && $inStock,
            'seller_id' => (int) $product->seller_id,
            'store' => $store instanceof VendorStore ? self::storeBrief($store) : null,
        ];
    }

    /**
     * รูปสินค้าทั้งหมด (รูปหลักก่อน) เป็น URL เต็ม ไม่ซ้ำ
     *
     * @return array<int, string>
     */
    public static function productImages(Product $product): array
    {
        $urls = [];

        if ($product->main_image_url) {
            $urls[] = self::imageUrl($product->main_image_url);
        }

        foreach ((array) ($product->image_urls ?? []) as $path) {
            if (is_string($path)) {
                $urls[] = self::imageUrl($path);
            }
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $urls[] = self::imageUrl($image->image_url);
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    /**
     * ร้านค้าแบบย่อ (แนบในสินค้า/ออเดอร์)
     *
     * @return array<string, mixed>
     */
    public static function storeBrief(VendorStore $store): array
    {
        return [
            'id' => (int) $store->id,
            'name' => (string) $store->store_name,
            'slug' => (string) $store->store_slug,
            'logo' => self::imageUrl($store->store_logo),
            'is_verified' => (bool) $store->is_verified,
            'rider_delivery' => $store->canUseRiderDelivery(),
        ];
    }

    /**
     * ร้านค้า (หน้ารายละเอียดร้าน) — ตัวเลขจากฐานข้อมูลจริงเท่านั้น
     *
     * @return array<string, mixed>
     */
    public static function store(VendorStore $store, ?int $productCount = null): array
    {
        return [
            'id' => (string) $store->id,
            'store_id' => (int) $store->id,
            'name' => (string) $store->store_name,
            'slug' => (string) $store->store_slug,
            'description' => $store->store_description,
            'logo' => self::imageUrl($store->store_logo),
            'banner' => self::imageUrl($store->store_banner),
            'rating' => round((float) ($store->rating_average ?? 0), 2),
            'ratingCount' => (int) ($store->rating_count ?? 0),
            'rating_count' => (int) ($store->rating_count ?? 0),
            'isOfficial' => (bool) $store->is_verified,
            'is_verified' => (bool) $store->is_verified,
            'isFeatured' => (bool) $store->is_featured_home,
            'productCount' => $productCount ?? (int) ($store->total_products ?? 0),
            'product_count' => $productCount ?? (int) ($store->total_products ?? 0),
            'followerCount' => (int) ($store->followers_count ?? 0),
            'follower_count' => (int) ($store->followers_count ?? 0),
            'joinedAt' => $store->created_at?->format('Y-m-d'),
            'rider_delivery' => $store->canUseRiderDelivery(),
            'cod_available' => $store->canUseRiderDelivery(),
        ];
    }

    /**
     * ข้อมูลแบ่งหน้า — มีทั้ง snake_case (มาตรฐานใหม่) และ camelCase (แอปรุ่นเดิมอ่าน)
     *
     * @return array<string, mixed>
     */
    public static function pagination($paginator): array
    {
        return [
            'total' => (int) $paginator->total(),
            'current_page' => (int) $paginator->currentPage(),
            'last_page' => (int) $paginator->lastPage(),
            'per_page' => (int) $paginator->perPage(),
            'has_more' => (bool) $paginator->hasMorePages(),
            'currentPage' => (int) $paginator->currentPage(),
            'lastPage' => (int) $paginator->lastPage(),
            'perPage' => (int) $paginator->perPage(),
            'hasMore' => (bool) $paginator->hasMorePages(),
        ];
    }

    // =====================================================
    // ออเดอร์ (ฝั่งผู้ซื้อ)
    // =====================================================

    /**
     * ป้ายสถานะภาษาไทย
     */
    public static function statusLabel(Order $order): string
    {
        return $order->status_label;
    }

    public static function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'รอชำระเงิน',
            'paid' => 'ชำระเงินแล้ว',
            'failed' => 'ชำระเงินไม่สำเร็จ',
            'refunded' => 'คืนเงินแล้ว',
            default => (string) $status,
        };
    }

    /**
     * ที่อยู่จัดส่งจาก snapshot (รองรับทั้งคีย์ของ shipping_addresses และคีย์แบบเก่า)
     *
     * @return array<string, mixed>|null
     */
    public static function shipping(Order $order): ?array
    {
        $snap = is_array($order->shipping_address_snapshot) ? $order->shipping_address_snapshot : null;
        if (! $snap) {
            return null;
        }

        $lat = $snap['latitude'] ?? null;
        $lng = $snap['longitude'] ?? null;

        return [
            'name' => $snap['recipient_name'] ?? ($snap['name'] ?? null),
            'phone' => $snap['phone_number'] ?? ($snap['phone'] ?? null),
            'address' => $snap['address_line_1'] ?? ($snap['address'] ?? null),
            'address_line_2' => $snap['address_line_2'] ?? null,
            'subdistrict' => $snap['sub_district'] ?? ($snap['subdistrict'] ?? null),
            'district' => $snap['district'] ?? null,
            'province' => $snap['province'] ?? null,
            'postal_code' => $snap['postal_code'] ?? null,
            'full_address' => $snap['full_address'] ?? null,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
            'notes' => $snap['notes'] ?? null,
        ];
    }

    /**
     * รายการสินค้าในออเดอร์
     *
     * @return array<string, mixed>
     */
    public static function orderItem(OrderItem $item): array
    {
        return [
            'id' => (int) $item->id,
            'product_id' => (int) $item->product_id,
            'product_name' => (string) $item->product_name,
            'product_image' => self::imageUrl($item->product_image),
            'attributes' => $item->product_attributes,
            'quantity' => (int) $item->quantity,
            'price' => round((float) $item->unit_price, 2),
            'unit_price' => round((float) $item->unit_price, 2),
            'subtotal' => round((float) $item->subtotal, 2),
            'discount' => round((float) $item->discount_amount, 2),
            'total' => round((float) $item->total, 2),
            'status' => (string) $item->status,
            'can_review' => in_array($item->status, ['delivered', 'completed'], true)
                ? ($item->relationLoaded('reviews') ? $item->reviews->isEmpty() : ! $item->hasReview())
                : false,
        ];
    }

    /**
     * สรุปงานไรเดอร์ของออเดอร์ (สำหรับการ์ด "ไรเดอร์กำลังมา")
     *
     * @return array<string, mixed>|null
     */
    public static function riderSummary(Order $order): ?array
    {
        if (! $order->isRiderDelivery()) {
            return null;
        }

        /** @var RiderJob|null $job */
        $job = RiderJob::forSource($order)->latest('id')->first();
        if (! $job) {
            return ['status' => 'not_requested', 'status_label' => 'รอร้านเรียกไรเดอร์'];
        }

        $rider = $job->rider;
        $active = in_array($job->status, RiderJob::ACTIVE_STATUSES, true);

        return [
            'job_id' => (int) $job->id,
            'job_number' => $job->job_number ?? null,
            'status' => (string) $job->status,
            'status_label' => $job->status_text ?? $job->status,
            'rider' => $rider && $active ? [
                'name' => $rider->full_name ?? $rider->user?->name,
                'vehicle_type' => $rider->vehicle_type,
                'vehicle_plate' => $rider->vehicle_plate,
                'phone' => $rider->phone ?? null,
            ] : null,
            // หน้าติดตามไรเดอร์แบบสด (เว็บ /taladsod/track/{token}) — มีเฉพาะระหว่างงานยังไม่จบ
            'tracking_url' => $active ? $job->tracking_url : null,
            'delivered_at' => $job->delivered_at?->toISOString(),
        ];
    }

    /**
     * ออเดอร์ฉบับเต็มสำหรับผู้ซื้อ
     *
     * @return array<string, mixed>
     */
    public static function order(Order $order): array
    {
        $order->loadMissing(['items', 'store']);

        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'checkout_group' => $order->checkout_group,
            'status' => (string) $order->status,
            'status_label' => $order->status_label,
            'payment_status' => (string) $order->payment_status,
            'payment_status_label' => self::paymentStatusLabel($order->payment_status),
            'payment_method' => $order->payment_method,
            'payment_method_label' => PaymentMethod::labelTh($order->payment_method),
            'delivery_method' => $order->delivery_method ?? Order::DELIVERY_PARCEL,
            'store' => $order->store ? self::storeBrief($order->store) : null,
            'subtotal' => round((float) $order->subtotal, 2),
            'shipping_fee' => round((float) $order->shipping_fee, 2),
            'discount' => round((float) $order->discount_amount, 2),
            'total_amount' => round((float) $order->total_amount, 2),
            'currency' => 'THB',
            'shipping' => self::shipping($order),
            'items' => $order->items->map(fn (OrderItem $item) => self::orderItem($item))->values()->all(),
            'note' => $order->customer_notes,
            'tracking_number' => $order->tracking_number,
            'shipping_provider' => $order->shipping_provider,
            'tracking_url' => $order->tracking_url,
            'rider' => self::riderSummary($order),
            'can_cancel' => $order->canBeCancelled(),
            'can_pay' => $order->canRetryPayment() && ! $order->isCod(),
            'can_confirm_received' => $order->status === 'delivered',
            'cancellation_reason' => $order->cancellation_reason,
            'created_at' => $order->created_at?->toISOString(),
            'paid_at' => $order->paid_at?->toISOString(),
            'shipped_at' => $order->shipped_at?->toISOString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
        ];
    }

    /**
     * ออเดอร์แบบย่อ (รายการ)
     *
     * @return array<string, mixed>
     */
    public static function orderSummary(Order $order): array
    {
        $first = $order->relationLoaded('items') ? $order->items->first() : $order->items()->first();

        return [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'status_label' => $order->status_label,
            'payment_status' => (string) $order->payment_status,
            'payment_method' => $order->payment_method,
            'delivery_method' => $order->delivery_method ?? Order::DELIVERY_PARCEL,
            'total_amount' => round((float) $order->total_amount, 2),
            'items_count' => (int) ($order->items_count ?? $order->items()->count()),
            'first_item' => $first ? [
                'product_name' => (string) $first->product_name,
                'product_image' => self::imageUrl($first->product_image),
            ] : null,
            'has_unread_messages' => (bool) $order->has_unread_messages,
            'last_message_at' => $order->last_message_at?->toISOString(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    /**
     * ข้อมูลการชำระเงินของรายการชำระ (QR พร้อมเพย์ / บัญชีโอน / redirect)
     *
     * @param  array<string, mixed>  $paymentData  ผลจาก provider->process() (ถ้ามี)
     * @return array<string, mixed>
     */
    public static function payment(PaymentTransaction $transaction, array $paymentData = []): array
    {
        $response = [
            'transaction_id' => (string) $transaction->transaction_id,
            'order_id' => $transaction->order_id ? (int) $transaction->order_id : null,
            'status' => (string) $transaction->status,
            'amount' => round((float) $transaction->amount, 2),
            'currency' => $transaction->currency ?? 'THB',
            'payment_method' => $transaction->payment_method,
            'expired_at' => $transaction->expired_at?->toISOString(),
        ];

        $gatewayResponse = $paymentData['response'] ?? (is_array($transaction->gateway_response) ? $transaction->gateway_response : []);

        $qr = $paymentData['qr_code'] ?? $transaction->promptpay_qr_code;
        if ($qr) {
            $response['qr_code'] = $qr;
            $response['qr_code_url'] = $paymentData['qr_code_url'] ?? null;
            $response['ref_no'] = $paymentData['ref_no'] ?? $transaction->promptpay_ref_no;
        }

        if (! empty($gatewayResponse['promptpay_name'])) {
            $response['promptpay'] = [
                'account_name' => $gatewayResponse['promptpay_name'],
                'promptpay_id' => $gatewayResponse['promptpay_id'] ?? null,
            ];
        }

        if (isset($paymentData['approval_url'])) {
            $response['redirect_url'] = $paymentData['approval_url'];
            $response['redirect_required'] = true;
        } elseif (isset($paymentData['authorize_uri'])) {
            $response['redirect_url'] = $paymentData['authorize_uri'];
            $response['redirect_required'] = true;
        } elseif (! empty($gatewayResponse['redirect_required'])) {
            $response['redirect_required'] = true;
        }

        if (isset($paymentData['client_secret'])) {
            $response['client_secret'] = $paymentData['client_secret'];
        }

        if (isset($gatewayResponse['deep_link'])) {
            $response['deep_link'] = $gatewayResponse['deep_link'];
        }

        if ($transaction->payment_method === 'bank_transfer' && isset($gatewayResponse['bank_name'])) {
            $response['bank_info'] = [
                'bank_name' => $gatewayResponse['bank_name'],
                'bank_code' => $gatewayResponse['bank_code'] ?? null,
                'account_number' => $gatewayResponse['account_number'] ?? null,
                'account_name' => $gatewayResponse['account_name'] ?? null,
                'branch' => $gatewayResponse['branch'] ?? null,
                'ref_no' => $gatewayResponse['ref_no'] ?? null,
                'instructions' => $gatewayResponse['instructions'] ?? null,
            ];
        }

        if ($transaction->gateway_transaction_id) {
            $response['gateway_transaction_id'] = $transaction->gateway_transaction_id;
        }

        return $response;
    }
}
