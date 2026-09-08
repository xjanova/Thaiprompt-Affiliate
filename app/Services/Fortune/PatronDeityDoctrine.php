<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 🕉️ PatronDeityDoctrine — เลือก "องค์เทพประจำตัว" จากตำรา ไม่ใช่จากไพ่
 * ════════════════════════════════════════════════════════════════════
 *
 * 🚨 (2026-09-08) เจ้าของสั่ง: *"คนหนึ่งอาจเหมาะกับการบูชาหลายองค์เทพได้
 *    แต่ต้องตามตำรา ไม่ใช่ตามไพ่ เดิมมันตามไพ่อย่างเดียว ถ้าไม่มีวันเกิดก็ใช้ไพ่แทน"*
 *
 * ของเดิม: องค์เทพมาจากคลัง `fortune_knowledge.patron_deity` ซึ่งผูกกับ "ไพ่ 78 ใบ"
 *   ⇒ ตรวจ prod แล้วพบว่า 4 องค์ (ลักษมี/พิฆเนศ/กวนอิม/ครุฑ) ครอบ 78% ของไพ่
 *     พญานาคมีแค่ 5 ใบ + พรอมต์สั่ง "เลือกองค์เดียว" ⇒ องค์ที่ไพ่น้อยแทบไม่เคยถูกเลือก
 *
 * ของใหม่ — 3 ชั้น เรียงตามน้ำหนัก:
 *   1. องค์ประธาน   ← เทวดานพเคราะห์ประจำวันเกิด + ปางพระประจำวัน (`by_birth_day`)
 *   2. องค์เสริม    ← ธาตุเจ้าเรือนจากราศีเกิด (`by_element`)
 *   3. องค์ของรอบนี้ ← หน้าไพ่ (คลังเดิม — เหลือบทบาท "จริตเฉพาะรอบ")
 *
 * ⚠️ ไม่มีวันเกิด → คืน null แล้ว caller ตกไปใช้ไพ่ล้วนเหมือนเดิม
 *    ([[rule_birth_time_ask_and_parse]] — ห้ามเดาวันเกิดมาคำนวณเอง)
 */
class PatronDeityDoctrine
{
    protected ThaiAstrologyService $astro;

    public function __construct(?ThaiAstrologyService $astro = null)
    {
        $this->astro = $astro ?? new ThaiAstrologyService;
    }

    /**
     * สวิตช์รวม — ปิดแล้วกลับไปเลือกองค์จากไพ่ล้วนแบบเดิม
     */
    public function isEnabled(): bool
    {
        return (bool) config('fortune_patron_deity_doctrine.enabled', true);
    }

    /**
     * หาองค์เทพตามตำราจากวันเกิด
     *
     * @param  float|null  $birthHour  ชั่วโมงเกิด (ทศนิยม) — ต้องเป็นเวลาที่ *ยืนยันแล้ว* เท่านั้น
     *                                 null = ไม่ทราบ (ใช้วันตามปฏิทิน ไม่เลื่อนเป็นวันโหร)
     * @return array{
     *     day_index:int, day_name:string, planet:string, buddha_pose:string,
     *     main:string, support:array<int,string>, element:string|null,
     *     element_deities:array<int,string>, all:array<int,string>
     * }|null  null = ไม่รู้วันเกิด / ตำราในคอนฟิกหาย ⇒ ให้ caller ใช้ไพ่แทน
     */
    public function resolve(?Carbon $birthDate, ?float $birthHour = null): ?array
    {
        if (! $this->isEnabled() || $birthDate === null) {
            return null;
        }

        // 🌙 วัน "ทางโหร" — เกิดก่อนย่ำรุ่ง (ก่อน 06:00) ยังนับเป็นคืนของเมื่อวาน
        //    ไม่ทราบเวลาเกิด → thaiWeekday คืนวันตามปฏิทินเหมือนเดิม (ไม่เดา)
        $dayIndex = $this->astro->thaiWeekday((int) $birthDate->dayOfWeek, $birthHour);

        // 🌑 วันเกิดที่ 8 — พุธกลางคืน (ราหู) ต้องรู้เวลาเกิดถึงจะแยกออก
        if ($this->astro->isWednesdayNight($dayIndex, $birthHour)) {
            $dayIndex = 7;
        }

        $table = (array) config('fortune_patron_deity_doctrine.by_birth_day', []);
        $entry = $table[$dayIndex] ?? null;

        if (! is_array($entry) || empty($entry['main'])) {
            return null;
        }

        $element = $this->elementOf($birthDate);
        $elementDeities = $element !== null
            ? (array) (config('fortune_patron_deity_doctrine.by_element', [])[$element] ?? [])
            : [];

        $main = (string) $entry['main'];

        // องค์เสริม = ตำราวันเกิด + ตำราธาตุ
        //
        // ⚠️ ต้อง "สลับหยิบ" ไม่ใช่ต่อท้าย — เพดานมีแค่ 2 ที่นั่ง ถ้าเรียงวันเกิดก่อนทั้งหมด
        //    องค์สายธาตุจะถูกตัดทิ้งทุกครั้ง ⇒ ตำราชั้นที่ 2 ตายสนิทโดยไม่มีใครรู้
        //    (แผลเดียวกับที่ทำให้พญานาคไม่เคยโผล่ในระบบเดิม)
        $max = (int) config('fortune_patron_deity_doctrine.max_support', 2);
        $daySupport = array_values((array) ($entry['support'] ?? []));
        $byElement = array_values($elementDeities);

        $pool = [];
        for ($i = 0; $i < max(count($daySupport), count($byElement)); $i++) {
            if (isset($daySupport[$i])) {
                $pool[] = $daySupport[$i];
            }
            if (isset($byElement[$i])) {
                $pool[] = $byElement[$i];
            }
        }

        $support = array_values(array_filter(
            array_unique($pool),
            fn ($name) => $name !== $main
        ));
        $support = array_slice($support, 0, max(0, $max));

        return [
            'day_index' => $dayIndex,
            'day_name' => (string) ($entry['day_name'] ?? ''),
            'planet' => (string) ($entry['planet'] ?? ''),
            'buddha_pose' => (string) ($entry['buddha_pose'] ?? ''),
            'main' => $main,
            'support' => $support,
            'element' => $element,
            'element_deities' => array_values($elementDeities),
            'all' => array_merge([$main], $support),
        ];
    }

