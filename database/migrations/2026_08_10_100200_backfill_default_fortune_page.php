<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-10) ระบบสาขา — สร้าง "สาขาแรก" จากค่าที่ใช้อยู่ + ติดป้ายแถวเก่าทั้งหมด
 *
 * ถ้าไม่ backfill: บิลเก่าทุกใบ fortune_page_id = null → รายงานแยกสาขาจะโชว์
 * "ไม่ระบุเพจ" เป็นก้อนใหญ่ที่สุด = ตัวเลขอ่านไม่รู้เรื่อง
 *
 * ⚠️ ตาราง fortune_readings บน prod ใหญ่ → update ทีละ 5,000 แถว ไม่ยิงรวดเดียว
 *    (ยิงรวดเดียวล็อกตารางนาน webhook ที่วิ่งพร้อมกันจะ timeout)
 */
return new class extends Migration
{
    /** จำนวนแถวต่อรอบ update */
    protected const CHUNK = 5000;

    /**
     * ตารางที่ต้องติดป้ายสาขา
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'fortune_readings',
        'fortune_customer_personas',
        'fortune_user_credits',
        'fortune_referrals',
        'fortune_commissions',
        'fortune_comment_engagements',
        'fortune_user_bans',
        'fortune_post_reactions',
    ];

    /**
     * สร้างสาขาเริ่มต้นแล้ว backfill
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_pages') || ! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        $settings = DB::table('fortune_telling_settings')->first();
        if (! $settings) {
            return; // ยังไม่เคยตั้งค่า = ติดตั้งใหม่ ปล่อยให้ seeder จัดการ
        }

        $now = now();

        // 1️⃣ สาขา Facebook (สาขาหลัก)
        $facebookPageId = $this->backfillPageFor(
            platform: 'facebook',
            externalId: $settings->facebook_page_id ?? null,
            code: 'main-facebook',
            name: $settings->fortune_brand_name ?? 'เพจแม่หมอหลัก (Facebook)',
            isDefault: true,
            now: $now
        );

        // 2️⃣ สาขา LINE (ถ้าตั้งค่าไว้) — เพื่อให้รายงานแยกช่องทางครบ ไม่มีก้อน null
        $linePageId = $this->backfillPageFor(
            platform: 'line',
            externalId: $settings->line_channel_id ?? null,
            code: 'main-line',
            name: 'แม่หมอ LINE OA',
            isDefault: false,
            now: $now
        );

        // 3️⃣ ติดป้ายแถวเก่า
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'fortune_page_id')) {
                continue;
            }

            $hasPlatform = Schema::hasColumn($tableName, 'platform');

            if ($facebookPageId) {
                $this->stamp($tableName, $facebookPageId, $hasPlatform ? 'facebook' : null);
            }

            if ($linePageId && $hasPlatform) {
                $this->stamp($tableName, $linePageId, 'line');
            }

            // ตารางที่ไม่มีคอลัมน์ platform → เหมาเป็นสาขาหลัก
            if (! $hasPlatform && $facebookPageId) {
                $this->stamp($tableName, $facebookPageId, null);
            }
        }
    }

    /**
     * ย้อนกลับ: ล้างป้ายสาขาและลบแถวสาขาที่ migration นี้สร้าง
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'fortune_page_id')) {
                DB::table($tableName)->update(['fortune_page_id' => null]);
            }
        }

        if (Schema::hasTable('fortune_pages')) {
            DB::table('fortune_pages')->whereIn('code', ['main-facebook', 'main-line'])->delete();
        }
    }

    /**
     * สร้าง (หรือหา) แถวสาขาสำหรับช่องทางหนึ่ง
     *
     * @return int|null id ของสาขา — null ถ้ายังไม่ได้ตั้งค่าช่องทางนั้น
     */
    protected function backfillPageFor(
        string $platform,
        ?string $externalId,
        string $code,
        string $name,
        bool $isDefault,
        $now
    ): ?int {
        if (empty($externalId)) {
            return null;
        }

        $existing = DB::table('fortune_pages')
            ->where('platform', $platform)
            ->where('external_page_id', $externalId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('fortune_pages')->insertGetId([
            'code' => $code,
            'name' => $name,
            'brand_name' => null,
            'platform' => $platform,
            'external_page_id' => $externalId,
            // ⚠️ ไม่ก็อป token/secret ลงมา — ปล่อย null ให้ fallback ไปค่า global
            //    (ค่า global ยังเป็นของเพจนี้อยู่แล้ว) จะได้ไม่มีความลับซ้ำ 2 ที่
            'page_access_token' => null,
            'app_secret' => null,
            'verify_token' => null,
            'settings_override' => null,
            'owner_user_id' => null,
            'is_active' => true,
            'is_default' => $isDefault,
            'notes' => 'สร้างอัตโนมัติจากค่าที่ใช้อยู่เดิม (migration 2026-08-10)',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * ติดป้ายสาขาให้แถวที่ยังว่าง ทีละ CHUNK แถว
     */
    protected function stamp(string $tableName, int $pageId, ?string $platform): void
    {
        do {
            $query = DB::table($tableName)->whereNull('fortune_page_id');

            if ($platform !== null) {
                $query->where('platform', $platform);
            }

            $affected = $query->limit(self::CHUNK)->update(['fortune_page_id' => $pageId]);
        } while ($affected > 0);
    }
};
