<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Illuminate\Support\Facades\Log;

/**
 * 📈 กราฟชีวิตสำหรับคำทำนาย 39 / บทสรุป 99 — ตัวกลางระหว่างบิลกับ LifeGraph (ตำราจาก GenLotto)
 *
 * อินพุตชุดเดียวกับรูปผังดวงของบิลนั้นเสมอ (ห้ามอ่านเวลา/จังหวัดเกิดเองที่นี่):
 *   39 = วันเกิด + เวลา/จังหวัด "ต่อบิล" ($chartInputs ของ processPaymentConfirmed)
 *   99 = birthDateTimeForChart() + birthProvinceIfKnown() (ชุดเดียวกับ buildCelticBirthChartUrl)
 * จำผลไว้ต่อคำขอ ⇒ พรอมต์กับรูปใช้กราฟก้อนเดียวกัน · ล้มทุกกรณี = null/'' (กราฟเป็นของเสริม ห้ามขวางบิล)
 */
final class LifeGraphService
{
    /** @var array<string, array|null> */
    private static array $memo = [];

    /** กราฟจากวันเกิด + เวลา/จังหวัดที่ล็อกแล้ว — null = ผูกดวงไม่ได้ */
    public static function forInputs(?string $birthDate, ?float $hour, ?string $province, string $name = ''): ?array
    {
        if ($birthDate === null || trim($birthDate) === '') {
            return null;
        }
        $key = implode('|', [$birthDate, $hour ?? '-', $province ?? '-', now()->format('Y-m-d')]);
        if (! array_key_exists($key, self::$memo)) {
            try {
                self::$memo[$key] = (new LifeGraph)->compute(
                    ['name' => $name, 'birth_date' => $birthDate, 'birth_hour' => $hour, 'birth_province' => $province],
                    now()->toDateTimeImmutable()
                );
            } catch (\Throwable $e) {
                Log::warning('LifeGraph: คำนวณกราฟชีวิตไม่สำเร็จ', ['birth_date' => $birthDate, 'error' => $e->getMessage()]);
                self::$memo[$key] = null;
            }
            // worker คิวรันหลายสิบงานต่อโปรเซส (กราฟละ ~0.5MB) — จำแค่ 4 ดวงล่าสุดพอให้พรอมต์กับรูปของบิลเดียวกันใช้ร่วมกัน
            while (count(self::$memo) > 4) {
                array_shift(self::$memo);
            }
        }
        $g = self::$memo[$key];
        if ($g !== null) {
            $g['profile']['name'] = $name;
        }

        return $g;
    }

    /** กราฟของบิล 99 — อินพุตชุดเดียวกับรูปผัง (buildCelticBirthChartUrl) · ไม่มีวันเกิดในบิล = null */
    public static function forReading(FortuneReading $reading): ?array
    {
        if (empty($reading->birth_date)) {
            return null;
        }
        $bd = $reading->birthDateTimeForChart() ?? \Carbon\Carbon::parse($reading->birth_date)->format('Y-m-d');

        return self::forInputs($bd, null, $reading->birthProvinceIfKnown(), (string) $reading->resolveCustomerName());
    }

    /** วาดรูปกราฟชีวิต 1080×1350 แล้วคืน URL — null ถ้าวาดไม่ได้ */
    public static function imageUrl(?array $g, int $readingId): ?string
    {
        if ($g === null) {
            return null;
        }
        $key = 'r'.$readingId.'-'.substr(md5(json_encode([$g['profile'], $g['current']['age'], LifeGraph::VERSION, now()->format('Y-m-d')])), 0, 10);

        return (new LifeGraphImage)->save($g, $key);
    }

