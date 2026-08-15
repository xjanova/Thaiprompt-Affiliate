<?php

namespace App\Models\Concerns;

use App\Models\FortunePage;
use App\Services\Fortune\FortunePageContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 🏬 (2026-08-10) ติดป้ายสาขาให้อัตโนมัติตอนสร้างแถวใหม่
 *
 * ทำไมใช้ hook แทนการไล่แก้จุดที่ create():
 *   บิล/persona/เครดิต ถูกสร้างกระจายหลายสิบจุดทั้งใน webhook, job, คอนโซล
 *   ไล่แก้ทีละจุด = ลืมบางจุดแน่นอน แล้วจะได้บิล fortune_page_id = null แบบเงียบๆ
 *   (แถวเงียบแบบนี้คือสิ่งที่ทำให้รายงานสาขาเชื่อไม่ได้)
 *
 * ⚠️ ต้นทางของค่าคือ FortunePageContext เท่านั้น
 *    ถ้า context ไม่ถูก set (เช่นคอนโซลที่แอดมินรันเอง) จะได้ null — ตั้งใจให้เป็นแบบนั้น
 *    ใส่มั่วเป็นสาขาหลักจะทำให้ตัวเลขรายได้ของสาขาหลักเฟ้อ
 */
trait BelongsToFortunePage
{
    /**
     * ตารางไหนมีคอลัมน์ fortune_page_id แล้วบ้าง (memo ต่อโปรเซส)
     *
     * @var array<string, bool>
     */
    protected static array $fortunePageColumnCache = [];

    /**
     * ผูก hook ตอน boot model
     */
    public static function bootBelongsToFortunePage(): void
    {
        static::creating(function ($model) {
            if (! empty($model->fortune_page_id)) {
                return;
            }

            $pageId = FortunePageContext::currentId();

            if ($pageId === null) {
                return; // ไม่มี context = ไม่ต้องเช็คคอลัมน์ ไม่เสีย query
            }

            // ⚠️ ช่วงดีพลอย: โค้ดใหม่ขึ้นก่อน migration เสร็จได้เสมอ
            //    ถ้ายัดค่าลงคอลัมน์ที่ยังไม่มี = INSERT พังทั้งคำสั่ง = บอทตายทั้งระบบ
            //    เช็คครั้งเดียวต่อโปรเซส แล้วจำไว้
            if (! static::fortunePageColumnExists($model->getTable())) {
                return;
            }

            $model->fortune_page_id = $pageId;
        });
    }

    /**
     * ตารางนี้มีคอลัมน์ fortune_page_id หรือยัง
     */
    protected static function fortunePageColumnExists(string $table): bool
    {
        if (array_key_exists($table, static::$fortunePageColumnCache)) {
            return static::$fortunePageColumnCache[$table];
        }

        try {
            $exists = \Illuminate\Support\Facades\Schema::hasColumn($table, 'fortune_page_id');
        } catch (\Throwable $e) {
            $exists = false;
        }

        return static::$fortunePageColumnCache[$table] = $exists;
    }

    /**
     * ความสัมพันธ์กับสาขา
     */
    public function fortunePage(): BelongsTo
    {
        return $this->belongsTo(FortunePage::class, 'fortune_page_id');
    }

    /**
     * กรองเฉพาะสาขาที่ระบุ (ใช้ในหน้ารายงาน/ฟิลเตอร์หลังบ้าน)
     *
     * @param  int|string|null  $pageId  null/'' = ไม่กรอง
     */
    public function scopeForFortunePage(Builder $query, $pageId): Builder
    {
        if ($pageId === null || $pageId === '') {
            return $query;
        }

        // 'none' = แถวที่ยังไม่มีสาขา (ของเก่าที่ backfill ไม่ถึง / งานคอนโซล)
        if ($pageId === 'none') {
            return $query->whereNull('fortune_page_id');
        }

        return $query->where('fortune_page_id', (int) $pageId);
    }

    /**
     * 🏬 (2026-08-15) กรองเฉพาะสาขาที่ "กำลังทำงานอยู่ตอนนี้"
     *
     * ใช้กับคิวรีที่ถามว่า "งานนี้ทำไปแล้วหรือยัง" (สมุดกันโพสซ้ำ ฯลฯ)
     * ซึ่งเดิมถามรวมทุกสาขา → สาขาแรกทำเสร็จ สาขาที่เหลือถูกข้ามเงียบๆ
     *
     * ไม่กรองใน 2 กรณี (ตั้งใจ — ต้องได้พฤติกรรมเดิมเป๊ะ):
     *   1. ไม่มี context (คอนโซลที่แอดมินรันเอง / ระบบเดิมที่ยังไม่มีสาขา)
     *   2. คอลัมน์ยังไม่มี — ช่วง deploy โค้ดขึ้นก่อน migration เสมอ
     *      ถ้าไม่กันไว้ คิวรีจะพังทั้งคำสั่ง = cron ตายทั้งระบบ
     */
    public function scopeForCurrentFortunePage(Builder $query): Builder
    {
        $pageId = FortunePageContext::currentId();

        if ($pageId === null) {
            return $query;
        }

        if (! static::fortunePageColumnExists($query->getModel()->getTable())) {
            return $query;
        }

        return $query->where('fortune_page_id', $pageId);
    }
}
