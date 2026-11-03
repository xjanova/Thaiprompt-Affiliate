<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * 📖 (2026-11-03 FB web-only) บันทึกคำทำนายล่าสุดที่ลูกค้าจ่ายแล้ว — แยกออกมาเป็น service
 *
 * ต้นฉบับคือ FortuneConversationService::buildLastReadingRecallContext() (บอท FB/LINE)
 * ตัวนี้ "คัดลอก" ตรรกะมา (ของเดิมไม่ได้ลบ ไม่ได้แก้ — บอทยังเรียกของเดิม) เพื่อให้ห้องแม่หมอบนเว็บ
 * ใช้ได้ด้วย โดยมีความต่าง 2 ข้อ:
 *   1. รับ PSID ได้หลายตัว (FB ให้ PSID คนละตัวต่อเพจ — ลูกค้าคนเดียวมีได้หลายตัว)
 *   2. โหมดเว็บ (forWeb) ตัดราคา (39฿/99฿) ประโยค "ไม่คิดเงินเพิ่ม" และ "ชวนเปิดบิลใหม่" ออก
 *      (บนเว็บการเปิดไพ่ใช้การ์ดแพ็กเกจ + วอลเลต — ห้ามพูดเรื่องบิล/ราคาแบบในเพจ)
 *
 * ⚠️ fortune_readings ไม่มีคอลัมน์ line_user_id — ค้นด้วย platform_user_id / facebook_user_id เท่านั้น
 */
class FortuneLastReadingRecallService
{
    /** ย้อนหลังกี่วัน */
    public const RECALL_DAYS = 30;

    /** ความยาวเนื้อคำทำนายที่ยกมา */
    public const BODY_MAX_CHARS = 1200;

