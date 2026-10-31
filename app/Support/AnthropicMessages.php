<?php

namespace App\Support;

/**
 * 🤖 (2026-10-30) สร้าง body ของ Anthropic Messages API ให้ตรงกับกติกาของแต่ละรุ่น
 *
 * ทำไมต้องมี: Claude รุ่นใหม่เปลี่ยนกติกา request จนโค้ดเดิมยิงแล้วได้ 400
 *   - Claude 4.7 ขึ้นไป + ทุกรุ่นตระกูล 5.x → ส่ง temperature / top_p / top_k = 400
 *     (โค้ดเดิมส่ง temperature ทุก call → claude-opus-4-7 ตัวแรกใน dropdown ใช้ไม่ได้เลย)
 *   - Opus 5.5 / Fable / Mythos → คิด (thinking) ตลอด ปิดไม่ได้ ({type:"disabled"} = 400)
 *     thinking กิน max_tokens ด้วย → chat ที่ตั้ง max_tokens 512 อาจได้ข้อความว่าง
 *   - Sonnet 5.5 → ปิด thinking ด้วย {type:"between_tools"} ({type:"disabled"} = 400)
 *   - Haiku 5.5 / Sonnet 5 / Opus 5 → เปิด thinking เป็นค่าเริ่มต้น แต่ส่ง disabled ได้ (ที่ effort ≤ high)
 *   - content[0] อาจเป็น block "thinking" (text ว่าง) → ต้องหา block type=text ตัวแรกแทน
 *
 * ยังคง anthropic-version: 2023-06-01 — เวอร์ชัน 2026-10-21 ห้าม temperature ทุกรุ่น
 * ถ้าจะย้ายเวอร์ชันต้องตัด temperature ของรุ่นเก่าออกด้วย (เปลี่ยนผลลัพธ์ของรุ่น 4.6 ลงไป)
 */
final class AnthropicMessages
{
    public const API_VERSION = '2023-06-01';

    /**
     * รุ่นที่ยังรับ temperature ได้ (รุ่นก่อน 4.7) — นอกนั้นถือว่ารับไม่ได้
     * ใช้ whitelist เพราะรุ่นที่ออกใหม่ต่อจากนี้น่าจะห้าม sampling เหมือน 4.7+
     */
    private const SAMPLING_MODEL_PATTERN = '/^claude-(3|opus-4-[0-6]|opus-4$|opus-4-\d{8}|sonnet-4-[0-6]|sonnet-4$|sonnet-4-\d{8}|haiku-4-5)/';

    /** รุ่นที่ thinking ปิดไม่ได้ → บังคับ effort ต่ำ + เผื่อ token ให้ thinking */
    private const ALWAYS_THINKING_PATTERN = '/^claude-(opus-5-5|fable|mythos)/';

    /** รุ่นที่ปิด thinking ด้วย between_tools (disabled = 400) */
    private const BETWEEN_TOOLS_PATTERN = '/^claude-sonnet-5-5/';

    /** รุ่นที่เปิด thinking อัตโนมัติ แต่ส่ง {type:"disabled"} ได้ */
    private const DEFAULT_THINKING_PATTERN = '/^claude-(opus-5($|-\d{8})|sonnet-5($|-\d{8})|haiku-5)/';

    /** token เผื่อให้ thinking ของรุ่นที่ปิดไม่ได้ (effort=low ใช้ไม่มาก แต่กันข้อความว่าง) */
    private const THINKING_HEADROOM_TOKENS = 2048;

    /**
     * รุ่นนี้ยังรับ temperature ได้ไหม
     */
    public static function supportsSampling(string $model): bool
    {
        return (bool) preg_match(self::SAMPLING_MODEL_PATTERN, strtolower($model));
    }

    /**
     * สร้าง body สำหรับ POST /v1/messages
     *
     * @param  array<int, array{role: string, content: mixed}>  $messages  user/assistant เท่านั้น
     * @param  float|null  $temperature  ส่งเฉพาะรุ่นที่รับได้ — รุ่นใหม่จะถูกตัดทิ้งเงียบๆ
     * @return array<string, mixed>
     */
    public static function body(string $model, string $system, array $messages, int $maxTokens, ?float $temperature = null): array
    {
        $id = strtolower($model);
        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        if ($system !== '') {
            $body['system'] = $system;
        }

        if ($temperature !== null && self::supportsSampling($id)) {
            $body['temperature'] = $temperature;
        }

        if (preg_match(self::ALWAYS_THINKING_PATTERN, $id)) {
            // คิดตลอด — ลดความลึกให้ต่ำสุดและเผื่อ token ให้ thinking ไม่กินส่วนคำตอบ
            $body['output_config'] = ['effort' => 'low'];
            $body['max_tokens'] = $maxTokens + self::THINKING_HEADROOM_TOKENS;
        } elseif (preg_match(self::BETWEEN_TOOLS_PATTERN, $id)) {
            $body['thinking'] = ['type' => 'between_tools'];
        } elseif (preg_match(self::DEFAULT_THINKING_PATTERN, $id)) {
            $body['thinking'] = ['type' => 'disabled'];
        }

        return $body;
    }

    /**
     * ดึงข้อความตอบจาก response — ต่อ text block ทุกก้อน ข้าม thinking block
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function text(?array $data): string
    {
        $text = '';
        $blocks = $data['content'] ?? [];
        foreach (is_array($blocks) ? $blocks : [] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        return $text;
    }
}
