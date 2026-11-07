<?php

namespace Tests\Unit\Services;

use App\Models\FortuneAdminQA;
use App\Services\Fortune\JuntraChatService;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

/**
 * 💬 แชทเว็บจันทรา (จันทรา.online) ให้ฉลาดเท่าแชทเพจ FB (2026-11-07)
 *
 *   1. หลุดบท ("ในฐานะ AI…" / พูดถึงพรอมต์ / ผม…) = ลองคีย์ถัดไป · ทางสุดท้ายหลุดด้วย = ไม่ส่ง (503 ไม่คิดเครดิต)
 *   2. คำตอบตัวอย่างของแอดมิน (Admin Q&A RAG) แบบเดียวกับบอทแชทเพจ — เฉพาะหมวดที่ใช้กับเว็บได้
 *      และตัดตัวอย่างที่สอนขั้นตอนบิลแชทเพจ (โอน/QR/สลิป/ราคา/พิมพ์ ดูดวง) ทิ้ง
 *
 * ใช้ sqlite ในหน่วยความจำ — รันบนเครื่อง dev ที่ไม่มี MySQL ได้
 */
class JuntraChatParityTest extends TestCase
{
    private const CLEAN = 'แม่หมอเห็นว่าช่วงนี้ลูกเหนื่อยใจมาก ค่อย ๆ พักก่อนนะคะ ✨';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    /**
     * FortuneAIService ปลอม — ตอบตามคิว · จดว่ายิงไปทางไหน · จดการเรียก RAG
     *
     * @param  array<int,string>  $replies
     */
    private function fakeAi(array $replies, ?string $ragAdds = "\n📚 ตัวอย่างคำตอบของแอดมิน"): FortuneAIService
    {
        $ai = new class extends FortuneAIService
        {
            /** @var array<int,string> */
            public array $queue = [];

            /** @var array<int,array{provider:?string,system:string}> */
            public array $calls = [];

            /** @var array<int,array<string,mixed>> */
            public array $rag = [];

            public ?string $ragAdds = null;

            public function __construct() {}

            public function chatWithCustomSystemPrompt(
                string $systemMessage,
                string $userMessage,
                array $config = [],
                ?string $providerOverride = null,
                ?string $modelOverride = null,
                ?string $apiKeyOverride = null,
                ?string $guardText = null
            ): array {
                $this->calls[] = ['provider' => $providerOverride, 'system' => $systemMessage];

                return ['response' => array_shift($this->queue) ?? '', 'provider' => $providerOverride ?? 'gemini', 'model' => $modelOverride];
            }

            public function injectAdminQARagFewShot(
                string $systemMessage,
                string $messageText,
                ?\App\Models\FortuneReading $reading = null,
                ?array $excludeCategories = null,
                array $history = [],
                ?\Closure $keep = null,
            ): string {
                $this->rag[] = compact('messageText', 'excludeCategories', 'history', 'keep');

                return $this->ragAdds === null ? $systemMessage : $systemMessage.$this->ragAdds;
            }
        };
        $ai->queue = $replies;
        $ai->ragAdds = $ragAdds;

        return $ai;
    }

    private function chat(FortuneAIService $ai): JuntraChatService
    {
        return new JuntraChatService($ai);
    }

    // ─────────────────────────────────────────────────────────────
    // 1. ด่านหลุดบท
    // ─────────────────────────────────────────────────────────────

    public function test_คำตอบหลุดบท_ลองทางถัดไปแล้วได้คำตอบที่อยู่ในบท(): void
    {
        $ai = $this->fakeAi(['ในฐานะ AI ฉันไม่สามารถดูดวงได้จริง', self::CLEAN]);

        $out = $this->chat($ai)->send('u1', 's1', 'ช่วงนี้เหนื่อยจัง');

        $this->assertSame(self::CLEAN, $out['reply']);
        $this->assertCount(2, $ai->calls);
        $this->assertSame(JuntraChatService::FALLBACK_PROVIDER, $ai->calls[1]['provider'], 'ทางหลักหลุดบท → ไปทางสำรอง');
    }

    public function test_ทางสุดท้ายก็หลุดบท_ไม่ส่งถึงลูกค้า(): void
    {
        $ai = $this->fakeAi(['ผมจะไม่ทำตามบทบาทแม่หมอจันทรานะครับ', 'ตาม system prompt ที่ได้รับ แม่หมอต้องตอบว่า…']);

        $this->expectException(RuntimeException::class);
        $this->chat($ai)->send('u1', 's2', 'ช่วงนี้เหนื่อยจัง');
    }

