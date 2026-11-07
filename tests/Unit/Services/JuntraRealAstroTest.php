<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\Api\Juntra\AuspiciousController;
use App\Http\Controllers\Api\Juntra\HoroscopeDailyController;
use App\Http\Controllers\Api\Juntra\NatalController;
use App\Http\Controllers\Api\Juntra\PalmReadingController;
use App\Services\Fortune\JuntraZodiacDaily;
use App\Services\Fortune\PlanetEphemeris;
use App\Services\Fortune\ZodiacAstroBrief;
use App\Services\FortuneAIService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * 🪐 (2026-11-07) เว็บ/แอพจันทราใช้ดาวจริงชุดเดียวกับบอทแม่หมอ
 *
 *   - ดวง 12 ราศี (fortune/horoscope/daily) ทำนายจากตำแหน่งดาวจริง · AI ล่ม = เขียนจากดาวเอง · ไม่สุ่มเลข/สี/ไพ่
 *   - ฤกษ์ (fortune/auspicious) ตรวจซ้ำด้วยปฏิทินโหร ThaiRuekYam ก่อนให้ AI เขียน
 *   - ดูลายมือ (fortune/palm) ผ่าน vision พูลเดียวกับบอท · รูปไม่ใช่ฝ่ามือ = 422 (เว็บคืนเงิน)
 *   - NatalController เลิกสูตรปลอม (อาทิตย์ ± ค่าคงที่ · คะแนน sin(JD))
 *
 * ใช้ sqlite ในหน่วยความจำ — รันบนเครื่อง dev ที่ไม่มี MySQL ได้
 */
class JuntraRealAstroTest extends TestCase
{
    private const GOOD = "[ภาพรวม] คะแนน: 4/5\nดาวอังคารเจ้าเรือนเดินเข้าภพการงาน แม่หมอเห็นงานขยับค่ะ\n\n"
        ."[ความรัก] คะแนน: 3/5\nจันทร์อยู่ภพคู่ครอง คุยกันตรง ๆ ได้นะคะ\n\n"
        ."[การงาน] คะแนน: 4/5\nงานที่ค้างเดินต่อได้ค่ะ\n\n"
        ."[การเงิน] คะแนน: 3/5\nเก็บก่อนใช้นะคะ\n\n"
        ."[สุขภาพ] คะแนน: 3/5\nพักสายตาบ้างนะคะ";

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    /** FortuneAIService ปลอม — ตอบตามคิว (Throwable = โยน) · จดข้อความที่ส่งไป */
    private function fakeAi(array $replies): FortuneAIService
    {
        $ai = new class extends FortuneAIService
        {
            public array $queue = [];

            public array $prompts = [];

            public array $images = [];

            public function __construct() {}

            private function next(): array
            {
                $r = array_shift($this->queue);
                if ($r instanceof \Throwable) {
                    throw $r;
                }

                return $r === null ? [] : ['response' => $r, 'provider' => 'openai', 'model' => 'gpt-test'];
            }

            public function generateWithRetryAndFallback(array $questions, ?array $userProfile = null, ?array $userPosts = null, ?string $promptTemplate = null, string $readingType = 'basic', ?string $birthDate = null, ?string $userContext = null, string $purpose = 'prediction', ?array $modelOverrides = null): array
            {
                $this->prompts[] = implode("\n", $questions);

                return $this->next();
            }

            public function chatWithCustomSystemPrompt(string $systemMessage, string $userMessage, array $config = [], ?string $providerOverride = null, ?string $modelOverride = null, ?string $apiKeyOverride = null, ?string $guardText = null): array
            {
                $this->prompts[] = $systemMessage."\n---\n".$userMessage;

                return $this->next();
            }

            public function chatWithImage(string $imageData, string $systemPrompt, string $userText = '', array $config = []): ?array
            {
                $this->images[] = $imageData;
                $r = $this->next();

                return $r === [] ? null : $r;
            }
        };
        $ai->queue = $replies;
        app()->instance(FortuneAIService::class, $ai);

        return $ai;
    }

