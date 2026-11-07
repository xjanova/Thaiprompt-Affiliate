<?php

namespace Tests\Feature;

use App\Models\FortuneVoiceUsage;
use App\Services\Fortune\VoicePlanParser;
use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;
use Tests\Unit\Services\NarrationFidelityTest;

/**
 * 🎬 (2026-11-04) POST /api/v1/juntra/server/voice/narration — บทเล่าคำทำนายของโรงละคร (สัญญาข้อ 23.1)
 *
 * AI เขียน → ตรวจความตรง → เขียนใหม่ 1 ครั้ง → ยังผิด = ประโยคจากคำทำนาย · AI ล่มก็ยังได้บท
 * คำตอบ AI ผ่านตัวกรองขาออกจริง (stripInternalContextTags) เหมือนบน prod
 */
class JuntraNarrationApiTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,array{system:string,user:string,config:array}> */
    private array $aiCalls = [];

    /** คำตอบของ AI ทีละครั้ง — Throwable = ทางนั้นล่ม · หมดคิว = ตอบว่าง */
    private array $aiScript = [];

    private const CARDS = [
        ['position' => 1, 'name_th' => 'คู่รัก', 'reversed' => false],
        ['position' => 2, 'name_th' => 'หอคอย', 'reversed' => true],
        ['position' => 3, 'name_th' => 'ดวงดาว', 'reversed' => false],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        (require database_path('migrations/2026_11_04_120000_create_fortune_voice_usages_table.php'))->up();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('chatWithCustomSystemPrompt')->andReturnUsing(function (...$args) {
            $this->aiCalls[] = ['system' => $args[0], 'user' => $args[1], 'config' => $args[2] ?? []];
            $next = array_shift($this->aiScript);
            if ($next instanceof \Throwable) {
                throw $next;
            }

            return ['response' => FortuneAIService::stripInternalContextTags((string) $next), 'provider' => 'gemini'];
        });
        $this->app->instance(FortuneAIService::class, $ai);
    }

    private function aiJson(array $chapters): string
    {
        return "```json\n".json_encode(['chapters' => $chapters], JSON_UNESCAPED_UNICODE)."\n```";
    }

    private function goodThree(): array
    {
        return [
            ['anchor' => 'birth', 'tone' => null, 'energy' => null, 'say' => '{calm} ลูกเกิดวันพุธ ราศีมีน ธาตุน้ำ ใจอ่อนโยนนะ'],
            ['anchor' => 'graph', 'tone' => null, 'energy' => null, 'say' => 'ดวงลูกช่วงนี้ขึ้นลงนิดหน่อย แต่ปลายทางสว่าง'],
            ['anchor' => 'card:1', 'tone' => 'good', 'energy' => 4, 'say' => 'ใบแรกคู่รัก ลูกเคยมีความสัมพันธ์ที่ดีมาก่อน'],
            ['anchor' => 'card:2', 'tone' => 'caution', 'energy' => 2, 'say' => '{softly} หอคอยกลับหัว ช่วงวันที่ 15 ระวังคำพูดนะลูก'],
            ['anchor' => 'card:3', 'tone' => 'good', 'energy' => 5, 'say' => 'ดวงดาวพาความหวังกลับมา ใส่สีชมพูไว้นะ'],
            ['anchor' => 'summary', 'tone' => 'good', 'energy' => 4, 'say' => '{warmly} คำตอบคือใช่ ความรักครั้งนี้ไปต่อได้ ใจเย็นไว้'],
        ];
    }

    private function narrate(array $overrides = [])
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/voice/narration', $overrides + [
            'persona' => 'maemor',
            'spread' => 'three',
            'reading_text' => NarrationFidelityTest::THREE,
            'cards' => self::CARDS,
            'birth_known' => true,
            'user_ref' => '42',
        ]);
    }

    public function test_a_faithful_script_comes_back_in_the_fixed_order_and_is_cached(): void
    {
        $this->aiScript = [$this->aiJson(array_reverse($this->goodThree()))];

        $res = $this->narrate()->assertOk();
        $this->assertSame(['birth', 'graph', 'card:1', 'card:2', 'card:3', 'summary', 'outro'], array_column($res->json('data.chapters'), 'anchor'));
        $this->assertSame(['birth', 'graph', 'card', 'card', 'card', 'summary', 'outro'], array_column($res->json('data.chapters'), 'kind'));
        $this->assertSame(['passed' => 6, 'rewritten' => 0, 'extractive' => 0], $res->json('data.fidelity'));

        $card2 = $res->json('data.chapters.3');
        // แท็ก {softly} ของ AI รอดตัวกรองขาออก แล้วกลายเป็น [softly] ของ ElevenLabs
        $this->assertSame('[softly] หอคอยกลับหัว ช่วงวันที่ 15 ระวังคำพูดนะลูก', $card2['say']);
        $this->assertSame(['caution', 2], [$card2['tone'], $card2['energy']]);
        // ฟันธง "ใช่" → โทนสรุปเป็น good เสมอ ไม่ว่า AI ส่งอะไร
        $this->assertSame('good', $res->json('data.chapters.5.tone'));
        $this->assertNull($res->json('data.chapters.0.tone'));
        $this->assertSame(64, strlen($res->json('data.script_hash')));

        // AI เห็นไพ่จริง + งบต่อบท + คำทำนายในรั้ว
        $this->assertStringContainsString('ใบที่ 2: หอคอย (กลับหัว)', $this->aiCalls[0]['user']);
        $this->assertStringContainsString("====\n## 🎯 ฟันธง", $this->aiCalls[0]['user']);
        $this->assertStringContainsString('ห้ามทำนายเพิ่ม', $this->aiCalls[0]['system']);

        $total = array_sum(array_map(fn ($c) => mb_strlen($c['say']), $res->json('data.chapters')));
        $this->assertLessThanOrEqual(500, $total);

        $usage = FortuneVoiceUsage::sole();
        $this->assertSame(['narration', 'gemini', 'maemor', '42'], [$usage->kind, $usage->provider, $usage->persona, $usage->user_ref]);

        // คำทำนายเดิม = บทเดิม ไม่เรียก AI ซ้ำ
        $this->narrate()->assertOk()->assertJsonPath('data.script_hash', $res->json('data.script_hash'));
        $this->assertCount(1, $this->aiCalls);
    }

    public function test_a_wrong_chapter_is_rewritten_once_with_the_reason(): void
    {
        $bad = $this->goodThree();
        $bad[2]['say'] = 'ใบแรกคือหอคอย ลูกเคยผ่านเรื่องหนักมา';
        $this->aiScript = [
            $this->aiJson($bad),
            $this->aiJson([['anchor' => 'card:1', 'tone' => 'good', 'energy' => 4, 'say' => 'คู่รักตั้งตรง ลูกเคยมีความสัมพันธ์ที่ดี']]),
        ];

        $res = $this->narrate()->assertOk();
        $this->assertSame(['passed' => 6, 'rewritten' => 1, 'extractive' => 0], $res->json('data.fidelity'));
        $this->assertSame('คู่รักตั้งตรง ลูกเคยมีความสัมพันธ์ที่ดี', $res->json('data.chapters.2.say'));

        $this->assertCount(2, $this->aiCalls);
        $this->assertStringContainsString('card:1', $this->aiCalls[1]['user']);
        $this->assertStringContainsString('หอคอย ซึ่งเป็นใบที่ 2', $this->aiCalls[1]['user']);
        $this->assertStringNotContainsString('- card:2', $this->aiCalls[1]['user']);
    }

    public function test_a_chapter_still_wrong_after_the_rewrite_uses_the_readings_own_sentences(): void
    {
        $bad = $this->goodThree();
        $bad[5]['say'] = 'คำตอบคือไม่ใช่ ความรักนี้ไม่ไปไหน';
        $this->aiScript = [
            $this->aiJson($bad),
            $this->aiJson([['anchor' => 'summary', 'tone' => 'caution', 'energy' => 1, 'say' => 'ไม่ใช่นะ ยังไม่ถึงเวลา']]),
        ];

        $res = $this->narrate()->assertOk();
        $this->assertSame(['passed' => 5, 'rewritten' => 0, 'extractive' => 1], $res->json('data.fidelity'));
        $summary = $res->json('data.chapters.5');
        $this->assertSame('summary', $summary['anchor']);
        $this->assertStringContainsString('ความรักครั้งนี้ไปต่อได้', $summary['say']);
        $this->assertStringNotContainsString('ผล:', $summary['say']);
        $this->assertSame('good', $summary['tone']);
    }

    public function test_when_the_ai_is_down_every_chapter_comes_from_the_reading(): void
    {
        $this->aiScript = [new \RuntimeException('gemini high demand ?key=AIzaSECRET'), new \RuntimeException('luna down')];

        $res = $this->narrate()->assertOk();
        $this->assertSame(['passed' => 0, 'rewritten' => 0, 'extractive' => 6], $res->json('data.fidelity'));
        $this->assertSame('[calm] มาดูภาพรวมของดวงลูกช่วงนี้กันก่อนนะ ว่าขึ้นลงอย่างไร', $res->json('data.chapters.1.say'));
        $this->assertStringContainsString('หอคอยกลับหัว', $res->json('data.chapters.3.say'));
        $this->assertSame('extractive', FortuneVoiceUsage::sole()->provider);
    }

    public function test_busaba_without_a_birth_date_skips_the_birth_chapter_and_never_says_luuk(): void
    {
        $chapters = array_slice($this->goodThree(), 1);
        $chapters[0]['say'] = 'ช่วงนี้ดวงของคุณขึ้นลงนิดหน่อยค่ะ';
        $chapters[1]['say'] = 'ใบแรกคู่รัก คุณเคยมีความสัมพันธ์ที่ดีมาก่อน';
        $chapters[2]['say'] = 'หอคอยกลับหัว ช่วงวันที่ 15 ระวังคำพูดนะคะ';
        $chapters[3]['say'] = 'ดวงดาวพาความหวังกลับมาค่ะ';
        $chapters[4]['say'] = 'คำตอบคือใช่ค่ะ ความรักครั้งนี้ไปต่อได้';
        $this->aiScript = [$this->aiJson($chapters)];

        $res = $this->narrate(['persona' => 'busaba', 'birth_known' => false])->assertOk();
        $this->assertSame(['graph', 'card:1', 'card:2', 'card:3', 'summary', 'outro'], array_column($res->json('data.chapters'), 'anchor'));
        foreach ($res->json('data.chapters') as $c) {
            $this->assertDoesNotMatchRegularExpression('/ลูก(?!ค้า|ของ)/u', $c['say'], $c['anchor']);
        }
        $this->assertStringContainsString('เรียกลูกค้าว่า "คุณ"', $this->aiCalls[0]['system']);
        $this->assertStringNotContainsString('- birth:', $this->aiCalls[0]['user']);
    }

    public function test_a_year_reading_keeps_its_month_tones_and_stays_inside_the_budget(): void
    {
        $months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $tones = ['ดี', 'ระวัง', 'กลาง'];
        $reading = "## 🎯 ฟันธง\nผล: ปีนี้ดีกว่าปีก่อน\n";
        $ai = [['anchor' => 'graph', 'say' => 'ปีนี้ขึ้น ๆ ลง ๆ แต่ปลายปีสว่าง']];
        foreach ($months as $i => $m) {
            $reading .= "## 📅 {$m} · ".$tones[$i % 3]."\nธีม: ".str_repeat('เดือนนี้มีเรื่องให้ทำมากมาย ', 4)."\n";
            // AI ส่งโทนกลับข้างมาทุกเดือน + ยาวเกินงบ
            $ai[] = ['anchor' => 'month:'.($i + 1), 'tone' => 'good', 'energy' => 3, 'say' => $m.' '.str_repeat('มีเรื่องให้ทำมากมาย ', 10)];
        }
        $reading .= "## 🧭 คำแนะนำ\nวางแผนการเงินให้ดี";
        $ai[] = ['anchor' => 'summary', 'say' => 'ปีนี้ดีกว่าปีก่อน วางแผนการเงินให้ดี'];
        $this->aiScript = [$this->aiJson($ai)];

        $res = $this->narrate(['spread' => 'year', 'reading_text' => $reading, 'cards' => [], 'birth_known' => false])->assertOk();
        $chapters = $res->json('data.chapters');
        $this->assertCount(15, $chapters);
        $this->assertSame(['good', 'caution', 'neutral'], array_column(array_slice($chapters, 1, 3), 'tone'));
        $this->assertSame('month:12', $chapters[12]['anchor']);
        $this->assertLessThanOrEqual(950, array_sum(array_map(fn ($c) => mb_strlen($c['say']), $chapters)));
    }

    /**
     * 🎙️ (2026-11-07, owner) "ตั้งเพดาน เสียงพากย์ พรีเมี่ยมไว้ ไม่อั้น"
     * budget_chars = 0 → ไม่แบ่งงบรวม แต่ละบทเล่าได้ครบเท่าเนื้อหาหัวข้อ ไม่โดนตัดกลางประโยค
     */
    public function test_an_unlimited_budget_keeps_every_chapter_whole(): void
    {
        $months = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $reading = "## 🎯 ฟันธง\nผล: ปีนี้ดีกว่าปีก่อน\n";
        $ai = [['anchor' => 'graph', 'say' => 'ปีนี้ขึ้น ๆ ลง ๆ แต่ปลายปีสว่าง']];
        foreach ($months as $i => $m) {
            $reading .= "## 📅 {$m} · ดี\nธีม: ".str_repeat('เดือนนี้มีเรื่องให้ทำมากมาย ', 4)."\n";
            // ยาวเกิน cap ที่บอก AI (250) — prod: AI เขียนเกินนิดเดียวแล้ววลีท้ายหลุด
            $ai[] = ['anchor' => 'month:'.($i + 1), 'tone' => 'good', 'energy' => 3, 'say' => $m.' '.str_repeat('มีเรื่องให้ทำมากมาย ', 15).'จบเดือน'];
        }
        $reading .= "## 🧭 คำแนะนำ\nวางแผนการเงินให้ดี";
        $ai[] = ['anchor' => 'summary', 'say' => 'ปีนี้ดีกว่าปีก่อน วางแผนการเงินให้ดี'];
        $this->aiScript = [$this->aiJson($ai)];

        $res = $this->narrate(['spread' => 'year', 'reading_text' => $reading, 'cards' => [], 'birth_known' => false, 'budget_chars' => 0])->assertOk();
        $chapters = $res->json('data.chapters');
        $this->assertCount(15, $chapters);
        foreach (array_slice($chapters, 1, 12) as $c) {
            // ครบทั้งบท จนถึงคำสุดท้าย — งบเดิม 950 ตัวแบ่ง 14 บทจะเหลือบทละ ~60 ตัว
            $this->assertGreaterThan(250, mb_strlen($c['say']), $c['anchor']);
            $this->assertStringEndsWith('จบเดือน', $c['say'], $c['anchor']);
        }
        $this->assertGreaterThan(950, array_sum(array_map(fn ($c) => mb_strlen($c['say']), $chapters)));

        // AI ถูกบอกให้เล่าครบ + มี token พอสำหรับบทยาว
        $this->assertStringContainsString('เล่าให้ครบทุกใจความ', $this->aiCalls[0]['system']);
        $this->assertStringContainsString('month:1: มกราคม เท่านั้น · ไม่เกิน 250 ตัวอักษร', $this->aiCalls[0]['user']);
        $this->assertGreaterThan(3000, $this->aiCalls[0]['config']['max_tokens']);

        // งบเดิมยังเป็นค่าตั้งต้นของคนที่ไม่ส่ง budget_chars (เสียงฟรี)
        Cache::flush();
        $this->aiScript = [$this->aiJson($ai)];
        $capped = $this->narrate(['spread' => 'year', 'reading_text' => $reading, 'cards' => [], 'birth_known' => false])->assertOk();
        $this->assertLessThanOrEqual(950, array_sum(array_map(fn ($c) => mb_strlen($c['say']), $capped->json('data.chapters'))));
        $this->assertStringContainsString('เล่าใจความสำคัญที่สุด', $this->aiCalls[1]['system']);
    }

    public function test_unknown_or_tag_only_chapters_never_reach_the_voice(): void
    {
        $this->assertSame('', VoicePlanParser::cleanSay('{-sighs}', 300));
        $this->assertSame('[sighs] ดวงดี', VoicePlanParser::cleanSay('{sighs} {ถอนใจ} ดวงดี', 300));
    }

    public function test_bad_input_is_rejected(): void
    {
        $this->narrate(['budget_chars' => 50])->assertStatus(422);
        $this->narrate(['budget_chars' => 5000])->assertStatus(422);
        $this->narrate(['persona' => 'stranger'])->assertStatus(422);
        $this->narrate(['spread' => '../etc'])->assertStatus(422);
        $this->narrate(['user_ref' => 'a b'])->assertStatus(422);
        $this->narrate(['reading_text' => 'สั้น'])->assertStatus(422);
        $this->assertSame([], $this->aiCalls);
    }
}
