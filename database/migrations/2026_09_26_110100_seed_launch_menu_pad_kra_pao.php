<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * เมนูเปิดตัวตลาดสด (data migration — รันตอน deploy ครั้งเดียว, รันซ้ำได้ไม่เกิดข้อมูลซ้ำ)
 *
 * 1. หมวด "อาหารพร้อมทาน"
 * 2. ร้าน "ครัวไทยพร้อม" ของแอดมินคนแรก (ยืนยันแล้ว เปิดใช้งาน ยังไม่ปักหมุด + ปิดร้านไว้ — เจ้าของเปิดร้านพร้อมตำแหน่งเอง)
 * 3. สินค้า "ผัดกะเพราราดข้าว" 50 บาท ทำตามสั่ง (ไม่ตัดสต็อก)
 *    - กลุ่ม "เลือกเนื้อสัตว์" (บังคับ เลือก 1): หมูสับ +0, ไก่ +0, หมึก +10, กุ้ง +20
 *    - กลุ่ม "เพิ่มเติม" (ไม่บังคับ เลือกได้สูงสุด 1): ไข่ดาว +10
 *
 * ไม่มีผู้ใช้แอดมิน → ข้ามส่วนร้าน/สินค้า (บันทึก log) ไม่ทำให้ deploy ล้ม
 * ใช้ DB::table ตรงๆ (ไม่ผูกกับ Model ที่อาจเปลี่ยนภายหลัง)
 */