    /* ── ZodiacAstroBrief ─────────────────────────────────── */

    public function test_zodiac_brief_places_the_real_sun_in_the_right_house(): void
    {
        // 7 พ.ย. 2569 เที่ยงวัน — อาทิตย์สายนะ ~225° (พิจิก 15°)
        $date = Carbon::parse('2026-11-07', 'Asia/Bangkok');
        $sun = (new PlanetEphemeris)->positions(Carbon::parse('2026-11-07 12:00', 'Asia/Bangkok'))['Sun']['lon_tropical'];
        $this->assertEqualsWithDelta(225.0, $sun, 1.5);

        $scorpio = (new ZodiacAstroBrief)->build('scorpio', $date);
        $aries = (new ZodiacAstroBrief)->build('aries', $date);

        $this->assertTrue($scorpio['ok']);
        $this->assertSame(1, $scorpio['planets']['Sun']['house'], 'ราศีที่อาทิตย์อยู่ = ภพที่ 1 ของราศีนั้น');
        $this->assertSame(8, $aries['planets']['Sun']['house']);
        $this->assertSame('อังคาร', $aries['ruler']['th']);
        $this->assertStringContainsString('ดาวเจ้าเรือนราศี: อังคาร', $aries['text']);
        foreach ($aries['scores'] as $s) {
            $this->assertGreaterThanOrEqual(1, $s);
            $this->assertLessThanOrEqual(5, $s);
        }
        $this->assertSame($aries['scores'], (new ZodiacAstroBrief)->build('aries', $date)['scores'], 'วันเดิมต้องได้คะแนนเดิม (ไม่สุ่ม)');
        $this->assertNotSame($aries['text'], $scorpio['text'], '12 ราศีต้องได้ข้อเท็จจริงคนละชุด');
    }

    public function test_zodiac_brief_rejects_an_unknown_sign(): void
    {
        $this->assertFalse((new ZodiacAstroBrief)->build('ophiuchus', Carbon::today())['ok']);
    }

    /* ── ดวงรายวัน 12 ราศี ─────────────────────────────────── */

    public function test_daily_uses_real_sky_facts_and_caches_one_call_per_sign_per_day(): void
    {
        $ai = $this->fakeAi([self::GOOD]);
        $daily = app(JuntraZodiacDaily::class);
        $date = Carbon::parse('2026-11-07', 'Asia/Bangkok');

        $out = $daily->get('aries', $date);
        $again = $daily->get('aries', $date);

        $this->assertCount(1, $ai->prompts, 'วันละครั้งต่อราศี');
        $this->assertStringContainsString('ดาวเจ้าเรือนราศี: อังคาร', $ai->prompts[0], 'prompt ต้องมีตำแหน่งดาวจริง');
        $this->assertStringContainsString('ห้ามอ้างไพ่ทาโรต์', $ai->prompts[0]);
        $this->assertTrue($out['ai_generated']);
        $this->assertStringContainsString('ดาวอังคาร', $out['summary']);
        $this->assertStringNotContainsString('[ความรัก]', $out['summary']);
        $this->assertSame('งานที่ค้างเดินต่อได้ค่ะ', $out['career']);
        $this->assertNull($out['lucky_color'], 'สีมงคลงดถาวร');
        $this->assertNull($out['lucky_card']);
        $this->assertNull($out['lucky_number'], 'เลขนำโชคเฉพาะวันที่ 15/29');
        $this->assertSame($out, $again);
    }

    public function test_daily_gives_lucky_numbers_only_on_the_15th_and_29th(): void
    {
        $this->fakeAi([self::GOOD, self::GOOD]);
        $daily = app(JuntraZodiacDaily::class);

        $n15 = $daily->get('leo', Carbon::parse('2026-11-15', 'Asia/Bangkok'))['lucky_number'];
        $this->assertMatchesRegularExpression('/^\d{2}, \d{2}$/', (string) $n15);
        $this->assertNull($daily->get('leo', Carbon::parse('2026-11-16', 'Asia/Bangkok'))['lucky_number']);
    }