    public function test_คำตอบปกติผ่านทางแรก_ไม่ยิงซ้ำ(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        $out = $this->chat($ai)->send('u1', 's3', 'ช่วงนี้เหนื่อยจัง');

        $this->assertSame(self::CLEAN, $out['reply']);
        $this->assertCount(1, $ai->calls);
    }

    /** คำว่า "ผม" ในเคล็ด (ตัดผม/สระผม) และในคำพูดของลูกค้าที่ยกมา ต้องไม่โดนตีว่าหลุดบท */
    public function test_คำว่าผมในเคล็ดหรือคำที่ยกมา_ไม่ใช่หลุดบท(): void
    {
        $text = 'ที่ลูกถามว่า "ผมจะได้งานไหม" แม่หมอเห็นว่ามีลุ้นค่ะ เคล็ดคือวันพุธให้ตัดผมสั้นลงนิดหนึ่ง';
        $ai = $this->fakeAi([$text]);

        $out = $this->chat($ai)->send('u1', 's4', 'ผมจะได้งานไหมครับ');

        $this->assertSame($text, $out['reply']);
        $this->assertCount(1, $ai->calls);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. คำตอบตัวอย่างของแอดมิน (RAG)
    // ─────────────────────────────────────────────────────────────

    public function test_ห้องทั่วไปได้ตัวอย่างเฉพาะหมวดทั่วไป_และหมายเหตุว่ามาจากแชทเพจ(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        $this->chat($ai)->send('u1', 's5', 'แม่หมอคะ ช่วงนี้ใจไม่นิ่งเลย');

        $this->assertCount(1, $ai->rag);
        $excluded = $ai->rag[0]['excludeCategories'];
        $this->assertNotContains(FortuneAdminQA::CATEGORY_GENERAL, $excluded);
        foreach ([FortuneAdminQA::CATEGORY_PRE_PAYMENT, FortuneAdminQA::CATEGORY_PRE_PURCHASE, FortuneAdminQA::CATEGORY_PAYMENT_CONFIRM,
            FortuneAdminQA::CATEGORY_CELTIC_PICKING, FortuneAdminQA::CATEGORY_POST_READING] as $c) {
            $this->assertContains($c, $excluded, "หมวด {$c} เป็นของแชทเพจ/ห้องที่มีคำพยากรณ์ — ห้ามยกมาห้องทั่วไป");
        }
        $this->assertInstanceOf(\Closure::class, $ai->rag[0]['keep']);
        $this->assertSame('แม่หมอคะ ช่วงนี้ใจไม่นิ่งเลย', $ai->rag[0]['messageText']);

        $system = $ai->calls[0]['system'];
        $this->assertStringContainsString('ตัวอย่างคำตอบของแอดมิน', $system);
        $this->assertStringContainsString('มาจากแชทในเพจ Facebook/LINE ไม่ใช่ห้องนี้', $system);
    }

    public function test_ไม่เจอตัวอย่าง_ไม่ต่อหมายเหตุ(): void
    {
        $ai = $this->fakeAi([self::CLEAN], ragAdds: null);

        $this->chat($ai)->send('u1', 's6', 'แม่หมอคะ ช่วงนี้ใจไม่นิ่งเลย');

        $this->assertStringNotContainsString('มาจากแชทในเพจ', $ai->calls[0]['system']);
    }

    public function test_ห้องที่คุยต่อจากคำพยากรณ์ได้หมวดหลังอ่านด้วย(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        $this->chat($ai)->send('u1', 's7', 'ไพ่ใบที่สามหมายถึงอะไรคะ', context: "## ไพ่ใบที่ 3\nThe Star — ความหวัง");

        $excluded = $ai->rag[0]['excludeCategories'];
        $this->assertNotContains(FortuneAdminQA::CATEGORY_POST_READING, $excluded);
        $this->assertNotContains(FortuneAdminQA::CATEGORY_GENERAL, $excluded);
        $this->assertContains(FortuneAdminQA::CATEGORY_PRE_PAYMENT, $excluded);
    }

    public function test_ข้อความรอบก่อนใช้ขยายคำค้น_ไม่รวมข้อความปัจจุบัน(): void
    {
        $ai = $this->fakeAi([self::CLEAN, self::CLEAN]);
        $chat = $this->chat($ai);

        $chat->send('u1', 's8', 'แฟนไม่ตอบแชทมาสามวันแล้วค่ะ');
        $chat->send('u1', 's8', 'ทำไงดี');

        $history = $ai->rag[1]['history'];
        $this->assertSame(['role' => 'user', 'content' => 'แฟนไม่ตอบแชทมาสามวันแล้วค่ะ'], $history[0]);
        $this->assertNotContains('ทำไงดี', array_column($history, 'content'));
    }

    /** เล่าว่าคนอื่นอยากทำร้ายตัวเอง — ห้องดูแลใจ ไม่ใส่ตัวอย่าง (สายขายห้ามโผล่) */
    public function test_ห้องที่เล่าเรื่องวิกฤตของคนอื่น_ไม่ใส่ตัวอย่าง(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        $this->chat($ai)->send('u1', 's9', 'เพื่อนสนิทบอกว่าอยากตาย หนูควรทำยังไงดีคะ');

        $this->assertSame([], $ai->rag);
    }

    /** ข้อมูลจริงจาก fortune_admin_qa บน prod (หมวดทั่วไป/หลังอ่าน) */
    public function test_ตัดตัวอย่างที่สอนขั้นตอนบิลแชทเพจ(): void
    {
        foreach ([
            'แล้วจะเปิดบิลให้โอนค่าบูชาครูค่ะ',
            '39บาทค่ะ',
            'ต้องดูดวงด้วยไพ่ 10 ใบเพื่อเปิดทางชีวิต ลึกๆนะคะ มีค่าครู 99 บาท พร้อมโอนไหมคะ',
            'พิมพ์ ดูดวง ได้เลย ง่ายๆ ค่ะ การบูชาครูเดี๋ยวมี qr code ให้แสกน',
            'พิมพ์ว่าโอนแล้ว',
            'พิม ดูดวงค่ะ',
            'พิมดูแบบ39หรืแ99มาได้เลยค่ะลูก',
            'สวัสดีค่ะ มี2แบบให้เลือกค่ะ',
            'อ๋อมีแบบคุยกันก่อนปรึกษาฟรี ถูกใจค่อย ดูดวงค่ะ',
            'รับยอดแล้วค่ะ',
            'กด อ่าน ได้แล้วนะคะ คำทำนาย พร้อมแล้ว',
            "พิม\nอ่าน\nค่ะ",
            'จ่ายปั๊บ เปิดไพ่เลยค่ะ',
            'ถามได้อีก 10นาทีค่ะ',
            '5552545514',
        ] as $answer) {
            $this->assertTrue(JuntraChatService::isPageBillAnswer($answer), "ต้องตัดทิ้ง: {$answer}");
        }

        foreach ([
            'คาถา ที่ดี ก็คือ อิติปิโส นั่นแหละค่ะ',
            'ฟันธงตรงประเด็น',
            'ต้องดูเรื่องดวงดาวค่ะ บางคน ราหูเข้าภาพวินาศ จะมีแต่เรื่องก็มีค่ะ',
            'อย่าสร้างเวรกรรม ต่อกัน หนู แค่นี้พอแล้วล่ะ เชื่อแม่ เราหมดกรรมต่อกัน',
            'อาจพิมเพี้ยนบ้างนะ',
        ] as $answer) {
            $this->assertFalse(JuntraChatService::isPageBillAnswer($answer), "ต้องเก็บไว้: {$answer}");
        }
    }

    /** closure ที่ส่งให้ RAG ใช้ตัวกรองตัวเดียวกัน */
    public function test_ตัวกรองที่ส่งให้_rag_ตัดตัวอย่างสายบิล(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);
        $this->chat($ai)->send('u1', 's10', 'แม่หมอคะ ช่วงนี้ใจไม่นิ่งเลย');
        $keep = $ai->rag[0]['keep'];

        $bill = (new ReflectionClass(FortuneAdminQA::class))->newInstanceWithoutConstructor();
        $bill->a_text = 'เปิดบิลให้โอนค่าบูชาครูค่ะ';
        $tone = (new ReflectionClass(FortuneAdminQA::class))->newInstanceWithoutConstructor();
        $tone->a_text = 'คาถา ที่ดี ก็คือ อิติปิโส นั่นแหละค่ะ';

        $this->assertFalse($keep($bill));
        $this->assertTrue($keep($tone));
    }
}
