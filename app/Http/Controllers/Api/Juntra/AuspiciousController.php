<?php

namespace App\Http\Controllers\Api\Juntra;

use App\Http\Controllers\Controller;
use App\Services\Fortune\AuspiciousTimingDirective;
use App\Services\Fortune\PersonaBreakDetector;
use App\Services\Fortune\ThaiRuekYam;
use App\Services\FortuneAIService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🗓️ (2026-11-07) คำแนะนำฤกษ์ให้เว็บจันทรา — POST fortune/auspicious (ลูกค้าจ่าย ฿19 ที่ juntraweb แล้ว)
 *
 * juntraweb เรียกเส้นนี้มาตั้งแต่ก่อน 2026-09 แต่ไม่เคยมีอยู่จริง (404) ⇒ ลูกค้าที่มี token ไหลไปท่อแชท
 * ลูกค้าที่สมัครด้วยเบอร์/อีเมล (ส่วนใหญ่) ได้คำแนะนำที่เว็บเขียนเองจากผลคำนวณ
 *
 * juntraweb คัดวันมาเองด้วยตัวให้คะแนนของมัน (ฤกษ์บนจากตำแหน่งจันทร์จริง + ดิถี + ยาม)
 * ที่นี่ **ตรวจซ้ำทุกวันด้วยปฏิทินโหรของเรา** (ThaiRuekYam — ตรึงกับ myhora: ปฏิทินหลวง · กาลโยค · ฤกษ์ล่าง · ยามอัฏฐกาล)
 * แล้วตัดวันกาลกิณีของเจ้าของงานตามทักษา — วันที่ตำราห้าม แม่หมอต้องเตือน ห้ามฟันธงเป็นอันดับ 1
 */
class AuspiciousController extends Controller
{
    /** หมวดงานของ juntraweb (AuspiciousOccasions) → หมวดของ ThaiRuekYam::ACTIVITIES */
    private const ACT = [
        'wedding' => 'wedding', 'housewarming' => 'house', 'business' => 'shop',
        'vehicle' => 'car', 'travel' => 'travel',
    ];