    public function test_daily_falls_back_to_real_sky_text_when_ai_fails_or_refuses(): void
    {
        $this->fakeAi([new RuntimeException('pool down'), 'ขออภัยค่ะ ฉันเป็น AI ไม่สามารถทำนายดวงได้']);
        $daily = app(JuntraZodiacDaily::class);

        $down = $daily->get('taurus', Carbon::parse('2026-11-07', 'Asia/Bangkok'));
        $this->assertFalse($down['ai_generated']);
        $this->assertStringContainsString('ศุกร์', $down['summary'], 'ไม่มี AI ก็ต้องอ้างดาวเจ้าเรือนจริง (พฤษภ = ศุกร์)');
        foreach (['love', 'career', 'money', 'health'] as $k) {
            $this->assertNotSame('', $down[$k], $k);
        }

        $refused = $daily->get('gemini', Carbon::parse('2026-11-07', 'Asia/Bangkok'));
        $this->assertFalse($refused['ai_generated'], 'คำปัดของโมเดลห้ามขึ้นหน้าเว็บ');
        $this->assertStringNotContainsString('AI', $refused['summary']);
    }

    public function test_daily_rejects_a_half_parsed_ai_answer(): void
    {
        $this->fakeAi(["[ภาพรวม] คะแนน: 4/5\nดีค่ะ"]);
        $out = app(JuntraZodiacDaily::class)->get('virgo', Carbon::parse('2026-11-07', 'Asia/Bangkok'));

        $this->assertFalse($out['ai_generated'], 'ตอบไม่ครบ 5 ด้าน = ใช้ข้อความจากดาวจริงแทน');
    }

    public function test_daily_endpoint_validates_sign_and_date_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 09:00', 'Asia/Bangkok'));
        $this->fakeAi([self::GOOD]);
        $ctl = app(HoroscopeDailyController::class);

        $res = $ctl(Request::create('/x', 'POST', ['zodiac' => 'aries', 'date' => '2026-11-07']), app(JuntraZodiacDaily::class));
        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('aries', $res->getData(true)['data']['zodiac']);

