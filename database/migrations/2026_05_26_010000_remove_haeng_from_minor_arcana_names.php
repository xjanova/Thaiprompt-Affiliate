<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * 🃏 (2026-05-26) USER SPEC: ลบคำว่า "แห่ง" ออกจากชื่อไพ่ Minor Arcana
     *
     * เดิม: "สองแห่งดาบ" / "สิบแห่งเหรียญ" / "อัศวินแห่งถ้วย"
     * ใหม่: "สองดาบ" / "สิบเหรียญ" / "อัศวินถ้วย"
     *
     * เหตุผล: ลูกค้างง — คำว่า "แห่ง" ทำให้อ่านแล้วสะดุด ไม่เป็นธรรมชาติ
     *
     * 🔒 Safe + Idempotent:
     *   - Scope เฉพาะ type='minor_arcana' (56 ใบ) — ไม่กระทบ Major Arcana ที่มี "แห่ง" ใน context อื่น
     *     เช่น "กงล้อแห่งโชค" / "พลังแห่งหญิง" (keywords)
     *   - Rebuild name_th + description_th จาก suit + rank lookup — deterministic
     *   - รัน 2 ครั้งไม่เสีย (ครั้งที่ 2 update เป็นค่าเดิมที่ถูกต้องแล้ว)
     *   - upright_meaning_th / reversed_meaning_th ไม่แตะ (ไม่มี "แห่ง" อยู่แล้ว)
     *
     * Reverse: down() restore format "X แห่ง Y" — แต่ไม่แนะนำให้ rollback
     *          (เหตุผล: format ใหม่คือ user-facing requirement ที่ผ่านการ confirm แล้ว)
     */
    public function up(): void
    {
        $suitMap = [
            'wands' => 'ไม้เท้า',
            'cups' => 'ถ้วย',
            'swords' => 'ดาบ',
            'pentacles' => 'เหรียญ',
        ];

        $rankMap = [
            1 => 'เอซ', 2 => 'สอง', 3 => 'สาม', 4 => 'สี่',
            5 => 'ห้า', 6 => 'หก', 7 => 'เจ็ด', 8 => 'แปด',
            9 => 'เก้า', 10 => 'สิบ',
            11 => 'ข้าราชบริพาร', 12 => 'อัศวิน', 13 => 'ราชินี', 14 => 'กษัตริย์',
        ];

        $updated = 0;
        $skipped = 0;

        $cards = DB::table('tarot_cards')
            ->where('type', 'minor_arcana')
            ->whereIn('suit', array_keys($suitMap))
            ->get(['id', 'suit', 'number', 'name_th', 'description_th', 'keywords_th']);

        foreach ($cards as $card) {
            $suitTh = $suitMap[$card->suit] ?? null;
            $rankTh = $rankMap[$card->number] ?? null;

            if (! $suitTh || ! $rankTh) {
                $skipped++;
                Log::warning('remove_haeng_migration: ข้าม row ที่ map ไม่ได้', [
                    'id' => $card->id,
                    'suit' => $card->suit,
                    'number' => $card->number,
                ]);

                continue;
            }

            $newNameTh = $rankTh.$suitTh;

            // description_th: rebuild จาก keywords (รักษา keyword list เดิม)
            $keywordsTh = is_string($card->keywords_th)
                ? json_decode($card->keywords_th, true)
                : $card->keywords_th;
            $keywordsTh = is_array($keywordsTh) ? $keywordsTh : [];
            $newDescTh = $newNameTh.' - '.implode(', ', $keywordsTh);

            DB::table('tarot_cards')
                ->where('id', $card->id)
                ->update([
                    'name_th' => $newNameTh,
                    'description_th' => $newDescTh,
                ]);

            $updated++;
        }

        Log::info('remove_haeng_migration: เสร็จสิ้น', [
            'total_minor_arcana' => $cards->count(),
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Rollback: คืน format "X แห่ง Y" — สำหรับ disaster recovery เท่านั้น
     */
    public function down(): void
    {
        $suitMap = [
            'wands' => 'ไม้เท้า',
            'cups' => 'ถ้วย',
            'swords' => 'ดาบ',
            'pentacles' => 'เหรียญ',
        ];

        $rankMap = [
            1 => 'เอซ', 2 => 'สอง', 3 => 'สาม', 4 => 'สี่',
            5 => 'ห้า', 6 => 'หก', 7 => 'เจ็ด', 8 => 'แปด',
            9 => 'เก้า', 10 => 'สิบ',
            11 => 'ข้าราชบริพาร', 12 => 'อัศวิน', 13 => 'ราชินี', 14 => 'กษัตริย์',
        ];

        $cards = DB::table('tarot_cards')
            ->where('type', 'minor_arcana')
            ->whereIn('suit', array_keys($suitMap))
            ->get(['id', 'suit', 'number', 'keywords_th']);

        foreach ($cards as $card) {
            $suitTh = $suitMap[$card->suit] ?? null;
            $rankTh = $rankMap[$card->number] ?? null;

            if (! $suitTh || ! $rankTh) {
                continue;
            }

            $oldNameTh = $rankTh.'แห่ง'.$suitTh;

            $keywordsTh = is_string($card->keywords_th)
                ? json_decode($card->keywords_th, true)
                : $card->keywords_th;
            $keywordsTh = is_array($keywordsTh) ? $keywordsTh : [];
            $oldDescTh = $oldNameTh.' - '.implode(', ', $keywordsTh);

            DB::table('tarot_cards')
                ->where('id', $card->id)
                ->update([
                    'name_th' => $oldNameTh,
                    'description_th' => $oldDescTh,
                ]);
        }
    }
};
