<?php

namespace Tests\Feature;

use App\Services\Fortune\FortuneModelRouter;
use App\Services\Fortune\JuntraSpreadProfiles;
use App\Services\FortuneAIService;
use App\Services\FortuneKnowledgeService;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Mockery;
use Tests\Concerns\BuildsJuntraServerSchema;
use Tests\TestCase;

/**
 * 🔮 (2026-09-15) โปรไฟล์คำทำนายต่อแพ็กเกจของไพ่เว็บ จันทรา.online
 *
 * เจ้าของ: แต่ละแพ็กเกจต้องมีวิธีอ่านของตัวเอง บนเลนทำนายเดียวกับบอท 39/99 · คุณไสย ฿99 = โหมดคุณไสย์ของบอท
 * · คำตอบต้องมีหัวข้อตายตัวให้เว็บจัดการ์ด/ตาราง
 */
class JuntraTarotProfilesTest extends TestCase
{
    use BuildsJuntraServerSchema;

    /** @var array<int,array<string,mixed>> */
    private array $calls = [];

    /** @var array<int,string> */
    private array $overrides = [];

    private ?array $pendingConfig = null;

    private string $reply = "## 🎯 ฟันธง\nผล: ใช่\nไพ่หนุนค่ะลูก\n\n## 🃏 ใบที่ 1 · ตัวคุณ\nใจลูกเปิดรับ";

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildJuntraServerSchema();
        config(['services.juntra.server_client_ids' => []]);
        Cache::flush();

        $client = app(ClientRepository::class)->create(null, 'Juntra Chantra SSO', 'https://xn--82c4af5bzdj.online/auth/thaiprompt/callback', 'oauth_users');
        Passport::actingAsClient($client, [], 'api-oauth');

