<?php

namespace Tests\Feature;

use App\Models\FortuneEntryCard;
use App\Services\Fortune\FortuneEntryCardBuilder;
use Tests\TestCase;

/**
 * 🃏 การ์ดทางเข้าของแม่หมอ (DM ตอบคอมเมนต์ + เลือกวันเกิด)
 *
 * ล็อกกติกาที่พลาดแล้ว "การ์ดหายเงียบ" หรือ "ลูกค้าอ่านไม่รู้เรื่อง":
 *  1. payload ต้องห่อ attachment มาเอง — ลืมห่อ = FB กลืนกล่องทิ้งโดยไม่มี error
 *  2. เพดาน FB (title 80 / subtitle 80 / ป้ายปุ่ม 20) ห้ามเกินสักใบ
 *  3. การ์ดวันเกิดต้องครบ 7 ใบ และ payload ต้องตรงกับที่ handleQuickReply รับอยู่
 *  4. ตัดข้อความไทยห้ามทิ้งสระ/วรรณยุกต์ลอยขึ้นต้นท่อนถัดไป
 *  5. รูปหาย = ต้องคืน null ให้ caller ตกกลับไปข้อความเดิม (ห้ามส่ง URL ที่ 404)
 *  6. ราคาที่ number_format มาแล้ว ("1,099") ห้ามกลายเป็น 1
 *
 * ⚠️ ไม่ใช้ RefreshDatabase — builder ตัวนี้ไม่แตะ DB เลย
 */
