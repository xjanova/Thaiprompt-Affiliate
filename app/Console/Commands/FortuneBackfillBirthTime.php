<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Services\Fortune\ThaiAstrologyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 🕛 กู้ "เวลาเกิด" ย้อนหลัง — ลูกค้าเคยพิมพ์มาแล้ว แต่ตัวอ่านเก่าอ่านไม่ออก
 *
 * ที่มา (2026-09-03, บิล FTU-260903-X0866):
 *   ลูกค้าพิมพ์ *"เวลาเกิดตี5"* → ตัวอ่านเดิมไม่รู้จัก "ตี N" → `birth_time` = NULL
 *   ผังดวงคำนวณจากเที่ยงวันตามเดิม แต่ AI ประกาศว่า "รับข้อมูลเวลาเกิดแล้วค่ะ"
 *   ตรวจ prod เจอว่า **บิลจ่ายเงิน 1,253 ใบ มี birth_time 0 ใบ** — คอลัมน์ไม่เคยถูกเขียนเลย
 *
 *   ตัวอ่านใหม่ (`ThaiAstrologyService::parseThaiClock`) อ่านนาฬิกาไทยออกแล้ว
 *   ⇒ ข้อความเก่าที่เก็บไว้ในบิลยังอยู่ครบ เอามารีพาร์สย้อนหลังได้
 *
 * ⚠️ คำสั่งนี้แก้ **ระเบียนในฐานข้อมูล** เท่านั้น — คำทำนายที่ส่งไปแล้วยังเป็นของผังเที่ยงวัน
 *    และ **ไม่ยิงข้อความหาลูกค้า** (owner: แก้แล้วไม่ต้องตามเก็บ)
 *
 * @example
 *   php artisan fortune:backfill-birth-time                 # ดูอย่างเดียว (ค่าเริ่มต้น)
 *   php artisan fortune:backfill-birth-time --apply         # เขียนจริง
 *   php artisan fortune:backfill-birth-time --paid-only     # เฉพาะบิลที่จ่ายเงินแล้ว
 *   php artisan fortune:backfill-birth-time --id=12101      # เจาะบิลเดียว
 *
 * @tip รันแบบ dry ก่อนเสมอ แล้วดูคอลัมน์ "ที่มา" ว่าดึงจากแหล่งไหน
 *      ถ้าเจอ answer เยอะผิดปกติแปลว่าตัวอ่านผ่อนกฎอาจจับมั่ว — ตรวจตัวอย่างก่อน --apply
 */
class FortuneBackfillBirthTime extends Command
{
    protected $signature = 'fortune:backfill-birth-time
                            {--apply : เขียนลง DB จริง (ไม่ใส่ = ดูอย่างเดียว)}
                            {--limit=0 : จำกัดจำนวนบิลที่ประมวลผล (0 = ไม่จำกัด)}
                            {--paid-only : เฉพาะบิลที่จ่ายเงินแล้ว}
                            {--id= : เจาะบิลเดียวด้วย reading id}
                            {--samples=15 : จำนวนตัวอย่างที่พิมพ์ออกมาให้ดู}';

    protected $description = 'กู้เวลาเกิดย้อนหลังจากข้อความเก่าที่ตัวอ่านรุ่นเดิมอ่านไม่ออก';

    /**
     * แหล่งที่ถือว่าเป็น "คำตอบของคำถามวันเกิด" → ใช้ตัวอ่านผ่อนกฎได้
     *
     * เพราะรู้แน่ว่าทั้งข้อความคือคำตอบวันเกิด ไม่ต้องมีคำว่า "เกิด" กำกับ
     * (เทียบ FortuneReading::BIRTH_TIME_ANSWER_SOURCES)
     */
    private const ANSWER_SOURCES = ['celtic_birthdate_text', 'pending_birthdate', 'birthdate_partial'];

    public function handle(ThaiAstrologyService $astro): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');
        $maxSamples = (int) $this->option('samples');

