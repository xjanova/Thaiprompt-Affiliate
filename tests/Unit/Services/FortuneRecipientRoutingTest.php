<?php

namespace Tests\Unit\Services;

use App\Models\FortuneReading;
use App\Services\Fortune\FortuneRecipient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * 📮 ล็อกกฎ "บิลของลูกค้า LINE ห้ามไหลเข้า Facebook sender เด็ดขาด"
 *
 * ## เคสจริงที่จุดชนวน — reading 12537 / FTU-260907-C5731 (LINE · Celtic 99฿ · จ่ายแล้ว)
 * ```
 * [10:14:40] FortuneAiPingDispatcher: เริ่ม AI session {"reading_id":12537,"platform":"facebook",...}
 * [10:14:51] ❌ Facebook API Error (ครั้งที่ 1) {"recipient":"U388416b0...","error_code":100,
 *            "error_message":"(#100) Param recipient[id] must be a valid ID string"}
 * [10:14:53] ❌ ส่งข้อความล้มเหลวหลังลอง 2 ครั้ง {"chunk_text_preview":"🌙 *แม่หมอ..."}
 * ```
 * บิลใบนั้น `platform='line'` แต่โค้ดแยกช่องทางด้วย `! empty($reading->facebook_user_id)`
 * ซึ่ง **จริงเสมอ** สำหรับลูกค้า LINE (คอลัมน์นั้นเก็บ LINE uid) ⇒ ทุกใบตกเข้าสาขา FB
 * ⇒ ข้อความ 2 กล่องของลูกค้าที่จ่ายเงินแล้ว **หายถาวร ไม่มี fallback**
 *
 * ## เทสต์นี้ล็อกอะไร
 *   1. `fortune_readings` **ไม่มีคอลัมน์ `line_user_id`** — ห้ามมีใครกลับไปพึ่งมันอีก
 *   2. platform column เพี้ยน/ว่าง → ต้องเดาถูกจากรูปทรงของ id
 *   3. `platform='facebook'` + LINE uid → **ต้องชนะด้วย id** (กัน payload เก่าในคิว)
 *   4. ตัวจับ LINE uid ต้องไม่ false-positive ใส่ PSID ของ Facebook
 *
 * ⚠️ ใช้ PHPUnit\Framework\TestCase ตรง ๆ (ไม่แตะ DB) เครื่อง dev ที่ไม่มี MySQL รันได้
 */
class FortuneRecipientRoutingTest extends TestCase
{
    /** LINE uid ของเคสจริง 12537 */
    private const LINE_UID = 'U388416b096f835b950196c7a6001d1f3';

    /** PSID ของ Facebook = ตัวเลขล้วน */
    private const FB_PSID = '8231160590264117';

    /**
     * สร้าง reading ที่ยังไม่ save (ไม่แตะ DB)
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeReading(array $attributes): FortuneReading
    {
        $reading = new FortuneReading;

        foreach ($attributes as $key => $value) {
            $reading->{$key} = $value;
        }

        return $reading;
    }

    /**
     * 🚨 หัวใจของเทสต์: บิล LINE ที่มี LINE uid นั่งอยู่ในคอลัมน์ชื่อ facebook
     *    (= รูปร่างจริงของแถวบน prod) ต้อง resolve เป็น 'line' เท่านั้น
     */
    public function test_บิล_line_ที่เก็บ_uid_ไว้ในคอลัมน์_facebook_user_id_ต้องไปช่องทาง_line(): void
    {
        $reading = $this->makeReading([
            'platform' => 'line',
            'facebook_user_id' => self::LINE_UID,
            'platform_user_id' => self::LINE_UID,
        ]);

        $this->assertSame(
            ['platform' => 'line', 'user_id' => self::LINE_UID],
            FortuneRecipient::resolve($reading),
            'บิล LINE ต้องไม่มีทางถูกส่งเข้า Facebook sender'
        );
    }

    /**
     * 🩹 กันเคสข้อมูลเพี้ยน: คอลัมน์ platform บอก 'facebook' แต่ id เป็นทรง LINE
     *    (แถวเก่าที่ backfill ไม่ถึง / payload ของ job ที่ค้างคิวไว้ก่อน deploy)
     *    ⇒ ต้องเชื่อ id — ข้อนี้คือด่านที่กันบั๊กเดิมได้แม้ caller จะยังส่งค่าผิด
     */
    public function test_platform_บอก_facebook_แต่_id_เป็นทรง_line_ต้องชนะด้วย_id(): void
    {
        $reading = $this->makeReading([
            'platform' => 'facebook',
            'facebook_user_id' => self::LINE_UID,
            'platform_user_id' => self::LINE_UID,
        ]);

        $this->assertSame('line', FortuneRecipient::platformOf($reading));
    }

    /** บิล Facebook จริง (PSID ตัวเลข) ต้องไม่ถูกลากไปฝั่ง LINE */
    public function test_บิล_facebook_จริงยังไปช่องทาง_facebook(): void
    {
        $reading = $this->makeReading([
            'platform' => 'facebook',
            'facebook_user_id' => self::FB_PSID,
            'platform_user_id' => self::FB_PSID,
        ]);

        $this->assertSame(
            ['platform' => 'facebook', 'user_id' => self::FB_PSID],
            FortuneRecipient::resolve($reading)
        );
    }

    /** platform ว่าง/ขยะ → เดาจากรูปทรงของ id ไม่ใช่จาก "มี facebook_user_id ไหม" */
    public function test_platform_ว่างหรือขยะ_ต้องเดาจากรูปทรงของ_id(): void
    {
        $line = $this->makeReading([
            'platform' => null,
            'facebook_user_id' => self::LINE_UID,
        ]);
        $this->assertSame('line', FortuneRecipient::platformOf($line));

        $fb = $this->makeReading([
            'platform' => '   ',
            'facebook_user_id' => self::FB_PSID,
        ]);
        $this->assertSame('facebook', FortuneRecipient::platformOf($fb));

        $junk = $this->makeReading([
            'platform' => 'messenger',
            'facebook_user_id' => self::FB_PSID,
        ]);
        $this->assertSame('facebook', FortuneRecipient::platformOf($junk));
    }

