<?php

namespace App\Services;

use App\Exceptions\FreshMarketException;
use App\Models\FreshMarketBuyerPreference;
use App\Models\FreshMarketCartItem;
use App\Models\FreshMarketCategory;
use App\Models\FreshMarketListing;
use App\Models\FreshMarketOrder;
use App\Models\FreshMarketOrderItem;
use App\Models\FreshMarketReferral;
use App\Models\FreshMarketSeller;
use App\Models\FreshMarketSetting;
use App\Models\PlatformTransaction;
use App\Models\PlatformWallet;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDebt;
use App\Models\WalletTransaction;
use App\Services\Pricing\PricingEngine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FreshMarketService - Core Business Logic ตลาดสดไทยพร๊อม
 *
 * จัดการ: สินค้า, ค้นหา, ออเดอร์ (วงจรชีวิตครบ), เงิน (escrow/COD/GP/คืนเงิน), ไรเดอร์, คะแนน
 *
 * 💰 กฎเงิน (ห้ามละเมิด):
 *  - wallet: หักเงินผู้ซื้อตอนสร้างออเดอร์ทันที (ใน transaction เดียวกับการตัดสต็อก) → payment_status=paid, escrow=held
 *  - cod   : ยังไม่มีเงินเข้าระบบ (payment_status=pending, escrow=null) จนกว่าไรเดอร์/ร้านเก็บเงินได้
 *  - ยกเลิก: คืนเงิน "เฉพาะที่เก็บมาแล้วจริง" ครั้งเดียว (lock แถว + เช็ครายการคืนเงินเดิม)
 *  - ปิดออเดอร์: ปล่อย escrow ครั้งเดียว → ร้านได้ (ยอดสินค้า − GP), GP เข้ากระเป๋า fee ของแพลตฟอร์ม
 *  - COD นัดรับ: ร้านได้เงินสดเต็ม → หัก GP จาก wallet ร้าน (ไม่พอ = บันทึกหนี้ WalletDebt แล้วหักจากยอดขายถัดไป)
 *  - แคชแบ็ค/ค่าแนะนำ จ่ายจาก GP ที่แพลตฟอร์มได้รับจริงเท่านั้น (ไม่เสกเงิน)
 *
 * ทุกการเปลี่ยนสถานะ: DB::transaction + lockForUpdate + ตารางใน FreshMarketOrder::ACTIONS + idempotent
 */
class FreshMarketService
{
    // ===== reference_type ของ wallet_transactions (ใช้กันจ่ายซ้ำ) =====
    public const REF_PAYMENT = 'fresh_market_payment';

    public const REF_REFUND = 'fresh_market_refund';

    public const REF_PAYOUT = 'fresh_market_payout';

    public const REF_CASHBACK = 'fresh_market_cashback';

    public const REF_COD_GP = 'fresh_market_cod_gp';

    public const REF_REFERRAL = 'fresh_market_referral';

    public const REF_SUBSCRIPTION = 'fresh_market_subscription';

    /** source_type ของหนี้ค่า GP (COD ที่หักจาก wallet ร้านไม่ได้) */
    public const DEBT_SOURCE_GP = 'fresh_market_gp';

    /** จำนวนบรรทัดสินค้าสูงสุดต่อออเดอร์ */
    public const MAX_ORDER_LINES = 30;

    // ===== sub_type ของ platform_transactions =====
    public const PLATFORM_GP = 'fresh_market_gp';

    public const PLATFORM_GP_DEBT = 'fresh_market_gp_debt';

    public const PLATFORM_CASHBACK = 'fresh_market_cashback';

    public const PLATFORM_REFERRAL = 'fresh_market_referral';

    public const PLATFORM_SUBSCRIPTION = 'fresh_market_subscription';

    protected WalletService $wallets;

    protected FreshMarketOrderNotifier $notifier;

    /**
     * ไม่แตะฐานข้อมูลตอนสร้าง (controller ถูกสร้างตอน route:list / boot ได้โดยไม่ต้องมี DB)
     */
    public function __construct()
    {
        $this->wallets = app(WalletService::class);
        $this->notifier = app(FreshMarketOrderNotifier::class);
    }

    /**
     * ค่าตั้งระบบตลาดสด (โหลดตอนใช้จริง — cache ใน request อยู่แล้วที่ FreshMarketSetting::getSettings)
     */
    protected function settings(): FreshMarketSetting
    {
        return FreshMarketSetting::getSettings();
    }

    // ╔══════════════════════════════════════════╗
    // ║  ผู้ขาย                                   ║
    // ╚══════════════════════════════════════════╝