    /**
     * 📚 (2026-10-12) รูปกราฟชีวิตของบิลนี้สำหรับ "อ่านย้อนหลัง" — owner: บันทึกไว้ให้ลูกค้า · บิลเก่าที่ไม่มี = สร้างใหม่แล้วบันทึกเข้าไป
     *   มีรูปที่บันทึกไว้ (39 = lifegraph_image_url · 99 = celtic_finale_lifegraph_url) และไฟล์ยังอยู่ → ใช้รูปเดิม
     *   ไม่มี/ไฟล์หาย → วาดจากวันเกิดในบิล แล้วเก็บลง lifegraph_image_url · ไม่มีวันเกิด/วาดไม่ได้ = null
     */
    public static function ensureForReading(FortuneReading $reading): ?string
    {
        try {
            foreach (['lifegraph_image_url', 'celtic_finale_lifegraph_url'] as $stateKey) {
                $url = $reading->getConversationState($stateKey);
                if (is_string($url) && $url !== '' && self::storedFileExists($url)) {
                    return $url;
                }
            }
            $url = self::imageUrl(self::forReading($reading), (int) $reading->id);
            if ($url !== null) {
                $reading->setConversationState('lifegraph_image_url', $url);
            }

            return $url;
        } catch (\Throwable $e) {
            Log::warning('LifeGraph: เตรียมรูปกราฟชีวิตย้อนหลังไม่สำเร็จ', ['reading_id' => $reading->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** URL รูปบน public disk ของเรา (/storage/...) ยังมีไฟล์อยู่ไหม — URL แบบอื่นถือว่ามี */
    private static function storedFileExists(string $url): bool
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (! str_starts_with($path, '/storage/')) {
            return true;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists(rawurldecode(substr($path, strlen('/storage/'))));
    }

    /**
     * 📝 บล็อกสั้นในพรอมต์ (≤ 6 บรรทัด) — ช่วงดาวเสวย · ดาวแทรกตอนนี้/ถัดไป · ทักษาจรปีนี้ · ช่วงถัดไป + ช่วงรุ่ง/ระวังที่ยังมาไม่ถึง
     * ข้อความตำราจาก LifeGraphTexts (≥ 2 แหล่ง) · คู่ที่ตำราขัดกัน = บอกว่าไม่ทาย ไม่แต่งแทน
     *
     * @param  string  $useHint  บอกโมเดลว่าใช้ตรงไหนของคำทำนาย (ต่างกันระหว่าง 39 กับ 99)
     */
    public static function promptBlock(?array $g, string $useHint): string
    {
        $p = $g['current']['period'] ?? null;
        if ($g === null || $p === null) {
            return '';
        }
        $th = static fn (int $n): string => LifeGraphTables::planetTh($n);
        $said = static fn (?array $t): string => $t !== null ? 'ตำราว่า'.$t['tone'].': '.$t['text'] : 'ตำราขัดกัน ไม่ทายคู่นี้';
        $cur = $g['current'];
        $nat = LifeGraphTexts::PERIOD[$p['planet']]; // คำทายเต็มอยู่ในบรรทัด "⏳ ดาวเสวยอายุ" ของผังดวงแล้ว — ที่นี่บอกแค่ดี/ร้าย
        $natal = '';
        foreach ($g['reading']['items'] as $it) {
            if ($it['kind'] === 'natal') {
                $natal = ' · '.$it['text'];
            }
        }
        $lines = [
            '[📈 กราฟชีวิตตามตำรามหาทักษา — คำนวณจริงจากวันเกิดชุดเดียวกับผังดวง · '.$useHint.' · ห้ามแต่งอายุ วันที่ หรือช่วงดี-ร้ายนอกจากนี้]',
            'ตอนนี้อายุ '.$cur['age'].': '.$th($p['planet']).'เสวยอายุ '.$p['from_age'].'-'.$p['to_age'].' ปี = ตำราว่าช่วง'.$nat['tone'].$natal,
        ];
        $s = $cur['sub'];
        if ($s !== null) {
            $lines[] = 'ดาวแทรกตอนนี้ '.$th($s['planet']).' ถึงราว '.LifeGraphTables::thDate($s['until_date']).' = '.$said(LifeGraphTexts::SUB[$p['planet'].'-'.$s['planet']] ?? null);
            foreach ($g['periods'] as $po) {
                foreach ($po['subs'] as $x) {
                    if ($x['from_date'] === $s['until_date'] && $x['from_age'] > $s['from_age']) {
                        $lines[] = 'ดาวแทรกถัดไป '.$th($x['planet']).' ตั้งแต่ราว '.LifeGraphTables::thDate($x['from_date']).' = '.$said(LifeGraphTexts::SUB[$po['planet'].'-'.$x['planet']] ?? null);
                        break 2;
                    }
                }
            }
        }
        foreach ($g['reading']['items'] as $it) {
            if ($it['kind'] === 'jon') {
                $lines[] = $it['title'].': '.$it['text'];
            }
        }
        $tail = [];
        foreach ($g['periods'] as $po) {
            if ($po['from_age'] === $p['to_age']) {
                $tail[] = 'ช่วงถัดไป '.$po['planet_th'].'เสวย '.$po['from_age'].'-'.$po['to_age'].' ปี = ตำราว่าช่วง'.LifeGraphTexts::PERIOD[$po['planet']]['tone'];
            }
        }
        $ahead = static function (array $list) use ($cur): array {
            $ages = array_values(array_filter(array_column($list, 'age'), static fn ($a) => $a > $cur['age']));
            sort($ages);

            return $ages;
        };
        $extremes = [];
        if ($up = $ahead($g['peaks'])) {
            $extremes[] = 'ช่วงรุ่งข้างหน้า อายุ '.implode(', ', $up);
        }
        if ($down = $ahead($g['lows'])) {
            $extremes[] = 'ช่วงควรระวังข้างหน้า อายุ '.implode(', ', $down);
        }
        if ($extremes) {
            // มาจากคะแนนกราฟ (ค่าน้ำหนักที่ระบบออกแบบ) — ห้ามให้โมเดลเล่าว่าเป็นคำทำนายตามตำรา
            $tail[] = implode(' · ', $extremes).' (จากคะแนนกราฟที่ระบบออกแบบ ไม่ใช่ตำรา)';
        }
        if ($tail) {
            $lines[] = implode(' · ', $tail);
        }

        return implode("\n", $lines);
    }

    /** ล้างที่จำไว้ (เทสต์) */
    public static function flush(): void
    {
        self::$memo = [];
    }
}