    /**
     * 🕳️ กฎข้อ 8 ของ LINE_MESSAGING_RULES: `fortune_readings` ไม่มีคอลัมน์ `line_user_id`
     *    อ่านแล้วได้ null เสมอ ⇒ ใครเอาไปใช้แยกช่องทางคือพังทันที
     */
    public function test_คอลัมน์_line_user_id_ไม่มีอยู่จริงจึงอ่านได้เป็น_null(): void
    {
        $reading = $this->makeReading([
            'platform' => 'line',
            'facebook_user_id' => self::LINE_UID,
        ]);

        $this->assertNull($reading->line_user_id);
        $this->assertNotContains('line_user_id', (new FortuneReading)->getFillable());
    }

    /** payload ของ job ที่ serialize ค่าผิดไว้ ต้องถูกซ่อมตอนหยิบมาใช้ */
    public function test_normalize_ซ่อม_payload_ของ_job_ที่ระบุช่องทางผิด(): void
    {
        $this->assertSame(
            ['platform' => 'line', 'user_id' => self::LINE_UID],
            FortuneRecipient::normalize('facebook', self::LINE_UID),
            'job ที่ค้างคิวไว้ก่อน deploy ต้องไม่ยิงผิดช่องทางต่อ'
        );

        $this->assertSame(
            ['platform' => 'facebook', 'user_id' => self::FB_PSID],
            FortuneRecipient::normalize('', self::FB_PSID)
        );
    }

    /**
     * ตัวจับ LINE uid = ด่านสุดท้ายใน FacebookWebhookService — false positive
     * แปลว่าตัดข้อความของลูกค้า FB ทิ้ง จึงต้องแคบและตรงเป๊ะ
     *
     * @param  bool  $expected  ควรถูกจับว่าเป็น LINE uid หรือไม่
     */
    #[DataProvider('lineIdShapeProvider')]
    public function test_ตัวจับ_line_uid_ต้องแม่นทั้งสองทาง(?string $id, bool $expected): void
    {
        $this->assertSame($expected, FortuneRecipient::looksLikeLineUserId($id), 'id: '.var_export($id, true));
    }

    /**
     * 🔌 ด่านต้องถูก "ต่อสาย" จริง ไม่ใช่แค่มีเมธอดอยู่
     *
     * กฎ: ทุกเมธอดใน FacebookWebhookService ที่ประกอบ payload
     *     `'recipient' => ['id' => $recipientId]` เอง **ต้อง** เรียก isMisroutedLineRecipient()
     *
     * เทสต์นี้กวาดจากซอร์สโดยตรง ⇒ ถ้าวันหน้ามีใครเพิ่มทางส่งใหม่แล้วลืมด่าน เทสต์แดงทันที
     * (บทเรียน: ของเดิมแก้ sendCelticThinkingAck จุดเดียว แต่ 2 จุดข้าง ๆ ยังพังอยู่ 7 วัน)
     */
    public function test_ทุกทางส่งของ_facebook_sender_ต้องมีด่านกัน_line_uid(): void
    {
        $file = (new \ReflectionClass(\App\Services\FacebookWebhookService::class))->getFileName();
        $source = file_get_contents($file);

        // ซอยไฟล์ตามหัวเมธอด — ชิ้นแรกคือส่วนก่อนเมธอดแรก (ข้าม)
        $chunks = preg_split(
            '/\n    (?:public|protected|private) function (\w+)\(/',
            $source,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $missing = [];
        $checked = 0;

        for ($i = 1; $i < count($chunks); $i += 2) {
            $name = $chunks[$i];
            $body = $chunks[$i + 1] ?? '';

            if (! str_contains($body, "'recipient' => ['id' => \$recipientId]")) {
                continue;
            }

            $checked++;

            if (! str_contains($body, 'isMisroutedLineRecipient(')) {
                $missing[] = $name;
            }
        }

        $this->assertGreaterThan(0, $checked, 'หาเมธอดที่ยิง Graph API ด้วย $recipientId ไม่เจอ — regex ผิดหรือโค้ดถูกรื้อ');
        $this->assertSame([], $missing, 'ทางส่งเหล่านี้ยังไม่มีด่านกัน LINE uid: '.implode(', ', $missing));
    }

    /**
     * @return array<string, array{0:?string,1:bool}>
     */
    public static function lineIdShapeProvider(): array
    {
        return [
            'LINE uid เคสจริง' => [self::LINE_UID, true],
            'LINE uid ตัวพิมพ์ใหญ่' => ['U'.strtoupper(substr(self::LINE_UID, 1)), true],
            'LINE uid มีช่องว่างติดมา' => ['  '.self::LINE_UID.' ', true],
            'PSID ตัวเลขล้วน' => [self::FB_PSID, false],
            'PSID ที่บังเอิญยาว 33' => [str_repeat('9', 33), false],
            'ขึ้นต้น U แต่สั้นไป' => ['U388416b096f835b950196c7a6001d1f', false],
            'ขึ้นต้น U แต่ยาวไป' => [self::LINE_UID.'0', false],
            'ขึ้นต้น U แต่มีตัวอักษรนอก hex' => ['U388416b096f835b950196c7a6001d1z', false],
            'comment id ของ FB' => ['1234567890_9876543210', false],
            'ค่าว่าง' => ['', false],
            'null' => [null, false],
        ];
    }
}
