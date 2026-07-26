<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CategoryImageService — ตัวแก้ปัญหา "หมวดหมู่ไม่ควรมีภาพตาย"
 *
 * แก้ภาพหมวดหมู่แบบ 3 ชั้น (three-tier cover resolution):
 *   ชั้น 1  image   — ภาพที่แอดมินอัปโหลดไว้เอง (แปลง path ให้เป็น URL ที่ใช้งานได้จริง)
 *   ชั้น 2  mosaic  — ถ้าไม่มีภาพแอดมิน ดึงภาพสินค้าใหม่ล่าสุด (สูงสุด 4 ชิ้น) จาก
 *                     หมวดนั้น "หรือหมวดลูกหลานทุกชั้น" มาต่อเป็นโมเสก 2x2
 *                     (ถ้าได้ภาพเดียวก็ใช้เป็นภาพเดี่ยว mode = image)
 *                     ชั้นนี้ทำให้การ์ดหมวดหมู่ "ซ่อมตัวเอง" — ตราบใดที่หมวดยังมีสินค้าขาย
 *                     ก็จะมีภาพจริงที่ยังมีชีวิตอยู่เสมอ
 *   ชั้น 3  glyph   — ไม่มีทั้งภาพแอดมินและสินค้า → ใช้ gradient + ไอคอน Font Awesome
 *
 * 🚀 ประสิทธิภาพ: สร้าง "แผนที่ภาพปก" ของทุกหมวดพร้อมกันครั้งเดียว (batch)
 *    ใช้เพียง 2 คิวรีต่อการสร้าง cache 1 รอบ (1 = ตารางหมวดหมู่, 1 = สินค้าจัดอันดับ)
 *    แล้ว cache ไว้ 1 ชั่วโมง — Blade เรียก cover() กี่ครั้งก็ไม่เกิด N+1
 *
 * @example
 * $service = app(CategoryImageService::class);
 * $cover = $service->cover($category);
 * // ['mode' => 'mosaic', 'urls' => [...], 'icon' => 'fa-solid fa-shirt', 'products' => [...]]
 */
class CategoryImageService
{
    /** คีย์ cache ของแผนที่ภาพปก (ขึ้นเวอร์ชันเมื่อโครงสร้าง payload เปลี่ยน) */
    public const CACHE_KEY = 'storefront:category_covers:v1';

    /** อายุ cache (วินาที) ~1 ชั่วโมง */
    public const CACHE_TTL = 3600;

    /** จำนวนภาพสูงสุดต่อหนึ่งหมวด (โมเสก 2x2) */
    public const MAX_COVER_IMAGES = 4;

    /** จำนวนสินค้าที่ดึงเผื่อไว้ต่อหนึ่งหมวด (เผื่อบางแถวภาพใช้ไม่ได้) */
    private const CANDIDATES_PER_CATEGORY = 6;

    /** เพดานแถวสำหรับโหมดสำรอง (กรณี DB ไม่รองรับ window function) */
    private const FALLBACK_ROW_LIMIT = 400;

    /** โฮสต์ภาพที่ "ตายแล้ว" — ถ้าเจอให้ถือว่าไม่มีภาพ (via.placeholder.com resolve ไม่ได้อีกแล้ว) */
    private const DEAD_IMAGE_HOSTS = [
        'via.placeholder.com',
        'www.via.placeholder.com',
        'placeholder.com',
        'www.placeholder.com',
        'placehold.it',
        'www.placehold.it',
    ];

