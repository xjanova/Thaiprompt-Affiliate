<?php

namespace App\Services;

use App\Models\FreshMarketBuyerPreference;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\RiderJob;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketService - Core Business Logic ตลาดสดไทยพร๊อม
 *
 * จัดการ: สินค้า, ออเดอร์, ค้นหา, Escrow, MLM, Cashback, Rider
 */
class FreshMarketService
{
    protected FreshMarketSetting $settings;

    public function __construct()
    {
        $this->settings = FreshMarketSetting::getSettings();
    }

    // ===== Listing Management =====

    /**
     * สร้างรายการสินค้าใหม่
     *
     * @param  FreshMarketSeller  $seller  ผู้ขาย
     * @param  array  $data  ข้อมูลสินค้า
     * @return FreshMarketListing
     *
     * @throws \Exception
     */
    public function createListing(FreshMarketSeller $seller, array $data): FreshMarketListing
    {
        // ตรวจสอบว่าลงขายได้อีกหรือไม่
        if (! $seller->canCreateListing()) {
            throw new \Exception('คุณลงขายสินค้าเต็มจำนวนแล้ว กรุณาอัพเกรดแพ็กเกจ');
        }

        return DB::transaction(function () use ($seller, $data) {
            // ถ้าไม่ระบุพิกัด ใช้พิกัดร้าน
            if (empty($data['latitude']) && $seller->latitude) {
                $data['latitude'] = $seller->latitude;
                $data['longitude'] = $seller->longitude;
            }

            // ค่า commission เริ่มต้น
            if (! isset($data['commission_rate'])) {
                $data['commission_rate'] = $this->settings->platform_fee_percentage;
            }

            // ค่า delivery_radius_km เริ่มต้น
            if (! isset($data['delivery_radius_km'])) {
                $data['delivery_radius_km'] = $this->settings->default_search_radius_km;
            }

            $listing = FreshMarketListing::create(array_merge($data, [
                'seller_id' => $seller->id,
                'status' => 'active',
                'is_available' => true,
            ]));

            // อัพเดทจำนวน listing ของ seller
            $seller->increment('total_listings');

            Log::info('FreshMarket: สร้าง listing สำเร็จ', [
                'listing_id' => $listing->id,
                'seller_id' => $seller->id,
                'title' => $listing->title,
            ]);

            return $listing;
        });
    }

    // ===== Search =====

    /**
     * ค้นหาสินค้าตามพิกัด + เงื่อนไข
     *
     * @param  float  $lat  ละติจูด
     * @param  float  $lng  ลองจิจูด
     * @param  float|null  $radiusKm  รัศมีค้นหา (กม.)
     * @param  array  $filters  เงื่อนไข (query, category_id, min_price, max_price, sort)
     * @return Collection
     */
    public function searchListings(float $lat, float $lng, ?float $radiusKm = null, array $filters = []): Collection
    {
        $radius = min(
            $radiusKm ?? $this->settings->default_search_radius_km,
            $this->settings->max_search_radius_km
        );

        $query = FreshMarketListing::active()
            ->inStock()
            ->nearby($lat, $lng, $radius)
            ->with(['seller:id,shop_name,rating_average', 'category:id,name,icon']);

        // กรองตามคำค้น
        if (! empty($filters['query'])) {
            $query->search($filters['query']);
        }

        // กรองตามหมวดหมู่
        if (! empty($filters['category_id'])) {
            $query->inCategory($filters['category_id']);
        }

        // กรองตามช่วงราคา
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
        }

        // กรองเฉพาะออร์แกนิค
        if (! empty($filters['organic'])) {
            $query->where('is_organic', true);
        }

