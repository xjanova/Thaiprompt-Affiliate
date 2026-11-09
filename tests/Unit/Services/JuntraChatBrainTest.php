<?php

namespace Tests\Unit\Services;

use App\Jobs\ExtractCustomerPersonaJob;
use App\Models\FortuneCustomerPersona;
use App\Services\Fortune\JuntraChatService;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * 🧠 แชทเว็บจันทราใช้ "สมอง" ชุดเดียวกับแชทเพจ FB (2026-11-07 · แพ็ก E)
 *
 *   1. ด่านกันมโนว่าเปิดไพ่/ทำพิธีให้แล้ว — FortuneAIService::detectHallucinatedReading ตัวเดียวกับแชทเพจ
 *   2. ผังดวงจริงจากวันเกิดในโปรไฟล์ — ThaiAstrologyService::formatPersonBlock ตัวเดียวกับบิลดูดวงของเพจ
 *   3. ความจำระยะยาว (CustomerPersonaService platform "web") — อ่าน เขียน และธงเปราะบางข้ามห้อง
 *
 * ใช้ sqlite ในหน่วยความจำ — รันบนเครื่อง dev ที่ไม่มี MySQL ได้
 */
class JuntraChatBrainTest extends TestCase
{
    private const CLEAN = 'แม่หมอเห็นว่าช่วงนี้ลูกเหนื่อยใจมาก ค่อย ๆ พักก่อนนะคะ ✨';

    /** คำตอบที่แกล้งทำว่าเปิดไพ่ให้แล้ว (เคสจริงของแชทเพจ 2026-05-26) */
    private const FAKE_OPEN = 'เรียบร้อยแล้วค่ะลูก แม่หมอเปิดไพ่ให้แล้ว ใบแรก Five of Pentacles ตามด้วย The Star '
        .'และ Eight of Swords บอกว่าช่วงนี้ลูกจะเจอเรื่องเงินติดขัดก่อนจะคลี่คลายในเดือนหน้านะคะ';

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

    /** @param  array<int,string>  $replies */
    private function fakeAi(array $replies): FortuneAIService
    {
        $ai = new class extends FortuneAIService
        {
            /** @var array<int,string> */
            public array $queue = [];

            /** @var array<int,array{provider:?string,system:string}> */
            public array $calls = [];

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
                return $systemMessage;
            }
        };
        $ai->queue = $replies;