    public function __invoke(Request $request, FortuneAIService $ai): JsonResponse
    {
        $data = $request->validate([
            'prompt' => 'required|string|max:12000',
            'occasion' => 'nullable|string|max:300',
            'category' => 'nullable|string|max:40',
            'days' => 'nullable|array|max:12',
            'days.*' => 'date_format:Y-m-d',
            'birth_date' => 'nullable|date_format:Y-m-d|before:today|after:1900-01-01',
        ]);

        $occasion = (string) ($data['occasion'] ?? '');
        $act = self::ACT[$data['category'] ?? ''] ?? (AuspiciousTimingDirective::detectActivity($occasion) ?? 'general');
        $person = ! empty($data['birth_date'])
            ? AuspiciousTimingDirective::personFromBirth(Carbon::parse($data['birth_date']))
            : null;

        $checks = $this->check($act, array_values(array_unique($data['days'] ?? [])), $person);

        $user = $data['prompt'];
        if ($checks !== []) {
            $user .= "\n\n".$this->checkBlock($act, $checks, $person);
        }

        try {
            $result = $ai->chatWithCustomSystemPrompt(
                $this->systemPrompt(),
                $user,
                ['temperature' => 0.6, 'max_tokens' => 1800],
                guardText: $occasion !== '' ? $occasion : null,
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra auspicious: AI ไม่พร้อม', ['err' => mb_substr($e->getMessage(), 0, 200)]);

            // 503 = juntraweb ถอยไปใช้คำแนะนำที่เขียนจากผลคำนวณเอง (ลูกค้ายังได้ของ)
            return response()->json(['message' => 'ระบบคำแนะนำไม่พร้อมชั่วคราว', 'checks' => $checks], 503);
        }

        $text = trim((string) ($result['response'] ?? ''));
        if ($text === '' || ($reason = PersonaBreakDetector::reason($text, strict: true)) !== null) {
            Log::warning('Juntra auspicious: คำตอบใช้ไม่ได้', ['reason' => $reason ?? 'empty']);

            return response()->json(['message' => 'ระบบคำแนะนำไม่พร้อมชั่วคราว', 'reason_code' => 'model_refused', 'checks' => $checks], 503);
        }

        return response()->json(['data' => [
            'advice' => $text,
            'checks' => $checks,
            'activity' => $act,
            'ai_provider' => $result['provider'] ?? null,
            'ai_model' => $result['model'] ?? null,
        ]]);
    }

    /**
     * ตรวจทุกวันด้วยปฏิทินโหรของเรา
     *
     * @return array<int, array{date:string, ok:?bool, kala:bool, reasons:array, goods:array, windows:array}>
     */
    private function check(string $act, array $days, ?array $person): array
    {
        $engine = new ThaiRuekYam;
        $mongkol = (bool) (ThaiRuekYam::ACTIVITIES[$act]['mongkol'] ?? false);
        $out = [];
        foreach ($days as $ymd) {
            try {
                $D = $engine->day($ymd);
                $cls = ThaiRuekYam::classify(ThaiRuekYam::evaluate($act, $D), $D);
            } catch (\Throwable $e) {
                Log::info('Juntra auspicious: ตรวจวันไม่ได้', ['ymd' => $ymd, 'err' => mb_substr($e->getMessage(), 0, 120)]);
                $out[] = ['date' => $ymd, 'ok' => null, 'kala' => false, 'reasons' => [], 'goods' => [], 'windows' => []];

                continue;
            }
            $kala = $mongkol && $person !== null && ($person['kala_wd'] ?? null) === $D['wd'];
            $reasons = $cls['reasons'];
            if ($kala) {
                $reasons[] = 'วัน'.ThaiRuekYam::WD[$D['wd']].'เป็นวันกาลกิณีของเจ้าของงาน';
            }
            $out[] = [
                'date' => $ymd,
                'ok' => $cls['cls'] === 'na' ? null : ($cls['cls'] === 'good' && ! $kala),
                'kala' => $kala,
                'reasons' => array_values($reasons),
                'goods' => array_values($cls['goods']),
                'windows' => array_map(fn (array $w) => ['from' => $w['from_hm'], 'to' => $w['to_hm'], 'star' => (bool) $w['star']], $cls['wins']),
            ];
        }

        return $out;
    }

    private function checkBlock(string $act, array $checks, ?array $person): string
    {
        $label = ThaiRuekYam::ACTIVITIES[$act]['label'] ?? 'งานมงคลทั่วไป';
        $lines = [
            '════════ ตรวจซ้ำด้วยปฏิทินโหรไทย (ปฏิทินหลวง · กาลโยค · ฤกษ์ล่าง · ยามอัฏฐกาล) — หมวด '.$label.' ════════',
        ];
        if ($person !== null && ($person['kala_planet'] ?? '') !== '') {
            $lines[] = 'ทักษาเจ้าของงาน: กาลกิณีคือ'.$person['kala_planet'];
        }
        foreach ($checks as $c) {
            $d = Carbon::parse($c['date'])->locale('th')->translatedFormat('D j M');
            if ($c['ok'] === null) {
                $lines[] = "- {$d}: ตรวจด้วยปฏิทินหลวงไม่ได้ — ใช้ผลชุดแรกอย่างเดียว";
            } elseif ($c['ok']) {
                $win = array_map(fn ($w) => $w['from'].'–'.$w['to'].($w['star'] ? ' ★' : ''), $c['windows']);
                $lines[] = "- {$d}: ✅ ผ่านเกณฑ์".($c['goods'] !== [] ? ' ('.implode(', ', $c['goods']).')' : '')
                    .($win !== [] ? ' · ช่วงเวลาที่ผ่านเกณฑ์: '.implode(', ', $win) : '');
            } else {
                $lines[] = "- {$d}: ⛔ ตำราห้าม — ".implode(' / ', $c['reasons']);
            }
        }
        $lines[] = 'กติกา: วันที่ขึ้น ⛔ ห้ามเลือกเป็นอันดับ 1 และต้องบอกลูกค้าตรง ๆ ว่าตำราห้ามเพราะอะไร'
            .' · ถ้าทุกวันขึ้น ⛔ ให้บอกว่ายังไม่มีวันที่ผ่านเกณฑ์ในช่วงนี้ และแนะนำให้ลองขยายช่วงวัน'
            .' · เวลาพิธีให้อยู่ในช่วงที่ผ่านเกณฑ์เท่านั้น';

        return implode("\n", $lines);
    }

    private function systemPrompt(): string
    {
        return 'คุณคือ "แม่หมอ" โหรไทยของเว็บจันทรา ให้คำแนะนำฤกษ์งานมงคลจากผลคำนวณที่ระบบส่งมาเท่านั้น'
            ."\n- ห้ามเพิ่มวัน ห้ามเปลี่ยนชื่อฤกษ์ ห้ามแต่งเวลาเอง · ถ้าผลสองชุดขัดกัน ให้เชื่อชุด \"ตรวจซ้ำด้วยปฏิทินโหรไทย\""
            ."\n- พูดแทนตัวเองว่าแม่หมอ ลงท้ายค่ะ/นะคะ อบอุ่น ไม่ขู่ ไม่ทักทายยาว"
            ."\n- ห้ามพูดว่าเป็น AI/ระบบ/โมเดล ห้ามขายของหรือชวนจ่ายเงินเพิ่ม ห้ามให้เบอร์โทรหรือบัญชีโอนเงิน";
    }
}