    /**
     * ตารางไอคอน Font Awesome 6 ตามชื่อ/slug หมวดหมู่
     *
     * ⚠️ ใช้จับคู่ "แบบตรงตัว (exact)" เป็นหลัก ส่วนการจับคู่แบบ substring
     * เป็นทางเลือกสุดท้ายและบังคับให้คีย์ยาว >= 4 ตัวอักษร
     * เพื่อกันเคสชนกันแบบเดิม เช่น 'small-appliances' ไปโดน 'all'
     * หรือ 'haircare' ไปโดน 'car' และ 'ยานยนต์' ไปโดน 'ยา'
     */
    private const ICON_MAP = [
        // อิเล็กทรอนิกส์
        'อิเล็กทรอนิกส์' => 'fa-solid fa-microchip',
        'electronics' => 'fa-solid fa-microchip',
        'คอมพิวเตอร์' => 'fa-solid fa-desktop',
        'computer' => 'fa-solid fa-desktop',
        'โทรศัพท์' => 'fa-solid fa-mobile-screen-button',
        'phone' => 'fa-solid fa-mobile-screen-button',
        'มือถือ' => 'fa-solid fa-mobile-screen-button',
        'mobile' => 'fa-solid fa-mobile-screen-button',
        'แล็ปท็อป' => 'fa-solid fa-laptop',
        'laptop' => 'fa-solid fa-laptop',
        'กล้อง' => 'fa-solid fa-camera-retro',
        'camera' => 'fa-solid fa-camera-retro',
        'เครื่องเสียง' => 'fa-solid fa-headphones',
        'audio' => 'fa-solid fa-headphones',
        'หูฟัง' => 'fa-solid fa-headphones',
        'headphone' => 'fa-solid fa-headphones',
        'เกม' => 'fa-solid fa-gamepad',
        'game' => 'fa-solid fa-gamepad',
        'gaming' => 'fa-solid fa-gamepad',

        // แฟชั่น
        'แฟชั่น' => 'fa-solid fa-shirt',
        'fashion' => 'fa-solid fa-shirt',
        'เสื้อผ้า' => 'fa-solid fa-shirt',
        'clothing' => 'fa-solid fa-shirt',
        'รองเท้า' => 'fa-solid fa-shoe-prints',
        'shoes' => 'fa-solid fa-shoe-prints',
        'กระเป๋า' => 'fa-solid fa-bag-shopping',
        'bags' => 'fa-solid fa-bag-shopping',
        'เครื่องประดับ' => 'fa-solid fa-gem',
        'jewelry' => 'fa-solid fa-gem',
        'นาฬิกา' => 'fa-solid fa-clock',
        'watches' => 'fa-solid fa-clock',
        'แว่นตา' => 'fa-solid fa-glasses',
        'glasses' => 'fa-solid fa-glasses',

        // บ้านและสวน
        'บ้านและสวน' => 'fa-solid fa-house',
        'home' => 'fa-solid fa-house',
        'เฟอร์นิเจอร์' => 'fa-solid fa-couch',
        'furniture' => 'fa-solid fa-couch',
        'ห้องครัว' => 'fa-solid fa-utensils',
        'kitchen' => 'fa-solid fa-utensils',
        'ตกแต่งบ้าน' => 'fa-solid fa-lamp',
        'decor' => 'fa-solid fa-paintbrush',
        'สวน' => 'fa-solid fa-seedling',
        'garden' => 'fa-solid fa-seedling',
        'เครื่องใช้ไฟฟ้า' => 'fa-solid fa-plug',
        'appliances' => 'fa-solid fa-plug',
        'small-appliances' => 'fa-solid fa-blender',

        // ความงาม
        'ความงาม' => 'fa-solid fa-spa',
        'beauty' => 'fa-solid fa-spa',
        'เครื่องสำอาง' => 'fa-solid fa-spray-can-sparkles',
        'cosmetics' => 'fa-solid fa-spray-can-sparkles',
        'สกินแคร์' => 'fa-solid fa-bottle-droplet',
        'skincare' => 'fa-solid fa-bottle-droplet',
        'น้ำหอม' => 'fa-solid fa-bottle-droplet',
        'perfume' => 'fa-solid fa-bottle-droplet',
        'haircare' => 'fa-solid fa-pump-soap',
        'ผลิตภัณฑ์เส้นผม' => 'fa-solid fa-pump-soap',

        // สุขภาพ
        'สุขภาพ' => 'fa-solid fa-heart-pulse',
        'health' => 'fa-solid fa-heart-pulse',
        'กีฬา' => 'fa-solid fa-dumbbell',
        'sport' => 'fa-solid fa-dumbbell',
        'sports' => 'fa-solid fa-dumbbell',
        'ออกกำลังกาย' => 'fa-solid fa-person-running',
        'fitness' => 'fa-solid fa-person-running',
        'ยา' => 'fa-solid fa-pills',
        'medicine' => 'fa-solid fa-pills',
        'อาหารเสริม' => 'fa-solid fa-capsules',
        'supplements' => 'fa-solid fa-capsules',

        // อาหาร
        'อาหาร' => 'fa-solid fa-burger',
        'food' => 'fa-solid fa-burger',
        'เครื่องดื่ม' => 'fa-solid fa-mug-hot',
        'beverages' => 'fa-solid fa-mug-hot',
        'drinks' => 'fa-solid fa-mug-hot',
        'ขนม' => 'fa-solid fa-cookie',
        'snacks' => 'fa-solid fa-cookie',

        // แม่และเด็ก
        'แม่และเด็ก' => 'fa-solid fa-baby-carriage',
        'mom' => 'fa-solid fa-baby-carriage',
        'baby' => 'fa-solid fa-baby',
        'เด็ก' => 'fa-solid fa-baby',
        'ของเล่น' => 'fa-solid fa-puzzle-piece',
        'toys' => 'fa-solid fa-puzzle-piece',

        // ยานยนต์
        'ยานยนต์' => 'fa-solid fa-car',
        'automotive' => 'fa-solid fa-car',
        'รถยนต์' => 'fa-solid fa-car',
        'car' => 'fa-solid fa-car',
        'มอเตอร์ไซค์' => 'fa-solid fa-motorcycle',
        'motorcycle' => 'fa-solid fa-motorcycle',

        // หนังสือ
        'หนังสือ' => 'fa-solid fa-book',
        'books' => 'fa-solid fa-book',
        'สื่อการเรียน' => 'fa-solid fa-graduation-cap',
        'education' => 'fa-solid fa-graduation-cap',

        // สัตว์เลี้ยง
        'สัตว์เลี้ยง' => 'fa-solid fa-paw',
        'pets' => 'fa-solid fa-paw',

        // สายมู / ความเชื่อ
        'สายมู' => 'fa-solid fa-hands-praying',
        'มูเตลู' => 'fa-solid fa-hands-praying',
        'เครื่องราง' => 'fa-solid fa-hands-praying',
        'amulet' => 'fa-solid fa-hands-praying',
        'ดูดวง' => 'fa-solid fa-wand-magic-sparkles',

        // อื่นๆ
        'อื่นๆ' => 'fa-solid fa-ellipsis',
        'others' => 'fa-solid fa-ellipsis',
        'other' => 'fa-solid fa-ellipsis',
        'ทั้งหมด' => 'fa-solid fa-grip',
        'all' => 'fa-solid fa-grip',

        // บริการ
        'บริการ' => 'fa-solid fa-handshake',
        'services' => 'fa-solid fa-handshake',

        // ดิจิทัล
        'ดิจิทัล' => 'fa-solid fa-cloud',
        'digital' => 'fa-solid fa-cloud',
        'ซอฟต์แวร์' => 'fa-solid fa-code',
        'software' => 'fa-solid fa-code',
    ];