class FortuneEntryCardBuilderTest extends TestCase
{
    private FortuneEntryCardBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new FortuneEntryCardBuilder;
    }

    /**
     * ดึง elements ออกจาก payload พร้อมยืนยันว่าห่อ attachment ถูกทรง
     *
     * @return array<int, array<string, mixed>>
     */
    private function elements(?array $payload): array
    {
        $this->assertNotNull($payload, 'builder คืน null — การ์ดจะหายทั้งชุด');

        // กติกาข้อ 1 — ตัวส่งไม่ห่อ attachment ให้ ต้องห่อมาจาก builder
        $this->assertSame('template', $payload['attachment']['type'] ?? null);
        $this->assertSame('generic', $payload['attachment']['payload']['template_type'] ?? null);

        // รูปทุกใบเป็น 1:1 — horizontal จะครอบภาพหาย
        $this->assertSame('square', $payload['attachment']['payload']['image_aspect_ratio'] ?? null);

        return $payload['attachment']['payload']['elements'] ?? [];
    }

    /**
     * ทุกใบต้องอยู่ในเพดานของ Facebook — เกินแล้วโดนตัดกลางคำ (หรือปฏิเสธทั้งกล่อง)
     */
    private function assertWithinFacebookLimits(array $elements): void
    {
        foreach ($elements as $element) {
            $this->assertLessThanOrEqual(80, mb_strlen($element['title']), 'title เกิน 80: '.$element['title']);
            $this->assertLessThanOrEqual(80, mb_strlen($element['subtitle']), 'subtitle เกิน 80: '.$element['subtitle']);
            $this->assertNotSame('', trim($element['title']), 'การ์ดไม่มีหัวข้อ = ลูกค้าไม่รู้ว่าคืออะไร');
            $this->assertStringStartsWith('https://', $element['image_url']);

            foreach ($element['buttons'] as $button) {
                $this->assertSame('postback', $button['type']);
                $this->assertLessThanOrEqual(20, mb_strlen($button['title']), 'ป้ายปุ่มเกิน 20: '.$button['title']);
                $this->assertNotSame('', $button['payload'], 'ปุ่มไม่มี payload = กดแล้วไม่เกิดอะไร');
            }
        }
    }

    /**
     * การ์ดวันเกิดต้องครบ 7 ใบ + payload ตรงกับที่ FacebookWebhookController รับอยู่แล้ว
     */
    public function test_การ์ดวันเกิดครบเจ็ดใบและ_payload_ตรงกับ_state_machine_เดิม(): void
    {
        $elements = $this->elements($this->builder->facebookDays());

        $this->assertCount(7, $elements, 'ขาดใบไหนไป = คนเกิดวันนั้นไม่มีปุ่มให้กด');

        foreach ($elements as $index => $element) {
            $this->assertSame(
                'DAILY_BDAY_'.$index,
                $element['buttons'][0]['payload'],
                'payload ต้องเป็นชุดเดิม ไม่งั้นต้องไปแก้ state machine ตาม'
            );
        }

        $this->assertWithinFacebookLimits($elements);
    }

    /**
     * การ์ดทางเข้าต้องมี 2 ใบ และปุ่มต้องแยก "ฟรี" กับ "เสียเงิน" ให้ชัด
     *
     * 🚨 เจ้าของสั่งไว้ 2026-08-07 หลังลูกค้าเหมาว่าปุ่มฟรีคือแพคเกจ 39 บาทแล้วมาโวยว่าโดนหลอก
     *    ⇒ ป้ายปุ่มใบฟรี **ต้องมีคำว่า "ฟรี"** เสมอ (หัวข้อการ์ดหมุนตามข้อความชวน พึ่งไม่ได้)
     */
    public function test_การ์ดทางเข้ามีสองใบและปุ่มฟรีต้องมีคำว่าฟรี(): void
    {
        $elements = $this->elements($this->builder->facebookEntry([
            'invite_text' => "🌙 สวัสดีค่ะคุณสมชาย แม่หมอจันทราเองนะคะ\nวันนี้แม่หมอเปิดดวงประจำวันเกิดให้ฟรี ไม่มีค่าใช้จ่ายสักบาทค่ะ",
            'deep_price' => 39,
        ]));

        $this->assertCount(2, $elements);

        $this->assertSame('DAILY_FREE_START', $elements[0]['buttons'][0]['payload']);
        $this->assertStringContainsString('ฟรี', $elements[0]['buttons'][0]['title']);

        $this->assertSame('DAILY_VIP_PACKAGES', $elements[1]['buttons'][0]['payload']);
        $this->assertStringContainsString('39', $elements[1]['title'], 'ราคาต้องมาจากหลังบ้าน ไม่ใช่ฝังในรูป');

        $this->assertWithinFacebookLimits($elements);
    }

    /**
     * ข้อความชวนยาว ๆ ต้องถูกย่อลงการ์ด โดย**ไม่มีสระ/วรรณยุกต์ลอยขึ้นต้นท่อนหลัง**
     *
     * 🐛 ของเดิมตัดที่ตัวอักษรที่ 80 พอดี ได้ "…ไม่ม" กับ "ีค่าใช้จ่าย"
     *    สระอีไปโผล่หัวคำบรรยาย = ลูกค้าอ่านแล้วนึกว่าระบบพัง
     */
    public function test_ย่อข้อความไทยแล้วต้องไม่มีสระลอยขึ้นต้น(): void
    {
        $longSingleLine = 'บรรทัดเดียวยาวมากไม่มีขึ้นบรรทัดใหม่เลยแม่หมอจันทราเปิดดวงประจำวันเกิด'
            .'ให้ฟรีไม่มีค่าใช้จ่ายกดปุ่มด้านล่างแล้วบอกวันเกิดมาได้เลยนะคะเดี๋ยวแม่หมอส่งให้ทันที';

        $elements = $this->elements($this->builder->facebookEntry([
            'invite_text' => $longSingleLine,
            'deep_price' => 39,
        ]));

        foreach (['title', 'subtitle'] as $field) {
            $value = $elements[0][$field];

            $this->assertSame(
                0,
                preg_match('/^\p{M}/u', $value),
                "{$field} ขึ้นต้นด้วยสระ/วรรณยุกต์ลอย: {$value}"
            );
        }

        $this->assertWithinFacebookLimits($elements);
    }

    /**
     * ไม่มีข้อความชวน (สวิตช์ปิด / คลังว่าง) ก็ต้องยังได้การ์ดที่อ่านรู้เรื่อง
     */
    public function test_ไม่มีข้อความชวนก็ยังต้องได้การ์ดที่บอกว่าฟรี(): void
    {
        $elements = $this->elements($this->builder->facebookEntry([
            'invite_text' => null,
            'deep_price' => null,
        ]));

        $this->assertStringContainsString('ฟรี', $elements[0]['title'].$elements[0]['subtitle']);

        // ไม่มีราคา → ห้ามโชว์ "เริ่ม 0 บาท"
        $this->assertStringNotContainsString('0 บาท', $elements[1]['title']);

        $this->assertWithinFacebookLimits($elements);
    }

    /**
     * รูปที่ไม่มีไฟล์จริงต้องคืน null — ส่ง URL ที่ 404 ไป FB ปฏิเสธทั้งกล่อง
     * ⇒ ลูกค้าไม่ได้อะไรเลย ซึ่งแย่กว่าตกกลับไปเมนูข้อความ
     */
    public function test_รูปหายต้องคืน_null_ไม่ใช่ส่ง_url_ที่_404(): void
    {
        $imageUrl = new \ReflectionMethod($this->builder, 'imageUrl');
        $imageUrl->setAccessible(true);

        $this->assertNull($imageUrl->invoke($this->builder, 'images/fortune/entry/ไม่มีไฟล์นี้.jpg'));
        $this->assertNotNull($imageUrl->invoke($this->builder, 'images/fortune/days/day-0.jpg'));
    }

    /**
     * ราคาที่ผ่าน number_format มาแล้วต้องอ่านได้ถูก — (int) "1,099" = 1
     */
    public function test_ราคาที่มี_comma_ต้องไม่กลายเป็นหนึ่ง(): void
    {
        $elements = $this->elements($this->builder->facebookEntry([
            'invite_text' => 'ทดสอบ',
            'deep_price' => '1,099',
        ]));

        $this->assertStringContainsString('1099', $elements[1]['title']);
    }

    /**
     * สร้าง override แบบไม่แตะ DB (ยังไม่ save) เพื่อทดสอบตรรกะการทับค่า
     */
    private function override(string $key, array $attrs): \Illuminate\Support\Collection
    {
        return collect([$key => new FortuneEntryCard(array_merge(['card_key' => $key], $attrs))]);
    }

    /**
     * โหมด "ใช้คำที่พิมพ์เอง" ต้องทับคำ DM ที่หมุนอยู่
     */
    public function test_โหมดพิมพ์เองต้องทับคำ_dm(): void
    {
        $builder = (new FortuneEntryCardBuilder)->useOverrides(
            $this->override(FortuneEntryCardBuilder::KEY_FREE, [
                'text_mode' => FortuneEntryCard::MODE_CUSTOM,
                'title' => 'หัวข้อที่แอดมินพิมพ์เอง',
                'subtitle' => 'คำบรรยายที่แอดมินพิมพ์เอง',
                'button_label' => '🎁 กดรับฟรี',
            ])
        );

        $elements = $this->elements($builder->facebookEntry([
            'invite_text' => 'คำ DM ที่หมุนมาจากคลัง ต้องไม่ถูกใช้',
            'deep_price' => 39,
        ]));

        $this->assertSame('หัวข้อที่แอดมินพิมพ์เอง', $elements[0]['title']);
        $this->assertSame('คำบรรยายที่แอดมินพิมพ์เอง', $elements[0]['subtitle']);
        $this->assertSame('🎁 กดรับฟรี', $elements[0]['buttons'][0]['title']);
    }

    /**
     * โหมด "ใช้คำ DM" ต้องไม่หยิบคำที่พิมพ์ค้างไว้มาใช้
     *
     * 🪤 เคสที่พลาดง่าย: แอดมินพิมพ์ทดลองไว้แล้วสลับโหมดกลับ — ถ้ายังหยิบมาใช้
     *    = คำ DM ที่หมุนอยู่ตายทั้งระบบโดยไม่มีใครรู้
     */
    public function test_โหมดใช้คำ_dm_ต้องไม่หยิบคำที่พิมพ์ค้างไว้(): void
    {
        $builder = (new FortuneEntryCardBuilder)->useOverrides(
            $this->override(FortuneEntryCardBuilder::KEY_FREE, [
                'text_mode' => FortuneEntryCard::MODE_INVITE,
                'title' => 'คำที่พิมพ์ค้างไว้ ห้ามใช้',
                'subtitle' => 'คำบรรยายที่พิมพ์ค้างไว้ ห้ามใช้',
            ])
        );

        $elements = $this->elements($builder->facebookEntry([
            'invite_text' => "🌙 คำ DM จริงจากคลัง\nบรรทัดที่สองของคำ DM",
            'deep_price' => 39,
        ]));

        $this->assertStringContainsString('คำ DM จริงจากคลัง', $elements[0]['title']);
        $this->assertStringNotContainsString('ห้ามใช้', $elements[0]['title'].$elements[0]['subtitle']);
    }

    /**
     * โหมดพิมพ์เองแต่เว้นบางช่องว่าง → ช่องที่ว่างต้องตกกลับไปใช้ค่าเดิม ไม่ใช่ปล่อยโล่ง
     */
    public function test_พิมพ์เองไม่ครบช่องต้องตกกลับค่าเดิม(): void
    {
        $builder = (new FortuneEntryCardBuilder)->useOverrides(
            $this->override(FortuneEntryCardBuilder::KEY_FREE, [
                'text_mode' => FortuneEntryCard::MODE_CUSTOM,
                'title' => 'พิมพ์แค่หัวข้อ',
                'subtitle' => '   ',   // เว้นวรรคล้วน ต้องนับเป็นว่าง
            ])
        );

        $elements = $this->elements($builder->facebookEntry([
            'invite_text' => "🌙 คำ DM จริง\nคำบรรยายจากคำ DM",
            'deep_price' => 39,
        ]));

        $this->assertSame('พิมพ์แค่หัวข้อ', $elements[0]['title']);
        $this->assertNotSame('', trim($elements[0]['subtitle']));
        $this->assertStringContainsString('คำบรรยายจากคำ DM', $elements[0]['subtitle']);
    }

    /**
     * คำที่แอดมินพิมพ์ยาวเกินเพดาน FB ต้องถูกตัดให้ — ช่องกรอกยอมรับได้ถึง 120 ตัว
     */
    public function test_คำที่พิมพ์ยาวเกินต้องถูกตัดให้พอดีเพดาน(): void
    {
        $builder = (new FortuneEntryCardBuilder)->useOverrides(
            $this->override('day-0', [
                'title' => str_repeat('ก', 120),
                'subtitle' => str_repeat('ข', 120),
            ])
        );

        $elements = $this->elements($builder->facebookDays());

        $this->assertLessThanOrEqual(80, mb_strlen($elements[0]['title']));
        $this->assertLessThanOrEqual(80, mb_strlen($elements[0]['subtitle']));
        $this->assertWithinFacebookLimits($elements);
    }

    /**
     * ทะเบียนการ์ดต้องครบ 9 ใบ และรูปเริ่มต้นต้องมีไฟล์จริงทุกใบ
     *
     * 🚨 ใบไหนไฟล์หาย = การ์ดชุดนั้นถูกตีตกทั้งชุดตอน runtime (เงียบ ๆ)
     */
    public function test_ทะเบียนการ์ดครบและรูปเริ่มต้นมีไฟล์จริงทุกใบ(): void
    {
        $registry = FortuneEntryCardBuilder::registry();

        $this->assertCount(9, $registry);

        foreach ($registry as $card) {
            $this->assertFileExists(
                public_path($card['default_image']),
                'รูปเริ่มต้นหาย: '.$card['label'].' → '.$card['default_image']
            );
        }
    }
}