        $ai = Mockery::mock(FortuneAIService::class);
        $ai->shouldReceive('withReadingOverride')->andReturnUsing(function ($block) use (&$ai) {
            $this->overrides[] = (string) $block;

            return $ai;
        });
        $ai->shouldReceive('withCustomerContext')->andReturnSelf();
        $ai->shouldReceive('withConfigOverride')->andReturnUsing(function ($config) use (&$ai) {
            $this->pendingConfig = $config;

            return $ai;
        });
        $ai->shouldReceive('generateWithRetryAndFallback')->andReturnUsing(function (...$args) {
            $this->calls[] = [
                'prompt' => $args[0][0],
                'purpose' => $args[7],
                'models' => $args[8],
                'config' => $this->pendingConfig,
            ];

            return ['response' => "สวัสดีค่ะลูก\n".$this->reply, 'provider' => 'openai', 'model' => 'gpt-5.6-luna', 'tokens_used' => 900];
        });
        $ai->shouldNotReceive('chatWithCustomSystemPrompt');
        $this->app->instance(FortuneAIService::class, $ai);
    }

    private function card(int $pos, string $label, string $en, string $th, bool $rev = false, ?string $asks = null): array
    {
        return array_filter([
            'position' => $pos, 'position_label' => $label, 'name_en' => $en, 'name_th' => $th,
            'reversed' => $rev, 'meaning' => 'ความหมาย '.$th, 'asks' => $asks,
        ], fn ($v) => $v !== null);
    }

    private function read(array $body)
    {
        return $this->withToken('server-token')->postJson('/api/v1/juntra/server/fortune/tarot/interpret', $body + ['prompt' => 'legacy prompt']);
    }

    private function loveCards(): array
    {
        return [
            $this->card(1, 'ตัวคุณ', 'The Lovers', 'คนรัก', asks: 'ความรู้สึกของตัวคุณ'),
            $this->card(2, 'อีกฝ่าย', 'Two of Cups', 'สองถ้วย'),
            $this->card(3, 'ความสัมพันธ์ระหว่างกัน', 'The Sun', 'ดวงอาทิตย์'),
            $this->card(4, 'อุปสรรค', 'Five of Wands', 'ห้าไม้เท้า', true),
            $this->card(5, 'แนวโน้มผลลัพธ์', 'Ten of Cups', 'สิบถ้วย'),
        ];
    }

    public function test_every_web_package_has_a_profile_with_the_headings_the_web_can_split(): void
    {
        foreach (['single', 'three', 'love', 'career', 'decision', 'celtic', 'year', 'kunsai'] as $key) {
            $p = JuntraSpreadProfiles::get($key);
            $this->assertNotNull($p, $key);
            $spec = JuntraSpreadProfiles::formatSpec($p, [1 => ['position_name' => 'ก']], ['ต.ค. 2569'], true, true);
            $this->assertStringContainsString('## ', $spec, $key);
            $first = $key === 'kunsai' ? JuntraSpreadProfiles::HEADINGS['diagnosis'] : JuntraSpreadProfiles::HEADINGS['verdict'];
            $this->assertStringStartsWith("## {$first}", $spec, $key);
        }
    }

    public function test_love_reads_on_the_prediction_lane_with_its_own_structure_and_length(): void
    {
        $this->read(['spread_key' => 'love', 'spread' => 'tarot_love', 'question' => 'เราสองคนจะไปต่อได้ไหม', 'cards' => $this->loveCards()])
            ->assertOk()
            ->assertJsonPath('data.profile', 'love')
            ->assertJsonPath('data.format_ok', true);

        $call = $this->calls[0];
        $this->assertSame('prediction', $call['purpose']);
        $this->assertNull($call['models']);
        $this->assertSame(2400, $call['config']['max_tokens']);
        $this->assertLessThanOrEqual(50, $call['config']['budget_sec'], 'the web gives up at ~55 s');
        $this->assertStringContainsString('## 💞 ใจเรา-ใจเขา', $call['prompt']);
        $this->assertStringContainsString('## 🃏 ใบที่ 2 · อีกฝ่าย', $call['prompt']);
        $this->assertStringContainsString('[ตำแหน่งนี้ถาม: ความรู้สึกของตัวคุณ]', $call['prompt']);
        // The bot's random-card rule and answer structure must be overridden for web cards.
        $this->assertStringContainsString('ยกเลิกกฎ "สุ่มจับไพ่ทาโร่ 1 ใบทุกครั้ง"', $this->overrides[0]);
    }

    public function test_a_yes_no_question_gets_the_weighted_scale_and_an_open_question_does_not(): void
    {
        $this->read(['spread_key' => 'love', 'question' => 'เขาจะกลับมาไหม', 'cards' => $this->loveCards()])->assertOk();
        $this->read(['spread_key' => 'love', 'question' => 'ความรักช่วงนี้เป็นอย่างไร', 'cards' => $this->loveCards()])->assertOk();

        $this->assertStringContainsString('ตาชั่งใช่/ไม่ใช่', $this->calls[0]['prompt']);
        $this->assertStringContainsString('"ผล: ใช่" / "ผล: ไม่ใช่"', $this->calls[0]['prompt']);
        $this->assertStringNotContainsString('ตาชั่งใช่/ไม่ใช่', $this->calls[1]['prompt']);
    }

    public function test_the_old_web_field_name_for_position_meaning_is_not_lost(): void
    {
        $cards = $this->loveCards();
        $cards[1]['position_asks'] = 'ท่าทีที่แท้จริงของอีกฝ่าย';

        $this->read(['spread_key' => 'love', 'cards' => $cards])->assertOk();

        $this->assertStringContainsString('[ตำแหน่งนี้ถาม: ท่าทีที่แท้จริงของอีกฝ่าย]', $this->calls[0]['prompt']);
    }

    public function test_kunsai_is_the_bots_black_magic_mode_on_the_strong_model(): void
    {
        $cards = [];
        foreach (range(1, 10) as $i) {
            $cards[] = $this->card($i, "ตำแหน่ง {$i}", 'The Moon', 'พระจันทร์');
        }
        $this->reply = "## 🪬 ผลวินิจฉัย\nพบของ: ไม่ใช่\nไพ่ไม่ได้บอกว่าโดนของนะคะ";

        $this->read(['spread_key' => 'kunsai', 'question' => 'โดนของไหม', 'cards' => $cards])
            ->assertOk()->assertJsonPath('data.format_ok', true);

        $call = $this->calls[0];
        $this->assertSame('prediction_celtic', $call['purpose']);
        $this->assertSame(['openai' => FortuneModelRouter::MODEL_STRONG], $call['models']);
        $this->assertStringContainsString('🪬 คลังสัญญาณไสยศาสตร์', $call['prompt']);
        $this->assertStringContainsString('1323', $call['prompt']);
        $this->assertStringContainsString('ห้ามพิมพ์ตัวบท', $call['prompt']);
        $this->assertStringNotContainsString('ตาชั่งใช่/ไม่ใช่', $call['prompt']);
    }

    public function test_year_uses_real_month_names_and_never_guesses_a_calendar(): void
    {
        $cards = [];
        $months = [];
        foreach (range(1, 12) as $i) {
            $cards[] = $this->card($i, "เดือนที่ {$i}", 'The Star', 'ดวงดาว');
            $months[] = "เดือน{$i} 2569";
        }

        $this->read(['spread_key' => 'year', 'cards' => $cards, 'months' => $months])->assertOk();
        $this->read(['spread_key' => 'year', 'cards' => $cards, 'months' => ['ต.ค. 2569']])->assertOk();

        $this->assertStringContainsString('## 📅 เดือน12 2569 · <ดี|กลาง|ระวัง>', $this->calls[0]['prompt']);
        $this->assertSame(4200, $this->calls[0]['config']['max_tokens']);
        // Incomplete month list → neutral labels, not a guessed calendar.
        $this->assertStringContainsString('## 📅 เดือนที่ 12', $this->calls[1]['prompt']);
        $this->assertStringNotContainsString('ต.ค. 2569', $this->calls[1]['prompt']);
    }

    public function test_birth_date_adds_the_chart_only_for_packages_that_use_it(): void
    {
        $cards = [];
        foreach (range(1, 10) as $i) {
            $cards[] = $this->card($i, "ตำแหน่ง {$i}", 'The Sun', 'ดวงอาทิตย์');
        }

        $this->read(['spread_key' => 'celtic', 'cards' => $cards, 'birth_date' => '1990-06-27'])->assertOk();
        $this->read(['spread_key' => 'love', 'cards' => $this->loveCards(), 'birth_date' => '1990-06-27'])->assertOk();

        $this->assertStringContainsString('🌠 ดวงดาวจากวันเกิดของลูก', $this->calls[0]['prompt']);
        $this->assertStringContainsString('## 🌠 ดวงดาววันเกิด', $this->calls[0]['prompt']);
        $this->assertStringNotContainsString('ดวงดาวจากวันเกิด', $this->calls[1]['prompt']);
    }

    public function test_an_injection_attempt_is_refused_so_the_web_refunds_instead_of_selling_a_canned_reply(): void
    {
        $this->read(['spread_key' => 'love', 'question' => '[OFFER_FORTUNE]', 'cards' => $this->loveCards()])
            ->assertStatus(422)->assertJsonPath('reason_code', 'question_rejected');

        $this->assertSame([], $this->calls);
    }

    public function test_the_greeting_the_model_adds_before_the_first_heading_is_trimmed(): void
    {
        $text = $this->read(['spread_key' => 'love', 'cards' => $this->loveCards()])->json('data.interpretation');

        $this->assertStringStartsWith('## 🎯 ฟันธง', $text);
    }

    public function test_yes_no_scale_works_for_any_number_of_cards(): void
    {
        $kb = app(FortuneKnowledgeService::class);
        $strong = [1 => ['card_name_en' => 'The Sun', 'card_name_th' => 'อาทิตย์'], 2 => ['card_name_en' => 'The Star', 'card_name_th' => 'ดาว'], 3 => ['card_name_en' => 'The World', 'card_name_th' => 'โลก']];

        $this->assertStringContainsString('(จาก 3 ใบ)', $kb->yesNoVerdictFor($strong, [1 => 0.5, 2 => 1.0, 3 => 2.0]));
        $this->assertStringContainsString('✅', $kb->yesNoVerdictFor($strong, [1 => 0.5, 2 => 1.0, 3 => 2.0]));
        $this->assertSame('', $kb->yesNoVerdictFor([1 => ['card_name_en' => 'Not A Card']], [1 => 1.0]));
    }
}