    /**
     * สมัครเป็นผู้ขาย (เว็บ / API / LINE — ต้องเป็นการเลือกของผู้ใช้เองเท่านั้น)
     *
     * @param  array  $data  shop_name, shop_description, phone, address, province, district, sub_district, latitude, longitude
     *
     * @throws FreshMarketException SELLER_EXISTS
     */
    public function registerSeller(User $user, array $data): FreshMarketSeller
    {
        $existing = FreshMarketSeller::withTrashed()->where('user_id', $user->id)->first();

        if ($existing && ! $existing->trashed()) {
            throw FreshMarketException::make('SELLER_EXISTS', 'คุณสมัครเป็นผู้ขายไว้แล้ว', 409);
        }

        $fields = array_intersect_key($data, array_flip([
            'shop_name', 'shop_description', 'shop_image', 'phone', 'address',
            'province', 'district', 'sub_district', 'latitude', 'longitude',
        ]));

        $fields = array_merge($fields, [
            'user_id' => $user->id,
            'line_user_id' => $data['line_user_id'] ?? $user->line_user_id,
            'line_display_name' => $data['line_display_name'] ?? $user->line_display_name ?? null,
            'is_active' => true,
            'is_suspended' => false,
            // ปิดอนุมัติอัตโนมัติ → ต้องรอแอดมินยืนยันก่อน สินค้าจึงจะแสดง
            'is_verified' => FreshMarketListing::autoApproveSellers(),
        ], $this->initialSubscription());

        try {
            if ($existing) {
                // เคยปิดร้าน (soft delete) → เปิดใหม่ด้วยข้อมูลล่าสุด
                $existing->restore();
                $existing->update($fields);

                return $existing->fresh();
            }

            return FreshMarketSeller::create($fields);
        } catch (QueryException $e) {
            // ชน unique fm_seller_user_unique (กดสมัครซ้ำพร้อมกัน)
            Log::warning('FreshMarket: สมัครผู้ขายซ้ำ', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            throw FreshMarketException::make('SELLER_EXISTS', 'คุณสมัครเป็นผู้ขายไว้แล้ว', 409);
        }
    }

    /**
     * แพ็กเกจเริ่มต้นของร้านใหม่ (ช่วงทดลองใช้สมาชิกรายเดือน ถ้าเปิดโหมดสมาชิก)
     */
    protected function initialSubscription(): array
    {
        $trialDays = (int) ($this->settings()->free_trial_days ?? 0);

        if ($this->settings()->fee_mode !== 'percentage' && $trialDays > 0) {
            return [
                'subscription_type' => 'monthly',
                'subscription_expires_at' => now()->addDays($trialDays),
            ];
        }

        return ['subscription_type' => 'free', 'subscription_expires_at' => null];
    }

    /**
     * สมัคร/ต่ออายุสมาชิกรายเดือน (หักจาก wallet ร้าน) → ลงขายได้ตามโควต้าสมาชิก
     *
     * @throws FreshMarketException
     */
    public function subscribeSeller(FreshMarketSeller $seller): FreshMarketSeller
    {
        if ($this->settings()->fee_mode === 'percentage') {
            throw FreshMarketException::make('SUBSCRIPTION_DISABLED', 'ขณะนี้ไม่มีแพ็กเกจสมาชิกรายเดือน', 422);
        }

        $fee = round((float) $this->settings()->monthly_subscription_fee, 2);
        $user = $seller->user;

        if (! $user) {
            throw FreshMarketException::make('SELLER_NOT_FOUND', 'ไม่พบบัญชีผู้ขาย', 404);
        }

        return DB::transaction(function () use ($seller, $fee, $user) {
            $locked = FreshMarketSeller::whereKey($seller->id)->lockForUpdate()->first();

            if ($fee > 0) {
                // กันกดซ้ำ: มีรายการสมัครภายใน 1 นาทีที่ผ่านมาแล้ว → ไม่หักซ้ำ
                $recent = WalletTransaction::where('reference_type', self::REF_SUBSCRIPTION)
                    ->where('reference_id', $locked->id)
                    ->where('created_at', '>=', now()->subMinute())
                    ->exists();

                if ($recent) {
                    throw FreshMarketException::make('DUPLICATE_REQUEST', 'ระบบกำลังดำเนินการต่ออายุสมาชิกให้อยู่ กรุณารอสักครู่', 409);
                }

                $wallet = $this->lockActiveWallet($user);

                if ((float) $wallet->balance < $fee) {
                    throw FreshMarketException::make(
                        'INSUFFICIENT_BALANCE',
                        'ยอดเงินใน Wallet ไม่เพียงพอ (ค่าสมาชิก ฿'.number_format($fee, 2).')',
                        422
                    );
                }

                $this->wallets->deductForService($wallet, $fee, 'ค่าสมาชิกร้านตลาดสด 30 วัน', self::REF_SUBSCRIPTION, $locked->id, [
                    'seller_id' => $locked->id,
                ]);

                PlatformWallet::getFeeWallet()->addFunds($fee, self::PLATFORM_SUBSCRIPTION, FreshMarketSeller::class, $locked->id, [
                    'user_id' => $user->id,
                ]);
            }

            $base = $locked->subscription_expires_at && $locked->subscription_expires_at->isFuture()
                ? $locked->subscription_expires_at->copy()
                : now();

            $locked->update([
                'subscription_type' => 'monthly',
                'subscription_expires_at' => $base->addDays(30),
            ]);

            return $locked->fresh();
        });
    }

    // ╔══════════════════════════════════════════╗
    // ║  สินค้า                                   ║
    // ╚══════════════════════════════════════════╝

    /**
     * สร้างรายการสินค้าใหม่
     *
     * @param  array  $data  ข้อมูลสินค้า (รับ category_hint จาก LINE ได้ — แปลงเป็น category_id ให้)
     *
     * @throws FreshMarketException LISTING_LIMIT | SELLER_SUSPENDED
     */
    public function createListing(FreshMarketSeller $seller, array $data): FreshMarketListing
    {
        if (! $seller->is_active || $seller->is_suspended) {
            throw FreshMarketException::make('SELLER_SUSPENDED', 'ร้านของคุณถูกระงับหรือปิดอยู่ ไม่สามารถลงขายได้', 403);
        }

        if (! $seller->canCreateListing()) {
            throw FreshMarketException::make('LISTING_LIMIT', $this->listingLimitMessage(), 403);
        }

        return DB::transaction(function () use ($seller, $data) {
            // ถ้าไม่ระบุพิกัด ใช้พิกัดร้าน
            if (empty($data['latitude']) && $seller->latitude) {
                $data['latitude'] = $seller->latitude;
                $data['longitude'] = $seller->longitude;
            }

            // หมวดหมู่จากคำใบ้ (LINE/AI)
            if (empty($data['category_id']) && ! empty($data['category_hint'])) {
                $data['category_id'] = FreshMarketCategory::guessIdFromHint($data['category_hint']);
            }

            if (! isset($data['delivery_radius_km'])) {
                $data['delivery_radius_km'] = $this->settings()->default_search_radius_km;
            }

            // สต็อก: ต้องมีอย่างน้อย 1 (ลงผ่าน LINE ที่ไม่ได้บอกจำนวน → ค่าเริ่มต้นจากตั้งค่า)
            $qty = isset($data['quantity_available']) ? (int) $data['quantity_available'] : 0;
            if ($qty < 1) {
                $qty = max(1, (int) Setting::get('fresh_market.default_line_stock', 10));
            }
            $data['quantity_available'] = $qty;

            // แคชแบ็คต้องไม่เกิน GP (แพลตฟอร์มออกเงินจาก GP)
            $probe = new FreshMarketListing(['seller_id' => $seller->id]);
            $probe->setRelation('seller', $seller);
            $gpRate = $this->gpRateFor($probe);
            $data['commission_rate'] = $gpRate;
            $data['cashback_percentage'] = min(max(0, (float) ($data['cashback_percentage'] ?? 0)), $gpRate);
            $data['cashback_amount'] = max(0, (float) ($data['cashback_amount'] ?? 0));

            $fillable = array_flip((new FreshMarketListing)->getFillable());
            $attributes = array_intersect_key($data, $fillable);

            $listing = FreshMarketListing::create(array_merge($attributes, [
                'seller_id' => $seller->id,
                'status' => 'active',
                'is_available' => true,
            ]));

            $seller->refreshStats();

            Log::info('FreshMarket: สร้าง listing สำเร็จ', [
                'listing_id' => $listing->id,
                'seller_id' => $seller->id,
            ]);

            return $listing;
        });
    }

    /**
     * ข้อความเมื่อลงขายเต็มโควต้า
     */
    public function listingLimitMessage(): string
    {
        $free = (int) $this->settings()->max_listings_free;

        if ($this->settings()->fee_mode === 'percentage') {
            return 'ไม่สามารถลงขายเพิ่มได้ในขณะนี้';
        }

        $fee = number_format((float) $this->settings()->monthly_subscription_fee, 0);

        return "ร้านทั่วไปลงขายได้สูงสุด {$free} รายการ สมัครสมาชิกรายเดือน ฿{$fee} เพื่อลงขายเพิ่มได้";
    }

    /**
     * แคชแบ็คสูงสุด (%) ที่ร้านตั้งได้ = อัตรา GP (แพลตฟอร์มจ่ายแคชแบ็คจาก GP)
     */
    public function maxCashbackPercent(FreshMarketSeller $seller): float
    {
        $probe = new FreshMarketListing(['seller_id' => $seller->id]);
        $probe->setRelation('seller', $seller);

        return $this->gpRateFor($probe);
    }

    /**
     * อัตรา GP (%) ของสินค้า — อำนาจตัดสินอยู่ที่ PricingEngine
     * โหมดสมาชิกล้วน (fee_mode=subscription) ร้านที่เป็นสมาชิกอยู่ไม่เสีย GP
     */
    public function gpRateFor(FreshMarketListing $listing): float
    {
        $seller = $listing->relationLoaded('seller') ? $listing->seller : $listing->seller()->first();

        if ($this->settings()->fee_mode === 'subscription' && $seller?->hasPaidSubscription()) {
            return 0.0;
        }

        $rate = (float) app(PricingEngine::class)->gpRateForFreshListing($listing);

        return round(max(0, min(100, $rate)), 2);
    }

    // ╔══════════════════════════════════════════╗
    // ║  ค้นหา                                    ║
    // ╚══════════════════════════════════════════╝

    /**
     * ค้นหาสินค้าตามพิกัด + เงื่อนไข (เฉพาะสินค้าที่ผู้ซื้อเห็นได้)
     *
     * @param  array  $filters  query, category_id (id หรือ slug), min_price, max_price, organic, sort
     */
    public function searchListings(float $lat, float $lng, ?float $radiusKm = null, array $filters = []): Collection
    {
        $radius = min(
            $radiusKm ?? (float) $this->settings()->default_search_radius_km,
            (float) $this->settings()->max_search_radius_km
        );

        // ร้านเคลื่อนที่ที่เปิดอยู่ใช้ตำแหน่งปัจจุบัน / ร้านที่ปิดยังแสดง (สั่งไม่ได้) — ดู FreshMarketListing::scopeNearby
        $query = FreshMarketListing::visibleToBuyers()
            ->inStock()
            ->nearby($lat, $lng, $radius)
            ->with(['seller:'.FreshMarketSeller::SUMMARY_COLUMNS, 'category:id,name,icon'])
            ->withCount('optionGroups');

        if (! empty($filters['query'])) {
            $query->search($filters['query']);
        }

        if (! empty($filters['category_id'])) {
            $query->inCategory($filters['category_id']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange(
                isset($filters['min_price']) && $filters['min_price'] !== '' ? (float) $filters['min_price'] : null,
                isset($filters['max_price']) && $filters['max_price'] !== '' ? (float) $filters['max_price'] : null
            );
        }

        if (! empty($filters['organic'])) {
            $query->where('is_organic', true);
        }

        match ($filters['sort'] ?? 'distance') {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'popular' => $query->orderBy('order_count', 'desc'),
            default => null, // เรียงตามระยะทางจาก scope nearby อยู่แล้ว
        };

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
            (float) $preference->latitude,
            (float) $preference->longitude,
            $preference->max_distance_km !== null ? (float) $preference->max_distance_km : null,
            $filters
        );
    }

    // ╔══════════════════════════════════════════╗
    // ║  ค่าส่งไรเดอร์                            ║
    // ╚══════════════════════════════════════════╝

    /**
     * คำนวณค่าส่งไรเดอร์จากร้าน → ตำแหน่งผู้ซื้อ (คำนวณฝั่งเซิร์ฟเวอร์เท่านั้น)
     *
     * @return array{available: bool, code: ?string, message: ?string, distance_km: ?float, total_fee: float, estimated_duration_minutes: ?int, max_distance_km: float}
     */
    public function quoteDelivery(FreshMarketListing $listing, float $lat, float $lng): array
    {
        $maxKm = (float) Setting::get('rider.max_distance_km', 15);
        $result = [
            'available' => false,
            'code' => null,
            'message' => null,
            'distance_km' => null,
            'total_fee' => 0.0,
            'estimated_duration_minutes' => null,
            'max_distance_km' => $maxKm,
        ];

        if (! $this->settings()->rider_enabled) {
            return array_merge($result, ['code' => 'RIDER_DISABLED', 'message' => 'ขณะนี้ยังไม่เปิดบริการส่งด้วยไรเดอร์']);
        }

        if (abs($lat) > 90 || abs($lng) > 180 || ($lat == 0.0 && $lng == 0.0)) {
            return array_merge($result, ['code' => 'DELIVERY_LOCATION_REQUIRED', 'message' => 'กรุณาปักหมุดตำแหน่งจัดส่งให้ถูกต้อง']);
        }

        $listing->loadMissing('seller');
        $seller = $listing->seller;

        // ร้านปิดอยู่ (รวมร้านเคลื่อนที่ที่ยังไม่ได้เปิดร้านวันนี้) → ยังไม่รู้จุดรับของ และสั่งไม่ได้
        if ($seller && ! $seller->isOpenNow()) {
            return array_merge($result, ['code' => 'SHOP_CLOSED', 'message' => 'ร้าน'.FreshMarketSeller::CLOSED_MESSAGE]);
        }

        // จุดรับของ: ร้านเคลื่อนที่ = ตำแหน่งที่เปิดร้านตอนนี้ / ร้านประจำ = พิกัดร้าน / ไม่มีทั้งคู่ = พิกัดสินค้า
        $pickup = $seller?->pickupPoint();
        $pickupLat = $pickup ? $pickup['latitude'] : ($listing->latitude !== null ? (float) $listing->latitude : null);
        $pickupLng = $pickup ? $pickup['longitude'] : ($listing->longitude !== null ? (float) $listing->longitude : null);

        if (! $pickupLat || ! $pickupLng) {
            return array_merge($result, [
                'code' => 'PICKUP_LOCATION_MISSING',
                'message' => 'ร้านนี้ยังไม่ได้ปักหมุดตำแหน่งร้าน จึงยังส่งด้วยไรเดอร์ไม่ได้ กรุณาเลือกรับเอง',
            ]);
        }

        try {
            $quote = app(DeliveryFeeCalculator::class)->quote($pickupLat, $pickupLng, $lat, $lng);
        } catch (\Throwable $e) {
            Log::error('FreshMarket: คำนวณค่าส่งไรเดอร์ล้มเหลว', ['listing_id' => $listing->id, 'error' => $e->getMessage()]);

            return array_merge($result, ['code' => 'QUOTE_FAILED', 'message' => 'คำนวณค่าส่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง']);
        }

        $distance = round((float) ($quote['distance_km'] ?? 0), 2);
        $maxKm = isset($quote['max_distance_km']) ? (float) $quote['max_distance_km'] : $maxKm;
        $result['max_distance_km'] = $maxKm;
        $result['distance_km'] = $distance;
        $result['total_fee'] = round((float) ($quote['total_fee'] ?? 0), 2);
        $result['estimated_duration_minutes'] = isset($quote['estimated_duration_minutes']) ? (int) $quote['estimated_duration_minutes'] : null;

        $outOfArea = array_key_exists('within_service_area', $quote)
            ? ! $quote['within_service_area']
            : ($maxKm > 0 && $distance > $maxKm);

        if ($outOfArea) {
            return array_merge($result, [
                'code' => 'OUT_OF_DELIVERY_AREA',
                'message' => 'ระยะทาง '.number_format($distance, 1).' กม. เกินพื้นที่ให้บริการไรเดอร์ (สูงสุด '.number_format($maxKm, 0).' กม.)',
            ]);
        }

        $result['available'] = true;

        return $result;
    }

    // ╔══════════════════════════════════════════╗
    // ║  สร้างออเดอร์                             ║
    // ╚══════════════════════════════════════════╝

    /**
     * วิธีชำระเงินที่เปิดอยู่
     *
     * @return array<int, string> wallet | cod
     */
    public function availablePaymentMethods(): array
    {
        $methods = [];

        if ($this->settings()->escrow_enabled) {
            $methods[] = 'wallet';
        }

        if ($this->settings()->cod_enabled) {
            $methods[] = 'cod';
        }

        return $methods;
    }

    /**
     * ตรวจ/แปลงวิธีชำระเงิน ('escrow' เดิม = 'wallet')
     *
     * @param  string|null  $requested  ที่ผู้ใช้เลือก (null = ใช้ค่าเริ่มต้น)
     * @param  string|null  $preferredDefault  ค่าเริ่มต้นที่ช่องทางนั้นอยากใช้ (เช่นเว็บ/LINE ใช้ cod)
     *
     * @throws FreshMarketException PAYMENT_METHOD_DISABLED
     */
    public function resolvePaymentMethod(?string $requested, ?string $preferredDefault = null): string
    {
        $available = $this->availablePaymentMethods();

        if (empty($available)) {
            throw FreshMarketException::make('PAYMENT_METHOD_DISABLED', 'ขณะนี้ตลาดสดยังไม่เปิดรับชำระเงิน', 422);
        }

        $requested = $requested === 'escrow' ? 'wallet' : $requested;

        if ($requested === null || $requested === '') {
            return ($preferredDefault && in_array($preferredDefault, $available, true)) ? $preferredDefault : $available[0];
        }

        if ($requested === 'transfer') {
            throw FreshMarketException::make(
                'PAYMENT_METHOD_DISABLED',
                'ยังไม่รองรับการโอนเงิน กรุณาเลือกชำระผ่าน Wallet หรือเก็บเงินปลายทาง',
                422
            );
        }

        if (! in_array($requested, $available, true)) {
            throw FreshMarketException::make('PAYMENT_METHOD_DISABLED', 'วิธีชำระเงินนี้ปิดใช้งานอยู่', 422);
        }

        return $requested;
    }

    /**
     * สร้างคำสั่งซื้อสินค้าเดียว (ทางเดิมของเว็บ/API/LINE) → ส่งต่อไปทางหลายรายการ
     *
     * @param  array  $data  quantity, option_ids (ตัวเลือกที่เลือก), item_note, delivery_type (pickup|rider),
     *                       payment_method (wallet|cod|null), default_payment_method, buyer_latitude, buyer_longitude,
     *                       delivery_address, delivery_notes, channel
     *
     * @throws FreshMarketException
     */
    public function createOrder(User $buyer, FreshMarketListing $listing, array $data): FreshMarketOrder
    {
        $quantity = (int) ($data['quantity'] ?? 1);

        if ($quantity < 1 || $quantity > 999) {
            throw FreshMarketException::make('INVALID_QUANTITY', 'จำนวนสินค้าไม่ถูกต้อง', 422);
        }

        $hasOptions = array_key_exists('option_ids', $data) && $data['option_ids'] !== null;

        // LINE ยังไม่มีขั้นเลือกตัวเลือก → กลุ่มที่บังคับใช้ตัวเลือกที่ถูกที่สุดให้อัตโนมัติ
        if (! array_key_exists('apply_default_options', $data)) {
            $data['apply_default_options'] = ! $hasOptions && ($data['channel'] ?? null) === 'line';
        }

        return $this->createOrderFromItems($buyer, (int) $listing->seller_id, [[
            'listing_id' => (int) $listing->id,
            'quantity' => $quantity,
            'option_ids' => $hasOptions ? $data['option_ids'] : [],
            'note' => $data['item_note'] ?? null,
        ]], $data);
    }

    /**
     * สร้างคำสั่งซื้อหลายรายการจากร้านเดียว (ตะกร้า / แอป / เว็บ) — ราคา ตัวเลือก ค่าส่ง GP คำนวณฝั่งเซิร์ฟเวอร์ทั้งหมด
     *
     * @param  int  $sellerId  ร้านของออเดอร์ (ทุกรายการต้องเป็นของร้านนี้)
     * @param  array<int, array{listing_id: int, quantity: int, option_ids?: array, note?: ?string}>  $items
     * @param  array  $data  delivery_type, payment_method, default_payment_method, buyer_latitude, buyer_longitude,
     *                       delivery_address, delivery_notes, channel, apply_default_options (bool),
     *                       cart_item_ids (รายการตะกร้าที่ต้องลบเมื่อสั่งสำเร็จ — lock + ตรวจครบในธุรกรรมเดียวกัน)
     *
     * @throws FreshMarketException
     */
    public function createOrderFromItems(User $buyer, int $sellerId, array $items, array $data): FreshMarketOrder
    {
        $lines = $this->normalizeOrderLines($items);

        $deliveryType = $data['delivery_type'] ?? 'pickup';

        if (! in_array($deliveryType, ['pickup', 'rider'], true)) {
            throw FreshMarketException::make('INVALID_DELIVERY_TYPE', 'รองรับเฉพาะรับเองหรือส่งด้วยไรเดอร์', 422);
        }

        $paymentMethod = $this->resolvePaymentMethod($data['payment_method'] ?? null, $data['default_payment_method'] ?? null);

        $seller = FreshMarketSeller::find($sellerId);

        if (! $seller || ! $seller->isVisibleToBuyers()) {
            throw FreshMarketException::make('LISTING_UNAVAILABLE', 'ร้านนี้ไม่พร้อมรับออเดอร์ในขณะนี้', 409);
        }

        // ร้านปิดอยู่ (กดปิดร้าน / เลยเวลาปิด / ร้านเคลื่อนที่ยังไม่เปิดวันนี้) → เห็นร้านได้แต่สั่งไม่ได้
        if (! $seller->isOpenNow()) {
            throw FreshMarketException::make('SHOP_CLOSED', 'ร้าน'.FreshMarketSeller::CLOSED_MESSAGE, 409);
        }

        // ห้ามซื้อสินค้าร้านตัวเอง (กันปั่น escrow/แคชแบ็ค)
        if ((int) $seller->user_id === (int) $buyer->id) {
            throw FreshMarketException::make('SELF_PURCHASE', 'ไม่สามารถสั่งซื้อสินค้าของร้านตัวเองได้', 403);
        }

        // รายการแรกใช้เป็นจุดรับของสำรอง (ร้านที่ยังไม่ปักหมุด) และคอลัมน์เดิมของออเดอร์
        $listing = FreshMarketListing::find($lines[0]['listing_id']);

        if (! $listing) {
            throw FreshMarketException::make('LISTING_UNAVAILABLE', 'สินค้านี้ไม่พร้อมขาย', 409);
        }

        if ((int) $listing->seller_id !== (int) $seller->id) {
            throw FreshMarketException::make('MIXED_SELLERS', 'สั่งได้ทีละร้าน กรุณาแยกออเดอร์ตามร้าน', 422);
        }

        $listing->setRelation('seller', $seller);

        // ร้านค้างค่า GP เกินเพดาน → ไม่รับเก็บเงินปลายทางชั่วคราว (จ่ายผ่าน wallet แล้วระบบหักหนี้ให้อัตโนมัติ)
        if ($paymentMethod === 'cod'
            && $seller->outstandingGpDebt() > (float) Setting::get('fresh_market.max_seller_gp_debt', 500)) {
            throw FreshMarketException::make('PAYMENT_METHOD_DISABLED', 'ร้านนี้รับเฉพาะชำระผ่าน Wallet ชั่วคราว', 422);
        }

        $deliveryFee = 0.0;
        $distance = null;
        $buyerLat = isset($data['buyer_latitude']) && is_numeric($data['buyer_latitude']) ? (float) $data['buyer_latitude'] : null;
        $buyerLng = isset($data['buyer_longitude']) && is_numeric($data['buyer_longitude']) ? (float) $data['buyer_longitude'] : null;
        $address = trim((string) ($data['delivery_address'] ?? ''));

        if ($deliveryType === 'rider') {
            if ($buyerLat === null || $buyerLng === null) {
                throw FreshMarketException::make('DELIVERY_LOCATION_REQUIRED', 'กรุณาปักหมุดตำแหน่งจัดส่ง', 422);
            }

            if ($address === '') {
                throw FreshMarketException::make('DELIVERY_ADDRESS_REQUIRED', 'กรุณากรอกที่อยู่จัดส่ง', 422);
            }

            $quote = $this->quoteDelivery($listing, $buyerLat, $buyerLng);

            if (! $quote['available']) {
                throw FreshMarketException::make($quote['code'] ?? 'RIDER_UNAVAILABLE', $quote['message'] ?? 'ส่งด้วยไรเดอร์ไม่ได้ในขณะนี้', 422);
            }

            $deliveryFee = round((float) $quote['total_fee'], 2);
            $distance = $quote['distance_km'];
        }

        $order = DB::transaction(function () use (
            $buyer, $seller, $lines, $deliveryType, $paymentMethod,
            $deliveryFee, $distance, $buyerLat, $buyerLng, $address, $data
        ) {
            // ตะกร้า: lock + ตรวจว่ารายการยังอยู่ครบ (กดชำระซ้ำ/สองแท็บพร้อมกัน → ครั้งที่สองไม่มีของในตะกร้าแล้ว)
            $cartItemIds = FreshMarketCartItem::normalizeOptionIds($data['cart_item_ids'] ?? []);

            if (! empty($cartItemIds)) {
                $cartRows = FreshMarketCartItem::whereIn('id', $cartItemIds)
                    ->where('user_id', $buyer->id)
                    ->lockForUpdate()
                    ->get();

                if ($cartRows->count() !== count($cartItemIds)) {
                    throw FreshMarketException::make('CART_CHANGED', 'ตะกร้ามีการเปลี่ยนแปลง กรุณาตรวจสอบตะกร้าอีกครั้ง', 409);
                }
            }

            // lock สินค้าทุกรายการ (เรียงตาม id กัน deadlock) → อ่านราคา/สต็อก/ตัวเลือกล่าสุด
            $listingIds = array_values(array_unique(array_column($lines, 'listing_id')));
            sort($listingIds);
            $lockedListings = FreshMarketListing::whereIn('id', $listingIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $qtyByListing = [];
            foreach ($lines as $line) {
                $qtyByListing[$line['listing_id']] = ($qtyByListing[$line['listing_id']] ?? 0) + $line['quantity'];
            }

            foreach ($listingIds as $listingId) {
                $locked = $lockedListings->get($listingId);

                if (! $locked) {
                    throw FreshMarketException::make('LISTING_UNAVAILABLE', 'มีสินค้าบางรายการไม่พร้อมขายแล้ว', 409);
                }

                if ((int) $locked->seller_id !== (int) $seller->id) {
                    throw FreshMarketException::make('MIXED_SELLERS', 'สั่งได้ทีละร้าน กรุณาแยกออเดอร์ตามร้าน', 422);
                }

                if (! $locked->isAvailableForPurchase()) {
                    throw FreshMarketException::make('LISTING_UNAVAILABLE', 'สินค้า "'.$locked->title.'" ไม่พร้อมขายแล้ว', 409);
                }

                if ($locked->tracksStock() && $locked->quantity_available < $qtyByListing[$listingId]) {
                    throw FreshMarketException::make(
                        'OUT_OF_STOCK',
                        'สินค้า "'.$locked->title.'" ไม่เพียงพอ (เหลือ '.$locked->quantity_available.' '.$locked->unit.')',
                        409
                    );
                }

                $locked->setRelation('seller', $seller);
            }

            // ราคาต่อบรรทัด = ราคาสินค้า + ตัวเลือก (ตรวจกติกาตัวเลือกทุกบรรทัด) → GP + แคชแบ็คต่อบรรทัด
            $optionService = app(FreshMarketOptionService::class);
            $resolved = [];
            $gpRates = [];
            $totalAmount = 0.0;
            $platformFee = 0.0;
            $cashback = 0.0;

            foreach ($lines as $line) {
                $locked = $lockedListings->get($line['listing_id']);
                $priced = $optionService->resolveLine(
                    $locked,
                    $line['option_ids'],
                    $line['quantity'],
                    $line['note'],
                    (bool) ($data['apply_default_options'] ?? false)
                );

                $rate = $gpRates[$locked->id] ??= $this->gpRateFor($locked);
                $lineGp = round($priced['line_total'] * $rate / 100, 2);
                $lineCashback = $this->computeCashback($locked, $line['quantity'], $priced['line_total'], $lineGp);

                $resolved[] = $priced + [
                    'listing' => $locked,
                    'gp_rate' => $rate,
                    'platform_fee' => $lineGp,
                    'cashback_amount' => $lineCashback,
                ];

                $totalAmount += $priced['line_total'];
                $platformFee += $lineGp;
                $cashback += $lineCashback;
            }

            $totalAmount = round($totalAmount, 2);
            $platformFee = round($platformFee, 2);
            $cashback = round(min($cashback, $platformFee), 2);
            $first = $resolved[0];

            // COD ส่งด้วยไรเดอร์: ยอดที่ไรเดอร์ต้องเก็บต้องไม่เกินวงเงิน COD ต่องาน
            // (ตรวจตั้งแต่ตอนสั่ง — ไม่งั้นร้านเตรียมของเสร็จแล้วเรียกไรเดอร์ไม่ได้ ออเดอร์ค้างที่ READY)
            if ($paymentMethod === 'cod' && $deliveryType === 'rider') {
                $codLimit = app(DeliveryFeeCalculator::class)->maxCodAmount();
                $codTotal = round($totalAmount + $deliveryFee, 2);

                if ($codTotal > $codLimit) {
                    throw FreshMarketException::make(
                        'COD_NOT_AVAILABLE',
                        $codLimit > 0
                            ? 'ยอดรวม '.number_format($codTotal, 2).' บาท เกินวงเงินเก็บเงินปลายทาง '.number_format($codLimit, 2).' บาท กรุณาชำระผ่าน Wallet หรือลดจำนวนสินค้า'
                            : 'ขณะนี้ปิดรับเก็บเงินปลายทางสำหรับการส่งด้วยไรเดอร์ กรุณาชำระผ่าน Wallet',
                        422
                    );
                }
            }

            // อัตรา GP ของออเดอร์: ทุกรายการอัตราเดียวกัน = อัตรานั้น, ต่างกัน = อัตราเฉลี่ยถ่วงน้ำหนัก
            $distinctRates = array_values(array_unique(array_map(fn ($r) => (string) $r, $gpRates)));
            $gpRate = count($distinctRates) === 1
                ? (float) $distinctRates[0]
                : ($totalAmount > 0 ? round($platformFee / $totalAmount * 100, 2) : 0.0);
            $sellerEarning = round($totalAmount - $platformFee, 2);

            // คอลัมน์เดิม listing_id/quantity/unit_price = รายการแรก (หน้าเก่ายังแสดงได้) · total_amount = ยอดสินค้ารวมทุกรายการ
            $order = new FreshMarketOrder([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'listing_id' => $first['listing_id'],
                'quantity' => $first['quantity'],
                'unit_price' => $first['unit_price'],
                'total_amount' => $totalAmount,
                'platform_fee' => $platformFee,
                'gp_rate' => $gpRate,
                'seller_earning' => $sellerEarning,
                'refunded_amount' => 0,
                'delivery_type' => $deliveryType,
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $distance,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'order_status' => FreshMarketOrder::STATUS_PENDING,
                'escrow_status' => null,
                'buyer_latitude' => $buyerLat,
                'buyer_longitude' => $buyerLng,
                'delivery_address' => $address !== '' ? mb_substr($address, 0, 500) : null,
                'delivery_notes' => isset($data['delivery_notes']) ? mb_substr((string) $data['delivery_notes'], 0, 500) : null,
                'cashback_amount' => $cashback,
            ]);
            $order->appendHistory([
                'action' => 'create',
                'to' => FreshMarketOrder::STATUS_PENDING,
                'by' => 'buyer',
                'user_id' => $buyer->id,
                'meta' => [
                    'payment_method' => $paymentMethod,
                    'channel' => $data['channel'] ?? null,
                    'lines' => count($resolved),
                ],
            ]);
            $order->save();

            // รายการสินค้า (snapshot ชื่อ/ตัวเลือก/ราคา ณ ตอนสั่ง)
            $itemModels = [];
            foreach ($resolved as $row) {
                /** @var FreshMarketListing $rowListing */
                $rowListing = $row['listing'];

                $itemModels[] = $order->items()->create([
                    'listing_id' => $rowListing->id,
                    'title' => mb_substr((string) $rowListing->title, 0, 255),
                    'unit' => $rowListing->unit ? mb_substr((string) $rowListing->unit, 0, 30) : null,
                    'image_url' => $row['image_url'] ?? $rowListing->primary_image,
                    'quantity' => $row['quantity'],
                    'base_price' => $row['base_price'],
                    'options_price' => $row['options_price'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                    'selected_options' => $row['selected_options'],
                    'note' => $row['note'],
                    'track_stock' => $rowListing->tracksStock(),
                    'stock_deducted' => $rowListing->tracksStock(),
                    'gp_rate' => $row['gp_rate'],
                    'platform_fee' => $row['platform_fee'],
                    'cashback_amount' => $row['cashback_amount'],
                ]);
            }
            $order->setRelation('items', new \Illuminate\Database\Eloquent\Collection($itemModels));

            // ตัดสต็อกแบบมีเงื่อนไข (เฉพาะสินค้าที่ตัดสต็อก) — สองคนแย่งชิ้นสุดท้าย สำเร็จคนเดียว
            foreach ($qtyByListing as $listingId => $qty) {
                $locked = $lockedListings->get($listingId);

                if ($locked->tracksStock()) {
                    if (! FreshMarketListing::reserveStock((int) $listingId, (int) $qty)) {
                        throw FreshMarketException::make('OUT_OF_STOCK', 'สินค้า "'.$locked->title.'" ไม่เพียงพอ', 409);
                    }
                } else {
                    // ทำตามสั่ง: ไม่แตะสต็อก แค่นับยอดสั่ง (ใช้เรียงสินค้ายอดนิยม)
                    FreshMarketListing::whereKey($listingId)->increment('order_count');
                }
            }

            // ชำระผ่าน wallet: หักเงินจริงก่อน แล้วค่อยถือว่า escrow
            if ($paymentMethod === 'wallet') {
                $this->captureWalletPayment($order, $buyer);
            }

            // สั่งสำเร็จ → ล้างรายการตะกร้าที่ใช้สั่ง (ธุรกรรมเดียวกัน: สั่งไม่สำเร็จ ตะกร้ายังอยู่)
            if (! empty($cartItemIds)) {
                FreshMarketCartItem::whereIn('id', $cartItemIds)->where('user_id', $buyer->id)->delete();
            }

            return $order;
        });

        Log::info('FreshMarket: สร้าง order สำเร็จ', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'lines' => count($lines),
            'payment_method' => $order->payment_method,
        ]);

        $this->notifier->sellerNewOrder($order);

        return $order->fresh(['listing', 'seller', 'items']) ?? $order;
    }

    /**
     * ตรวจ/จัดรูปรายการสั่งซื้อจาก client (ยังไม่ดูราคา — ราคาคำนวณใน transaction)
     *
     * @return array<int, array{listing_id: int, quantity: int, option_ids: array<int,int>, note: ?string}>
     *
     * @throws FreshMarketException CART_EMPTY | TOO_MANY_ITEMS | INVALID_ITEM | INVALID_QUANTITY
     */
    protected function normalizeOrderLines(array $items): array
    {
        $items = array_values(array_filter($items, 'is_array'));

        if (empty($items)) {
            throw FreshMarketException::make('CART_EMPTY', 'ยังไม่มีสินค้าในรายการสั่งซื้อ', 422);
        }

        if (count($items) > self::MAX_ORDER_LINES) {
            throw FreshMarketException::make('TOO_MANY_ITEMS', 'สั่งได้สูงสุด '.self::MAX_ORDER_LINES.' รายการต่อออเดอร์', 422);
        }

        $lines = [];

        foreach ($items as $item) {
            $listingId = (int) ($item['listing_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($listingId < 1) {
                throw FreshMarketException::make('INVALID_ITEM', 'รายการสินค้าไม่ถูกต้อง', 422);
            }

            if ($quantity < 1 || $quantity > 999) {
                throw FreshMarketException::make('INVALID_QUANTITY', 'จำนวนสินค้าไม่ถูกต้อง (1-999)', 422);
            }

            $note = isset($item['note']) && is_scalar($item['note']) ? trim((string) $item['note']) : '';

            $lines[] = [
                'listing_id' => $listingId,
                'quantity' => $quantity,
                'option_ids' => FreshMarketCartItem::normalizeOptionIds($item['option_ids'] ?? []),
                'note' => $note !== '' ? mb_substr($note, 0, 255) : null,
            ];
        }

        return $lines;
    }

    /**
     * หักเงินผู้ซื้อจาก wallet (ต้องอยู่ใน transaction ของการสร้างออเดอร์)
     *
     * @throws FreshMarketException WALLET_INACTIVE | INSUFFICIENT_BALANCE
     */
    protected function captureWalletPayment(FreshMarketOrder $order, User $buyer): void
    {
        $amount = $order->grand_total;

        if ($amount <= 0) {
            return;
        }

        $wallet = $this->lockActiveWallet($buyer);

        if ((float) $wallet->balance < $amount) {
            throw FreshMarketException::make(
                'INSUFFICIENT_BALANCE',
                'ยอดเงินใน Wallet ไม่เพียงพอ (ต้องชำระ ฿'.number_format($amount, 2)
                    .' คงเหลือ ฿'.number_format((float) $wallet->balance, 2).')',
                422
            );
        }

        $this->wallets->deductForService(
            $wallet,
            $amount,
            'ชำระค่าสินค้าตลาดสด #'.$order->order_number,
            self::REF_PAYMENT,
            $order->id,
            ['order_number' => $order->order_number, 'items' => $order->riderItemsSummary()]
        );

        $order->payment_status = 'paid';
        $order->escrow_status = 'held';
        $order->paid_at = now();
        $order->save();
    }

    /**
     * คำนวณแคชแบ็ค (จ่ายจาก GP จึงไม่เกิน GP)
     */
    protected function computeCashback(FreshMarketListing $listing, int $quantity, float $total, float $platformFee): float
    {
        if (! $this->settings()->cashback_enabled || $platformFee <= 0) {
            return 0.0;
        }

        $cashback = 0.0;

        if ((float) $listing->cashback_amount > 0) {
            $cashback = (float) $listing->cashback_amount * $quantity;
        } elseif ((float) $listing->cashback_percentage > 0) {
            $cashback = $total * ((float) $listing->cashback_percentage / 100);
        }

        return round(min(max(0, $cashback), $platformFee), 2);
    }

    // ╔══════════════════════════════════════════╗
    // ║  เปลี่ยนสถานะออเดอร์                     ║
    // ╚══════════════════════════════════════════╝

    /**
     * ทำ action กับออเดอร์ (จุดเดียวที่ เว็บ / API / LINE / แอดมิน เรียก)
     *
     * @param  string  $action  accept|prepare|ready|handover|confirm|complete|cancel (deliver = handover)
     * @param  string  $role  buyer|seller|admin|system
     * @param  array  $payload  reason (ยกเลิก)
     *
     * @throws FreshMarketException
     */
    public function applyAction(FreshMarketOrder $order, string $action, string $role, ?User $actor = null, array $payload = []): FreshMarketOrder
    {
        $action = $action === 'deliver' ? 'handover' : $action;

        if (! in_array($role, ['buyer', 'seller', 'admin', 'system'], true)) {
            throw FreshMarketException::make('FORBIDDEN', 'ไม่มีสิทธิ์ทำรายการนี้', 403);
        }

        return match ($action) {
            'accept', 'prepare', 'ready', 'handover' => $this->stepTransition($order, $action, $role, $actor),
            'cancel' => $this->cancelOrder($order, (string) ($payload['reason'] ?? ''), $role, $actor),
            // ยืนยันรับของ = ผู้ซื้อเท่านั้น / ปิดออเดอร์แทน = แอดมินหรือระบบเท่านั้น
            'confirm' => $role === 'buyer'
                ? $this->completeOrder($order, 'buyer', $actor)
                : throw FreshMarketException::make('FORBIDDEN', 'เฉพาะผู้ซื้อที่ยืนยันรับสินค้าได้', 403),
            'complete' => in_array($role, ['admin', 'system'], true)
                ? $this->completeOrder($order, $role, $actor)
                : throw FreshMarketException::make('FORBIDDEN', 'ไม่มีสิทธิ์ปิดออเดอร์', 403),
            default => throw FreshMarketException::make('INVALID_ACTION', 'ไม่รู้จักคำสั่งนี้', 422),
        };
    }

    /**
     * ผู้ขายรับออเดอร์ (คงไว้เพื่อความเข้ากันได้)
     */
    public function acceptOrder(FreshMarketOrder $order, ?User $actor = null): FreshMarketOrder
    {
        return $this->stepTransition($order, 'accept', 'seller', $actor);
    }

    /**
     * ขั้นตอนฝั่งร้าน: รับ → เตรียม → พร้อม → (นัดรับ) ส่งมอบ
     */
    protected function stepTransition(FreshMarketOrder $order, string $action, string $role, ?User $actor): FreshMarketOrder
    {
        $target = FreshMarketOrder::ACTIONS[$action]['to'];

        [$locked, $changed] = DB::transaction(function () use ($order, $action, $role, $actor, $target) {
            $locked = $this->lockOrder($order);

            // กดซ้ำ → ไม่ทำซ้ำ
            if ($locked->order_status === $target) {
                return [$locked, false];
            }

            $this->assertCanTransition($locked, $action, $role);

            $from = $locked->order_status;
            $locked->order_status = $target;
            $now = now();

            if ($action === 'accept') {
                $locked->accepted_at = $now;
            } elseif ($action === 'prepare') {
                $locked->preparing_at = $now;
            } elseif ($action === 'ready') {
                $locked->ready_at = $now;
            } elseif ($action === 'handover') {
                $locked->delivered_at = $now;
            }

            $locked->appendHistory([
                'action' => $action,
                'from' => $from,
                'to' => $target,
                'by' => $role,
                'user_id' => $actor?->id,
            ]);
            $locked->save();

            return [$locked, true];
        });

        if ($changed) {
            $this->notifier->statusChanged($locked, $action, $role);

            $shouldDispatch = $locked->delivery_type === 'rider' && (
                $action === 'ready'
                || ($action === 'accept' && (bool) Setting::get('fresh_market.rider_dispatch_on_accept', false))
            );

            if ($shouldDispatch) {
                $this->dispatchRider($locked);
            }
        }

        return $locked->fresh() ?? $locked;
    }

    /**
     * ยกเลิกออเดอร์ + คืนสต็อก (ถ้ายังไม่ส่งมอบ) + คืนเงินเฉพาะที่เก็บมาแล้วจริง
     *
     * @param  string  $cancelledBy  buyer|seller|admin|system
     *
     * @throws FreshMarketException
     */
    public function cancelOrder(FreshMarketOrder $order, string $reason, string $cancelledBy = 'system', ?User $actor = null): FreshMarketOrder
    {
        if (! in_array($cancelledBy, ['buyer', 'seller', 'admin', 'system'], true)) {
            throw FreshMarketException::make('FORBIDDEN', 'ไม่มีสิทธิ์ยกเลิกออเดอร์นี้', 403);
        }

        $role = $cancelledBy;
        $reason = mb_substr(trim($reason), 0, 500);

        if ($reason === '') {
            $reason = match ($role) {
                'buyer' => 'ผู้ซื้อยกเลิก',
                'seller' => 'ร้านค้ายกเลิก',
                'admin' => 'แอดมินยกเลิก',
                default => 'ระบบยกเลิกอัตโนมัติ',
            };
        }

        [$locked, $changed, $refunded] = DB::transaction(function () use ($order, $reason, $role, $actor) {
            $locked = $this->lockOrder($order);

            if ($locked->order_status === FreshMarketOrder::STATUS_CANCELLED) {
                return [$locked, false, 0.0];
            }

            $this->assertCanTransition($locked, 'cancel', $role);

            $from = $locked->order_status;
            $locked->order_status = FreshMarketOrder::STATUS_CANCELLED;
            $locked->cancelled_at = now();
            $locked->cancel_reason = $reason;
            $locked->cancelled_by = $role;

            $refunded = $this->refundCollectedFunds($locked, $reason, $role, $actor);

            $locked->appendHistory([
                'action' => 'cancel',
                'from' => $from,
                'to' => FreshMarketOrder::STATUS_CANCELLED,
                'by' => $role,
                'user_id' => $actor?->id,
                'reason' => $reason,
                'meta' => $refunded > 0 ? ['refunded' => $refunded] : [],
            ]);
            $locked->save();

            // คืนสต็อกเฉพาะที่ของยังอยู่กับร้าน
            $stillAtShop = in_array($from, [
                FreshMarketOrder::STATUS_PENDING, FreshMarketOrder::STATUS_ACCEPTED,
                FreshMarketOrder::STATUS_PREPARING, FreshMarketOrder::STATUS_READY,
            ], true);

            if ($stillAtShop) {
                $this->restoreOrderStock($locked);
            }

            return [$locked, true, $refunded];
        });

        if ($changed) {
            if ($locked->delivery_type === 'rider') {
                $this->cancelRiderJobs($locked, $role, $reason);
            }

            $this->notifier->statusChanged($locked, 'cancel', $role, ['refunded' => $refunded]);

            Log::info('FreshMarket: ยกเลิก order', [
                'order_id' => $locked->id,
                'by' => $role,
                'actor_id' => $actor?->id,
                'refunded' => $refunded,
            ]);
        }

        return $locked;
    }

    /**
     * คืนเงินที่ระบบถือไว้ให้ผู้ซื้อ (เรียกภายใน transaction ที่ lock ออเดอร์แล้ว)
     *
     * - wallet: คืนยอดที่หักไปจริง (หักค่าส่งออกถ้าไรเดอร์ส่งสำเร็จและได้ค่าส่งไปแล้ว)
     * - cod ที่ไรเดอร์เก็บเงินแล้ว (escrow held): คืนค่าสินค้า
     * - cod ที่ยังไม่เก็บเงิน: ไม่คืนอะไร (ไม่มีเงินในระบบ)
     *
     * @return float ยอดที่คืนจริง
     */
    protected function refundCollectedFunds(FreshMarketOrder $locked, string $reason, string $role, ?User $actor): float
    {
        // COD ส่งด้วยไรเดอร์ที่ส่งถึงแล้ว: ผู้ซื้อจ่ายเงินสดให้ไรเดอร์ไปแล้ว แต่ไรเดอร์ยังนำส่งเข้าระบบไม่ได้
        // → ยังคืนเงินไม่ได้ (ระบบไม่มีเงินก้อนนี้) และห้ามยกเลิกเงียบๆ ทั้งที่ผู้ซื้อจ่ายไปแล้ว
        if ($locked->payment_method === 'cod' && $locked->delivery_type === 'rider'
            && $this->riderJobCompleted($locked) && ! $this->riderCodSettled($locked)) {
            throw FreshMarketException::make(
                'PAYMENT_NOT_COLLECTED',
                'ไรเดอร์ยังไม่ได้นำส่งเงินเก็บปลายทางเข้าระบบ จึงยกเลิกและคืนเงินไม่ได้ในตอนนี้ กรุณารอให้ระบบรับยอดจากไรเดอร์ก่อน',
                409
            );
        }

        if ($locked->payment_status !== 'paid' || $locked->escrow_status !== 'held') {
            return 0.0;
        }

        $alreadyRefunded = $this->walletTxSum(self::REF_REFUND, (int) $locked->id, (int) $locked->buyer_id);

        if ($locked->payment_method === 'cod') {
            $collected = round((float) $locked->total_amount, 2);
        } else {
            $collected = $locked->walletPaidAmount();

            // ไรเดอร์ส่งสำเร็จแล้ว → ค่าส่งถูกจ่ายให้ไรเดอร์ไปแล้ว ไม่คืน
            if ($this->riderJobCompleted($locked)) {
                $collected = round($collected - (float) $locked->delivery_fee, 2);
            }
        }

        $refund = round(max(0, $collected - $alreadyRefunded), 2);

        if ($refund > 0) {
            $buyer = $locked->buyer;

            if (! $buyer) {
                throw FreshMarketException::make('BUYER_NOT_FOUND', 'ไม่พบบัญชีผู้ซื้อสำหรับคืนเงิน', 409);
            }

            $wallet = $this->wallets->getOrCreateWallet($buyer);

            if (! $wallet->isActive()) {
                throw FreshMarketException::make(
                    'WALLET_INACTIVE',
                    'กระเป๋าเงินผู้ซื้อถูกระงับ ระบบคืนเงินอัตโนมัติไม่ได้ กรุณาติดต่อแอดมิน',
                    409
                );
            }

            $this->wallets->deposit(
                $wallet,
                $refund,
                'คืนเงินตลาดสด #'.$locked->order_number,
                self::REF_REFUND,
                $locked->id,
                ['reason' => $reason, 'by' => $role, 'actor_id' => $actor?->id]
            );
        }

        $locked->payment_status = 'refunded';
        $locked->escrow_status = 'refunded';
        $locked->refunded_amount = round($alreadyRefunded + $refund, 2);

        return $refund;
    }

    /**
     * ปิดออเดอร์ (ผู้ซื้อยืนยันรับ / แอดมิน / ระบบ) + ปล่อยเงินครั้งเดียว
     *
     * @param  string  $by  buyer|admin|system
     *
     * @throws FreshMarketException
     */
    public function completeOrder(FreshMarketOrder $order, string $by = 'buyer', ?User $actor = null): FreshMarketOrder
    {
        if (! in_array($by, ['buyer', 'admin', 'system'], true)) {
            throw FreshMarketException::make('FORBIDDEN', 'ไม่มีสิทธิ์ปิดออเดอร์', 403);
        }

        $role = $by;
        $action = $role === 'buyer' ? 'confirm' : 'complete';

        [$locked, $changed, $summary] = DB::transaction(function () use ($order, $role, $action, $actor) {
            $locked = $this->lockOrder($order);

            if ($locked->order_status === FreshMarketOrder::STATUS_COMPLETED) {
                return [$locked, false, []];
            }

            $this->assertCanTransition($locked, $action, $role);

            // COD ส่งไรเดอร์: ต้องหักเงินที่ไรเดอร์เก็บเข้าระบบเรียบร้อยก่อน จึงจะโอนให้ร้าน (ไม่จ่ายเงินที่ยังไม่มีจริง)
            if ($locked->payment_method === 'cod' && $locked->delivery_type === 'rider' && ! $this->riderCodSettled($locked)) {
                throw FreshMarketException::make(
                    'PAYMENT_NOT_COLLECTED',
                    'ระบบกำลังรับยอดเงินเก็บปลายทางจากไรเดอร์ กรุณาลองใหม่อีกครั้งในอีกสักครู่',
                    409
                );
            }

            // ต้องมีเงินจริงก่อนปิด: wallet ต้องถูกหักแล้ว / COD ส่งไรเดอร์ต้องเก็บเงินได้แล้ว
            // (COD นัดรับ: ร้านกดส่งมอบ = ยืนยันรับเงินสดแล้ว — ACTIONS บังคับให้ผ่าน handover ก่อน)
            $platformHolds = $locked->payment_status === 'paid' && $locked->escrow_status === 'held';
            $codPickup = $locked->payment_method === 'cod' && $locked->delivery_type === 'pickup';

            if (! $platformHolds && ! $codPickup) {
                throw FreshMarketException::make(
                    'PAYMENT_NOT_COLLECTED',
                    'ออเดอร์นี้ยังไม่ได้รับชำระเงินเข้าระบบ จึงปิดออเดอร์ไม่ได้ กรุณาติดต่อแอดมิน',
                    409
                );
            }

            $from = $locked->order_status;
            $now = now();
            $locked->order_status = FreshMarketOrder::STATUS_COMPLETED;
            $locked->completed_at = $now;
            $locked->delivered_at = $locked->delivered_at ?? $now;

            if ($role === 'buyer') {
                $locked->buyer_confirmed_at = $now;
            }

            $summary = $this->settleCompletedOrder($locked);

            $locked->appendHistory([
                'action' => $action,
                'from' => $from,
                'to' => FreshMarketOrder::STATUS_COMPLETED,
                'by' => $role,
                'user_id' => $actor?->id,
                'meta' => array_filter($summary, fn ($v) => (float) $v > 0),
            ]);
            $locked->save();

            return [$locked, true, $summary];
        });

        if ($changed) {
            try {
                $locked->seller?->refreshStats();
            } catch (\Throwable $e) {
                Log::warning('FreshMarket: อัปเดตสถิติร้านล้มเหลว', ['order_id' => $locked->id, 'error' => $e->getMessage()]);
            }

            $this->notifier->statusChanged($locked, $action, $role, $summary);

            Log::info('FreshMarket: order เสร็จสิ้น', ['order_id' => $locked->id, 'by' => $role] + $summary);
        }

        return $locked;
    }

    /**
     * จ่ายเงินตอนปิดออเดอร์ (ภายใน transaction ที่ lock ออเดอร์แล้ว — idempotent ทุกขา)
     *
     * @return array{payout: float, gp_collected: float, gp_debt: float, debt_collected: float, cashback_paid: float, referral_paid: float}
     */
    protected function settleCompletedOrder(FreshMarketOrder $locked): array
    {
        $summary = [
            'payout' => 0.0,
            'gp_collected' => 0.0,
            'gp_debt' => 0.0,
            'debt_collected' => 0.0,
            'cashback_paid' => 0.0,
            'referral_paid' => 0.0,
        ];

        $gp = round((float) $locked->platform_fee, 2);
        $net = round((float) $locked->seller_earning, 2);
        $sellerUser = $locked->seller?->user;

        if (! $sellerUser) {
            throw FreshMarketException::make('SELLER_NOT_FOUND', 'ไม่พบบัญชีผู้ขาย ปิดออเดอร์ไม่ได้ กรุณาติดต่อแอดมิน', 409);
        }

        $gpReceived = false;

        if ($locked->payment_status === 'paid' && $locked->escrow_status === 'held') {
            // 1) ระบบถือเงินไว้ → หักหนี้ GP ค้างของร้าน (ถ้ามี) → โอนส่วนที่เหลือให้ร้าน
            $debtCollected = $net > 0 ? $this->collectSellerGpDebt($sellerUser, $net, $locked) : 0.0;
            $payout = round($net - $debtCollected, 2);

            if ($payout > 0 && ! $this->walletTxExists(self::REF_PAYOUT, (int) $locked->id, (int) $sellerUser->id)) {
                $this->wallets->deposit(
                    $this->wallets->getOrCreateWallet($sellerUser),
                    $payout,
                    'รายได้ขายสินค้าตลาดสด #'.$locked->order_number.' (หัก GP แล้ว)',
                    self::REF_PAYOUT,
                    $locked->id,
                    ['gross' => (float) $locked->total_amount, 'gp' => $gp, 'debt_collected' => $debtCollected]
                );
            }

            // 2) GP เข้ากระเป๋าค่าธรรมเนียมแพลตฟอร์ม
            if ($gp > 0) {
                $this->creditPlatform($gp, self::PLATFORM_GP, $locked, ['gp_rate' => (float) $locked->gp_rate]);
            }

            $locked->escrow_status = 'released';
            $summary['payout'] = $payout;
            $summary['debt_collected'] = $debtCollected;
            $summary['gp_collected'] = $gp;
            $gpReceived = true;
        } else {
            // COD นัดรับ: ร้านรับเงินสดเต็มจากผู้ซื้อ → เก็บ GP จาก wallet ร้าน (ไม่พอ = บันทึกหนี้)
            $locked->payment_status = 'paid';
            $locked->paid_at = $locked->paid_at ?? now();

            if ($gp > 0) {
                if ($this->chargeSellerGpForCod($sellerUser, $gp, $locked)) {
                    $summary['gp_collected'] = $gp;
                    $gpReceived = true;
                } else {
                    $summary['gp_debt'] = $gp;
                }
            } else {
                $gpReceived = true;
            }
        }

        // 3) แคชแบ็ค + ค่าแนะนำ จ่ายจาก GP ที่ได้รับจริงเท่านั้น
        if ($gpReceived) {
            $summary['cashback_paid'] = $this->payCashback($locked, $gp);
            $summary['referral_paid'] = $this->payReferralFee($locked, round($gp - $summary['cashback_paid'], 2));
        }

        return $summary;
    }

    /**
     * หักค่า GP จาก wallet ร้าน (COD นัดรับ) — ไม่พอให้บันทึกเป็นหนี้
     *
     * @return bool true = เก็บ GP เข้าแพลตฟอร์มได้แล้ว, false = บันทึกเป็นหนี้
     */
    protected function chargeSellerGpForCod(User $sellerUser, float $gp, FreshMarketOrder $locked): bool
    {
        if ($this->walletTxExists(self::REF_COD_GP, (int) $locked->id, (int) $sellerUser->id)) {
            return true;
        }

        $debtExists = WalletDebt::where('source_type', self::DEBT_SOURCE_GP)
            ->where('source_id', $locked->id)
            ->exists();

        if ($debtExists) {
            return false;
        }

        $wallet = $this->wallets->getOrCreateWallet($sellerUser);
        $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

        if ($lockedWallet && $lockedWallet->isActive() && (float) $lockedWallet->balance >= $gp) {
            $this->wallets->deductForService(
                $lockedWallet,
                $gp,
                'ค่า GP ตลาดสด (เก็บเงินปลายทาง) #'.$locked->order_number,
                self::REF_COD_GP,
                $locked->id,
                ['gp_rate' => (float) $locked->gp_rate]
            );

            $this->creditPlatform($gp, self::PLATFORM_GP, $locked, ['source' => 'cod_seller_wallet']);

            return true;
        }

        WalletDebt::createDebt(
            (int) $sellerUser->id,
            $gp,
            self::DEBT_SOURCE_GP,
            (int) $locked->id,
            'ค่า GP ตลาดสด (เก็บเงินปลายทาง) #'.$locked->order_number,
            null,
            1,
            ['order_number' => $locked->order_number, 'gp_rate' => (float) $locked->gp_rate]
        );

        return false;
    }

    /**
     * หักหนี้ค่า GP ค้างของร้านจากเงินที่กำลังจะโอนให้ร้าน
     *
     * @return float ยอดหนี้ที่หักได้ (เข้ากระเป๋า fee แพลตฟอร์ม)
     */
    protected function collectSellerGpDebt(User $sellerUser, float $available, FreshMarketOrder $locked): float
    {
        $debts = WalletDebt::active()
            ->forUser((int) $sellerUser->id)
            ->where('source_type', self::DEBT_SOURCE_GP)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        $collected = 0.0;
        $debtIds = [];

        foreach ($debts as $debt) {
            $room = round($available - $collected, 2);

            if ($room <= 0) {
                break;
            }

            $take = min((float) $debt->remaining_amount, $room);

            if ($take <= 0) {
                continue;
            }

            $collected += (float) $debt->deduct($take);
            $debtIds[] = (int) $debt->id;
        }

        $collected = round($collected, 2);

        if ($collected > 0) {
            $this->creditPlatform($collected, self::PLATFORM_GP_DEBT, $locked, ['debt_ids' => $debtIds]);
        }

        return $collected;
    }

    /**
     * จ่ายแคชแบ็คให้ผู้ซื้อ (เงินมาจาก GP ในกระเป๋า fee) — ล้มได้โดยไม่กระทบการปิดออเดอร์
     */
    protected function payCashback(FreshMarketOrder $locked, float $gp): float
    {
        $amount = round(min((float) $locked->cashback_amount, $gp), 2);

        if (! $this->settings()->cashback_enabled || $amount <= 0 || $locked->cashback_processed) {
            return 0.0;
        }

        $buyer = $locked->buyer;

        if (! $buyer || $this->walletTxExists(self::REF_CASHBACK, (int) $locked->id, (int) $buyer->id)) {
            $locked->cashback_processed = true;

            return 0.0;
        }

        try {
            DB::transaction(function () use ($locked, $buyer, $amount) {
                $this->debitPlatform($amount, self::PLATFORM_CASHBACK, $locked, ['buyer_id' => $buyer->id]);
                $this->wallets->deposit(
                    $this->wallets->getOrCreateWallet($buyer),
                    $amount,
                    'แคชแบ็คตลาดสด #'.$locked->order_number,
                    self::REF_CASHBACK,
                    $locked->id
                );
            });

            $locked->cashback_processed = true;

            return $amount;
        } catch (\Throwable $e) {
            Log::error('FreshMarket: จ่ายแคชแบ็คล้มเหลว (ข้ามไป)', ['order_id' => $locked->id, 'error' => $e->getMessage()]);

            return 0.0;
        }
    }

    /**
     * ค่าแนะนำเพื่อน: ผู้ซื้อที่มาจากลิงก์แนะนำ สั่งซื้อสำเร็จครั้งแรก → ผู้แนะนำได้ค่าแนะนำคงที่
     * (Setting fresh_market.referral_fee_amount, 0 = ปิด) ไม่เกินงบ GP ที่เหลือของออเดอร์นี้
     */
    protected function payReferralFee(FreshMarketOrder $locked, float $budget): float
    {
        $configured = (float) Setting::get('fresh_market.referral_fee_amount', 0);

        if ($configured <= 0 || $budget <= 0) {
            return 0.0;
        }

        $referral = FreshMarketReferral::where('referred_user_id', $locked->buyer_id)
            ->where('status', 'followed')
            ->where('commission_earned', '<=', 0)
            ->lockForUpdate()
            ->first();

        if (! $referral || (int) $referral->referrer_user_id === (int) $locked->buyer_id) {
            return 0.0;
        }

        $referrer = User::find($referral->referrer_user_id);

        if (! $referrer || $this->walletTxExists(self::REF_REFERRAL, (int) $referral->id, (int) $referrer->id)) {
            return 0.0;
        }

        $fee = round(min($configured, $budget), 2);

        try {
            DB::transaction(function () use ($locked, $referral, $referrer, $fee) {
                $this->debitPlatform($fee, self::PLATFORM_REFERRAL, $locked, ['referral_id' => $referral->id]);
                $this->wallets->deposit(
                    $this->wallets->getOrCreateWallet($referrer),
                    $fee,
                    'ค่าแนะนำเพื่อนตลาดสด (ออเดอร์ #'.$locked->order_number.')',
                    self::REF_REFERRAL,
                    $referral->id,
                    ['order_id' => $locked->id]
                );
                $referral->update(['commission_earned' => $fee, 'status' => 'converted']);
            });

            return $fee;
        } catch (\Throwable $e) {
            Log::error('FreshMarket: จ่ายค่าแนะนำล้มเหลว (ข้ามไป)', ['order_id' => $locked->id, 'error' => $e->getMessage()]);

            return 0.0;
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║  คะแนนรีวิว                               ║
    // ╚══════════════════════════════════════════╝

    /**
     * ผู้ซื้อให้คะแนนร้าน (+ ไรเดอร์ ถ้าส่งด้วยไรเดอร์)
     *
     * @throws FreshMarketException
     */
    public function rateOrder(
        FreshMarketOrder $order,
        User $buyer,
        int $rating,
        ?string $review = null,
        ?int $riderRating = null,
        ?string $riderReview = null
    ): FreshMarketOrder {
        if ((int) $order->buyer_id !== (int) $buyer->id) {
            throw FreshMarketException::make('FORBIDDEN', 'คุณไม่มีสิทธิ์ให้คะแนนออเดอร์นี้', 403);
        }

        if ($rating < 1 || $rating > 5 || ($riderRating !== null && ($riderRating < 1 || $riderRating > 5))) {
            throw FreshMarketException::make('INVALID_RATING', 'คะแนนต้องอยู่ระหว่าง 1-5', 422);
        }

        $fresh = $order->fresh();

        if (! $fresh || ! in_array($fresh->order_status, [FreshMarketOrder::STATUS_DELIVERED, FreshMarketOrder::STATUS_COMPLETED], true)) {
            throw FreshMarketException::make('NOT_RATEABLE', 'ให้คะแนนได้หลังได้รับสินค้าแล้วเท่านั้น', 409);
        }

        $updated = FreshMarketOrder::whereKey($fresh->id)
            ->whereNull('buyer_rating')
            ->update([
                'buyer_rating' => $rating,
                'buyer_review' => $review !== null && trim($review) !== '' ? mb_substr(trim($review), 0, 1000) : null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            throw FreshMarketException::make('ALREADY_RATED', 'คุณให้คะแนนออเดอร์นี้ไปแล้ว', 409);
        }

        $fresh->seller?->updateRating();

        if ($riderRating !== null && $fresh->rider_job_id) {
            $this->rateRider($fresh, $riderRating, $riderReview);
        }

        return $fresh->fresh();
    }

    /**
     * บันทึกคะแนนไรเดอร์ลงงาน + คำนวณคะแนนเฉลี่ยไรเดอร์ใหม่ (atomic)
     */
    protected function rateRider(FreshMarketOrder $order, int $rating, ?string $review): void
    {
        DB::transaction(function () use ($order, $rating, $review) {
            $job = RiderJob::whereKey($order->rider_job_id)->lockForUpdate()->first();

            if (! $job || ! $job->rider_id || $job->customer_rating !== null
                || ! in_array($job->status, ['delivered', 'completed'], true)) {
                return;
            }

            RiderJob::whereKey($job->id)->update([
                'customer_rating' => $rating,
                'customer_review' => $review !== null && trim($review) !== '' ? mb_substr(trim($review), 0, 1000) : null,
                'updated_at' => now(),
            ]);

            $rider = Rider::whereKey($job->rider_id)->lockForUpdate()->first();

            if (! $rider) {
                return;
            }

            $stats = RiderJob::where('rider_id', $rider->id)
                ->whereNotNull('customer_rating')
                ->selectRaw('AVG(customer_rating) as avg_rating, COUNT(*) as total')
                ->first();

            Rider::whereKey($rider->id)->update([
                'rating' => round((float) ($stats->avg_rating ?? 0), 2),
                'rating_count' => (int) ($stats->total ?? 0),
            ]);
        });
    }

    // ╔══════════════════════════════════════════╗
    // ║  ไรเดอร์                                  ║
    // ╚══════════════════════════════════════════╝

    /**
     * สร้างงานไรเดอร์ให้ออเดอร์ (ไม่ throw — ล้มเหลวจะแจ้งร้าน + แอดมิน ไม่ให้ค้างเงียบ)
     */
    public function dispatchRider(FreshMarketOrder $order): ?RiderJob
    {
        $order->loadMissing(['seller', 'listing']);
        $failReason = null;

        if (! $this->settings()->rider_enabled) {
            $failReason = 'ระบบไรเดอร์ปิดอยู่';
        } else {
            $pickup = $order->riderPickupPoint();

            if (empty($pickup['latitude']) || empty($pickup['longitude'])) {
                $failReason = 'ร้านยังไม่ได้ปักหมุดตำแหน่งร้าน กรุณาตั้งค่าตำแหน่งร้าน';
            } elseif (! $order->buyer_latitude || ! $order->buyer_longitude) {
                $failReason = 'ออเดอร์ไม่มีพิกัดจัดส่งของผู้ซื้อ';
            }
        }

        if ($failReason === null) {
            try {
                $job = app(RiderDispatchService::class)->createJobForSource($order, 'fresh_market');

                FreshMarketOrder::whereKey($order->id)->update(['rider_job_id' => $job->id]);
                $order->rider_job_id = $job->id;

                Log::info('FreshMarket: สร้างงานไรเดอร์สำเร็จ', ['order_id' => $order->id, 'job_id' => $job->id]);

                return $job;
            } catch (\App\Exceptions\RiderJobException $e) {
                // เหตุผลทางธุรกิจ (นอกพื้นที่, เกินวงเงิน COD, ออเดอร์ถูกยกเลิกแล้ว) → ข้อความไทยของระบบไรเดอร์
                Log::warning('FreshMarket: สร้างงานไรเดอร์ไม่ได้', ['order_id' => $order->id, 'code' => $e->errorCode]);
                $failReason = $e->getMessage();
            } catch (\Throwable $e) {
                Log::error('FreshMarket: สร้างงานไรเดอร์ล้มเหลว', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                $failReason = 'ระบบเรียกไรเดอร์ขัดข้องชั่วคราว';
            }
        }

        Log::warning('FreshMarket: เรียกไรเดอร์ไม่สำเร็จ', ['order_id' => $order->id, 'reason' => $failReason]);
        $this->notifier->riderDispatchFailed($order, $failReason);

        return null;
    }

    /**
     * แอดมินเรียกไรเดอร์ใหม่ (ออเดอร์ ready ที่ยังไม่มีไรเดอร์ / ส่งไม่สำเร็จ)
     *
     * @throws FreshMarketException
     */
    public function redispatchRider(FreshMarketOrder $order, ?User $admin = null): FreshMarketOrder
    {
        if ($order->delivery_type !== 'rider') {
            throw FreshMarketException::make('NOT_RIDER_ORDER', 'ออเดอร์นี้ไม่ได้ส่งด้วยไรเดอร์', 422);
        }

        $locked = DB::transaction(function () use ($order, $admin) {
            $locked = $this->lockOrder($order);

            if (! in_array($locked->order_status, [FreshMarketOrder::STATUS_READY, FreshMarketOrder::STATUS_DELIVERY_FAILED], true)) {
                throw FreshMarketException::make(
                    'INVALID_TRANSITION',
                    'เรียกไรเดอร์ใหม่ได้เฉพาะออเดอร์ที่พร้อมส่งหรือส่งไม่สำเร็จ',
                    409
                );
            }

            $activeJob = $locked->rider_job_id
                ? RiderJob::whereKey($locked->rider_job_id)
                    ->whereNotIn('status', ['completed', 'cancelled', 'failed'])
                    ->exists()
                : false;

            if ($activeJob) {
                throw FreshMarketException::make('RIDER_JOB_ACTIVE', 'ออเดอร์นี้มีงานไรเดอร์ที่ยังดำเนินอยู่แล้ว', 409);
            }

            $from = $locked->order_status;
            $locked->order_status = FreshMarketOrder::STATUS_READY;
            $locked->rider_job_id = null;
            $locked->rider_id = null;
            $locked->appendHistory([
                'action' => 'redispatch',
                'from' => $from,
                'to' => FreshMarketOrder::STATUS_READY,
                'by' => 'admin',
                'user_id' => $admin?->id,
            ]);
            $locked->save();

            return $locked;
        });

        if (! $this->dispatchRider($locked)) {
            throw FreshMarketException::make('DISPATCH_FAILED', 'เรียกไรเดอร์ไม่สำเร็จ ดูรายละเอียดในแจ้งเตือน', 422);
        }

        return $locked->fresh();
    }

    /**
     * ยกเลิกงานไรเดอร์ของออเดอร์ (หลัง commit)
     */
    protected function cancelRiderJobs(FreshMarketOrder $order, string $role, string $reason): void
    {
        try {
            app(RiderDispatchService::class)->cancelJobsForSource($order, $role, $reason);
        } catch (\Throwable $e) {
            Log::error('FreshMarket: ยกเลิกงานไรเดอร์ล้มเหลว', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║  งานอัตโนมัติ (scheduler)                ║
    // ╚══════════════════════════════════════════╝

    /**
     * ยกเลิกออเดอร์ pending ที่ร้านไม่รับเกินเวลา (คืนสต็อก + คืนเงิน)
     *
     * @return int จำนวนออเดอร์ที่ยกเลิก
     */
    public function expirePendingOrders(int $limit = 100): int
    {
        $minutes = max(5, (int) Setting::get('fresh_market.pending_expiry_minutes', 30));

        $ids = FreshMarketOrder::where('order_status', FreshMarketOrder::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $count = 0;

        foreach ($ids as $id) {
            $order = FreshMarketOrder::find($id);

            if (! $order) {
                continue;
            }

            try {
                $result = $this->cancelOrder($order, "ร้านไม่ได้ยืนยันออเดอร์ภายใน {$minutes} นาที ระบบยกเลิกให้อัตโนมัติ", 'system');

                if ($result->order_status === FreshMarketOrder::STATUS_CANCELLED) {
                    $count++;
                }
            } catch (FreshMarketException $e) {
                // สถานะเปลี่ยนไปแล้ว (ร้านเพิ่งกดรับ) → ข้าม
                Log::info('FreshMarket: ข้ามการยกเลิกอัตโนมัติ', ['order_id' => $id, 'code' => $e->errorCode()]);
            } catch (\Throwable $e) {
                Log::error('FreshMarket: ยกเลิกออเดอร์หมดเวลาล้มเหลว', ['order_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * ปิดออเดอร์ที่ส่งถึงแล้วแต่ผู้ซื้อไม่กดยืนยันเกินเวลา (ปล่อยเงินให้ร้าน)
     *
     * @return int จำนวนออเดอร์ที่ปิด
     */
    public function autoCompleteDeliveredOrders(int $limit = 100): int
    {
        $hours = max(1, (int) Setting::get('fresh_market.auto_complete_hours', 24));
        $cutoff = now()->subHours($hours);

        $ids = FreshMarketOrder::where('order_status', FreshMarketOrder::STATUS_DELIVERED)
            ->where(function ($q) use ($cutoff) {
                $q->where('delivered_at', '<=', $cutoff)
                    ->orWhere(fn ($q2) => $q2->whereNull('delivered_at')->where('updated_at', '<=', $cutoff));
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $count = 0;

        foreach ($ids as $id) {
            $order = FreshMarketOrder::find($id);

            if (! $order) {
                continue;
            }

            try {
                $result = $this->completeOrder($order, 'system');

                if ($result->order_status === FreshMarketOrder::STATUS_COMPLETED) {
                    $count++;
                }
            } catch (FreshMarketException $e) {
                Log::warning('FreshMarket: ปิดออเดอร์อัตโนมัติไม่ได้', ['order_id' => $id, 'code' => $e->errorCode(), 'message' => $e->getMessage()]);
            } catch (\Throwable $e) {
                Log::error('FreshMarket: ปิดออเดอร์อัตโนมัติล้มเหลว', ['order_id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    // ╔══════════════════════════════════════════╗
    // ║  Helpers                                 ║
    // ╚══════════════════════════════════════════╝

    /**
     * ดึงหมวดหมู่ทั้งหมด
     */
    public function getCategories(): Collection
    {
        return FreshMarketCategory::active()
            ->root()
            ->with('children')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * คืนสต็อกของออเดอร์ที่ถูกยกเลิก (เรียกภายใน transaction ที่ lock ออเดอร์แล้ว — สถานะ cancelled กันเรียกซ้ำ)
     *
     * - ออเดอร์หลายรายการ: คืนเฉพาะรายการที่ตัดสต็อกไปจริง (stock_deducted) รวมต่อสินค้า
     * - สินค้าทำตามสั่ง (ไม่ตัดสต็อก): ลดยอดสั่งกลับอย่างเดียว
     * - ออเดอร์เก่าก่อนมีตาราง items: คืนตามคอลัมน์ listing_id/quantity เดิม
     */
    protected function restoreOrderStock(FreshMarketOrder $locked): void
    {
        $items = $locked->items()->get();

        if ($items->isEmpty()) {
            if ($locked->listing_id) {
                FreshMarketListing::releaseStock((int) $locked->listing_id, (int) $locked->quantity);
            }

            return;
        }

        $restore = [];
        $untracked = [];

        foreach ($items as $item) {
            if (! $item->listing_id) {
                continue;
            }

            if ($item->stock_deducted) {
                $restore[$item->listing_id] = ($restore[$item->listing_id] ?? 0) + (int) $item->quantity;
            } else {
                $untracked[$item->listing_id] = true;
            }
        }

        foreach ($restore as $listingId => $qty) {
            FreshMarketListing::releaseStock((int) $listingId, (int) $qty);
        }

        foreach (array_keys($untracked) as $listingId) {
            if (isset($restore[$listingId])) {
                continue;
            }

            FreshMarketListing::withTrashed()
                ->whereKey($listingId)
                ->where('order_count', '>', 0)
                ->decrement('order_count');
        }

        // ตัดสต็อกคืนแล้ว → ไม่คืนซ้ำ
        FreshMarketOrderItem::where('order_id', $locked->id)->where('stock_deducted', true)->update(['stock_deducted' => false]);
    }

    /**
     * lock แถวออเดอร์ (ต้องอยู่ใน transaction)
     */
    protected function lockOrder(FreshMarketOrder $order): FreshMarketOrder
    {
        $locked = FreshMarketOrder::whereKey($order->id)->lockForUpdate()->first();

        if (! $locked) {
            throw FreshMarketException::make('ORDER_NOT_FOUND', 'ไม่พบออเดอร์', 404);
        }

        return $locked;
    }

    /**
     * ตรวจว่า role นี้ทำ action นี้ได้ในสถานะปัจจุบัน
     *
     * @throws FreshMarketException INVALID_TRANSITION
     */
    protected function assertCanTransition(FreshMarketOrder $locked, string $action, string $role): void
    {
        if ($locked->canTransition($action, $role)) {
            return;
        }

        $label = FreshMarketOrder::actionLabel($action);

        throw FreshMarketException::make(
            'INVALID_TRANSITION',
            "ไม่สามารถ{$label}ได้ เพราะออเดอร์อยู่ในสถานะ \"{$locked->status_label}\"",
            409
        );
    }

    /**
     * lock wallet ของผู้ใช้ และตรวจว่าใช้งานได้
     *
     * @throws FreshMarketException WALLET_INACTIVE
     */
    protected function lockActiveWallet(User $user): Wallet
    {
        $wallet = $this->wallets->getOrCreateWallet($user);
        $locked = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

        if (! $locked || ! $locked->isActive()) {
            throw FreshMarketException::make('WALLET_INACTIVE', 'กระเป๋าเงินถูกระงับหรือยังไม่พร้อมใช้งาน', 422);
        }

        return $locked;
    }

    /**
     * มีรายการ wallet อ้างอิงนี้แล้วหรือยัง (กันจ่ายซ้ำ)
     */
    protected function walletTxExists(string $referenceType, int $referenceId, int $userId): bool
    {
        return WalletTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * ยอดรวมรายการ wallet อ้างอิงนี้
     */
    protected function walletTxSum(string $referenceType, int $referenceId, int $userId): float
    {
        return round((float) WalletTransaction::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('amount'), 2);
    }

    /**
     * เงินเข้ากระเป๋า fee แพลตฟอร์ม (ครั้งเดียวต่อออเดอร์ต่อประเภท)
     */
    protected function creditPlatform(float $amount, string $subType, FreshMarketOrder $order, array $meta = []): void
    {
        if ($amount <= 0) {
            return;
        }

        $exists = PlatformTransaction::where('source_type', FreshMarketOrder::class)
            ->where('source_id', $order->id)
            ->where('sub_type', $subType)
            ->where('type', 'income')
            ->exists();

        if ($exists) {
            return;
        }

        PlatformWallet::getFeeWallet()->addFunds($amount, $subType, FreshMarketOrder::class, (int) $order->id, array_merge([
            'order_number' => $order->order_number,
        ], $meta));
    }

    /**
     * เงินออกจากกระเป๋า fee แพลตฟอร์ม (ครั้งเดียวต่อออเดอร์ต่อประเภท)
     */
    protected function debitPlatform(float $amount, string $subType, FreshMarketOrder $order, array $meta = []): void
    {
        if ($amount <= 0) {
            return;
        }

        $exists = PlatformTransaction::where('source_type', FreshMarketOrder::class)
            ->where('source_id', $order->id)
            ->where('sub_type', $subType)
            ->where('type', 'expense')
            ->exists();

        if ($exists) {
            return;
        }

        PlatformWallet::getFeeWallet()->deductFunds($amount, $subType, FreshMarketOrder::class, (int) $order->id, array_merge([
            'order_number' => $order->order_number,
        ], $meta));
    }

    /**
     * เงินเก็บปลายทางที่ไรเดอร์ถือไว้ ถูกหักเข้าระบบแล้วหรือยัง (rider_jobs.cod_settled_at)
     */
    protected function riderCodSettled(FreshMarketOrder $order): bool
    {
        if (! $order->rider_job_id) {
            return false;
        }

        return RiderJob::whereKey($order->rider_job_id)->whereNotNull('cod_settled_at')->exists();
    }

    /**
     * งานไรเดอร์ของออเดอร์ส่งสำเร็จแล้วหรือยัง (ไรเดอร์ได้ค่าส่งไปแล้ว)
     */
    protected function riderJobCompleted(FreshMarketOrder $order): bool
    {
        if (! $order->rider_job_id) {
            return false;
        }

        return RiderJob::whereKey($order->rider_job_id)->where('status', 'completed')->exists();
    }
}