        // เรียงลำดับ
        $sort = $filters['sort'] ?? 'distance';
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('order_count', 'desc');
                break;
            case 'distance':
            default:
                // already ordered by distance from nearby scope
                break;
        }

        return $query->limit(50)->get();
    }

    /**
     * ค้นหาสินค้าตาม preference ของผู้ซื้อ
     */
    public function searchByPreference(FreshMarketBuyerPreference $preference): Collection
    {
        if (! $preference->hasLocation()) {
            return collect();
        }

        $filters = [];

        if ($preference->preferred_categories) {
            $filters['category_id'] = $preference->preferred_categories[0] ?? null;
        }

        if ($preference->max_price) {
            $filters['max_price'] = $preference->max_price;
        }

        if ($preference->preferred_organic) {
            $filters['organic'] = true;
        }

        return $this->searchListings(
            $preference->latitude,
            $preference->longitude,
            $preference->max_distance_km,
            $filters
        );
    }

    // ===== Order Management =====

    /**
     * สร้างคำสั่งซื้อ
     *
     * @param  User  $buyer  ผู้ซื้อ
     * @param  FreshMarketListing  $listing  สินค้า
     * @param  array  $data  ข้อมูลเพิ่มเติม (quantity, delivery_type, buyer_lat/lng, etc.)
     * @return FreshMarketOrder
     *
     * @throws \Exception
     */
    public function createOrder(User $buyer, FreshMarketListing $listing, array $data): FreshMarketOrder
    {
        // ตรวจสอบสินค้าพร้อมขาย
        if (! $listing->isAvailableForPurchase()) {
            throw new \Exception('สินค้านี้ไม่พร้อมขาย');
        }

        $quantity = $data['quantity'] ?? 1;

        if ($quantity > $listing->quantity_available) {
            throw new \Exception('สินค้าไม่เพียงพอ');
        }

        return DB::transaction(function () use ($buyer, $listing, $data, $quantity) {
            $unitPrice = $listing->price;
            $totalAmount = $unitPrice * $quantity;
            $deliveryType = $data['delivery_type'] ?? 'pickup';

            // คำนวณค่าจัดส่ง
            $deliveryFee = 0;
            if ($deliveryType === 'rider' && isset($data['buyer_latitude'])) {
                $deliveryFee = $this->calculateDeliveryFee(
                    $listing->latitude, $listing->longitude,
                    $data['buyer_latitude'], $data['buyer_longitude']
                );
            }

            // คำนวณค่าบริการ platform
            $platformFee = round($totalAmount * ($this->settings->platform_fee_percentage / 100), 2);
            $sellerEarning = $totalAmount - $platformFee;

            // คำนวณ cashback
            $cashbackAmount = 0;
            if ($this->settings->cashback_enabled && $listing->cashback_amount > 0) {
                $cashbackAmount = $listing->cashback_amount * $quantity;
            } elseif ($this->settings->cashback_enabled && $listing->cashback_percentage > 0) {
                $cashbackAmount = round($totalAmount * ($listing->cashback_percentage / 100), 2);
            }

            // กำหนดวิธีชำระเงิน
            $paymentMethod = $data['payment_method'] ?? ($this->settings->escrow_enabled ? 'escrow' : 'cod');

            $order = FreshMarketOrder::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $listing->seller_id,
                'listing_id' => $listing->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
                'seller_earning' => $sellerEarning,
                'delivery_type' => $deliveryType,
                'delivery_fee' => $deliveryFee,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'escrow_status' => $paymentMethod === 'escrow' ? 'held' : null,
                'buyer_latitude' => $data['buyer_latitude'] ?? null,
                'buyer_longitude' => $data['buyer_longitude'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'cashback_amount' => $cashbackAmount,
            ]);

            // ลดจำนวนสินค้า
            $listing->decrementStock($quantity);

            Log::info('FreshMarket: สร้าง order สำเร็จ', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'buyer_id' => $buyer->id,
                'listing_id' => $listing->id,
                'total' => $totalAmount,
            ]);

            return $order;
        });
    }

    /**
     * ผู้ขายรับออเดอร์
     */
    public function acceptOrder(FreshMarketOrder $order): FreshMarketOrder
    {
        if ($order->order_status !== 'pending') {
            throw new \Exception('สถานะออเดอร์ไม่ถูกต้อง');
        }

        $order->markAsAccepted();

        // ถ้าเป็นแบบ rider → สร้าง rider job
        if ($order->delivery_type === 'rider' && $this->settings->rider_enabled) {
            $this->dispatchRider($order);
        }

        return $order->fresh();
    }

    /**
     * เสร็จสิ้นออเดอร์ → จ่ายเงิน + MLM + Cashback
     */
    public function completeOrder(FreshMarketOrder $order): FreshMarketOrder
    {
        return DB::transaction(function () use ($order) {
            $order->markAsCompleted();

            // 1. Release escrow → จ่ายเงินให้ผู้ขาย
            if ($order->escrow_status === 'held') {
                $this->releaseEscrow($order);
            }

            // 2. คำนวณ MLM Commission
            if ($this->settings->mlm_commission_enabled && ! $order->mlm_commission_processed) {
                $this->processMlmCommission($order);
            }

            // 3. คำนวณ Cashback
            if ($this->settings->cashback_enabled && $order->cashback_amount > 0 && ! $order->cashback_processed) {
                $this->processCashback($order);
            }

            // 4. อัพเดทสถิติ seller
            $order->seller?->refreshStats();

            Log::info('FreshMarket: order เสร็จสิ้น พร้อม commission + cashback', [
                'order_id' => $order->id,
            ]);

            return $order;
        });
    }

    /**
     * ยกเลิกออเดอร์
     */
    public function cancelOrder(FreshMarketOrder $order, string $reason, string $cancelledBy = 'system'): FreshMarketOrder
    {
        if (! $order->canBeCancelled()) {
            throw new \Exception('ออเดอร์นี้ไม่สามารถยกเลิกได้');
        }

        return DB::transaction(function () use ($order, $reason, $cancelledBy) {
            $order->cancel($reason, $cancelledBy);

            // คืน stock
            if ($order->listing) {
                $order->listing->increment('quantity_available', $order->quantity);
                $order->listing->update(['status' => 'active', 'is_available' => true]);
            }

            // คืนเงิน escrow ให้ผู้ซื้อ (ถ้ามี)
            if ($order->escrow_status === 'held') {
                try {
                    $buyerUser = $order->buyer;
                    if ($buyerUser) {
                        $walletService = app(WalletService::class);
                        $wallet = $walletService->getOrCreateWallet($buyerUser);
                        $walletService->deposit(
                            $wallet,
                            $order->total_amount + $order->delivery_fee,
                            'คืนเงินตลาดสด #'.$order->order_number,
                            FreshMarketOrder::class,
                            $order->id
                        );
                    }

                    $order->update(['escrow_status' => 'refunded', 'payment_status' => 'refunded']);

                    Log::info('FreshMarket: คืนเงิน escrow สำเร็จ', [
                        'order_id' => $order->id,
                        'amount' => $order->total_amount + $order->delivery_fee,
                    ]);
                } catch (\Exception $e) {
                    Log::error('FreshMarket: คืนเงิน escrow ล้มเหลว', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('FreshMarket: ยกเลิก order', [
                'order_id' => $order->id,
                'reason' => $reason,
                'cancelled_by' => $cancelledBy,
            ]);

            return $order;
        });
    }

    // ===== Escrow =====

    /**
     * ปล่อย escrow → จ่ายเงินให้ผู้ขาย
     */
    protected function releaseEscrow(FreshMarketOrder $order): void
    {
        try {
            $seller = $order->seller;
            $sellerUser = $seller?->user;

            if ($sellerUser) {
                // เรียก WalletService จ่ายเงินให้ผู้ขาย
                $walletService = app(WalletService::class);
                $wallet = $walletService->getOrCreateWallet($sellerUser);
                $walletService->deposit(
                    $wallet,
                    $order->seller_earning,
                    'ขายสินค้าตลาดสด #'.$order->order_number,
                    FreshMarketOrder::class,
                    $order->id
                );
            }

            $order->update([
                'escrow_status' => 'released',
                'payment_status' => 'released',
            ]);

            Log::info('FreshMarket: ปล่อย escrow สำเร็จ', [
                'order_id' => $order->id,
                'amount' => $order->seller_earning,
            ]);
        } catch (\Exception $e) {
            Log::error('FreshMarket: ปล่อย escrow ล้มเหลว', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ===== MLM Commission =====

    /**
     * คำนวณและจ่าย MLM Commission
     *
     * เนื่องจาก MlmCommissionService::processOrderCommissions() รับ Order model (ecommerce)
     * เราจึงใช้ calculateCommissionsWithRollup() โดยตรงแทน
     * หรือจ่าย commission ผ่าน WalletService ตรงๆ
     */
    protected function processMlmCommission(FreshMarketOrder $order): void
    {
        try {
            $seller = $order->seller;

            if (! $seller || ! $seller->mlm_member_id) {
                return;
            }

            // ใช้ MlmCommissionService ผ่าน calculateCommissionsWithRollup
            if (class_exists(\App\Services\MlmCommissionService::class)
                && class_exists(\App\Models\MlmMember::class)) {
                $mlmMember = \App\Models\MlmMember::find($seller->mlm_member_id);

                if ($mlmMember) {
                    $mlmService = app(\App\Services\MlmCommissionService::class);
                    $listing = $order->listing;
                    $pvValue = $listing?->pv_value ?? $order->total_amount;

                    // คำนวณ commission ด้วย PV (Point Value)
                    if (method_exists($mlmService, 'calculateCommissionsWithRollup')) {
                        $mlmService->calculateCommissionsWithRollup(
                            $mlmMember,
                            $pvValue,
                            'fresh_market_purchase',
                            $order->id
                        );
                    }
                }
            }

            $order->update(['mlm_commission_processed' => true]);

            Log::info('FreshMarket: MLM commission processed', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('FreshMarket: MLM commission error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ===== Cashback =====

    /**
     * จ่าย Cashback ให้ผู้ซื้อ
     */
    protected function processCashback(FreshMarketOrder $order): void
    {
        try {
            $buyer = $order->buyer;

            if (! $buyer || $order->cashback_amount <= 0) {
                return;
            }

            // เรียก WalletService จ่าย cashback
            $walletService = app(WalletService::class);
            $wallet = $walletService->getOrCreateWallet($buyer);
            $walletService->deposit(
                $wallet,
                $order->cashback_amount,
                'แคชแบ็คตลาดสด #'.$order->order_number,
                FreshMarketOrder::class,
                $order->id
            );

            $order->update(['cashback_processed' => true]);

            Log::info('FreshMarket: cashback processed', [
                'order_id' => $order->id,
                'buyer_id' => $buyer->id,
                'amount' => $order->cashback_amount,
            ]);
        } catch (\Exception $e) {
            Log::error('FreshMarket: cashback error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ===== Rider =====

    /**
     * สร้าง Rider Job สำหรับจัดส่ง
     */
    public function dispatchRider(FreshMarketOrder $order): ?RiderJob
    {
        try {
            $seller = $order->seller;

            if (! $seller || ! $seller->latitude) {
                Log::warning('FreshMarket: ไม่มีพิกัดผู้ขาย ไม่สามารถเรียก rider', [
                    'order_id' => $order->id,
                ]);

                return null;
            }

            // ตรวจสอบพิกัดผู้ซื้อ
            if (! $order->buyer_latitude || ! $order->buyer_longitude) {
                Log::warning('FreshMarket: ไม่มีพิกัดผู้ซื้อ ไม่สามารถเรียก rider', [
                    'order_id' => $order->id,
                ]);

                return null;
            }

            $distanceKm = $this->haversineDistance(
                $seller->latitude, $seller->longitude,
                $order->buyer_latitude, $order->buyer_longitude
            );

            $job = RiderJob::create([
                'job_type' => 'delivery',
                'title' => "จัดส่งสินค้าตลาดสด #{$order->order_number}",
                'description' => "ออเดอร์ตลาดสด #{$order->order_number}",
                'pickup_latitude' => $seller->latitude,
                'pickup_longitude' => $seller->longitude,
                'pickup_address' => $seller->address ?? $seller->shop_name,
                'pickup_contact_name' => $seller->shop_name,
                'pickup_contact_phone' => $seller->phone,
                'delivery_latitude' => $order->buyer_latitude,
                'delivery_longitude' => $order->buyer_longitude,
                'delivery_address' => $order->delivery_address,
                'delivery_notes' => $order->delivery_notes,
                'distance_km' => round($distanceKm, 2),
                'total_fee' => $order->delivery_fee,
                'status' => 'pending',
                'customer_id' => $order->buyer_id,
            ]);

            $order->update(['rider_job_id' => $job->id]);

            Log::info('FreshMarket: สร้าง rider job สำเร็จ', [
                'order_id' => $order->id,
                'job_id' => $job->id,
                'distance_km' => $distanceKm,
            ]);

            return $job;
        } catch (\Exception $e) {
            Log::error('FreshMarket: สร้าง rider job ล้มเหลว', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ===== Helpers =====

    /**
     * คำนวณค่าจัดส่งตามระยะทาง
     */
    public function calculateDeliveryFee(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $distanceKm = $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng);

        // อัตราค่าจัดส่งจาก settings (ใช้ค่าเดียวกับ RiderDispatchService)
        $baseFee = $this->settings->delivery_base_fee ?? 30.0;
        $perKmFee = $this->settings->delivery_per_km_fee ?? 10.0;

        return round($baseFee + ($distanceKm * $perKmFee), 2);
    }

    /**
     * คำนวณระยะทาง Haversine (กม.)
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // กม.

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * ดึงหมวดหมู่ทั้งหมด (cache)
     */
    public function getCategories(): Collection
    {
        return FreshMarketCategory::active()
            ->root()
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }
}
