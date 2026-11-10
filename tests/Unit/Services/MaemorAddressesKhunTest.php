<?php

namespace Tests\Unit\Services;

use App\Services\Fortune\FortuneCharacterVoiceService;
use App\Services\Fortune\JuntraChatService;
use App\Services\Fortune\JuntraSpreadProfiles;
use App\Services\Fortune\JuntraVoiceLibrary;
use App\Services\FortuneAIService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 🗣️ (2026-11-10, owner) แม่หมอจันทรา + ห้องคุณไสย เรียกลูกค้าว่า "คุณ" ห้ามใช้ "ลูก" / "ลูกดวง"
 *
 * owner: "ไม่ต้องใช้คำว่าลูก กับลูกค้า ให้ใช้คำว่าคุณ ... ทั้งพากย์เสียง และใน พร๊อม แก้ให้ครบ"
 *
 * ล็อกไว้:
 *   1. พรอมต์ของ maemor/kunsai (เว็บ + LINE/FB) ไม่มีคำเรียกลูกค้าว่า "ลูก" — ยังมีได้เฉพาะประโยคห้าม
 *      และกฎแยกความหมายตอนลูกค้าเรียกตัวเองว่า "ลูก"
 *   2. บทพากย์เสียง (DEFAULT_CLIPS + sample_text) ของ maemor/kunsai ไม่มีคำว่า "ลูก" ที่หมายถึงลูกค้าเลย
 *   3. คำเรียกลูกค้าที่ส่งให้ระบบเสียง/คำทำนาย = "คุณ" ทุกห้อง
 *
 * ไม่แตะ DB ไม่บูต Laravel — อ่านค่าคงที่ตรง ๆ
 */
class MaemorAddressesKhunTest extends TestCase
{
    /** คำเรียกลูกค้าว่า "ลูก" แบบที่เคยใช้ — ห้ามโผล่ในพรอมต์อีก */
    private const ADDRESS_PATTERN = '/จ้ะลูก|จ๊ะลูก|นะลูก(?!ค้า)|ค่ะลูก|คะลูก|ลูกจ๋า|ลูกเอ๋ย|ลูกรัก|เรียก(?:ผู้ใช้|ลูกค้า)ว่า\s*\**\\\\?"ลูก(?!ค้า)/u';

    /** "ลูก" ที่ไม่ใช่ลูกค้า/ลูกของคุณ — บทพากย์เสียงต้องไม่มีเลย */
    private const ANY_CUSTOMER_LUK = '/ลูก(?!ค้า|ของ|ชาย|สาว)/u';

    private static function constant(string $class, string $name): mixed
    {
        return (new \ReflectionClassConstant($class, $name))->getValue();
    }

    /** @return array<string, string> ชื่อ → ข้อความพรอมต์ */
    private static function prompts(): array
    {
        $out = [];
        $profiles = self::constant(JuntraChatService::class, 'PERSONA_PROFILES');
        foreach (['maemor', 'kunsai'] as $persona) {
            foreach ($profiles[$persona] as $key => $value) {
                if (is_string($value)) {
                    $out["chat.$persona.$key"] = $value;
                }
            }
        }
        foreach (['GREETING', 'GREETING_READING', 'FILLER'] as $c) {
            $out["chat.$c"] = self::constant(JuntraChatService::class, $c);
        }

        $blocks = self::constant(JuntraSpreadProfiles::class, 'PERSONA_READING_BLOCKS');
        $out['spread.kunsai'] = $blocks['kunsai'];

        foreach (['CUSTOMER_ADDRESS_DIRECTIVE', 'MAE_MOR_BOUNDARY_DIRECTIVE', 'NO_HEDGE_DIRECTIVE', 'FIERCE_TONE_DIRECTIVE', 'HUMAN_BACKCHANNEL_DIRECTIVE', 'CHAT_SYSTEM_MESSAGE_TEMPLATE'] as $c) {
            $out["ai.$c"] = self::constant(FortuneAIService::class, $c);
        }

        return $out;
    }

