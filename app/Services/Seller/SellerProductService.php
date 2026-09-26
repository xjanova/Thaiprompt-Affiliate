<?php

namespace App\Services\Seller;

use App\Http\Controllers\Seller\ProductController;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\VendorStore;
use App\Services\ImageUploadService;
use App\Services\Pricing\PricingEngine;
use App\Services\Shop\ShopPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * จัดการสินค้าของผู้ขายจากแอป (/api/v1/seller/products/*)
 *
 * กติกาเดียวกับหลังร้านเว็บ (Seller\ProductController):
 *   - ผู้ขายเห็น/แก้ได้เฉพาะสินค้า seller_id = ตัวเอง
 *   - GP มาจาก PricingEngine เท่านั้น (ไม่รับ commission_rate จากผู้ขาย) — commission_rate เป็นค่าแสดงผล
 *   - ค่าส่งต่อชิ้นไม่เกิน ProductController::MAX_SHIPPING_FEE · รูป JPG/PNG/WebP/GIF ไม่เกิน 5MB
 *   - ลบ = soft delete
 *
 * ต่างจากเว็บโดยตั้งใจ (ปลอดภัยกว่า):
 *   - สินค้าที่ทีมงานระงับ (is_blocked) → อ่านอย่างเดียว · สินค้ามีตัวเลือกย่อย (variants) → แก้บนเว็บ
 *   - ไม่รับ PV / เงินคืนลูกค้า จากแอป (ฟีเจอร์เฉพาะเว็บ) → คงค่าเดิม
 *   - ลบสินค้า/รูป ไม่ลบไฟล์รูปที่ออเดอร์เก่ายังอ้างอิงอยู่ (ประวัติคำสั่งซื้อยังเห็นรูป)
 */
class SellerProductService
{
    /** ราคาสูงสุดที่รับ (กันค่าเกินคอลัมน์ decimal(10,2) จน SQL error) — เท่ากับหน้าวางแผนราคา */
    public const MAX_PRICE = 10000000;

    /** รูปเพิ่มเติมสูงสุดต่อสินค้า (ไม่นับรูปหลัก) — เท่ากับฟอร์มเว็บ */
    public const MAX_GALLERY_IMAGES = 10;

    /** ขนาดรูปสูงสุด (KB) */
    public const MAX_IMAGE_KB = 5120;

    /**
     * รายละเอียดสินค้ายาวสุด (ไบต์) — products.description เป็น TEXT (65,535 ไบต์)
     * ภาษาไทย 3 ไบต์/ตัวใน utf8mb4 → นับเป็นไบต์ ไม่ใช่ตัวอักษร (ไม่งั้น DB strict ตอบ "Data too long" เป็น 500)
     */
    public const MAX_DESCRIPTION_BYTES = 65000;

    /** ชนิดไฟล์รูปที่รับ (ตรงกับ accept ของฟอร์มเว็บ) */
    public const IMAGE_MIMES = 'jpeg,jpg,png,webp,gif';

    /** ตัวกรองรายการสินค้า */
    public const FILTERS = ['all', 'active', 'hidden', 'out_of_stock', 'low_stock', 'blocked'];

    public const SHIPPING_METHODS = [
        'store_default' => 'ใช้ค่าเริ่มต้นของร้าน',
        'free' => 'ส่งฟรี',
        'flat_rate' => 'ค่าส่งเหมาต่อชิ้น',
        'weight_based' => 'คิดตามน้ำหนัก',
    ];

    public function __construct(
        private readonly ImageUploadService $images,
        private readonly PricingEngine $pricing,
    ) {}

    // =====================================================
    // Validation
    // =====================================================

    /**
     * กฎตรวจข้อมูลสินค้า (ชุดเดียวกับเว็บ + เพดานกันค่าล้นคอลัมน์)
     *
     * @return array<string, mixed>
     */
    public function rules(?Product $product = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:product_categories,id',
            'price' => 'required|numeric|min:0|max:'.self::MAX_PRICE,
            'compare_at_price' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'cost_price' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
            'stock_quantity' => 'required|integer|min:0|max:1000000',
            'description' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strlen($value) > self::MAX_DESCRIPTION_BYTES) {
                        $fail('รายละเอียดสินค้ายาวเกินไป (ภาษาไทยได้ประมาณ 21,000 ตัวอักษร) กรุณาตัดให้สั้นลง');
                    }
                },
            ],
            'short_description' => 'nullable|string|max:500',
            'brand' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric|min:0|max:999999',
            'dimensions' => 'nullable|string|max:100',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'track_inventory' => 'nullable|boolean',
            'shipping_method' => 'nullable|in:'.implode(',', array_keys(self::SHIPPING_METHODS)),
            'shipping_fee' => 'nullable|numeric|min:0|max:'.ProductController::MAX_SHIPPING_FEE,
            'shipping_weight_kg' => 'nullable|numeric|min:0|max:99999',
            'free_shipping_min_amount' => 'nullable|numeric|min:0|max:'.self::MAX_PRICE,
        ];

        if ($product === null) {
            $rules += [
                'main_image' => 'nullable|image|mimes:'.self::IMAGE_MIMES.'|max:'.self::MAX_IMAGE_KB,
                'images' => 'nullable|array|max:'.self::MAX_GALLERY_IMAGES,
                'images.*' => 'image|mimes:'.self::IMAGE_MIMES.'|max:'.self::MAX_IMAGE_KB,
            ];
        }

        return $rules;
    }

    /**
     * กฎตรวจการอัปโหลดรูปเพิ่ม
     *
     * @return array<string, string>
     */
    public function imageRules(): array
    {
        return [
            'images' => 'required|array|min:1|max:'.self::MAX_GALLERY_IMAGES,
            'images.*' => 'image|mimes:'.self::IMAGE_MIMES.'|max:'.self::MAX_IMAGE_KB,
        ];
    }

    /**
     * ข้อความ validation ภาษาไทย
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxShip = number_format(ProductController::MAX_SHIPPING_FEE);

        return [
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'name.max' => 'ชื่อสินค้าต้องไม่เกิน 255 ตัวอักษร',
            'category_id.required' => 'กรุณาเลือกหมวดหมู่สินค้า',
            'category_id.integer' => 'หมวดหมู่สินค้าไม่ถูกต้อง',
            'category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
            'price.required' => 'กรุณากรอกราคาขาย',
            'price.numeric' => 'ราคาขายต้องเป็นตัวเลข',
            'price.min' => 'ราคาขายต้องไม่ติดลบ',
            'price.max' => 'ราคาขายสูงเกินไป',
            'compare_at_price.numeric' => 'ราคาก่อนลดต้องเป็นตัวเลข',
            'compare_at_price.min' => 'ราคาก่อนลดต้องไม่ติดลบ',
            'compare_at_price.max' => 'ราคาก่อนลดสูงเกินไป',
            'cost_price.numeric' => 'ต้นทุนต้องเป็นตัวเลข',
            'cost_price.min' => 'ต้นทุนต้องไม่ติดลบ',
            'cost_price.max' => 'ต้นทุนสูงเกินไป',
            'stock_quantity.required' => 'กรุณากรอกจำนวนในสต็อก',
            'stock_quantity.integer' => 'จำนวนในสต็อกต้องเป็นจำนวนเต็ม',
            'stock_quantity.min' => 'จำนวนในสต็อกต้องไม่ติดลบ',
            'stock_quantity.max' => 'จำนวนในสต็อกมากเกินไป',
            'short_description.max' => 'คำอธิบายสั้นต้องไม่เกิน 500 ตัวอักษร',
            'brand.max' => 'ชื่อแบรนด์ต้องไม่เกิน 100 ตัวอักษร',
            'weight.numeric' => 'น้ำหนักต้องเป็นตัวเลข',
            'weight.min' => 'น้ำหนักต้องไม่ติดลบ',
            'weight.max' => 'น้ำหนักมากเกินไป',
            'dimensions.max' => 'ขนาดสินค้าต้องไม่เกิน 100 ตัวอักษร',
            'sku.unique' => 'รหัสสินค้า (SKU) นี้ถูกใช้แล้ว',
            'sku.max' => 'รหัสสินค้า (SKU) ต้องไม่เกิน 100 ตัวอักษร',
            'track_inventory.boolean' => 'ค่าการติดตามสต็อกไม่ถูกต้อง',
            'shipping_method.in' => 'วิธีคิดค่าจัดส่งไม่ถูกต้อง',
            'shipping_fee.numeric' => 'ค่าส่งต้องเป็นตัวเลข',
            'shipping_fee.min' => 'ค่าส่งต้องไม่ติดลบ',
            'shipping_fee.max' => "ค่าส่งต่อชิ้นต้องไม่เกิน ฿{$maxShip}",
            'shipping_weight_kg.numeric' => 'น้ำหนักจัดส่งต้องเป็นตัวเลข',
            'shipping_weight_kg.min' => 'น้ำหนักจัดส่งต้องไม่ติดลบ',
            'shipping_weight_kg.max' => 'น้ำหนักจัดส่งมากเกินไป',
            'free_shipping_min_amount.numeric' => 'ยอดส่งฟรีต้องเป็นตัวเลข',
            'free_shipping_min_amount.min' => 'ยอดส่งฟรีต้องไม่ติดลบ',
            'main_image.image' => 'รูปหลักต้องเป็นไฟล์รูปภาพ',
            'main_image.mimes' => 'รูปหลักต้องเป็น JPG, PNG, WebP หรือ GIF',
            'main_image.max' => 'รูปหลักต้องไม่เกิน 5MB',
            'images.required' => 'กรุณาเลือกรูปสินค้า',
            'images.array' => 'รูปสินค้าไม่ถูกต้อง',
            'images.max' => 'รูปเพิ่มเติมได้สูงสุด '.self::MAX_GALLERY_IMAGES.' รูป',
            'images.*.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'images.*.mimes' => 'รูปต้องเป็น JPG, PNG, WebP หรือ GIF',
            'images.*.max' => 'รูปต้องไม่เกิน 5MB ต่อรูป',
            'is_active.required' => 'กรุณาระบุสถานะการขาย',
            'is_active.boolean' => 'สถานะการขายไม่ถูกต้อง',
            'image_id.required' => 'กรุณาเลือกรูป',
            'image_id.integer' => 'รูปไม่ถูกต้อง',
            'order.required' => 'กรุณาส่งลำดับรูป',
            'order.array' => 'ลำดับรูปไม่ถูกต้อง',
            'order.*.integer' => 'ลำดับรูปไม่ถูกต้อง',
        ];
    }

    // =====================================================
    // อ่านข้อมูล
    // =====================================================

    /**
     * สินค้าของผู้ขายคนนี้ (สินค้าคนอื่น / ไม่มี = null → ตอบ 404 เหมือนไม่มีอยู่ กัน IDOR)
     */
    public function findOwned(User $seller, int $productId): ?Product
    {
        return Product::with(['images', 'category:id,name'])
            ->where('seller_id', $seller->id)
            ->whereKey($productId)
            ->first();
    }

    /**
     * query รายการสินค้าตามตัวกรอง/คำค้น
     */
    public function listQuery(User $seller, string $filter, ?string $search): Builder
    {
        $query = Product::query()
            ->with(['category:id,name', 'images'])
            ->where('seller_id', $seller->id);

        if ($search !== null && trim($search) !== '') {
            // escape ตัว wildcard ของ LIKE — คำค้นเป็นข้อความธรรมดาเสมอ
            $term = '%'.addcslashes(trim($search), '\\%_').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        $this->applyFilter($query, $filter);

        return $query->latest('id');
    }

    /**
     * จำนวนสินค้าแต่ละตัวกรอง (ตัวเลขบนแท็บ)
     *
     * @return array<string, int>
     */
    public function counts(User $seller): array
    {
        $counts = [];
        foreach (self::FILTERS as $filter) {
            $query = Product::query()->where('seller_id', $seller->id);
            $this->applyFilter($query, $filter);
            $counts[$filter] = (int) $query->count();
        }

        return $counts;
    }

    private function applyFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'active' => $query->where('is_active', true)->where('is_blocked', false),
            'hidden' => $query->where('is_active', false),
            'out_of_stock' => $query->outOfStock(),
            'low_stock' => $query->lowStock(),
            'blocked' => $query->where('is_blocked', true),
            default => null,
        };
    }

    /**
     * ข้อมูลประกอบฟอร์มสินค้า: หมวดหมู่ + GP/VAT (อ่านอย่างเดียว) + เพดานต่างๆ
     *
     * @return array<string, mixed>
     */
    public function formMeta(User $seller, ?Product $product = null): array
    {
        $store = VendorStore::where('user_id', $seller->id)->orderBy('id')->first();
        $subject = $product ?? (new Product)->forceFill([
            'seller_id' => $seller->id,
            'store_id' => $store?->id,
        ]);

        try {
            $gpInfo = $this->pricing->gpRateInfoForProduct($subject);
        } catch (\Throwable $e) {
            Log::warning('Seller product meta: GP info unavailable', ['error' => $e->getMessage()]);
            $gpInfo = ['rate' => null, 'source' => 'unknown', 'label_th' => 'อัตรา GP ตามที่แพลตฟอร์มกำหนด', 'clamped' => false];
        }

        $categories = ProductCategory::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (ProductCategory $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'parent_id' => $c->parent_id !== null ? (int) $c->parent_id : null,
            ])
            ->values();

        return [
            'categories' => $categories,
            'gp' => [
                'rate' => $gpInfo['rate'] !== null ? round((float) $gpInfo['rate'], 2) : null,
                'label' => (string) ($gpInfo['label_th'] ?? ''),
                'source' => (string) ($gpInfo['source'] ?? 'unknown'),
                'promo_active' => $this->safe(fn () => $this->pricing->gpPromoActive(), false),
            ],
            'vat_registered' => $this->safe(fn () => $this->pricing->storeVatRegistered($store), false),
            'shipping_methods' => collect(self::SHIPPING_METHODS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'limits' => [
                'max_price' => self::MAX_PRICE,
                'max_shipping_fee' => ProductController::MAX_SHIPPING_FEE,
                'max_gallery_images' => self::MAX_GALLERY_IMAGES,
                'max_image_mb' => (int) (self::MAX_IMAGE_KB / 1024),
                'image_types' => ['jpg', 'png', 'webp', 'gif'],
            ],
        ];
    }

    /**
     * คำนวณเงินที่ร้านได้รับจากราคาเดียว (PricingEngine ชุดเดียวกับตอนแบ่งเงินจริง)
     *
     * @return array<string, mixed>
     */
    public function quote(User $seller, float $price, ?float $cost, ?Product $product): array
    {
        $store = VendorStore::where('user_id', $seller->id)->orderBy('id')->first();
        $subject = $product ?? (new Product)->forceFill([
            'seller_id' => $seller->id,
            'store_id' => $store?->id,
            'pv_value' => 0,
        ]);

        // ไม่กรอกต้นทุน = ไม่คำนวณกำไร (ไม่ใช้ต้นทุนเดิมของสินค้าแทน)
        $overrides = ['cost_per_unit' => $cost !== null && $cost > 0 ? $cost : null];

        $breakdown = $this->pricing->breakdown($price, 1, $this->pricing->optionsForProduct($subject, $overrides));
        $gpInfo = $this->pricing->gpRateInfoForProduct($subject);
        $data = $breakdown->toArray();

        return [
            'unit_price' => (float) $data['unit_price'],
            'gp_rate' => (float) $data['gp_rate'],
            'gp_amount' => (float) $data['gp_amount'],
            'vat_amount' => (float) $data['vat_amount'],
            'payment_fee' => (float) $data['payment_fee'],
            'seller_net' => (float) $data['seller_net'],
            'profit' => $data['profit'] !== null ? (float) $data['profit'] : null,
            'margin_percent' => $data['margin_percent'] !== null ? (float) $data['margin_percent'] : null,
            'is_loss' => (bool) $data['is_loss'],
            // บรรทัดรายการหัก/ผลลัพธ์ (ข้อความภาษาไทยจาก PricingEngine) — ไม่รวมบรรทัด info
            'lines' => collect($data['lines'] ?? [])
                ->filter(fn ($line) => is_array($line) && ($line['kind'] ?? '') !== 'info')
                ->map(fn (array $line) => [
                    'key' => (string) ($line['key'] ?? ''),
                    'label' => (string) ($line['label_th'] ?? ''),
                    'amount' => (float) ($line['amount'] ?? 0),
                    'kind' => (string) ($line['kind'] ?? ''),
                ])
                ->values(),
            'warnings' => collect($data['warnings'] ?? [])
                ->map(fn ($w) => is_array($w) ? (string) ($w['message'] ?? '') : (string) $w)
                ->filter()
                ->values(),
            'gp_label' => (string) ($gpInfo['label_th'] ?? ''),
            'gp_promo_active' => $this->safe(fn () => $this->pricing->gpPromoActive(), false),
        ];
    }

    // =====================================================
    // สร้าง / แก้ไข / ลบ
    // =====================================================

    /**
     * สร้างสินค้าใหม่ (ตรรกะเดียวกับ ProductController::store บนเว็บ)
     *
     * @param  array<string, mixed>  $data  ค่าที่ผ่าน rules() แล้ว
     * @param  array<int, UploadedFile>  $gallery
     *
     * @throws \Throwable บันทึกไม่สำเร็จ (ลบไฟล์ที่อัปโหลดไปแล้วให้ก่อนโยนต่อ)
     */
    public function create(User $seller, array $data, ?UploadedFile $mainImage, array $gallery): Product
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($seller, $data, $mainImage, $gallery, &$uploaded) {
                // ไม่มีรูปหลักแต่มีรูปเพิ่มเติม → ใช้รูปแรกเป็นรูปหลัก
                if (! $mainImage && count($gallery) > 0) {
                    $mainImage = array_shift($gallery);
                }

                $mainPath = null;
                if ($mainImage) {
                    $mainPath = $this->images->uploadImage($mainImage, 'products', 1200, 1200, 90);
                    $uploaded[] = $mainPath;
                }

                $stock = (int) $data['stock_quantity'];
                $method = $data['shipping_method'] ?? 'store_default';

                $product = Product::create([
                    'seller_id' => $seller->id,
                    // ผูกร้าน (หน้าร้านแอป/POS ค้นด้วย store_id) + slug ไม่ซ้ำ (ชื่อไทยได้)
                    'store_id' => VendorStore::where('user_id', $seller->id)->orderBy('id')->value('id'),
                    'category_id' => (int) $data['category_id'],
                    'name' => trim((string) $data['name']),
                    'slug' => Product::generateUniqueSlug(trim((string) $data['name'])),
                    'sku' => ! empty($data['sku']) ? trim((string) $data['sku']) : 'PRD-'.strtoupper(Str::random(8)),
                    'description' => $data['description'] ?? null,
                    'short_description' => $data['short_description'] ?? null,
                    'price' => round((float) $data['price'], 2),
                    'compare_at_price' => $this->money($data['compare_at_price'] ?? null),
                    'cost_price' => $this->money($data['cost_price'] ?? null),
                    'stock_quantity' => $stock,
                    'track_inventory' => array_key_exists('track_inventory', $data) && $data['track_inventory'] !== null
                        ? (bool) $data['track_inventory']
                        : true,
                    'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                    'brand' => $data['brand'] ?? null,
                    'weight' => $data['weight'] ?? null,
                    'dimensions' => $data['dimensions'] ?? null,
                    'customer_cashback' => 0,
                    'cashback_percentage' => 0,
                    'main_image_url' => $mainPath,
                    'shipping_method' => $method,
                    // products.shipping_fee เป็น NOT NULL DEFAULT 0
                    'shipping_fee' => isset($data['shipping_fee']) ? round((float) $data['shipping_fee'], 2) : 0,
                    'shipping_weight_kg' => $data['shipping_weight_kg'] ?? null,
                    'free_shipping_min_amount' => $this->money($data['free_shipping_min_amount'] ?? null),
                    'is_active' => true,
                    'published_at' => now(),
                ]);

                $sort = 1;
                foreach ($gallery as $file) {
                    $path = $this->images->uploadImage($file, 'products', 1200, 1200, 85);
                    $uploaded[] = $path;
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $path,
                        'sort_order' => $sort++,
                    ]);
                }

                $this->syncDisplayedGpRate($product);

                return $product;
            });
        } catch (\Throwable $e) {
            // ลบไฟล์ที่อัปโหลดไปก่อนบันทึกไม่สำเร็จ (ไม่ให้ไฟล์กำพร้าค้างใน storage)
            foreach ($uploaded as $path) {
                $this->images->deleteImage($path);
            }

            throw $e;
        }
    }

    /**
     * แก้ไขข้อมูลสินค้า (ไม่รวมรูป — รูปมี endpoint แยก) ตรรกะเดียวกับ ProductController::update
     *
     * @param  array<string, mixed>  $data  ค่าที่ผ่าน rules($product) แล้ว
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $name = trim((string) $data['name']);
            $stock = (int) $data['stock_quantity'];

            $locked->update([
                'category_id' => (int) $data['category_id'],
                'name' => $name,
                // สร้าง slug ใหม่เฉพาะเมื่อเปลี่ยนชื่อ (SELLER-18)
                'slug' => $name !== $locked->name || blank($locked->slug)
                    ? Product::generateUniqueSlug($name, $locked->id)
                    : $locked->slug,
                'sku' => ! empty($data['sku']) ? trim((string) $data['sku']) : $locked->sku,
                'description' => $data['description'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'price' => round((float) $data['price'], 2),
                'compare_at_price' => $this->money($data['compare_at_price'] ?? null),
                'cost_price' => $this->money($data['cost_price'] ?? null),
                'stock_quantity' => $stock,
                'track_inventory' => array_key_exists('track_inventory', $data) && $data['track_inventory'] !== null
                    ? (bool) $data['track_inventory']
                    : true,
                'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
                'brand' => $data['brand'] ?? null,
                'weight' => $data['weight'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                // เงินคืนลูกค้า/PV เป็นฟีเจอร์เฉพาะเว็บ → คงค่าเดิม (ไม่แตะ)
                'shipping_method' => $data['shipping_method'] ?? $locked->shipping_method ?? 'store_default',
                'shipping_fee' => isset($data['shipping_fee']) ? round((float) $data['shipping_fee'], 2) : $locked->shipping_fee,
                // ส่งคีย์มาเป็น null = ล้างค่า (ไม่ส่งคีย์ = คงค่าเดิม)
                'shipping_weight_kg' => array_key_exists('shipping_weight_kg', $data) ? $data['shipping_weight_kg'] : $locked->shipping_weight_kg,
                'free_shipping_min_amount' => array_key_exists('free_shipping_min_amount', $data)
                    ? $this->money($data['free_shipping_min_amount'])
                    : $locked->free_shipping_min_amount,
            ]);

            $this->syncDisplayedGpRate($locked);

            return $locked;
        });
    }

    /**
     * เปิด/ปิดการขาย (ตั้งค่าตรงๆ ไม่ใช่สลับ — กดซ้ำ/ส่งซ้ำได้ผลเหมือนเดิม)
     */
    public function setActive(Product $product, bool $active): Product
    {
        $product->forceFill(['is_active' => $active])->save();

        return $product;
    }

    /**
     * ปรับจำนวนสต็อก (ตรงกับ ProductController::updateStock)
     */
    public function setStock(Product $product, int $quantity): Product
    {
        $product->forceFill([
            'stock_quantity' => $quantity,
            'stock_status' => $quantity > 0 ? 'in_stock' : 'out_of_stock',
        ])->save();

        return $product;
    }

    /**
     * ลบสินค้า (soft delete) — ไม่ลบไฟล์รูป (ออเดอร์เก่า/กู้คืนยังใช้ได้)
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    // =====================================================
    // รูปสินค้า
    // =====================================================

    /**
     * เพิ่มรูป (รูปแรกกลายเป็นรูปหลักถ้าสินค้ายังไม่มีรูปหลัก)
     *
     * @param  array<int, UploadedFile>  $files
     * @return array{ok: bool, code?: string, message?: string}
     */
    public function addImages(Product $product, array $files): array
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($product, $files, &$uploaded) {
                $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $galleryCount = ProductImage::where('product_id', $locked->id)->count();
                $hasMain = filled($locked->getRawOriginal('main_image_url'));
                $newGallery = count($files) - ($hasMain ? 0 : 1);

                if ($galleryCount + max(0, $newGallery) > self::MAX_GALLERY_IMAGES) {
                    return [
                        'ok' => false,
                        'code' => 'TOO_MANY_IMAGES',
                        'message' => 'รูปเพิ่มเติมได้สูงสุด '.self::MAX_GALLERY_IMAGES.' รูป (ไม่รวมรูปหลัก) ลบรูปเก่าก่อนนะ',
                    ];
                }

                $sort = (int) (ProductImage::where('product_id', $locked->id)->max('sort_order') ?? 0);
                foreach ($files as $file) {
                    if (! $hasMain) {
                        $path = $this->images->uploadImage($file, 'products', 1200, 1200, 90);
                        $uploaded[] = $path;
                        $locked->forceFill(['main_image_url' => $path])->save();
                        $hasMain = true;

                        continue;
                    }

                    $path = $this->images->uploadImage($file, 'products', 1200, 1200, 85);
                    $uploaded[] = $path;
                    ProductImage::create([
                        'product_id' => $locked->id,
                        'image_url' => $path,
                        'sort_order' => ++$sort,
                    ]);
                }

                return ['ok' => true];
            });
        } catch (\Throwable $e) {
            foreach ($uploaded as $path) {
                $this->images->deleteImage($path);
            }

            throw $e;
        }
    }

    /**
     * ลบรูป — image_id 0 = รูปหลัก (ต้องมีรูปอื่นขึ้นมาแทน สินค้าจะได้ไม่ไร้รูป)
     *
     * @return array{ok: bool, status?: int, code?: string, message?: string}
     */
    public function deleteImage(Product $product, int $imageId): array
    {
        $removedPath = DB::transaction(function () use ($product, $imageId) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($imageId === 0) {
                $mainPath = $locked->getRawOriginal('main_image_url');
                if (blank($mainPath)) {
                    return ['error' => ['status' => 404, 'code' => 'IMAGE_NOT_FOUND', 'message' => 'ไม่พบรูปนี้ในสินค้า']];
                }

                $next = ProductImage::where('product_id', $locked->id)->orderBy('sort_order')->orderBy('id')->first();
                if (! $next) {
                    return ['error' => [
                        'status' => 422,
                        'code' => 'MAIN_IMAGE_REQUIRED',
                        'message' => 'สินค้าต้องมีรูปหลักอย่างน้อย 1 รูป เพิ่มรูปใหม่ก่อนแล้วค่อยลบรูปนี้',
                    ]];
                }

                // เลื่อนรูปถัดไปขึ้นเป็นรูปหลัก
                $locked->forceFill(['main_image_url' => $next->image_url])->save();
                $next->delete();

                return ['path' => $mainPath];
            }

            $image = ProductImage::where('product_id', $locked->id)->whereKey($imageId)->first();
            if (! $image) {
                return ['error' => ['status' => 404, 'code' => 'IMAGE_NOT_FOUND', 'message' => 'ไม่พบรูปนี้ในสินค้า']];
            }

            $path = $image->image_url;
            $image->delete();

            return ['path' => $path];
        });

        if (isset($removedPath['error'])) {
            return ['ok' => false] + $removedPath['error'];
        }

        $this->deleteFileIfUnused($removedPath['path'] ?? null);

        return ['ok' => true];
    }

    /**
     * ตั้งรูปเพิ่มเติมเป็นรูปหลัก — สลับตำแหน่งกับรูปหลักเดิม (รูปหลักเดิมไปอยู่ลำดับของรูปที่เลือก)
     *
     * @return array{ok: bool, status?: int, code?: string, message?: string}
     */
    public function setMainImage(Product $product, int $imageId): array
    {
        return DB::transaction(function () use ($product, $imageId) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $image = ProductImage::where('product_id', $locked->id)->whereKey($imageId)->first();

            if (! $image) {
                return ['ok' => false, 'status' => 404, 'code' => 'IMAGE_NOT_FOUND', 'message' => 'ไม่พบรูปนี้ในสินค้า'];
            }

            $oldMain = $locked->getRawOriginal('main_image_url');
            $locked->forceFill(['main_image_url' => $image->image_url])->save();

            if (filled($oldMain)) {
                $image->forceFill(['image_url' => $oldMain])->save();
            } else {
                $image->delete();
            }

            return ['ok' => true];
        });
    }

    /**
     * เรียงลำดับรูปเพิ่มเติมใหม่ — ต้องส่ง id ครบทุกรูปของสินค้า (กันลำดับหาย/ใส่ id ของสินค้าอื่น)
     *
     * @param  array<int, int>  $order
     * @return array{ok: bool, status?: int, code?: string, message?: string}
     */
    public function reorderImages(Product $product, array $order): array
    {
        return DB::transaction(function () use ($product, $order) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $ids = ProductImage::where('product_id', $product->id)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $given = collect($order)->map(fn ($id) => (int) $id);

            if ($given->unique()->count() !== $given->count() || $given->sort()->values()->all() !== $ids) {
                return ['ok' => false, 'status' => 409, 'code' => 'IMAGE_ORDER_MISMATCH', 'message' => 'รายการรูปเปลี่ยนไปแล้ว รีเฟรชแล้วลองใหม่นะ'];
            }

            foreach ($given->values() as $index => $id) {
                ProductImage::whereKey($id)->where('product_id', $product->id)->update(['sort_order' => $index + 1]);
            }

            return ['ok' => true];
        });
    }

    // =====================================================
    // แสดงผล
    // =====================================================

    /**
     * สินค้าที่แก้จากแอปไม่ได้ + เหตุผล (null = แก้ได้)
     *
     * @return array{code: string, message: string}|null
     */
    public function readOnlyReason(Product $product): ?array
    {
        if ($product->is_blocked) {
            return [
                'code' => 'PRODUCT_BLOCKED',
                'message' => trim('สินค้านี้ถูกระงับโดยทีมงาน แก้ไขไม่ได้'
                    .($product->block_reason ? ' · เหตุผล: '.$product->block_reason : '')),
            ];
        }

        if ($product->has_variants || $product->parent_product_id) {
            return [
                'code' => 'VARIANTS_WEB_ONLY',
                'message' => 'สินค้านี้มีตัวเลือกย่อย (สี/ขนาด) แก้ไขได้บนเว็บไซต์เท่านั้น',
            ];
        }

        return null;
    }

    /**
     * ข้อมูลสินค้าสำหรับแอป
     *
     * @return array<string, mixed>
     */
    public function present(Product $product, bool $detail = false): array
    {
        $readOnly = $this->readOnlyReason($product);
        $mainPath = $product->getRawOriginal('main_image_url');
        $gallery = $product->relationLoaded('images') ? $product->images : $product->images()->get();
        $mainUrl = ShopPresenter::imageUrl($mainPath) ?? ShopPresenter::imageUrl($gallery->first()?->image_url);
        $stock = (int) $product->stock_quantity;
        $track = (bool) $product->track_inventory;
        $threshold = (int) ($product->low_stock_threshold ?? 0);

        $data = [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
            'stock_quantity' => $stock,
            'track_inventory' => $track,
            'stock_status' => $product->stock_status,
            'is_out_of_stock' => $track && $stock <= 0,
            'is_low_stock' => $track && $stock > 0 && $stock <= $threshold,
            'is_active' => (bool) $product->is_active,
            'is_hidden' => (bool) $product->is_hidden,
            'is_blocked' => (bool) $product->is_blocked,
            'block_reason' => $product->is_blocked ? $product->block_reason : null,
            'is_public_approved' => (bool) $product->is_public_approved,
            'has_variants' => (bool) $product->has_variants || (bool) $product->parent_product_id,
            'read_only' => $readOnly !== null,
            'read_only_code' => $readOnly['code'] ?? null,
            'read_only_reason' => $readOnly['message'] ?? null,
            'main_image' => $mainUrl,
            'image_count' => (filled($mainPath) ? 1 : 0) + $gallery->count(),
            'category' => $product->category ? ['id' => (int) $product->category->id, 'name' => (string) $product->category->name] : null,
            'sales_count' => (int) ($product->sales_count ?? 0),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];

        if (! $detail) {
            return $data;
        }

        $images = [];
        if (filled($mainPath)) {
            $images[] = ['id' => 0, 'url' => ShopPresenter::imageUrl($mainPath), 'is_main' => true];
        }
        foreach ($gallery as $img) {
            $url = ShopPresenter::imageUrl($img->image_url);
            if ($url) {
                $images[] = ['id' => (int) $img->id, 'url' => $url, 'is_main' => false];
            }
        }

        return $data + [
            'category_id' => $product->category_id !== null ? (int) $product->category_id : null,
            'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'brand' => $product->brand,
            'weight' => $product->weight !== null ? (float) $product->weight : null,
            'dimensions' => $product->dimensions,
            'shipping_method' => $product->shipping_method ?: 'store_default',
            'shipping_fee' => (float) ($product->shipping_fee ?? 0),
            'shipping_weight_kg' => $product->shipping_weight_kg !== null ? (float) $product->shipping_weight_kg : null,
            'free_shipping_min_amount' => $product->free_shipping_min_amount !== null ? (float) $product->free_shipping_min_amount : null,
            'low_stock_threshold' => $threshold,
            'gp_rate' => $product->commission_rate !== null ? (float) $product->commission_rate : null,
            'view_count' => (int) ($product->view_count ?? 0),
            'images' => $images,
            'web_edit_path' => '/seller/products/'.$product->id.'/edit',
            'created_at' => $product->created_at?->toIso8601String(),
        ];
    }

    // =====================================================
    // ตัวช่วย
    // =====================================================

    /**
     * เขียนอัตรา GP ที่แพลตฟอร์มคิดจริงลง products.commission_rate (ค่าแสดงผลเท่านั้น — ตรงกับเว็บ)
     */
    private function syncDisplayedGpRate(Product $product): void
    {
        try {
            $rate = $this->pricing->gpRateForProduct($product);
            $product->forceFill(['commission_rate' => round($rate, 2)])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Seller product (app): sync displayed GP rate failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ลบไฟล์รูปเมื่อไม่มีสินค้า/รูปสินค้า/ออเดอร์ไหนอ้างอิงแล้ว (order_items.product_image เก็บ path รูปหลัก ณ ตอนซื้อ)
     */
    private function deleteFileIfUnused(?string $path): void
    {
        if ($path === null || $path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return;
        }

        try {
            $inUse = Product::withTrashed()->where('main_image_url', $path)->exists()
                || ProductImage::where('image_url', $path)->exists()
                || OrderItem::where('product_image', $path)
                    ->orWhere('product_image', 'like', '%/'.addcslashes($path, '\\%_'))
                    ->exists();

            if (! $inUse) {
                $this->images->deleteImage($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Seller product (app): image cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    private function money(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 2);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $fn
     * @param  T  $fallback
     * @return T
     */
    private function safe(callable $fn, mixed $fallback): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