    /**
     * สร้างบล็อกระลึกคำทำนายล่าสุด
     *
     * @param  array<int,string>  $platformUserIds  PSID / LINE userId ของลูกค้า (หลายตัวได้)
     * @param  bool  $forWeb  true = ข้อความสำหรับห้องแม่หมอบนเว็บ (ไม่มีราคา ไม่ชวนเปิดบิล)
     * @return string|null null = ไม่มีคำทำนายให้ระลึกถึง
     *
     * @example
     * $block = app(FortuneLastReadingRecallService::class)->build(['2754...'], forWeb: true);
     */
    public function build(array $platformUserIds, bool $forWeb = false): ?string
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $platformUserIds), fn ($v) => $v !== '')));
        if ($ids === []) {
            return null;
        }

        try {
            $latest = FortuneReading::query()
                ->where(function ($q) use ($ids) {
                    $q->whereIn('platform_user_id', $ids)
                        ->orWhereIn('facebook_user_id', $ids);
                })
                ->where('is_paid', true)
                ->whereIn('reading_type', [
                    FortuneReading::READING_TYPE_DEEP,
                    FortuneReading::READING_TYPE_CELTIC_CROSS,
                ])
                ->whereNotNull('ai_response')
                ->where('ai_response', '!=', '')
                ->where('created_at', '>', now()->subDays(self::RECALL_DAYS))
                ->orderByDesc('created_at')
                ->first();

            if (! $latest) {
                return null;
            }

            return $this->render($latest, $forWeb);
        } catch (\Throwable $e) {
            // ของแถม — ห้ามทำให้แชทพัง
            Log::warning('Fortune: LastReadingRecall ล้มเหลว (non-blocking)', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * ประกอบข้อความจากคำทำนาย 1 ใบ
     */
    protected function render(FortuneReading $latest, bool $forWeb): string
    {
        $isCeltic = $latest->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS;
        $typeLabel = $forWeb
            ? ($isCeltic ? 'Celtic Cross 10 ใบ' : 'ดูดวงเชิงลึก')
            : ($isCeltic ? 'Celtic Cross 10 ใบ (99฿)' : 'ดูดวงเชิงลึก (39฿)');

        $daysAgo = (int) $latest->created_at->diffInDays(now());
        $timeAgo = match (true) {
            $daysAgo === 0 => 'วันนี้',
            $daysAgo === 1 => 'เมื่อวาน',
            default => "เมื่อ {$daysAgo} วันก่อน",
        };

        $body = mb_substr(trim((string) $latest->ai_response), 0, self::BODY_MAX_CHARS);
        if ($forWeb) {
            // คำทำนายบางใบมี CTA ราคา/ช่องทางติดต่อติดท้าย — กรองแบบข้อความลูกค้า (ไม่แทนทั้งก้อน)
            $body = WebChatContinuitySanitizer::sanitizeInbound($body, 'user');
        }

        $qaText = '';
        if ($isCeltic) {
            $rows = $latest->celticQuestions()
                ->whereNotNull('answered_at')
                ->orderBy('sequence')
                ->limit(4)
                ->get(['question', 'response']);

            $lines = [];
            foreach ($rows as $i => $row) {
                $q = trim((string) $row->question);
                if ($q === '' || $q === '__PREDICT_ALL__' || str_starts_with($q, '[IMAGE_ATTACHED]')) {
                    continue;
                }
                $a = trim((string) $row->response);
                if ($forWeb) {
                    $q = WebChatContinuitySanitizer::sanitizeInbound($q, 'user');
                    $a = WebChatContinuitySanitizer::sanitizeInbound($a, 'user');
                    if ($q === '') {
                        continue;
                    }
                }
                $lines[] = ($i + 1).'. ถาม: '.mb_substr($q, 0, 90)
                    ."\n   ตอบ(ย่อ): ".mb_substr($a, 0, 220);
            }

            if (! empty($lines)) {
                $qaText = "\n\n— คำถามที่คุยกันไว้รอบนั้น —\n".implode("\n", $lines);
            }
        }

        if ($forWeb) {
            return "📖 บันทึกคำทำนายล่าสุดของลูกคนนี้ (เปิดไพ่ในเพจ ส่งให้ไปแล้ว)\n"
                ."บริการ: {$typeLabel} · {$timeAgo}\n\n"
                ."— เนื้อคำทำนายที่ส่งไป (ย่อ) —\n".$body.$qaText."\n\n"
                ."กติกาการใช้บันทึกนี้:\n"
                ."✅ ลูกถามย้อนถึงคำทำนายนี้ได้ — อธิบายเพิ่ม ขยายความ ให้กำลังใจได้\n"
                ."✅ อ้างชื่อไพ่/ประเด็น ได้เฉพาะที่มีอยู่ในบันทึกนี้เท่านั้น\n"
                ."❌ ห้ามเปิดไพ่ใบใหม่ ❌ ห้ามแต่งชื่อไพ่ที่ไม่มีในบันทึก\n"
                .'ถ้าลูกอยากเปิดไพ่ชุดใหม่ ให้ทำตามกติกาของห้องนี้ (ชวนเลือกแบบการดูดวงด้านล่าง)';
        }

        // โหมดบอท — ข้อความเดียวกับต้นฉบับใน FortuneConversationService ทุกตัวอักษร
        return "📖 บันทึกคำทำนายล่าสุดของเจ้าชะตาคนนี้ (ส่งให้ไปแล้ว · จ่ายเงินแล้ว)\n"
            ."บริการ: {$typeLabel} · {$timeAgo}\n\n"
            ."— เนื้อคำทำนายที่ส่งไป (ย่อ) —\n".$body.$qaText."\n\n"
            ."[กติกาการใช้บันทึกนี้ — สำคัญ]\n"
            ."✅ เจ้าชะตาถามย้อนถึงคำทำนายนี้ได้ตลอด **แม้เวลาคุยของบิลจะหมดไปแล้ว** — อธิบายเพิ่ม ขยายความ ให้กำลังใจ ต่อยอดได้เต็มที่ ไม่คิดเงินเพิ่ม\n"
            ."✅ อ้างชื่อไพ่/ดาว/ประเด็น ได้เฉพาะที่ *มีอยู่ในบันทึกนี้* เท่านั้น\n"
            ."❌ ห้ามเปิดไพ่ใบใหม่ ❌ ห้ามสร้างคำทำนายรอบใหม่ ❌ ห้ามแต่งชื่อไพ่ที่ไม่มีในบันทึก\n"
            .'❌ ถ้าเจ้าชะตาอยากได้ "ทำนายรอบใหม่ / เปิดไพ่ชุดใหม่ / เรื่องที่ยังไม่เคยเปิด" → ชวนเปิดบิลใหม่ตามปกติ';
    }
}