    #[Test]
    public function maemor_and_kunsai_prompts_never_address_the_customer_as_luk(): void
    {
        foreach (self::prompts() as $name => $text) {
            $this->assertDoesNotMatchRegularExpression(self::ADDRESS_PATTERN, $text, "พรอมต์ {$name} ยังเรียกลูกค้าว่า \"ลูก\"");
        }
    }

    #[Test]
    public function the_address_rule_tells_the_ai_to_say_khun(): void
    {
        $directive = self::constant(FortuneAIService::class, 'CUSTOMER_ADDRESS_DIRECTIVE');
        // หัวข้อต้องมี "กฎเรียกลูกค้า" — ใช้เป็นเครื่องหมายกันฉีดซ้ำ
        $this->assertStringContainsString('กฎเรียกลูกค้า', $directive);
        $this->assertStringContainsString('เรียก "คุณ" เสมอ', $directive);
        $this->assertStringContainsString('ห้ามใช้คำว่า ลูก หรือ ลูกดวง', $directive);
        // "ลูกค้า" ในพรอมต์ = ศัพท์ภายใน (เหมือน "เจ้าชะตา") — ห้ามหลุดไปเรียกผู้ใช้ในคำตอบ
        $this->assertStringContainsString('ห้ามเรียกผู้ใช้ว่า "ลูกค้า" ในคำตอบ', $directive);
        $this->assertStringContainsString('พูดกับผู้ใช้ให้ใช้ "คุณ"', $directive);
        $this->assertSame(1, substr_count($directive, 'กฎเรียกลูกค้า'), 'เครื่องหมายกันฉีดซ้ำต้องมีจุดเดียว');

        $system = self::constant(JuntraChatService::class, 'SYSTEM_PROMPT');
        $this->assertStringContainsString('เรียกผู้ใช้ว่า "คุณ" เสมอ', $system);
        $this->assertStringContainsString('ค่ะ/คะ/นะคะ', $system);
        $this->assertStringContainsString('ห้ามเรียกผู้ใช้ว่า "ลูกค้า" ในคำตอบ', $system);

        $kunsai = self::constant(JuntraChatService::class, 'PERSONA_PROFILES')['kunsai']['system'];
        $this->assertStringContainsString('เรียกผู้ใช้ว่า "คุณ" เสมอ', $kunsai);
        $this->assertStringContainsString('เรียกตัวเองว่า "แม่หมอ"', $kunsai);
        $this->assertStringContainsString('ห้ามเรียกผู้ใช้ว่า "ลูกค้า" ในคำตอบ', $kunsai);

        $blocks = self::constant(JuntraSpreadProfiles::class, 'PERSONA_READING_BLOCKS');
        $this->assertStringContainsString('ลูกค้า เรียกผู้ใช้ในคำทำนาย', $blocks['kunsai']);
        $this->assertStringContainsString('"ลูก" "ลูกค้า" และ "เจ้าชะตา"', $blocks['busaba']);
    }

    #[Test]
    public function voice_scripts_contain_no_customer_luk(): void
    {
        $clips = JuntraVoiceLibrary::DEFAULT_CLIPS;
        foreach (['maemor', 'kunsai'] as $persona) {
            $this->assertNotEmpty($clips[$persona] ?? []);
            foreach ($clips[$persona] as $key => $script) {
                $this->assertDoesNotMatchRegularExpression(self::ANY_CUSTOMER_LUK, $script, "บทพากย์ {$persona}.{$key} ยังมี \"ลูก\"");
            }
            $sample = FortuneCharacterVoiceService::PERSONAS[$persona]['sample_text'];
            $this->assertDoesNotMatchRegularExpression(self::ANY_CUSTOMER_LUK, $sample, "sample_text ของ {$persona} ยังมี \"ลูก\"");
        }
    }

    #[Test]
    public function every_persona_hands_khun_to_voice_and_reading(): void
    {
        foreach (JuntraChatService::PERSONAS as $persona) {
            $this->assertSame('คุณ', JuntraChatService::voiceIdentity($persona)['customer'], $persona);
            $this->assertSame('คุณ', JuntraSpreadProfiles::customerWord(['persona' => $persona]), $persona);
        }
    }
}
