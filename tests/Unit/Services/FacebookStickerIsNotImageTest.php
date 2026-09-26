<?php

namespace Tests\Unit\Services;

use App\Services\FacebookWebhookService;
use PHPUnit\Framework\TestCase;

/**
 * 👍 สติกเกอร์ไม่ใช่รูป — ล็อก FacebookWebhookService::extractImageFromAttachments()
 *
 * เคสจริง FTU-260927-T0255 (2026-09-27): ลูกค้ากด 👍 ตอนบิล Celtic รอจ่าย
 *   FB ส่ง 👍 มาเป็น type=image + payload.sticker_id → เดิมได้ URL กลับไป → วิ่งเข้าเส้นสลิป
 *   → vision ปิด ตัวแยกรูปตอบ general_photo → บอทตอบ "ขอบคุณค่ะที่ส่งสลิปมาให้แม่หมอ"
 *
 * เมธอดนี้ไม่แตะ state ของ service → สร้างแบบไม่เรียก constructor (ไม่ต้องมี DB)
 */
class FacebookStickerIsNotImageTest extends TestCase
{
    private function extract(array $attachments): ?string
    {
        $svc = (new \ReflectionClass(FacebookWebhookService::class))->newInstanceWithoutConstructor();

        return $svc->extractImageFromAttachments($attachments);
    }

    public function test_ปุ่มยกนิ้ว_ไม่ใช่รูป(): void
    {
        // หน้าตาจริงของ 👍 ที่ Messenger ส่งมา
        $like = [[
            'type' => 'image',
            'payload' => [
                'url' => 'https://scontent.xx.fbcdn.net/v/t39.1997-6/39178562_1505197616293642_5411344281094848512_n.png',
                'sticker_id' => 369239263222822,
            ],
        ]];

        $this->assertNull($this->extract($like), '👍 ต้องไม่ถูกส่งต่อเป็นรูป — ไม่งั้นไหลเข้าเส้นสลิป');
    }

    public function test_รูปจริง_ยังได้_url(): void
    {
        $photo = [['type' => 'image', 'payload' => ['url' => 'https://scontent.xx.fbcdn.net/slip.jpg']]];

        $this->assertSame('https://scontent.xx.fbcdn.net/slip.jpg', $this->extract($photo));
    }

    public function test_สติกเกอร์ปนรูปจริง_ได้รูปจริง(): void
    {
        $mixed = [
            ['type' => 'image', 'payload' => ['url' => 'https://x.test/sticker.png', 'sticker_id' => 1]],
            ['type' => 'image', 'payload' => ['url' => 'https://x.test/slip.jpg']],
        ];

        $this->assertSame('https://x.test/slip.jpg', $this->extract($mixed));
    }

    public function test_ข้อความเสียง_ไม่ใช่รูป(): void
    {
        $this->assertNull($this->extract([['type' => 'audio', 'payload' => ['url' => 'https://x.test/voice.mp4']]]));
    }
}