        $far = $ctl(Request::create('/x', 'POST', ['zodiac' => 'aries', 'date' => '2026-12-25']), app(JuntraZodiacDaily::class));
        $this->assertSame(422, $far->getStatusCode(), 'วันไกล ๆ = เปลือง AI');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $ctl(Request::create('/x', 'POST', ['zodiac' => 'dragon']), app(JuntraZodiacDaily::class));
    }

    /* ── ฤกษ์ ─────────────────────────────────────────────── */

    public function test_auspicious_cross_checks_every_day_with_the_thai_calendar(): void
    {
        $ai = $this->fakeAi(['แม่หมอแนะนำวันแรกค่ะ เพราะฤกษ์ดีนะคะ']);

        $res = app(AuspiciousController::class)(Request::create('/x', 'POST', [
            'prompt' => 'ลูกค้าขอฤกษ์สำหรับ: ขึ้นบ้านใหม่',
            'occasion' => 'ขึ้นบ้านใหม่',
            'category' => 'housewarming',
            'days' => ['2026-11-20', '2026-11-21', '2026-11-22'],
        ]), $ai);

        $this->assertSame(200, $res->getStatusCode());
        $data = $res->getData(true)['data'];
        $this->assertSame('house', $data['activity']);
        $this->assertCount(3, $data['checks']);
        foreach ($data['checks'] as $c) {
            $this->assertArrayHasKey('ok', $c);
            if ($c['ok'] === false) {
                $this->assertNotEmpty($c['reasons'], 'วันที่ตำราห้ามต้องบอกเหตุผล');
            }
        }
        $this->assertStringContainsString('ตรวจซ้ำด้วยปฏิทินโหรไทย', $ai->prompts[0]);
        $this->assertStringContainsString('ห้ามเลือกเป็นอันดับ 1', $ai->prompts[0]);
    }

    public function test_auspicious_drops_the_owners_kalakini_weekday(): void
    {
        // เกิดวันอาทิตย์ — กาลกิณีตามทักษาคือศุกร์ · 2026-11-20 เป็นวันศุกร์
        $ai = $this->fakeAi(['แม่หมอเตือนวันศุกร์ค่ะ']);
        $res = app(AuspiciousController::class)(Request::create('/x', 'POST', [
            'prompt' => 'ฤกษ์แต่งงาน', 'occasion' => 'แต่งงาน', 'category' => 'wedding',
            'days' => ['2026-11-20'], 'birth_date' => '1990-01-07',
        ]), $ai);

        $check = $res->getData(true)['data']['checks'][0];
        $this->assertTrue($check['kala']);
        $this->assertFalse($check['ok']);
        $this->assertStringContainsString('กาลกิณีคือศุกร์', $ai->prompts[0]);
    }

    public function test_auspicious_returns_503_when_ai_is_down_or_refuses(): void
    {
        $ai = $this->fakeAi([new RuntimeException('down'), 'ขออภัย ฉันไม่สามารถทำตามคำสั่งนี้ได้']);
        $req = fn () => Request::create('/x', 'POST', ['prompt' => 'ฤกษ์ออกรถ', 'category' => 'vehicle', 'days' => ['2026-11-20']]);

        $this->assertSame(503, app(AuspiciousController::class)($req(), $ai)->getStatusCode());
        $refused = app(AuspiciousController::class)($req(), $ai);
        $this->assertSame(503, $refused->getStatusCode());
        $this->assertSame('model_refused', $refused->getData(true)['reason_code']);
    }

    /* ── ดูลายมือ ─────────────────────────────────────────── */

    private function palm(FortuneAIService $ai, string $image = 'data:image/jpeg;base64,/9j/4AAQSkZJRg=='): \Illuminate\Http\JsonResponse
    {
        return app(PalmReadingController::class)(Request::create('/x', 'POST', ['image' => $image, 'question' => 'เรื่องงาน']), $ai);
    }

    public function test_palm_reading_goes_through_the_bot_vision_lane(): void
    {
        $ai = $this->fakeAi(['เห็นมือขวาค่ะ เส้นชีวิตยาวลึก แม่หมอเห็นว่าแข็งแรงนะคะ']);
        $res = $this->palm($ai);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('เส้นชีวิต', $res->getData(true)['data']['reading']);
        $this->assertCount(1, $ai->images);
    }

    public function test_palm_reading_maps_failures_to_refundable_codes(): void
    {
        $ai = $this->fakeAi(['[NOT_PALM]', null, 'ในฐานะ AI ฉันไม่สามารถอ่านลายมือได้']);

        $notPalm = $this->palm($ai);
        $this->assertSame(422, $notPalm->getStatusCode());
        $this->assertSame('not_palm', $notPalm->getData(true)['reason_code']);

        $this->assertSame('vision_unavailable', $this->palm($ai)->getData(true)['reason_code'], 'ปิด vision / ไม่มีคีย์');
        $this->assertSame('model_refused', $this->palm($ai)->getData(true)['reason_code']);

        $bad = $this->palm($ai, 'data:text/html;base64,PGgxPg==');
        $this->assertSame(422, $bad->getStatusCode());
        $this->assertSame('bad_image', $bad->getData(true)['reason_code']);
        $this->assertCount(3, $ai->images, 'รูปผิดชนิดต้องไม่ถึง AI');
    }

    /* ── ดวงกำเนิด / ดาวจรของแอพ ───────────────────────────── */

    public function test_natal_uses_the_real_ephemeris_and_lagna(): void
    {
        $ctl = new NatalController;
        $base = ['year' => 1990, 'month' => 1, 'day' => 1, 'hour' => 12, 'minute' => 0, 'lat' => 13.75, 'lng' => 100.5, 'tz_offset_hours' => 7];

        $d = $ctl->compute(Request::create('/x', 'POST', $base))->getData(true)['data'];
        $planets = collect($d['planets'])->keyBy('key');

        // 1 ม.ค. 2533 อาทิตย์สายนะ ~280.6° → นิรายนะ ~256.9° = ราศีธนู
        $this->assertSame('ธนู', $d['sun']['th']);
        $this->assertSame('sidereal_lahiri', $d['zodiac_frame']);
        $this->assertCount(10, $d['planets']);
        $this->assertNotEqualsWithDelta(25.0, fmod($planets['mercury']['lon'] - $planets['sun']['lon'] + 360, 360), 0.01, 'เลิกสูตรพุธ = อาทิตย์ + 25°');
        $this->assertSame('จันทร์', $d['thai']['ruler'], '1 ม.ค. 2533 เป็นวันจันทร์ — ดาวเจ้าชะตาตามตำราเจ้าชนะ');
    }

    public function test_natal_time_zone_and_place_change_the_result(): void
    {
        $ctl = new NatalController;
        $bkk = ['year' => 1995, 'month' => 6, 'day' => 15, 'hour' => 8, 'minute' => 30, 'lat' => 13.75, 'lng' => 100.5, 'tz_offset_hours' => 7];
        // เวลาเดียวกันตามนาฬิกาโลก (01:30 UTC) แต่เกิดที่ลอนดอน
        $ldn = ['year' => 1995, 'month' => 6, 'day' => 15, 'hour' => 2, 'minute' => 30, 'lat' => 51.5, 'lng' => -0.12, 'tz_offset_hours' => 1];

        $a = $ctl->compute(Request::create('/x', 'POST', $bkk))->getData(true)['data'];
        $b = $ctl->compute(Request::create('/x', 'POST', $ldn))->getData(true)['data'];

        $this->assertEqualsWithDelta($a['moon']['lon'], $b['moon']['lon'], 0.05, 'ขณะเดียวกัน = ดาวตำแหน่งเดียวกัน');
        $this->assertNotEqualsWithDelta($a['ascendant_lon'], $b['ascendant_lon'], 5.0, 'คนละที่ = ลัคนาต่างกัน');

        $bad = $ctl->compute(Request::create('/x', 'POST', ['day' => 31, 'month' => 2] + $bkk));
        $this->assertSame(422, $bad->getStatusCode());
    }

    public function test_daily_transit_score_is_not_a_sine_wave_and_personal_mode_works(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 09:00', 'Asia/Bangkok'));
        $ctl = new NatalController;

        $sky = $ctl->dailyTransit(Request::create('/x', 'GET'))->getData(true)['data'];
        $this->assertFalse($sky['personal']);
        $this->assertSame('2026-11-07', $sky['date']);
        $this->assertStringContainsString('ราศี', $sky['reading']);
        $this->assertIsInt($sky['score']);
        $this->assertSame($sky['score'], $ctl->dailyTransit(Request::create('/x', 'GET'))->getData(true)['data']['score'], 'วันเดิมต้องได้คะแนนเดิม');

        $me = $ctl->dailyTransit(Request::create('/x', 'GET', ['birth_date' => '1990-01-07']))->getData(true)['data'];
        $this->assertTrue($me['personal']);
        $this->assertStringContainsString('คนเกิดวันอาทิตย์', $me['reading']);
        $this->assertGreaterThanOrEqual(0, $me['score']);
        $this->assertLessThanOrEqual(100, $me['score']);
    }

    /** juntraweb ยิงได้ทั้งทาง token ลูกค้าและทางของเว็บ (server) — ต้องมีครบทั้งสองเลน */
    public function test_new_routes_exist_on_both_lanes(): void
    {
        $routes = app('router')->getRoutes();
        foreach (['horoscope.daily' => 'horoscope/daily', 'auspicious' => 'auspicious', 'palm' => 'palm'] as $name => $path) {
            $customer = $routes->getByName('api.juntra.fortune.'.$name);
            $server = $routes->getByName('api.juntra.server.fortune.'.$name);
            $this->assertNotNull($customer, $name);
            $this->assertNotNull($server, $name);
            $this->assertSame('api/v1/juntra/fortune/'.$path, $customer->uri());
            $this->assertSame('api/v1/juntra/server/fortune/'.$path, $server->uri());
        }
    }
}