    /**
     * เวอร์ชันที่รับบิลตรง ๆ — หาวันเกิดตามลำดับความน่าเชื่อถือ
     *
     * บิลนี้ → บิลเก่าของลูกค้าคนเดียวกัน ([[rule_birthdate_single_resolver]])
     * ⚠️ เวลาเกิดใช้เฉพาะที่ *ยืนยันแล้ว* — คอลัมน์ `birth_time` มีค่า 12:00 มาตรฐาน
     *    อยู่ทุกบิล ถ้าเช็คแค่ "มีค่าไหม" จะกลายเป็นทุกคนเกิดเที่ยงวัน
     */
    public function resolveForReading(FortuneReading $reading): ?array
    {
        try {
            $birthDate = $reading->birth_date;

            if ($birthDate === null) {
                $hit = BirthdateResolver::forReading($reading);
                $birthDate = $hit['date'] ?? null;
            }

            if ($birthDate === null) {
                return null;
            }

            $hour = $reading->birthTimeIsKnown() ? (float) $reading->birthHourFloat() : null;

            return $this->resolve(Carbon::parse($birthDate), $hour);
        } catch (\Throwable $e) {
            Log::warning('PatronDeityDoctrine: หาองค์เทพตามตำราไม่สำเร็จ (ตกไปใช้ไพ่)', [
                'reading_id' => $reading->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * บล็อกข้อเท็จจริงที่ฉีดเข้าพรอมต์ — องค์ประธาน + องค์เสริม พร้อมของบูชา/วันไหว้
     *
     * @param  array<string,mixed>  $doctrine  ผลจาก resolve()
     */
    public function knowledgeBlock(array $doctrine): string
    {
        $out = "🕉️ องค์เทพตามตำรา (คำนวณจากวันเกิดจริงของเจ้าชะตา — ไม่ใช่จากหน้าไพ่)\n"
            ."• เกิดวัน{$doctrine['day_name']} → ดาวประจำวัน {$doctrine['planet']} · พระประจำวันเกิด {$doctrine['buddha_pose']}\n";

        if (! empty($doctrine['element'])) {
            $out .= "• ธาตุเจ้าเรือนจากราศีเกิด: ธาตุ{$doctrine['element']}\n";
        }

        $out .= $this->rosterLine((string) $doctrine['main'], 'องค์ประธาน');

        foreach ((array) $doctrine['support'] as $name) {
            $out .= $this->rosterLine((string) $name, 'องค์เสริม');
        }

        return $out;
    }

    /**
     * 1 บรรทัดของ 1 องค์ — ระดับ · ด้านที่หนุน · ของบูชา · วันไหว้ (ดึงจากทะเบียน)
     *
     * องค์ที่ไม่มีในทะเบียนจะได้แค่ชื่อ — มีเทสต์ integrity กันไว้ไม่ให้เกิดขึ้นจริง
     * (พรอมต์สั่งให้แม่หมอบอกวิธีบูชา ⇒ ไม่มีข้อมูล = เปิดช่องให้มโน)
     */
    protected function rosterLine(string $name, string $role): string
    {
        $meta = (array) (config('fortune_patron_deity_doctrine.roster', [])[$name] ?? []);

        $parts = array_filter([
            $meta['level'] ?? null,
            $meta['domain'] ?? null,
            ! empty($meta['offering']) ? 'ของบูชา: '.$meta['offering'] : null,
            ! empty($meta['day']) ? 'วันไหว้: '.$meta['day'] : null,
        ]);

        return "• [{$role}] {$name}".($parts === [] ? '' : ' — '.implode(' · ', $parts))."\n";
    }

    /**
     * 🎯 ตำราชั้นที่ 3 — องค์ที่เด่นเรื่อง "ที่ลูกค้าถาม" (เสริม ไม่ใช่แทนองค์ประธาน)
     *
     * เจ้าของสั่ง 2026-09-08 ให้ทำเป็นชั้นแยก แทนที่จะยัดสีวลี/หลวงปู่ทวดไปผูกวันเกิดมั่ว ๆ
     * — องค์กลุ่มนี้เด่นที่ "เรื่อง" ไม่ใช่ที่ "วันเกิด"
     *
     * @param  string  $text  ข้อความ**ของลูกค้า** (ไม่ใช่ทั้งบทสนทนา — ยิ่งกว้างยิ่งจับมั่ว)
     * @param  array<int,string>  $exclude  องค์ที่พูดไปแล้ว (องค์ประธาน/เสริม) — กันซ้ำในบล็อกเดียว
     * @return array{topics:array<int,string>, deities:array<int,string>} ว่างทั้งคู่ = ไม่เข้าเรื่องไหน
     */
    public function topicDeities(string $text, array $exclude = []): array
    {
        $text = trim($text);

        if ($text === '' || ! $this->isEnabled()) {
            return ['topics' => [], 'deities' => []];
        }

        $topics = [];
        $deities = [];

        foreach ((array) config('fortune_patron_deity_doctrine.by_topic', []) as $topic) {
            foreach ((array) ($topic['keywords'] ?? []) as $keyword) {
                if ($keyword !== '' && mb_strpos($text, (string) $keyword) !== false) {
                    $topics[] = (string) ($topic['label'] ?? '');
                    $deities = array_merge($deities, (array) ($topic['deities'] ?? []));
                    break; // เข้าเรื่องนี้แล้ว ไม่ต้องนับคีย์เวิร์ดที่เหลือของเรื่องเดียวกัน
                }
            }
        }

        $deities = array_values(array_filter(
            array_unique($deities),
            fn ($name) => ! in_array($name, $exclude, true)
        ));

        $max = (int) config('fortune_patron_deity_doctrine.max_topic_deities', 3);

        return [
            'topics' => array_values(array_unique($topics)),
            'deities' => array_slice($deities, 0, max(0, $max)),
        ];
    }

    /**
     * บล็อก "องค์สำหรับเรื่องที่ถามรอบนี้" — ต่อท้าย knowledgeBlock()
     *
     * @param  array{topics:array<int,string>, deities:array<int,string>}  $hit  ผลจาก topicDeities()
     */
    public function topicBlock(array $hit): string
    {
        if (empty($hit['deities'])) {
            return '';
        }

        $out = '🎯 องค์ที่เด่นเฉพาะ "เรื่องที่ลูกถามรอบนี้" ('.implode(' + ', $hit['topics']).") — *เสริม ไม่ใช่แทนองค์ประธาน*\n";

        foreach ($hit['deities'] as $name) {
            $out .= $this->rosterLine((string) $name, 'องค์เฉพาะเรื่อง');
        }

        return $out;
    }

    /**
     * ธาตุเจ้าเรือนจากราศีเกิด (นิรายนะ — คำนวณจากดวงอาทิตย์จริง)
     *
     * [[rule_thai_astrology_is_sidereal]] — ห้ามใช้ราศีสากล
     */
    protected function elementOf(Carbon $birthDate): ?string
    {
        try {
            $sign = $this->astro->getZodiacSignForDate($birthDate);
            $element = $this->astro->getZodiacElement($sign);

            if ($element !== null) {
                return $element;
            }

            // ephemeris ให้ป้ายที่ prefix ไม่ตรง → ตกไปตารางวัน-เดือนแบบไทย
            return $this->astro->getZodiacElement(
                $this->astro->getZodiacSign((int) $birthDate->month, (int) $birthDate->day)
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