        return $ai;
    }

    /** ตารางความจำลูกค้า — ทุก migration ของตารางนี้ (ตัวสร้าง + คอลัมน์ที่เพิ่มทีหลัง เช่น risk_flags) */
    private function personaTable(): void
    {
        $files = collect(File::glob(database_path('migrations/*.php')))
            ->filter(fn ($f) => str_contains((string) file_get_contents($f), 'fortune_customer_personas'))
            ->sort()
            ->values();
        foreach ($files as $file) {
            try {
                (require $file)->up();
            } catch (\Throwable) {
                // ส่วนที่เป็นคำสั่งเฉพาะ MySQL (SHOW INDEX) — คอลัมน์ถูกเพิ่มไปก่อนถึงบรรทัดนั้นแล้ว
            }
        }
        $this->assertTrue(Schema::hasColumn('fortune_customer_personas', 'risk_flags'));
    }

    // ─────────────────────────────────────────────────────────────
    // 1. ด่านกันมโนว่าเปิดไพ่
    // ─────────────────────────────────────────────────────────────

    public function test_แชทฟรีมโนว่าเปิดไพ่ให้แล้ว_ลองทางถัดไป(): void
    {
        $ai = $this->fakeAi([self::FAKE_OPEN, self::CLEAN]);

        $out = (new JuntraChatService($ai))->send('jw1', 's1', 'ช่วยดูเรื่องเงินให้หน่อยค่ะ');

        $this->assertSame(self::CLEAN, $out['reply']);
        $this->assertCount(2, $ai->calls, 'คำตอบมโนห้ามถึงลูกค้า');
    }

    public function test_ทางสุดท้ายก็มโน_ไม่ส่งถึงลูกค้า(): void
    {
        $ai = $this->fakeAi([self::FAKE_OPEN, self::FAKE_OPEN]);

        $this->expectException(RuntimeException::class);
        (new JuntraChatService($ai))->send('jw1', 's2', 'ช่วยดูเรื่องเงินให้หน่อยค่ะ');
    }

    public function test_คำตอบชวนเปิดไพ่พร้อมป้าย_ผ่านแม้มีคำว่าตั้งจิต(): void
    {
        $offer = 'ได้เลยค่ะลูก ตั้งจิตนึกถึงเรื่องเงินให้มั่น แล้วเลือกแบบการเปิดไพ่ด้านล่างได้เลยนะคะ '
            .'แม่หมอจะเปิดไพ่ให้ดูอย่างตั้งใจที่สุดค่ะ ✨ [[OFFER:money]]';
        $ai = $this->fakeAi([$offer]);

        $out = (new JuntraChatService($ai))->send('jw1', 's3', 'ช่วยดูเรื่องเงินให้หน่อยค่ะ', webOffers: true);

        $this->assertCount(1, $ai->calls);
        $this->assertSame('money', $out['offer_topic']);
    }

    public function test_ห้องคุยต่อจากคำพยากรณ์_เล่าไพ่ที่เปิดไปแล้วได้(): void
    {
        $ai = $this->fakeAi([self::FAKE_OPEN]);

        $out = (new JuntraChatService($ai))->send('jw1', 's4', 'ไพ่ที่ขึ้นแปลว่าอะไรคะ',
            context: 'ไพ่: Five of Pentacles, The Star, Eight of Swords · คำพยากรณ์: เงินติดขัดก่อนคลี่คลาย');

        $this->assertSame(self::FAKE_OPEN, $out['reply']);
        $this->assertCount(1, $ai->calls);
    }

    public function test_บทเล่าคำทำนายไม่โดนด่านมโนไพ่(): void
    {
        $ai = $this->fakeAi([self::FAKE_OPEN]);

        $out = (new JuntraChatService($ai))->complete('system', 'เล่าคำทำนายนี้', []);

        $this->assertSame(self::FAKE_OPEN, $out['response']);
    }

    // ─────────────────────────────────────────────────────────────
    // 2. ผังดวงจริงจากวันเกิด
    // ─────────────────────────────────────────────────────────────

    public function test_ห้องคุยทั่วไปได้พื้นดวงจริง_ไม่มีดาวจร(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        (new JuntraChatService($ai))->send('jw1', 's5', 'ช่วงนี้เหนื่อยจัง', astro: ['birth_date' => '1990-11-07']);

        $system = $ai->calls[0]['system'];
        $this->assertStringContainsString('พื้นดวงจริงของลูก', $system);
        $this->assertStringContainsString('ราศี: ตุลย์', $system, 'ราศีแบบไทย (นิรายนะ) ชุดเดียวกับผังในบิล');
        $this->assertStringContainsString('ดาวเจ้าชนะ: ดาวพุธ', $system);
        $this->assertStringNotContainsString('ดาวจรวันนี้', $system, 'ห้องฟรีไม่ได้ผังเต็ม');
    }

    public function test_ห้องคำพยากรณ์ได้ผังเต็มพร้อมดาวจร(): void
    {
        $ai = $this->fakeAi([self::CLEAN]);

        (new JuntraChatService($ai))->send('jw1', 's6', 'เรื่องงานจะเป็นยังไงคะ',
            context: 'ไพ่: The Sun · คำพยากรณ์: งานรุ่ง', astro: ['birth_date' => '1990-11-07', 'birth_time' => '08:30']);

        $system = $ai->calls[0]['system'];
        $this->assertStringContainsString('ผังดวงจริงของลูก', $system);
        $this->assertStringContainsString('ดาวจรวันนี้', $system);
        $this->assertStringContainsString('ลัคนา', $system);
    }

    public function test_ไม่มีวันเกิด_หรือวันเกิดผิดรูป_ไม่มีผังดวง(): void
    {
        foreach ([null, ['birth_date' => '07/11/1990'], ['birth_date' => now()->addDay()->toDateString()]] as $i => $astro) {
            $ai = $this->fakeAi([self::CLEAN]);
            (new JuntraChatService($ai))->send('jw1', "s7{$i}", 'ช่วงนี้เหนื่อยจัง', astro: $astro);
            $this->assertStringNotContainsString('พื้นดวงจริงของลูก', $ai->calls[0]['system']);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3. ความจำระยะยาว (ระบบเดียวกับแชทเพจ)
    // ─────────────────────────────────────────────────────────────

    public function test_ความจำของลูกค้าเว็บอยู่ใน_system_และเทิร์นนี้ถูกส่งไปจำต่อ(): void
    {
        $this->personaTable();
        Bus::fake([ExtractCustomerPersonaJob::class]);
        FortuneCustomerPersona::create([
            'platform' => JuntraChatService::MEMORY_PLATFORM,
            'platform_user_id' => 'jw7',
            'traits' => ['ขี้กังวล', 'ใจดี'],
            'likes' => ['แมว'],
            'observation_count' => 3,
        ]);
        $ai = $this->fakeAi([self::CLEAN]);

        (new JuntraChatService($ai))->send('jw7', 's9', 'ช่วงนี้เครียดเรื่องงานมากเลยค่ะ ทำยังไงดี', webMemory: true);

        $system = $ai->calls[0]['system'];
        $this->assertStringContainsString('สิ่งที่แม่หมอจำได้เกี่ยวกับลูกคนนี้', $system);
        $this->assertStringContainsString('แมว', $system);
        $this->assertStringNotContainsString('CUSTOMER_TIMELINE', $system, 'หัว timeline นับจากบิลแชทเพจ — ไม่ใช่ของลูกค้าเว็บ');
        Bus::assertDispatched(ExtractCustomerPersonaJob::class);
    }

    public function test_ทาง_token_ไม่อ่านไม่เขียนความจำเว็บ(): void
    {
        $this->personaTable();
        Bus::fake([ExtractCustomerPersonaJob::class]);
        FortuneCustomerPersona::create(['platform' => 'web', 'platform_user_id' => '15', 'likes' => ['แมว'], 'observation_count' => 1]);
        $ai = $this->fakeAi([self::CLEAN]);

        (new JuntraChatService($ai))->send('15', 's10', 'ช่วงนี้เครียดเรื่องงานมากเลยค่ะ ทำยังไงดี');

        $this->assertStringNotContainsString('แมว', $ai->calls[0]['system']);
        Bus::assertNotDispatched(ExtractCustomerPersonaJob::class);
    }

    /** 🤍 (2026-11-09 owner) เลิกห้องวิกฤตแล้ว — ธง mental_fragile ในความจำไม่ปิดการ์ดเสนอไพ่อีก */
    public function test_ความจำติดธงเปราะบาง_ไม่ทำให้เป็นห้องวิกฤต(): void
    {
        $this->personaTable();
        Bus::fake([ExtractCustomerPersonaJob::class]);
        FortuneCustomerPersona::create([
            'platform' => 'web', 'platform_user_id' => 'jw8', 'observation_count' => 1,
            'risk_flags' => ['mental_fragile' => true],
        ]);
        $ai = $this->fakeAi(['แม่หมอเปิดไพ่เรื่องงานให้ได้นะคะลูก [[OFFER:career]]']);

        $out = (new JuntraChatService($ai))->send('jw8', 's11', 'อยากดูดวงเรื่องงานค่ะ', webOffers: true, webMemory: true);

        $this->assertSame('career', $out['offer_topic']);
        $this->assertArrayNotHasKey('crisis', $out);
        $this->assertArrayNotHasKey('crisis_room', $out);
    }

    /** 🤍 (2026-11-09 owner) "อยากตาย ไม่ไหวแล้ว" ในแชทเว็บ → ส่งให้ AI ตอบตามปกติ ไม่มีคำปลอบสำเร็จรูป ไม่ติดธง */
    public function test_ข้อความอยากตาย_ไปให้_AI_ตอบตามปกติ(): void
    {
        $this->personaTable();
        Bus::fake([ExtractCustomerPersonaJob::class]);
        $ai = $this->fakeAi([self::CLEAN]);

        $out = (new JuntraChatService($ai))->send('jw9', 's12', 'อยากตาย ไม่ไหวแล้ว', webMemory: true);

        $this->assertCount(1, $ai->calls, 'ต้องเรียก AI');
        $this->assertSame('reply', $out['kind']);
        $this->assertSame(self::CLEAN, $out['reply']);
        $this->assertStringNotContainsString('ลูกค้าคนนี้เคยบอกว่าอยากทำร้าย', $ai->calls[0]['system']);
        $this->assertFalse((bool) (FortuneCustomerPersona::where('platform', 'web')->where('platform_user_id', 'jw9')->first()?->risk_flags['mental_fragile'] ?? false));
    }
}
