<?php

namespace App\Console\Commands;

use App\Models\EveMemberMemory;
use Illuminate\Console\Command;
use Throwable;

/**
 * 🧹 ดูแลความจำบทสนทนาของน้อง Eve
 *
 * ทำ 2 อย่าง ทั้งคู่อยู่นอกเส้นทางคำขอของลูกค้า (ห้ามทำตอนแชท เดี๋ยวคำตอบช้า):
 *   1) PRUNE  — ลบความจำที่เกินอายุ (ค่าเริ่มต้น 7 วัน) = เพดานการเก็บข้อมูลตาม PDPA
 *   2) TRIM   — แถวที่เทิร์นล้นเพดาน ตัดให้เหลือเฉพาะเทิร์นใหม่ + ทำบทสรุปแบบย่อจากเทิร์นเก่า
 *               เพื่อไม่ให้ prompt ยาวขึ้นเรื่อยๆ ตามอายุความจำ
 *
 * หมายเหตุ: loadMemberMemory() ใน EveAssistantController ลบแถวที่หมดอายุให้เองอยู่แล้ว
 * ตัวนี้จึงเป็น "ตาข่ายกันพลาด" — ถ้าไม่ได้รัน จะเปลืองพื้นที่เท่านั้น
 * ความจำที่หมดอายุจะไม่มีทางหลุดเข้า prompt อยู่ดี
 */
class EveMemoryMaintain extends Command
{
    protected $signature = 'eve:memory-maintain
                            {--days= : จำนวนวันที่เก็บ (ไม่ระบุ = ใช้ค่ามาตรฐาน 7 วัน)}
                            {--limit=500 : จำนวนแถวสูงสุดที่จัดการต่อ 1 รอบ}
                            {--dry : ดูผลอย่างเดียว ไม่แก้ข้อมูลจริง}';

    protected $description = 'ลบความจำน้อง Eve ที่เกินอายุ + ย่อเทิร์นเก่าของแถวที่ยาวเกิน';

    /** จำนวนเทิร์นใหม่ที่คงไว้เต็มรูปแบบหลังย่อ */
    private const KEEP_RECENT_TURNS = 12;

    /** เทิร์นเกินจำนวนนี้ถือว่าควรย่อ */
    private const TRIM_THRESHOLD = 32;

    /** ความยาวสูงสุดของบทสรุป */
    private const SUMMARY_MAX_CHARS = 600;

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: EveMemberMemory::RETENTION_DAYS);
        $limit = max(1, (int) $this->option('limit'));
        $dry = (bool) $this->option('dry');

        if ($days < 1) {
            $this->error('❌ --days ต้องมากกว่า 0');

            return self::FAILURE;
        }

        $this->info("🧹 ดูแลความจำน้อง Eve (เก็บ {$days} วัน, สูงสุด {$limit} แถว)".($dry ? ' [ทดลอง]' : ''));

        $pruned = $this->prune($days, $limit, $dry);
        $trimmed = $this->trim($limit, $dry);

        $this->info("✅ ลบที่หมดอายุ {$pruned} แถว · ย่อเทิร์นเก่า {$trimmed} แถว");

        return self::SUCCESS;
    }

    /**
     * ลบความจำที่เกินอายุ
     */
    private function prune(int $days, int $limit, bool $dry): int
    {
        try {
            $ids = EveMemberMemory::stale($days)->limit($limit)->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            if ($dry) {
                return $ids->count();
            }

            return EveMemberMemory::whereIn('id', $ids)->delete();
        } catch (Throwable $e) {
            $this->error('❌ ลบความจำที่หมดอายุไม่สำเร็จ: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * ย่อเทิร์นเก่าของแถวที่ยาวเกินเพดาน
     *
     * ไม่เรียก AI: สรุปด้วยการหยิบ "สิ่งที่ลูกค้าพูดเอง" มาต่อกัน
     * เหตุผล — เรียก AI ต่อแถวจะกินคีย์ก้อนเดียวกับงานที่ลูกค้าจ่ายเงิน (ดูดวง)
     * และบทสรุปแบบนี้ก็พอสำหรับเตือนความจำว่าเคยคุยเรื่องอะไรไว้
     */
    private function trim(int $limit, bool $dry): int
    {
        $done = 0;

        try {
            $rows = EveMemberMemory::whereNotNull('turns')->limit($limit)->get();

            foreach ($rows as $row) {
                $turns = is_array($row->turns) ? $row->turns : [];
                if (count($turns) <= self::TRIM_THRESHOLD) {
                    continue;
                }

                $old = array_slice($turns, 0, -self::KEEP_RECENT_TURNS);
                $recent = array_slice($turns, -self::KEEP_RECENT_TURNS);

                // เก็บเฉพาะสิ่งที่ลูกค้าพูด — คำตอบของ Eve สรุปแล้วไม่ได้ช่วยให้จำลูกค้าได้ดีขึ้น
                $said = [];
                foreach ($old as $turn) {
                    if (! is_array($turn) || ($turn['role'] ?? '') !== 'user') {
                        continue;
                    }
                    $text = trim((string) ($turn['content'] ?? ''));
                    if ($text !== '') {
                        $said[] = $text;
                    }
                }

                $summary = trim((string) ($row->summary ?? ''));
                if (! empty($said)) {
                    $summary = trim($summary.' '.implode(' · ', $said));
                }
                $summary = mb_substr($summary, -self::SUMMARY_MAX_CHARS);

                if ($dry) {
                    $done++;

                    continue;
                }

                $row->forceFill(['turns' => $recent, 'summary' => $summary])->save();
                $done++;
            }
        } catch (Throwable $e) {
            $this->error('❌ ย่อเทิร์นเก่าไม่สำเร็จ: '.$e->getMessage());
        }

        return $done;
    }
}