    /**
     * แผนที่ภาพปกของทุกหมวด (memo ระดับ request — กัน Blade เรียก cache ซ้ำ)
     *
     * @var array<int, array>|null
     */
    private ?array $map = null;

    /**
     * ดึงข้อมูลภาพปกของหมวดหมู่หนึ่งหมวด
     *
     * @param  ProductCategory|int|null  $category  โมเดลหมวดหมู่ หรือ id
     * @return array{mode: string, urls: array<int, string>, icon: string|null, products: array<int, array>}
     *
     * @example
     * $cover = $service->cover($category);
     * if ($cover['mode'] === 'glyph') { ...แสดง gradient + $cover['icon']... }
     */
    public function cover(ProductCategory|int|null $category): array
    {
        if ($category === null) {
            return $this->emptyPayload();
        }

        $id = $category instanceof ProductCategory ? (int) $category->id : (int) $category;

        $map = $this->map();
        if (isset($map[$id])) {
            return $map[$id];
        }

        // ไม่มีใน cache (เช่น หมวดเพิ่งถูกสร้างหลัง cache ถูกสร้าง)
        // → คำนวณเฉพาะชั้น 1 + ชั้น 3 แบบไม่ยิงคิวรีเพิ่ม
        if ($category instanceof ProductCategory) {
            return $this->buildPayload($category, []);
        }

        return $this->emptyPayload();
    }

