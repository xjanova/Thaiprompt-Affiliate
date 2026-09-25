<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แบนเนอร์เปิดตัว (data migration) — ตลาดสดไทยพร้อม / เปิดร้านฟรี / มาเป็นไรเดอร์
 *
 * idempotent: ใส่เฉพาะ campaign_key ที่ยังไม่มี — ถ้าแอดมินแก้ข้อความ/ปิดไปแล้ว รันซ้ำก็ไม่ทับ
 * รูปไม่มีตัวหนังสือ (แอป/เว็บวางข้อความทับ) อยู่ใน public/images/taladsod/ ไปพร้อมโค้ด
 * ลิงก์เดิม (link/link_type) ตั้งให้ตรงกับ CTA เพื่อให้ /api/v1/mobile/banners ของแอปรุ่นเก่ายังใช้ได้
 */
return new class extends Migration
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function banners(): array
    {
        return [
            [
                'campaign_key' => 'launch-2026-taladsod-home',
                'title' => 'เปิดตัวตลาดสดไทยพร้อม',
                'subtitle' => 'ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย',
                'image' => '/images/taladsod/banner-market.webp',
                'cta_label' => 'สั่งเลย',
                'cta_type' => 'screen',
                'cta_value' => 'taladsod',
                'link' => '/taladsod',
                'link_type' => 'internal',
                'position' => 'home',
                'audience' => 'all',
                'sort_order' => 1,
            ],
            [
                'campaign_key' => 'launch-2026-taladsod-market',
                'title' => 'เปิดตัวตลาดสดไทยพร้อม',
                'subtitle' => 'ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย',
                'image' => '/images/taladsod/banner-market.webp',
                'cta_label' => 'สั่งเลย',
                'cta_type' => 'screen',
                'cta_value' => 'taladsod',
                'link' => '/taladsod',
                'link_type' => 'internal',
                'position' => 'taladsod',
                'audience' => 'all',
                'sort_order' => 1,
            ],
            [
                'campaign_key' => 'launch-2026-merchant-free-gp',
                'title' => 'เปิดร้านฟรี ไม่มีค่า GP ช่วงเปิดตัว',
                'subtitle' => 'รถเข็น ตลาดนัด ร้านเล็กก็ขายได้',
                'image' => '/images/taladsod/banner-merchant.webp',
                'cta_label' => 'เปิดร้านเลย',
                'cta_type' => 'url',
                'cta_value' => '/taladsod/start/seller',
                'link' => '/taladsod/start/seller',
                'link_type' => 'external',
                'position' => 'merchant',
                'audience' => 'all',
                'sort_order' => 1,
            ],
            [
                'campaign_key' => 'launch-2026-rider-recruit',
                'title' => 'มาเป็นไรเดอร์ รับงานใกล้บ้าน',
                'subtitle' => 'ค่าส่งเข้ากระเป๋าทันทีที่ส่งสำเร็จ',
                'image' => '/images/taladsod/banner-rider.webp',
                'cta_label' => 'สมัครไรเดอร์',
                'cta_type' => 'screen',
                'cta_value' => 'rider',
                'link' => '/rider',
                'link_type' => 'internal',
                'position' => 'rider',
                'audience' => 'all',
                'sort_order' => 1,
            ],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('mobile_banners') || ! Schema::hasColumn('mobile_banners', 'campaign_key')) {
            return;
        }

        $now = now();

        foreach ($this->banners() as $banner) {
            $exists = DB::table('mobile_banners')->where('campaign_key', $banner['campaign_key'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('mobile_banners')->insert(array_merge($banner, [
                'link_target' => null,
                'is_active' => true,
                'start_date' => null,
                'end_date' => null,
                'view_count' => 0,
                'click_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // ให้ API แบนเนอร์อ่านข้อมูลใหม่ทันที (เปลี่ยนเวอร์ชัน cache — ไม่ต้องรอ 5 นาที)
        try {
            Cache::forever('app_banners:version', (string) now()->getTimestampMs());
        } catch (\Throwable) {
            // cache ใช้ไม่ได้ตอน migrate ไม่เป็นไร — หมดอายุเองใน 5 นาที
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('mobile_banners') || ! Schema::hasColumn('mobile_banners', 'campaign_key')) {
            return;
        }

        DB::table('mobile_banners')
            ->whereIn('campaign_key', array_column($this->banners(), 'campaign_key'))
            ->delete();
    }
};