        $query = FortuneReading::query()
            ->whereNotNull('birth_date')
            ->whereNull('birth_time');

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }
        if ($this->option('paid-only')) {
            $query->where('is_paid', 1);
        }

        $total = (clone $query)->count();
        $this->info($apply
            ? "✍️  โหมดเขียนจริง — บิลที่เข้าข่าย {$total} ใบ"
            : "👀 โหมดดูอย่างเดียว (ใส่ --apply เพื่อเขียนจริง) — บิลที่เข้าข่าย {$total} ใบ");

        if ($total === 0) {
            $this->info('ไม่มีบิลที่ต้องกู้');

            return self::SUCCESS;
        }

        $scanned = 0;
        $found = 0;
        $failed = 0;
        $bySource = [];
        $samples = [];

        $query->select(['id', 'bill_reference', 'reading_type', 'is_paid', 'birth_date', 'conversation_state', 'questions'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (
                $astro, $apply, $limit, $maxSamples,
                &$scanned, &$found, &$failed, &$bySource, &$samples
            ) {
                foreach ($rows as $reading) {
                    if ($limit > 0 && $scanned >= $limit) {
                        return false; // ครบโควต้ารอบนี้ — หยุด chunk
                    }
                    $scanned++;

                    $hit = $this->findBirthHour($astro, $reading);
                    if ($hit === null) {
                        continue;
                    }

                    [$hour, $source, $snippet] = $hit;
                    $time = FortuneReading::hourToTimeString($hour);
                    $found++;
                    $bySource[$source] = ($bySource[$source] ?? 0) + 1;

                    if (count($samples) < $maxSamples) {
                        $samples[] = [
                            $reading->id,
                            (string) $reading->bill_reference,
                            $reading->is_paid ? '✅' : '—',
                            substr($time, 0, 5),
                            $source,
                            mb_substr($snippet, 0, 40),
                        ];
                    }

                    if (! $apply) {
                        continue;
                    }

                    try {
                        // ⚠️ ห้ามตั้งธง birth_time_just_updated — ธงนั้นสั่งให้แม่หมอทักลูกค้า
                        //    ว่า "ปรับผังใหม่แล้ว" ซึ่งเป็นการตามเก็บที่ owner สั่งห้าม
                        $reading->update(['birth_time' => $time]);
                        $reading->setConversationState('birth_time_source', 'backfill:'.$source);
                        $reading->setConversationState('birth_time_updated_at', now()->toIso8601String());
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->warn("  ⚠️ บิล {$reading->id} เขียนไม่สำเร็จ: ".$e->getMessage());
                    }
                }

                return true;
            });

        if ($samples !== []) {
            $this->newLine();
            $this->table(['id', 'บิล', 'จ่าย', 'เวลาเกิด', 'ที่มา', 'ข้อความต้นทาง'], $samples);
        }

        $this->newLine();
        $this->info("สแกน {$scanned} ใบ · อ่านเวลาเกิดออก {$found} ใบ".($failed > 0 ? " · เขียนพลาด {$failed} ใบ" : ''));
        if ($bySource !== []) {
            arsort($bySource);
            foreach ($bySource as $src => $count) {
                $this->line("   • {$src}: {$count}");
            }
        }
        if (! $apply && $found > 0) {
            $this->newLine();
            $this->comment('ยังไม่ได้เขียนอะไรลง DB — ใส่ --apply เมื่อตรวจตัวอย่างข้างบนแล้วพอใจ');
        }

        return self::SUCCESS;
    }

    /**
     * ไล่หาเวลาเกิดจากข้อความเก่าทุกแหล่งของบิลนี้ — เจอที่แรกชนะ
     *
     * เรียงจาก "มั่นใจสูงสุด" ลงมา: คำตอบวันเกิดโดยตรง → บทสนทนา → คำถามที่ลูกค้าพิมพ์
     *
     * @return array{0:float,1:string,2:string}|null [ชั่วโมง, ที่มา, ข้อความต้นทาง]
     */
    private function findBirthHour(ThaiAstrologyService $astro, FortuneReading $reading): ?array
    {
        foreach ($this->candidateTexts($reading) as [$source, $text]) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }

            try {
                // คำตอบวันเกิด = รู้แน่ว่าทั้งข้อความคือคำตอบ → ผ่อนกฎได้
                // ข้อความแชททั่วไป → ต้องเข้มงวด ไม่งั้น "เงินเดือน 2.50 หมื่น" กลายเป็นเวลาเกิด
                $hour = in_array($source, self::ANSWER_SOURCES, true)
                    ? $astro->extractBirthHourFromAnswer($text)
                    : $astro->extractStatedBirthHour($text);
            } catch (\Throwable $e) {
                continue;
            }

            if ($hour !== null) {
                return [$hour, $source, preg_replace('/\s+/u', ' ', $text)];
            }
        }

        return null;
    }

    /**
     * ข้อความทุกชิ้นของบิลที่ลูกค้าอาจเคยบอกเวลาเกิดไว้
     *
     * @return \Generator<array{0:string,1:string}> [ที่มา, ข้อความ]
     */
    private function candidateTexts(FortuneReading $reading): \Generator
    {
        // 1) คำตอบวันเกิดโดยตรง
        foreach (self::ANSWER_SOURCES as $key) {
            $v = $reading->getConversationState($key);
            if (is_string($v) && $v !== '') {
                yield [$key, $v];
            }
        }

        // 2) บทสนทนาหลังจ่ายเงิน (Deep 39 / Celtic คุยต่อ) — เอาเฉพาะฝั่งลูกค้า
        //    ฝั่ง assistant มีคำว่า "เวลาเกิด" เต็มไปหมด ถ้าอ่านด้วยจะได้เวลาที่ AI แต่งเอง
        foreach ((array) $reading->getConversationState('pro_session_history', []) as $turn) {
            $turn = (array) $turn;
            // ต้องเป็น 'user' แบบชัดเจนเท่านั้น — ไม่มี role = ข้าม (ปลอดภัยกว่าเดาว่าเป็นลูกค้า)
            // prod ตรวจแล้ว 1,722 เทิร์นเป็น {role, content} ครบทุกตัว ไม่มีตกหล่น
            $role = (string) ($turn['role'] ?? $turn['sender'] ?? '');
            if ($role !== 'user') {
                continue;
            }
            $msg = (string) ($turn['content'] ?? $turn['message'] ?? $turn['text'] ?? '');
            if ($msg !== '') {
                yield ['pro_session_history', $msg];
            }
        }

        // 3) คำถามที่ลูกค้าพิมพ์ (เก็บไว้ 2 ที่: state ตอนรวบรวม + คอลัมน์ตอนบันทึก)
        foreach ((array) $reading->getConversationState('collected_questions', []) as $q) {
            if (is_string($q) && $q !== '') {
                yield ['collected_questions', $q];
            }
        }
        foreach ((array) $reading->questions as $q) {
            if (is_string($q) && $q !== '') {
                yield ['questions', $q];
            }
        }

        // 4) คำถาม Celtic รายข้อ (อยู่คนละตาราง — คอลัมน์ questions ของบิลว่างเสมอ)
        $celtic = DB::table('fortune_celtic_questions')
            ->where('fortune_reading_id', $reading->id)
            ->orderBy('sequence')
            ->pluck('question');
        foreach ($celtic as $q) {
            if (is_string($q) && $q !== '') {
                yield ['celtic_questions', $q];
            }
        }
    }
}