    /**
     * ดึงเฉพาะคลาสไอคอนของหมวดหมู่ (null = ไม่พบไอคอนที่เหมาะสม)
     */
    public function icon(ProductCategory|int|null $category): ?string
    {
        return $this->cover($category)['icon'] ?? null;
    }

    /**
     * ดึงรายการสินค้าตัวอย่าง (ใหม่ล่าสุด สูงสุด 4 ชิ้น) ของหมวดหมู่ + หมวดลูกหลาน
     *
     * @return array<int, array{id: int, slug: string, name: string, price: float, image: string}>
     */
    public function products(ProductCategory|int|null $category): array
    {
        return $this->cover($category)['products'] ?? [];
    }

    /**
     * ล้าง cache แผนที่ภาพปก (เรียกหลังแอดมินแก้ภาพ/ไอคอนหมวดหมู่ หรือเพิ่มสินค้าใหม่)
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->map = null;
    }

    /**
     * โหลดแผนที่ภาพปกจาก cache (สร้างใหม่เมื่อ cache หมดอายุ)
     *
     * @return array<int, array>
     */
    private function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        try {
            $this->map = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return $this->buildMap();
            });
        } catch (\Throwable $e) {
            // ถ้าอะไรพังก็ยังต้องเรนเดอร์หน้าร้านได้ (ตกไปใช้ชั้นไอคอน)
            Log::warning('CategoryImageService: สร้างแผนที่ภาพปกหมวดหมู่ไม่สำเร็จ', [
                'error' => $e->getMessage(),
            ]);
            $this->map = [];
        }

        return $this->map ?? [];
    }

    /**
     * สร้างแผนที่ภาพปกของทุกหมวดพร้อมกัน (batch — 2 คิวรีเท่านั้น)
     *
     * @return array<int, array>
     */
    private function buildMap(): array
    {
        // คิวรีที่ 1 — หมวดหมู่ทั้งหมด (ตารางเล็ก) เพื่อไต่สายลูกหลาน
        $categories = ProductCategory::query()->orderBy('id')->get();

        // คิวรีที่ 2 — สินค้าที่ขายได้จริง จัดอันดับใหม่สุดต่อหมวด
        $productsByCategory = $this->fetchRankedProducts();

        // ดัชนีลูกของแต่ละหมวด (parent_id => [child ids])
        $childrenIndex = [];
        foreach ($categories as $category) {
            $parentId = (int) ($category->parent_id ?? 0);
            $childrenIndex[$parentId][] = (int) $category->id;
        }

        $map = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category->id;

            // รวมสินค้าของหมวดนี้ + หมวดลูกหลานทุกชั้น
            $items = [];
            foreach ($this->collectSubtreeIds($categoryId, $childrenIndex) as $subtreeId) {
                foreach ($productsByCategory[$subtreeId] ?? [] as $row) {
                    $items[] = $row;
                }
            }

            // เรียงใหม่สุดก่อน แล้วตัดเหลือเท่าที่ต้องใช้
            usort($items, function ($a, $b) {
                return [$b['sort_ts'], $b['id']] <=> [$a['sort_ts'], $a['id']];
            });
            $items = array_slice($items, 0, self::MAX_COVER_IMAGES);

            $map[$categoryId] = $this->buildPayload($category, $items);
        }

        return $map;
    }

    /**
     * ไล่เก็บ id ของหมวดตัวเอง + หมวดลูกหลานทุกชั้น (มีตัวกันวนลูปกรณีข้อมูลพัง)
     *
     * @param  array<int, array<int, int>>  $childrenIndex  ดัชนี parent_id => [child ids]
     * @return array<int, int>
     */
    private function collectSubtreeIds(int $categoryId, array $childrenIndex): array
    {
        $ids = [];
        $stack = [$categoryId];
        $visited = [];

        while ($stack !== []) {
            $currentId = array_pop($stack);

            if (isset($visited[$currentId])) {
                continue; // กันข้อมูล parent_id วนกลับมาหาตัวเอง
            }

            $visited[$currentId] = true;
            $ids[] = $currentId;

            foreach ($childrenIndex[$currentId] ?? [] as $childId) {
                if (! isset($visited[$childId])) {
                    $stack[] = $childId;
                }
            }
        }

        return $ids;
    }

    /**
     * ประกอบ payload ของหมวดหมู่หนึ่งหมวดตามลำดับ 3 ชั้น
     *
     * @param  array<int, array>  $items  สินค้าที่ใช้ทำโมเสก (เรียงใหม่สุดก่อน)
     * @return array{mode: string, urls: array<int, string>, icon: string|null, products: array<int, array>}
     */
    private function buildPayload(ProductCategory $category, array $items): array
    {
        $icon = $this->resolveIcon($category);

        // สินค้าตัวอย่างสำหรับ mega menu (แนบไปเสมอ ไม่ว่าจะใช้ภาพชั้นไหน)
        $products = [];
        foreach ($items as $item) {
            $products[] = [
                'id' => $item['id'],
                'slug' => $item['slug'],
                'name' => $item['name'],
                'price' => $item['price'],
                'image' => $item['image'],
            ];
        }

        // ชั้น 1 — ภาพที่แอดมินอัปโหลด (ต้องแปลง path ให้เป็น URL ใช้งานได้จริง)
        $adminImage = $this->sanitizeUrl((string) ($category->image_url_full ?? ''));
        if ($adminImage !== null) {
            return [
                'mode' => 'image',
                'urls' => [$adminImage],
                'icon' => $icon,
                'products' => $products,
            ];
        }

        // ชั้น 2 — ภาพสินค้าจริงจากหมวดนี้/หมวดลูกหลาน
        $urls = [];
        foreach ($items as $item) {
            if (! in_array($item['image'], $urls, true)) {
                $urls[] = $item['image'];
            }
        }

        if (count($urls) === 1) {
            return ['mode' => 'image', 'urls' => $urls, 'icon' => $icon, 'products' => $products];
        }

        if (count($urls) > 1) {
            return ['mode' => 'mosaic', 'urls' => $urls, 'icon' => $icon, 'products' => $products];
        }

        // ชั้น 3 — ไม่มีภาพจริงเลย ใช้ gradient + ไอคอน
        return ['mode' => 'glyph', 'urls' => [], 'icon' => $icon, 'products' => $products];
    }

    /**
     * ดึงสินค้าที่ "ขายได้จริง" (publicVisible + inStock) พร้อมภาพ
     * จัดอันดับใหม่ล่าสุดต่อหมวดด้วย window function — 1 คิวรีสำหรับทั้งเว็บ
     *
     * @return array<int, array<int, array>> แยกกลุ่มตาม category_id
     */
    private function fetchRankedProducts(): array
    {
        $columns = ['id', 'category_id', 'name', 'slug', 'price', 'main_image_url', 'image_urls', 'created_at'];

        try {
            $inner = Product::query()
                ->publicVisible()
                ->inStock()
                ->whereNotNull('category_id')
                ->where(function ($query) {
                    $this->applyHasImageCondition($query);
                })
                ->select($columns)
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY created_at DESC, id DESC) AS tp_rn')
                ->toBase(); // ใช้ toBase() เพื่อให้ global scope (soft delete) ถูกผนวกครบ

            $rows = DB::query()
                ->fromSub($inner, 'tp_ranked')
                ->where('tp_rn', '<=', self::CANDIDATES_PER_CATEGORY)
                ->get();
        } catch (\Throwable $e) {
            // DB บางตัวไม่รองรับ window function → ใช้วิธีสำรอง (ยังคง 1 คิวรี มีเพดานแถว)
            Log::warning('CategoryImageService: window function ใช้ไม่ได้ ใช้โหมดสำรองแทน', [
                'error' => $e->getMessage(),
            ]);

            $rows = Product::query()
                ->publicVisible()
                ->inStock()
                ->whereNotNull('category_id')
                ->where(function ($query) {
                    $this->applyHasImageCondition($query);
                })
                ->select($columns)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::FALLBACK_ROW_LIMIT)
                ->get();
        }

        return $this->groupProductRows($rows);
    }

    /**
     * เงื่อนไข "สินค้ามีภาพ" (main_image_url หรือ image_urls ไม่ว่าง)
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    private function applyHasImageCondition($query): void
    {
        $query->where(function ($sub) {
            $sub->whereNotNull('main_image_url')->where('main_image_url', '!=', '');
        })->orWhere(function ($sub) {
            $sub->whereNotNull('image_urls')
                ->whereNotIn('image_urls', ['', '[]', 'null']);
        });
    }

    /**
     * แปลงแถวสินค้าดิบให้เป็นโครงสร้างที่ใช้ต่อได้ แล้วแยกกลุ่มตามหมวด
     *
     * @param  Collection<int, mixed>  $rows
     * @return array<int, array<int, array>>
     */
    private function groupProductRows($rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $categoryId = (int) ($row->category_id ?? 0);
            if ($categoryId <= 0) {
                continue;
            }

            // เผื่อโหมดสำรองที่ไม่ได้จัดอันดับมาให้ — จำกัดจำนวนต่อหมวดเอง
            if (count($grouped[$categoryId] ?? []) >= self::CANDIDATES_PER_CATEGORY) {
                continue;
            }

            $image = $this->resolveProductImage($row->main_image_url ?? null, $row->image_urls ?? null);
            if ($image === null) {
                continue;
            }

            $createdAt = $row->created_at ?? null;
            if ($createdAt instanceof \DateTimeInterface) {
                $sortTs = $createdAt->getTimestamp();
            } elseif (is_string($createdAt) && $createdAt !== '') {
                $sortTs = strtotime($createdAt) ?: 0;
            } else {
                $sortTs = 0;
            }

            $grouped[$categoryId][] = [
                'id' => (int) $row->id,
                'slug' => (string) ($row->slug ?? ''),
                'name' => (string) ($row->name ?? ''),
                'price' => (float) ($row->price ?? 0),
                'image' => $image,
                'sort_ts' => $sortTs,
            ];
        }

        return $grouped;
    }

    /**
     * เลือกภาพที่ใช้ได้ของสินค้าหนึ่งชิ้น (main_image_url ก่อน ถ้าไม่มีใช้ตัวแรกใน image_urls)
     *
     * @param  string|null  $mainImageUrl
     * @param  array|string|null  $imageUrls
     */
    private function resolveProductImage($mainImageUrl, $imageUrls): ?string
    {
        $candidate = trim((string) ($mainImageUrl ?? ''));

        if ($candidate === '') {
            $decoded = is_array($imageUrls) ? $imageUrls : json_decode((string) $imageUrls, true);

            if (is_array($decoded)) {
                foreach ($decoded as $url) {
                    if (is_string($url) && trim($url) !== '') {
                        $candidate = trim($url);
                        break;
                    }
                }
            }
        }

        return $this->sanitizeUrl($candidate);
    }

    /**
     * ตรวจ + แปลงค่าภาพให้เป็น URL ที่ใช้งานได้ (null = ใช้ไม่ได้/ภาพตาย)
     */
    private function sanitizeUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // ตัดโฮสต์ภาพที่ตายแล้วทิ้ง เพื่อไม่ให้มี "ภาพตาย" โผล่บนการ์ดหมวดหมู่
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        if ($host !== '' && in_array($host, self::DEAD_IMAGE_HOSTS, true)) {
            return null;
        }

        // เป็น URL เต็มอยู่แล้ว (https://... หรือ http://...)
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // เป็น absolute path บนเว็บอยู่แล้ว (/storage/...)
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // เป็น relative path ใน storage (เช่น categories/AbC.webp)
        return Storage::url($value);
    }

    /**
     * หาไอคอนของหมวดหมู่ตามลำดับความแม่นยำ
     *   1) คอลัมน์ icon ที่แอดมินตั้งไว้
     *   2) จับคู่ slug/ชื่อ แบบตรงตัว (exact)
     *   3) จับคู่แบบ substring — ทางเลือกสุดท้าย เรียงคีย์ยาวก่อน และคีย์ต้องยาว >= 4
     */
    private function resolveIcon(ProductCategory $category): ?string
    {
        $stored = trim((string) ($category->icon ?? ''));
        if ($stored !== '') {
            return $this->normalizeIconClass($stored);
        }

        $name = mb_strtolower(trim((string) $category->name));
        $slug = mb_strtolower(trim((string) $category->slug));

        // 2) exact match — slug ก่อน แล้วค่อยชื่อ
        foreach ([$slug, $name] as $key) {
            if ($key !== '' && isset(self::ICON_MAP[$key])) {
                return self::ICON_MAP[$key];
            }
        }

        // 2.1) exact match หลังแปลงขีด/ขีดล่างเป็นช่องว่าง (เช่น 'home-decor' → 'home decor')
        foreach ([$slug, $name] as $key) {
            $normalized = trim(str_replace(['-', '_'], ' ', $key));
            if ($normalized !== '' && isset(self::ICON_MAP[$normalized])) {
                return self::ICON_MAP[$normalized];
            }
        }

        // 3) substring — คีย์ยาวก่อน เพื่อลดการชนกัน (haircare ต้องไม่โดน car)
        foreach ($this->iconKeysByLength() as $key) {
            if (mb_strlen($key) < 4) {
                continue; // คีย์สั้นอันตราย ('all', 'car', 'ยา') ใช้ได้เฉพาะ exact
            }

            if (($name !== '' && str_contains($name, $key)) || ($slug !== '' && str_contains($slug, $key))) {
                return self::ICON_MAP[$key];
            }
        }

        return null;
    }

    /**
     * คีย์ของตารางไอคอน เรียงจากยาวไปสั้น (คำนวณครั้งเดียวต่อ request)
     *
     * @return array<int, string>
     */
    private function iconKeysByLength(): array
    {
        static $keys = null;

        if ($keys === null) {
            $keys = array_keys(self::ICON_MAP);
            usort($keys, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        }

        return $keys;
    }

    /**
     * ทำความสะอาดคลาสไอคอนที่มาจากฐานข้อมูล (กันอักขระแปลกปลอมหลุดเข้า class attribute)
     * และเติม prefix สไตล์ให้ถ้าแอดมินกรอกมาแค่ 'fa-heart'
     */
    private function normalizeIconClass(string $icon): ?string
    {
        $icon = trim(preg_replace('/[^a-zA-Z0-9\- ]/', '', $icon) ?? '');
        $icon = trim(preg_replace('/\s+/', ' ', $icon) ?? '');

        if ($icon === '') {
            return null;
        }

        $hasStyle = (bool) preg_match('/\b(fa-solid|fa-regular|fa-light|fa-thin|fa-duotone|fa-brands|fas|far|fal|fab)\b/', $icon);

        return $hasStyle ? $icon : 'fa-solid '.$icon;
    }

    /**
     * payload ว่าง (ชั้นไอคอนล้วน)
     *
     * @return array{mode: string, urls: array, icon: string|null, products: array}
     */
    private function emptyPayload(): array
    {
        return ['mode' => 'glyph', 'urls' => [], 'icon' => null, 'products' => []];
    }
}