return new class extends Migration
{
    private const CATEGORY_NAME = 'อาหารพร้อมทาน';

    private const SHOP_NAME = 'ครัวไทยพร้อม';

    private const LISTING_SLUG = 'pad-kra-pao-rad-khao';

    private const IMG = '/images/taladsod/';

    public function up(): void
    {
        $required = [
            'users', 'fresh_market_categories', 'fresh_market_sellers', 'fresh_market_listings',
            'fresh_market_listing_option_groups', 'fresh_market_listing_options',
        ];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                Log::warning('LaunchMenuSeed: ไม่มีตาราง '.$table.' — ข้ามการสร้างเมนูเปิดตัว');

                return;
            }
        }

        DB::transaction(function () {
            $now = now();
            $categoryId = $this->ensureCategory($now);
            $adminId = $this->firstAdminId();

            if (! $adminId) {
                Log::warning('LaunchMenuSeed: ยังไม่มีผู้ใช้แอดมิน — ข้ามการสร้างร้าน "'.self::SHOP_NAME.'" และเมนูเปิดตัว');

                return;
            }

            $sellerId = $this->ensureShop($adminId, $now);
            $listingId = $this->ensureListing($sellerId, $categoryId, $now);

            if ($listingId) {
                $this->ensureGroup($listingId, 'เลือกเนื้อสัตว์', 'single', true, 1, 1, 0, [
                    ['หมูสับ', 0, 'krapao-pork.webp'],
                    ['ไก่', 0, 'krapao-chicken.webp'],
                    ['หมึก', 10, 'krapao-squid.webp'],
                    ['กุ้ง', 20, 'krapao-shrimp.webp'],
                ], $now);

                $this->ensureGroup($listingId, 'เพิ่มเติม', 'multi', false, 0, 1, 1, [
                    ['ไข่ดาว', 10, 'fried-egg.webp'],
                ], $now);
            }

            // โควต้าลงขายของร้านนับจากสินค้าที่ยังไม่ถูกลบ
            DB::table('fresh_market_sellers')->where('id', $sellerId)->update([
                'total_listings' => DB::table('fresh_market_listings')
                    ->where('seller_id', $sellerId)
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['active', 'sold_out', 'draft'])
                    ->count(),
            ]);

            Log::info('LaunchMenuSeed: พร้อมเมนูเปิดตัว', [
                'category_id' => $categoryId,
                'seller_id' => $sellerId,
                'listing_id' => $listingId,
            ]);
        });
    }

    /**
     * หมวด "อาหารพร้อมทาน" (มีแล้วใช้ของเดิม)
     */
    private function ensureCategory($now): int
    {
        $existing = DB::table('fresh_market_categories')->where('name', self::CATEGORY_NAME)->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $slug = 'ready-meals';
        while (DB::table('fresh_market_categories')->where('slug', $slug)->exists()) {
            $slug = 'ready-meals-'.Str::lower(Str::random(4));
        }

        return (int) DB::table('fresh_market_categories')->insertGetId([
            'name' => self::CATEGORY_NAME,
            'slug' => $slug,
            'icon' => '🍛',
            'description' => 'อาหารตามสั่ง ข้าวราด กับข้าวปรุงสดใหม่ พร้อมทานทุกจาน',
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => true,
            'image_url' => self::IMG.'krapao-hero.webp',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * ผู้ใช้แอดมินคนแรก (role admin/super_admin, is_super_admin หรือ role_id ของบทบาทแอดมิน)
     */
    private function firstAdminId(): ?int
    {
        $query = DB::table('users')->where(function ($q) {
            if (Schema::hasColumn('users', 'role')) {
                $q->orWhereIn('role', ['admin', 'super_admin']);
            }

            if (Schema::hasColumn('users', 'is_super_admin')) {
                $q->orWhere('is_super_admin', true);
            }

            if (Schema::hasColumn('users', 'role_id') && Schema::hasTable('roles')) {
                $q->orWhereIn('role_id', DB::table('roles')->select('id')->whereIn('name', ['admin', 'super_admin']));
            }
        });

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $id = $query->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * ร้าน "ครัวไทยพร้อม" ของแอดมิน (มีแล้วใช้ของเดิม ไม่แก้สถานะที่แอดมินตั้งไว้)
     */
    private function ensureShop(int $adminId, $now): int
    {
        $byName = DB::table('fresh_market_sellers')
            ->where('shop_name', self::SHOP_NAME)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        if ($byName) {
            return (int) $byName;
        }

        // แอดมินมีร้านอยู่แล้ว (user_id unique) → ใช้ร้านนั้น ถ้าเคยปิดร้าน (soft delete) ให้เปิดกลับ
        $own = DB::table('fresh_market_sellers')->where('user_id', $adminId)->first();

        if ($own) {
            if ($own->deleted_at !== null) {
                DB::table('fresh_market_sellers')->where('id', $own->id)->update([
                    'deleted_at' => null,
                    'is_active' => true,
                    'is_suspended' => false,
                    'is_verified' => true,
                    'updated_at' => $now,
                ]);
            }

            return (int) $own->id;
        }

        $row = [
            'user_id' => $adminId,
            'shop_name' => self::SHOP_NAME,
            'shop_description' => 'ครัวตามสั่งของไทยพร้อม ผัดสดทุกจาน วัตถุดิบคัดเอง รสจัดจ้านแบบร้านข้างทาง สะอาดแบบกินได้ทุกวัน',
            'shop_image' => self::IMG.'krapao-hero.webp',
            'latitude' => null,
            'longitude' => null,
            'address' => null,
            'subscription_type' => 'free',
            'subscription_expires_at' => null,
            'is_verified' => true,
            'is_active' => true,
            'is_suspended' => false,
            'total_listings' => 0,
            'total_sales' => 0,
            'total_revenue' => 0,
            'rating_average' => 0,
            'rating_count' => 0,
            'referral_code' => $this->uniqueReferralCode(),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // ร้านรถเข็น/ตลาดนัด: ปิดร้านไว้ก่อน เจ้าของกด "เปิดร้านที่นี่วันนี้" พร้อมตำแหน่งเอง
        if (Schema::hasColumn('fresh_market_sellers', 'is_open')) {
            $row['is_open'] = false;
        }

        // ร้านนี้ไม่มีที่อยู่ประจำ → ตำแหน่งร้าน = จุดที่กด "เปิดร้านที่นี่วันนี้" (migration 2026_09_26_105000)
        if (Schema::hasColumn('fresh_market_sellers', 'is_mobile')) {
            $row['is_mobile'] = true;
        }

        return (int) DB::table('fresh_market_sellers')->insertGetId($row);
    }

    /**
     * สินค้า "ผัดกะเพราราดข้าว" (มีแล้ว — รวมที่ถูกลบ — ไม่สร้างซ้ำและไม่ชุบชีวิตสินค้าที่แอดมินลบไป)
     *
     * @return int|null id ของสินค้าที่ยังใช้งาน (null = ถูกลบไปแล้ว ไม่ต้องเติมตัวเลือก)
     */
    private function ensureListing(int $sellerId, int $categoryId, $now): ?int
    {
        $existing = DB::table('fresh_market_listings')->where('slug', self::LISTING_SLUG)->first();

        if ($existing) {
            return $existing->deleted_at === null ? (int) $existing->id : null;
        }

        $gallery = array_map(fn ($f) => self::IMG.$f, [
            'krapao-hero.webp',
            'krapao-hero-2.webp',
            'krapao-pork.webp',
            'krapao-chicken.webp',
            'krapao-squid.webp',
            'krapao-shrimp.webp',
        ]);

        $row = [
            'seller_id' => $sellerId,
            'category_id' => $categoryId,
            'slug' => self::LISTING_SLUG,
            'title' => 'ผัดกะเพราราดข้าว',
            'description' => 'ผัดกะเพราสูตรร้านตามสั่งแท้ ๆ ผัดไฟแรงจนหอมกลิ่นกระทะ ใบกะเพราสดเต็มกำ พริกกระเทียมตำหยาบ เผ็ดร้อนกำลังดี '
                .'ราดบนข้าวหอมมะลิร้อน ๆ เลือกเนื้อได้ตามใจ หมูสับนุ่มฉ่ำ ไก่ชิ้นพอดีคำ หมึกเด้งกรุบ หรือกุ้งสดตัวโต '
                .'เพิ่มไข่ดาวขอบกรอบไข่แดงเยิ้มได้อีกฟอง ผัดสดใหม่ทุกจานหลังกดสั่ง',
            'price' => 50,
            'compare_at_price' => null,
            // ทำตามสั่ง: ไม่ตัดสต็อก (จำนวนนี้ไม่ถูกใช้ — ใส่ไว้ให้หน้าเก่าที่ยังอ่านจำนวนคงเหลือสั่งได้)
            'quantity_available' => 999,
            'unit' => 'จาน',
            'images' => json_encode($gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'main_image_url' => self::IMG.'krapao-hero.webp',
            'latitude' => null,
            'longitude' => null,
            'delivery_radius_km' => 10,
            'is_available' => true,
            'is_featured' => true,
            'is_organic' => false,
            'tags' => json_encode(['ผัดกะเพรา', 'อาหารตามสั่ง', 'ข้าวราด', 'ทำสดใหม่', 'อาหารจานเดียว'], JSON_UNESCAPED_UNICODE),
            'freshness_level' => 'ผลิตวันนี้',
            'pv_value' => 0,
            'cashback_amount' => 0,
            'cashback_percentage' => 0,
            'commission_rate' => 0,
            'view_count' => 0,
            'order_count' => 0,
            'status' => 'active',
            'created_via' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('fresh_market_listings', 'track_stock')) {
            $row['track_stock'] = false;
        }

        return (int) DB::table('fresh_market_listings')->insertGetId($row);
    }

    /**
     * กลุ่มตัวเลือก (มีกลุ่มชื่อนี้แล้ว = ไม่แตะ)
     *
     * @param  array<int, array{0: string, 1: int|float, 2: string}>  $options  [ชื่อ, ราคาเพิ่ม, ไฟล์รูป]
     */
    private function ensureGroup(
        int $listingId,
        string $name,
        string $type,
        bool $required,
        int $min,
        ?int $max,
        int $sort,
        array $options,
        $now
    ): void {
        $exists = DB::table('fresh_market_listing_option_groups')
            ->where('listing_id', $listingId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return;
        }

        $groupId = DB::table('fresh_market_listing_option_groups')->insertGetId([
            'listing_id' => $listingId,
            'name' => $name,
            'selection_type' => $type,
            'is_required' => $required,
            'min_select' => $min,
            'max_select' => $max,
            'sort_order' => $sort,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_values($options) as $i => [$optionName, $delta, $image]) {
            DB::table('fresh_market_listing_options')->insert([
                'group_id' => $groupId,
                'listing_id' => $listingId,
                'name' => $optionName,
                'price_delta' => $delta,
                'image_url' => self::IMG.$image,
                'is_available' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function uniqueReferralCode(): string
    {
        do {
            $code = 'TSD'.strtoupper(Str::random(7));
        } while (DB::table('fresh_market_sellers')->where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * ไม่ลบข้อมูลตอน rollback — ร้าน/สินค้าอาจมีออเดอร์จริงแล้ว
     */
    public function down(): void
    {
        //
    }
};
