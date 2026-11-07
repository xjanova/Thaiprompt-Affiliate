<?php

namespace App\Services\Fortune;

use App\Services\FortuneAIService;
use App\Services\HoroscopeDailyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🌠 (2026-11-07) ดวงรายวัน 12 ราศีให้เว็บจันทรา — ทำนายจากตำแหน่งดาวจริง (ZodiacAstroBrief)
 *
 * เดิม juntraweb เรียก /fortune/horoscope/daily แต่ฝั่งนี้ไม่มีเส้นนี้เลย (404) ⇒ ทุกวันทุกราศี
 * เขียนเองจากข้างขึ้นข้างแรม + วันในสัปดาห์ ไม่มีตำแหน่งดาวสักดวง และสุ่มเลข/สี/ไพ่นำโชคจาก crc32
 *
 * กติกาเดียวกับดวงรายวันของเพจ (เจ้าของสั่ง 2026-08-02):
 *   - ทำนายจากข้อเท็จจริงของดาวเท่านั้น ห้ามอ้างไพ่/เลขศาสตร์/ของมงคล
 *   - สีมงคลงดถาวร · เลขนำโชคเฉพาะวันที่ 15 และ 29 (HoroscopeDailyService::LUCKY_NUMBER_DAYS)
 *
 * แคช: วันละครั้งต่อราศี (12 ครั้ง/วัน) · AI ล่ม = เขียนจากดาวจริงเองแล้วแคชสั้น ให้รอบหน้าลอง AI ใหม่
 */
class JuntraZodiacDaily
{
    private const TTL_AI_HOURS = 36;

    private const TTL_SKY_MINUTES = 30;

    public function __construct(private FortuneAIService $ai) {}

    /**
     * @return array{summary:string, love:string, career:string, money:string, health:string,
     *   lucky_number:?string, lucky_color:null, lucky_card:null, scores:array, astro:array,
     *   ai_generated:bool, ai_provider:?string, ai_model:?string}|null  null = ราศีที่ไม่รู้จัก
     */
    public function get(string $slug, Carbon $date): ?array
    {
        if (! isset(ZodiacAstroBrief::SLUGS[$slug])) {
            return null;
        }
        $key = 'juntra:zodiac-daily:v2:'.$slug.':'.$date->format('Y-m-d');
        if (is_array($hit = Cache::get($key))) {
            return $hit;
        }

        // คนเปิดหน้าพร้อมกันหลายคนตอนเช้า — ยิง AI รอบเดียวต่อราศี คนที่รออ่านของที่เพิ่งเขียนเสร็จ
        $lock = Cache::lock($key.':lock', 90);
        try {
            $lock->block(50);
        } catch (\Throwable) {
            // รอนานเกิน — ทำต่อเลย (ซ้ำได้แต่ไม่ค้าง)
        }

        try {
            if (is_array($hit = Cache::get($key))) {
                return $hit;
            }
            $brief = (new ZodiacAstroBrief)->build($slug, $date);
            $out = $this->fromAi($brief, $date) ?? $this->fromSky($brief, $date);
            Cache::put($key, $out, $out['ai_generated'] ? now()->addHours(self::TTL_AI_HOURS) : now()->addMinutes(self::TTL_SKY_MINUTES));

            return $out;
        } finally {
            rescue(fn () => $lock->release(), report: false);
        }
    }

    /** AI เขียนจากข้อเท็จจริงของดาว — null = ไม่มีคำทำนายที่ใช้ได้ (ผู้เรียกถอยไปเขียนจากดาวเอง) */
    private function fromAi(array $brief, Carbon $date): ?array
    {
        if (! $brief['ok']) {
            return null;
        }

        try {
            $result = $this->ai
                ->withSystemAuthoredTask()
                // คนแรกที่เปิดหน้ารออยู่ — ไม่ใช้เพดาน 150 วิของบอท
                ->withConfigOverride(['budget_sec' => 40, 'max_tokens' => 1600])
                ->generateWithRetryAndFallback(questions: [$this->prompt($brief)], promptTemplate: null, readingType: 'basic');
        } catch (\Throwable $e) {
            Log::warning('JuntraZodiacDaily: AI ไม่พร้อม — เขียนจากดาวจริงแทน', ['slug' => $brief['slug'], 'err' => mb_substr($e->getMessage(), 0, 200)]);

            return null;
        }

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '' || PersonaBreakDetector::reason($text, strict: true) !== null) {
            return null;
        }

