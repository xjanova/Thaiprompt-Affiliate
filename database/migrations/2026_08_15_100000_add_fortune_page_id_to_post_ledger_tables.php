<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-15) ติดป้ายสาขาให้ "สมุดบันทึกว่าโพสไปแล้ว" ทั้ง 3 เล่ม
 *
 * ทำไมต้องมี:
 *   cron โพสอัตโนมัติ (ดวงรายวัน / สายมู / แคมเปญคอนเทนต์) เช็คซ้ำด้วย
 *   "วันนี้ + slot นี้ โพสไปหรือยัง" จากตารางกลางที่ไม่รู้จักสาขา
 *   → พอวนโพสให้หลายสาขา สาขาแรกโพสแล้วบันทึกไว้
 *     สาขาที่ 2-N จะเห็นว่า "โพสแล้ว" แล้วข้ามเงียบๆ ไม่มี error ให้เห็น
 *   ต้องแยกสมุดรายสาขาก่อน ถึงจะวนโพสได้จริง
 *
 * ⚠️ backfill สำคัญมาก:
 *   แถวเก่าเป็น NULL ทั้งหมด ถ้าไม่เติมให้เป็นสาขาหลัก
 *   รอบโพสถัดไปของสาขาหลักจะมองไม่เห็นแถวของวันนี้ (เพราะกรอง fortune_page_id = 1)
 *   → โพสซ้ำลงเพจจริง นั่นคือความเสียหายที่มองเห็นได้จากภายนอก
 */
return new class extends Migration
{
    /**
     * ตารางที่ต้องติดป้ายสาขา
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'fortune_daily_horoscope_posts',
        'fortune_mystic_posts',
        'fortune_content_posts',
    ];

    /**
     * unique index เดิม → unique index ใหม่ที่รวมสาขาเข้าไปด้วย
     *
     * ⚠️ จุดที่พลาดแล้วพังทันที: ทั้ง 3 ตารางมี unique เดิมที่ "ไม่รู้จักสาขา"
     *    เช่น (post_date, day_of_birth) — พอสาขาที่ 2 จะโพสดวงวันเดียวกัน
     *    INSERT จะชน unique แล้วโยน error ทั้งรอบ cron
     *    ต้องขยาย unique ให้รวม fortune_page_id ก่อนเสมอ
     *
     * @var array<string, array{old: string, new: string, cols: array<int, string>}>
     */
    protected array $uniqueIndexes = [
        'fortune_daily_horoscope_posts' => [
            'old' => 'fdh_date_day_unique',
            'new' => 'fdh_page_date_day_unique',
            'cols' => ['fortune_page_id', 'post_date', 'day_of_birth'],
        ],
        'fortune_mystic_posts' => [
            'old' => 'fmp_date_slot_unique',
            'new' => 'fmp_page_date_slot_unique',
            'cols' => ['fortune_page_id', 'post_date', 'slot_hour'],
        ],
        'fortune_content_posts' => [
            'old' => 'fcp_camp_date_slot_unique',
            'new' => 'fcp_page_camp_date_slot_unique',
            'cols' => ['fortune_page_id', 'campaign_id', 'post_date', 'slot_time'],
        ],
    ];

    /**
     * เพิ่มคอลัมน์ fortune_page_id + backfill + ขยาย unique index
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // ⚠️ ALTER TABLE ห้ามใช้ hasTable() + return — ต้องเช็คทีละคอลัมน์
            if (! Schema::hasColumn($table, 'fortune_page_id')) {
                Schema::table($table, function (Blueprint $t) {
                    // ไม่ผูก foreign key: สาขาถูกปิดได้แต่ประวัติโพสต้องอยู่ต่อ
                    $t->unsignedBigInteger('fortune_page_id')->nullable()->after('id');
                    $t->index('fortune_page_id');
                });
            }
        }

        // เติมสาขาหลักให้ครบ "ก่อน" สร้าง unique ใหม่
        // (ปล่อย NULL ไว้แล้วสร้าง unique = MySQL ถือว่า NULL ไม่ซ้ำกัน → กันซ้ำไม่ได้จริง)
        $this->backfillToDefaultPage();

        $this->widenUniqueIndexes();
    }

    /**
     * ขยาย unique index เดิมให้รวมสาขา
     */
    protected function widenUniqueIndexes(): void
    {
        foreach ($this->uniqueIndexes as $table => $spec) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'fortune_page_id')) {
                continue;
            }

            // สร้างตัวใหม่ก่อน แล้วค่อยทิ้งตัวเก่า — ระหว่างทางยังมีเกราะกันซ้ำเสมอ
            if (! $this->indexExists($table, $spec['new'])) {
                Schema::table($table, function (Blueprint $t) use ($spec) {
                    $t->unique($spec['cols'], $spec['new']);
                });
            }

            if ($this->indexExists($table, $spec['old'])) {
                Schema::table($table, function (Blueprint $t) use ($spec) {
                    $t->dropUnique($spec['old']);
                });
            }
        }
    }

    /**
     * index ชื่อนี้มีอยู่จริงไหม
     */
    protected function indexExists(string $table, string $index): bool
    {
        try {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $index)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * เติมสาขาหลักให้แถวที่มีอยู่เดิม
     *
     * โพสเก่าทั้งหมดออกจากเพจหลักเพจเดียว (ตอนนั้นยังไม่มีระบบสาขา)
     * ทำเป็นก้อนละ 2,000 แถว — ตารางพวกนี้เล็ก (หลักร้อย) แต่กันไว้เผื่อโตขึ้น
     */
    protected function backfillToDefaultPage(): void
    {
        $defaultPageId = $this->defaultFacebookPageId();

        if ($defaultPageId === null) {
            return; // ยังไม่มีสาขา = ติดตั้งใหม่ ไม่มีอะไรให้เติม
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'fortune_page_id')) {
                continue;
            }

            do {
                $affected = DB::table($table)
                    ->whereNull('fortune_page_id')
                    ->limit(2000)
                    ->update(['fortune_page_id' => $defaultPageId]);
            } while ($affected > 0);
        }
    }

    /**
     * ไอดีสาขาหลักของ Facebook (null = ยังไม่มีตาราง/ยังไม่มีสาขา)
     */
    protected function defaultFacebookPageId(): ?int
    {
        if (! Schema::hasTable('fortune_pages')) {
            return null;
        }

        $id = DB::table('fortune_pages')
            ->where('platform', 'facebook')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * ถอนคอลัมน์ออก
     */
    public function down(): void
    {
        foreach ($this->uniqueIndexes as $table => $spec) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            // คืน unique เดิมก่อน แล้วค่อยทิ้งตัวใหม่ (มีเกราะกันซ้ำตลอดทาง)
            // ⚠️ ถ้ามีโพสของหลายสาขาในวัน/slot เดียวกันอยู่ unique เดิมจะสร้างไม่ได้
            //    ปล่อยให้ error ดังไปเลย ดีกว่าลบข้อมูลทิ้งเงียบๆ
            if (! $this->indexExists($table, $spec['old'])) {
                Schema::table($table, function (Blueprint $t) use ($spec) {
                    $cols = array_values(array_diff($spec['cols'], ['fortune_page_id']));
                    $t->unique($cols, $spec['old']);
                });
            }

            if ($this->indexExists($table, $spec['new'])) {
                Schema::table($table, function (Blueprint $t) use ($spec) {
                    $t->dropUnique($spec['new']);
                });
            }
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'fortune_page_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['fortune_page_id']);
                $t->dropColumn('fortune_page_id');
            });
        }
    }
};
