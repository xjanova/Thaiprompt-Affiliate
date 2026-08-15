<?php

namespace App\Services\Fortune;

use App\Models\FortuneCustomerPersona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🔗 (2026-08-15) เย็บ "ลูกค้าคนเดียวกัน" ข้ามสาขา ด้วย ASID
 *
 * ปัญหา: Facebook ให้ PSID คนละตัวต่อเพจ → คนเดียวทัก 3 สาขา = persona 3 ใบ
 *        แม่หมอเริ่มนับหนึ่งใหม่ทุกสาขา และข้อมูลบวมโดยไม่มีประโยชน์
 *        (เจ้าของ: "ไม่งั้นข้อมูลมันจะซ้ำซ้อนล้นเปล่าๆ ไร้ประโยชน์")
 *
 * ทำไมไม่ใช้ ids_for_pages ของ Meta:
 *   โดนล็อกด้วย Business Manager — page token ของสาขายิงแล้วได้ 400
 *   "Can only query using page access tokens where the page is owned by the same business as the app"
 *   → ใช้สะพานของเราเองแทน: ลิงก์จากแชทฝัง PSID (เซ็น HMAC) + ล็อกอิน FB ให้ ASID
 *     ASID ผูกกับ "แอป" ไม่ใช่ "เพจ" → สาขาไหนก็ได้ค่าเดียวกัน
 *
 * ⚠️ ขอบเขตที่เจ้าของกำหนดไว้ชัด (2026-08-15) — เย็บเฉพาะ "ความจำ/persona" เท่านั้น
 *    ❌ ห้ามเย็บระบบแบน — "แบนเพจใครเพจมัน โดนแบนเพจหลักไปเพจอื่นก็พร้อมแบนอีก"
 *    ❌ ห้ามเย็บโควตาดูดวงฟรี — ลูกค้าขอรับได้ทุกเพจ
 *    เพราะ 2 อย่างนั้นคือ "กติกาของเพจ" ไม่ใช่ "ตัวตนของคน"
 */
class CrossPageIdentityService
{
    /**
     * ผูก ASID เข้ากับ persona ของ PSID นี้
     *
     * เรียกตอนที่รู้ทั้งคู่พร้อมกัน = ลูกค้ากดลิงก์จากแชท (รู้ PSID จากโทเค็น)
     * แล้วล็อกอินด้วย Facebook (ได้ ASID)
     *
     * @return bool ผูกสำเร็จไหม
     */
    public function link(string $platform, string $platformUserId, string $asid): bool
    {
        if ($platformUserId === '' || $asid === '' || ! $this->ready()) {
            return false;
        }

        try {
            $affected = FortuneCustomerPersona::query()
                ->where('platform', $platform)
                ->where('platform_user_id', $platformUserId)
                ->whereNull('linked_asid')      // ผูกแล้วไม่ทับซ้ำ
                ->update(['linked_asid' => $asid]);

            if ($affected > 0) {
                Log::info('🔗 ผูก persona เข้ากับตัวตนข้ามสาขา', [
                    'platform' => $platform,
                    'rows' => $affected,
                    // 🔐 ไม่ log ASID/PSID เต็ม — เป็นตัวระบุตัวบุคคล
                    'psid_tail' => substr($platformUserId, -6),
                ]);
            }

            return $affected > 0;
        } catch (\Throwable $e) {
            // ผูกไม่ได้ต้องไม่ทำให้ล็อกอิน/แชทพัง — แค่เสียความจำข้ามสาขาไปรอบนึง
            Log::warning('🔗 ผูกตัวตนข้ามสาขาไม่สำเร็จ', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * persona ของคนเดียวกันที่อยู่สาขาอื่น
     *
     * ใช้ตอนประกอบความจำก่อนคุย — คนที่เคยเล่าเรื่องตัวเองไว้ที่เพจหลัก
     * มาทักสาขา 5 แล้วแม่หมอต้องจำได้ ไม่ใช่เริ่มนับหนึ่งใหม่
     *
     * @return Collection<int, FortuneCustomerPersona>
     */
    public function siblings(FortuneCustomerPersona $persona): Collection
    {
        $asid = $this->ready() ? ($persona->linked_asid ?? null) : null;

        if (empty($asid)) {
            return collect(); // ยังไม่เคยล็อกอิน = ไม่รู้ว่าเป็นคนเดียวกับใคร
        }

        try {
            return FortuneCustomerPersona::query()
                ->where('linked_asid', $asid)
                ->where('id', '!=', $persona->id)
                ->orderByDesc('updated_at')
                ->limit(5)   // เพดานกัน prompt บวม — เอาสาขาที่คุยล่าสุดพอ
                ->get();
        } catch (\Throwable $e) {
            Log::warning('🔗 ดึง persona สาขาอื่นไม่สำเร็จ', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * คอลัมน์พร้อมใช้หรือยัง
     *
     * ⚠️ deploy: โค้ดขึ้นก่อน migration เสมอ — ไม่กันไว้ = คิวรีพังทั้งเส้นคุย
     */
    protected function ready(): bool
    {
        static $ready = null;

        if ($ready !== null) {
            return $ready;
        }

        try {
            return $ready = Schema::hasColumn('fortune_customer_personas', 'linked_asid');
        } catch (\Throwable $e) {
            return $ready = false;
        }
    }
}