        $parsed = app(HoroscopeDailyService::class)->parseAIPrediction($text);
        // ต้องได้ครบทุกด้าน — ถ้า parse ไม่ออก overall จะเป็นข้อความทั้งก้อน (รวมหัวข้อ) ซึ่งเอาไปลงหน้าเว็บไม่ได้
        foreach (['love', 'career', 'finance', 'health'] as $k) {
            if (trim((string) ($parsed[$k] ?? '')) === '') {
                Log::info('JuntraZodiacDaily: AI ตอบไม่ครบรูปแบบ — เขียนจากดาวจริงแทน', ['slug' => $brief['slug'], 'missing' => $k]);

                return null;
            }
        }

        return $this->shape($brief, $date, [
            'summary' => (string) $parsed['overall'],
            'love' => (string) $parsed['love'],
            'career' => (string) $parsed['career'],
            'money' => (string) $parsed['finance'],
            'health' => (string) $parsed['health'],
        ], true, $result['provider'] ?? null, $result['model'] ?? null);
    }

    /**
     * ไม่มี AI — ประกอบประโยคจากตำแหน่งดาวจริงตรง ๆ (ทุกประโยคอ้างดาว/ภพที่คำนวณได้)
     * ไม่ดีเท่าคำทำนายของแม่หมอ แต่ไม่มีข้อความไหนแต่งขึ้นเอง
     */
    private function fromSky(array $brief, Carbon $date): array
    {
        if (! $brief['ok']) {
            $msg = 'วันนี้ระบบคำนวณตำแหน่งดาวไม่สำเร็จ แม่หมอขอยังไม่ทำนายดวงราศี'.$brief['sign'].' นะคะ แวะกลับมาอีกครั้งในอีกสักครู่';

            return $this->shape($brief, $date, ['summary' => $msg, 'love' => '', 'career' => '', 'money' => '', 'health' => ''], false, null, null);
        }

        $line = function (string $area) use ($brief): string {
            $parts = [];
            foreach (ZodiacAstroBrief::AREA_HOUSES[$area] as $h) {
                foreach ($brief['houses'][$h] as $key) {
                    $p = $brief['planets'][$key];
                    $parts[] = $p['th'].($p['retro'] ? ' (พักร) ' : '').'เข้าภพ'.ZodiacAstroBrief::HOUSE_TH[$h];
                }
            }

            return $parts !== [] ? implode(' · ', $parts) : '';
        };
        $tone = fn (int $s): string => match (true) {
            $s >= 4 => 'เป็นวันที่เดินหน้าได้',
            $s <= 2 => 'เป็นวันที่ควรตั้งรับและระวังให้มาก',
            default => 'ทรงตัว ทำตามแผนเดิมได้',
        };
        $s = $brief['scores'];
        $r = $brief['ruler'];
        $m = $brief['moon'];

        $summary = $r !== null
            ? "ดาวเจ้าเรือนราศี{$brief['sign']}คือ{$r['th']} วันนี้สถิตราศี{$r['sign']} ภพ".ZodiacAstroBrief::HOUSE_TH[$r['house']]
                .($r['retro'] ? ' และกำลังพักร เรื่องที่เคยค้างจะวนกลับมาให้สะสาง' : ' เรื่องในภพนี้จึงเป็นแก่นของวัน')
            : "ดวงราศี{$brief['sign']}วันนี้";
        if ($m !== null) {
            $summary .= ' · จันทร์อยู่ภพ'.ZodiacAstroBrief::HOUSE_TH[$m['house']].' ใจจะจดจ่อกับเรื่องนี้เป็นพิเศษ';
        }
        if ($brief['aspects'] !== []) {
            $a = $brief['aspects'][0];
            $summary .= " · {$r['th']}{$a['name']}{$a['with']} ({$a['nature']})";
        }
        $summary .= ' — ภาพรวม'.$tone($s['overall']);

        $area = fn (string $k, string $label, int $score): string => ($line($k) !== '' ? $line($k).' — ' : 'ไม่มีดาวเข้าภพของเรื่อง'.$label.'วันนี้ — ').$label.$tone($score);

        return $this->shape($brief, $date, [
            'summary' => $summary,
            'love' => $area('love', 'ความรัก', $s['love']),
            'career' => $area('career', 'การงาน', $s['career']),
            'money' => $area('finance', 'การเงิน', $s['finance']),
            'health' => $area('health', 'สุขภาพ', $s['health']),
        ], false, null, null);
    }

    private function shape(array $brief, Carbon $date, array $text, bool $ai, ?string $provider, ?string $model): array
    {
        return $text + [
            // 🔢 เลขนำโชคเฉพาะวันที่ 15/29 · สีมงคลและไพ่ประจำวันงดถาวร (ห้ามแต่งของที่ไม่ได้คำนวณ)
            'lucky_number' => $this->luckyNumber($brief, $date),
            'lucky_color' => null,
            'lucky_card' => null,
            'scores' => $brief['scores'],
            'astro' => [
                'frame' => 'sidereal_lahiri',
                'ruler' => $brief['ruler'] !== null ? array_intersect_key($brief['ruler'], array_flip(['th', 'sign', 'deg', 'house', 'retro'])) : null,
                'moon' => $brief['moon'] !== null ? array_intersect_key($brief['moon'], array_flip(['th', 'sign', 'deg', 'house'])) : null,
                'retrogrades' => $brief['retrogrades'],
                'aspects' => array_map(fn ($a) => array_intersect_key($a, array_flip(['with', 'name', 'nature', 'tone', 'orb'])), $brief['aspects']),
            ],
            'ai_generated' => $ai,
            'ai_provider' => $provider,
            'ai_model' => $model,
        ];
    }

    /** เลขดาวเจ้าเรือนราศี + ภพของจันทร์ · ลำดับราศี + เลขดาวของจันทร์ — ได้เลขเดิมทุกครั้งที่คำนวณวันเดิม */
    private function luckyNumber(array $brief, Carbon $date): ?string
    {
        if (! in_array((int) $date->day, HoroscopeDailyService::LUCKY_NUMBER_DAYS, true) || $brief['ruler'] === null || $brief['moon'] === null) {
            return null;
        }
        $a = ($brief['ruler']['num'] * 10 + $brief['moon']['house']) % 100;
        $b = ((ZodiacAstroBrief::SLUGS[$brief['slug']] + 1) * 10 + $brief['moon']['num']) % 100;

        return sprintf('%02d, %02d', $a, $b);
    }

    private function prompt(array $brief): string
    {
        return <<<PROMPT
คุณเป็นโหรที่อ่านดวงจากตำแหน่งดาวจริง ไม่ใช่คนเขียนคำคมให้กำลังใจ

ทำนายดวง **ราศี{$brief['sign']}** ประจำวันที่ **{$brief['thai_date']}**

════════ ข้อเท็จจริงทางโหราศาสตร์ของวันนี้ (คำนวณจากตำแหน่งดาวจริง) ════════
{$brief['text']}
═══════════════════════════════════════════════════════════════════

🚨 กฎเหล็ก (ผิดข้อใดข้อหนึ่ง = คำทำนายใช้ไม่ได้):
1. ทำนายจากข้อเท็จจริงด้านบนเท่านั้น — ทุกด้านต้องสาวกลับไปหาดาว/ภพ/มุมที่ระบุไว้ได้
   ห้ามอ้างดาว ราศี มุม หรือปรากฏการณ์ที่ไม่มีในรายการ ด้านไหนไม่มีดาวเข้า ให้ทำนายจากดาวเจ้าเรือนราศีกับจันทร์ และเขียนสั้นลง
   ⛔ ห้ามอ้างไพ่ทาโรต์ เลขศาสตร์ สีมงคล เลขนำโชค หรือของมงคลใด ๆ
2. ตัดน้ำ เข้าเนื้อทันที ห้ามประโยคกลวงแบบ "วันนี้เป็นวันที่ดี ขอให้มีสติ" ห้ามทักทาย
3. ฟันธง ห้าม "อาจจะ" "น่าจะ" บอกว่าควรทำอะไร ไม่ควรทำอะไร ให้เจาะจงพอเอาไปใช้ได้
4. ใช้คะแนนตามที่ระบุในข้อเท็จจริงเป๊ะ ๆ ห้ามเปลี่ยน
5. พูดแทนตัวเองว่า "แม่หมอ" ลงท้าย "ค่ะ/นะคะ" ห้ามพูดว่าเป็น AI หรือระบบ

รูปแบบผลลัพธ์ (ระบบ parse อัตโนมัติ ห้ามผิดรูปแบบ):

[ภาพรวม] คะแนน: X/5
3-4 ประโยค

[ความรัก] คะแนน: X/5
2-3 ประโยค

[การงาน] คะแนน: X/5
2-3 ประโยค

[การเงิน] คะแนน: X/5
2-3 ประโยค

[สุขภาพ] คะแนน: X/5
2-3 ประโยค
PROMPT;
    }
}
