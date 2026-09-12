<?php

namespace Tests\Unit\Services;

use App\Console\Commands\FortuneCelticRedeliver;
use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\LineFortuneWebhookController;
use App\Jobs\ProcessBufferedCelticMessageJob;
use App\Models\FortuneReading;
use App\Services\FortuneConversationService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * กันบั๊ก 3 ตัวจากบิล FTU-260912-J8005 (reading 12996, Celtic 99 ทาง FB) กลับมา
 *
 * 1) คำตอบรูปถูกส่งซ้ำ ~90 วิถัดมา พร้อมป้าย «[IMAGE_ATTACHED]» (FB ตั้งแต่ 1 ส.ค. ซ้ำ 13/14 รูป)
 *    เพราะเส้น vision ส่งเองแต่ไม่เคย mark delivered → fortune:celtic-redeliver ยิงซ้ำ
 * 2) ปุ่มคำถามแนะนำตาย / กดระหว่าง AI ตอบถูกทิ้ง / "ข้อ1ก่อน" "อยากรู้ทั้ง 2 ข้อ" ไม่ถูกจับ
 *    ⇒ ลูกค้าเสีย 8 จาก 15 นาที และคำถาม "เขามีคนอื่นหรือยัง" ไม่เคยถูกตอบ
 * 3) คำชม "ทำนายได้แม่นดีค่ะ" → เด้งเมนูราคา 39/99 + nudge (ด่านจับคำขึ้นต้น "ทำนาย")
 *
 * ไม่แตะ DB — สร้าง service ด้วย newInstanceWithoutConstructor (constructor อ่าน settings จาก DB)
 */
class CelticJ8005RegressionTest extends TestCase
{
    // ─── 3) คำชม / จ่ายไม่ได้ ≠ ขอดูดวง ─────────────────────────────────────

    public function test_คำชมหลังได้คำทำนาย_ไม่เปิดเมนูราคา(): void
    {
        foreach (['ทำนายได้แม่นดีค่ะ', 'ทำนายแม่นมากค่ะ', 'ทำนายตรงเป๊ะเลย', 'แม่หมอแม่นมาก', 'ไพ่ตรงมากค่ะ'] as $praise) {
            $this->assertFalse($this->isGenericRequest($praise), "คำชม \"{$praise}\" ต้องไม่เปิดเมนูราคา");
        }
    }

    public function test_บอกว่าไม่มีเงินหรือขอไว้ก่อน_ไม่เปิดเมนูราคา(): void
    {
        // ข้อความจริงจาก log prod 7 วันก่อนแก้ (สะกดตามต้นฉบับ)
        foreach ([
            'ลูกอยากดูคะแต่ลูกไม่เงินคะแม่',
            'ตอนนี้หนูไม่มีตังค์เลยนะค่ะแม่หมอหนูอยากดูดวงมากเลยค่ะ',
            'วันี้หนูจะจ่ายค้าข้าวสารค่ะทั้งห้าร้อยบาทค่ะเลยไม่มีพอจะดูดวง',
            'หนูอยากดูดวงค่ะแต่หนูติดจัดเรื้องเงินค่ะแม่หมอ',
            'ขอดูรอบหน้าค่ะ',
            'แต่อยากดูมากค่ะใว้พร้อมก่อนค่อยเข้ามาดูค่ะ',
            'ว่างแล้วจะติดต่อขอเปิดการดูดวงนะคะ',
            'ลูกมีเงินลูกพร้อมดูดวงคะตอนนี้ลูกขอเวลาพักรักษาตัวเองก่อนคะ',
            'อยากดูดวงแต่ไม่มีเงินค่ะ',                   // ขึ้นต้นด้วยคำขอ แต่มีคำหักมุม
        ] as $text) {
            $this->assertFalse($this->isGenericRequest($text), "\"{$text}\" ต้องไม่เปิดเมนูราคา");
        }
    }

    public function test_คำขอดูดวงจริงยังเปิดเมนูเหมือนเดิม(): void
    {
        foreach ([
            'ดูดวง', 'ดูดวงค่ะ', 'ทำนายดวง', 'อยากดูดวงค่ะ',
            'ดูดวงเรื่องเงินค่ะ',                       // หัวข้อการเงิน ≠ ไม่มีเงิน
            'ดูดวงแม่นๆ',                               // ขอให้ดูแม่น ๆ ไม่ใช่คำชม
            'เพื่อนบอกว่าทำนายแม่นมาก อยากดูบ้าง',       // ชมแต่ขอด้วย = คำขอ
            // จากรีวิวก่อนพุช — ขึ้นต้นด้วยคำขอ = คำขอ แม้จะมีคำชม/คำเรื่องเงินตามมา
            'ดูดวงหน่อยค่ะ เพื่อนบอกว่าแม่นมาก',
            'ดูดวงให้หน่อย ได้ยินว่าแม่นมาก',
            'ดูดวงตรงนี้เลยค่ะ',
            'ทำนายตรงนี้เลยค่ะ',                         // "ตรงนี้" = ที่นี่ ไม่ใช่ "ตรง" ที่แปลว่าแม่น
            'ดูดวงการเงินค่ะ ทำไมเงินไม่พอใช้',
            'ขอดูดวงเรื่องหนี้ กลัวหมดตัว',
            'ขอดูดวงสอบครั้งหน้าค่ะ',
        ] as $text) {
            $this->assertTrue($this->isGenericRequest($text), "\"{$text}\" ต้องยังเปิดเมนูเหมือนเดิม");
        }
    }

    public function test_ปลุกจากโหมดเงียบหลังลายังใช้ถ้อยคำเดิม(): void
    {
        // คนที่กลับมาพูดถึงดวงต้องได้คำตอบ — แค่ไม่ถูกยื่นเมนูราคาใส่
        $m = new ReflectionMethod(FortuneConversationService::class, 'matchesFortuneRequestWording');

        $this->assertTrue($m->invoke($this->service(), 'ทำนายได้แม่นดีค่ะ'));
        $this->assertTrue($m->invoke($this->service(), 'ลูกอยากดูคะแต่ลูกไม่เงินคะแม่'));
    }

    // ─── 2) เลือกข้อจากกล่องคำถามแนะนำ ──────────────────────────────────────

    public function test_ข้อความเลือกข้อแบบต่าง_ๆ_ได้เลขถูก(): void
    {
        $cases = [
            '1' => '1', '2' => '2', '1️⃣' => '1', '๒' => '2',
            'ข้อ1' => '1', 'ข้อ 2' => '2', 'ข้อที่ 2' => '2',
            'เอาข้อ 2 ค่ะ' => '2', 'ข้อ1ก่อน' => '1', 'ข้อ 2 ค่ะ' => '2',
        ];

        foreach ($cases as $text => $expected) {
            $this->assertSame($expected, $this->pickNumber((string) $text), "\"{$text}\"");
        }
    }

    public function test_คำถามจริงหรือเลขอื่น_ไม่ถือเป็นการเลือกข้อ(): void
    {
        // "2 ค่ะ" = อาจเป็นคำตอบของคำถามที่แม่หมอถามกลับ ("มีลูกกี่คนคะ") — ต้องมีคำว่า "ข้อ"
        foreach (['ข้อ 2 จะเป็นยังไง', '12', 'ข้อ 3', 'เขามีคนอื่นไหม', '1 บวก 2', '2 ค่ะ', '2คนค่ะ'] as $text) {
            $this->assertNull($this->pickNumber($text), "\"{$text}\" ต้องไม่ถูกตีเป็นการเลือกข้อ");
        }
    }

    public function test_อยากรู้ทั้ง_2_ข้อ_ถือเป็นการเลือกทั้งสองข้อ(): void
    {
        $m = new ReflectionMethod(FortuneConversationService::class, 'looksLikeCelticPickBoth');

        foreach (['อยากรู้ทั้ง 2 ข้อเลยค่ะ', 'ทั้งสองข้อ', '1และ2', 'ขอทั้งคู่ค่ะ', 'เอา 2 ข้อ'] as $text) {
            $this->assertTrue($m->invoke($this->service(), $text), "\"{$text}\"");
        }
        // คำถามรักที่มี "ทั้งคู่/ทั้งสอง" ต้องไม่ถูกไฮแจ็ก
        foreach (['ทั้งคู่จะรักกันไหม', 'เราทั้งสองจะรอดไหม'] as $text) {
            $this->assertFalse($m->invoke($this->service(), $text), "\"{$text}\"");
        }
    }

    public function test_เลือกทั้งสองข้อหลังตอบข้อ1ไปแล้ว_ได้แค่ข้อที่เหลือ(): void
    {
        $probe = $this->probe(
            ['เขาจะพัฒนาความสัมพันธ์กับลูกไหม', 'เขามีคนอื่นหรือยัง'],
            ['อยากรู้ว่าผู้ชายคนนี้จริงใจกับลูกไหม', 'เขาจะพัฒนาความสัมพันธ์กับลูกไหม'],
        );
        $m = new ReflectionMethod($probe, 'resolveCelticSuggestionPickBoth');

        $this->assertSame(
            ['answer' => 'เขามีคนอื่นหรือยัง', 'carry' => null],
            $m->invoke($probe, new FortuneReading, 'อยากรู้ทั้ง 2 ข้อเลยค่ะ'),
            'ข้อ 1 ถูกตอบไปแล้ว — ต้องตอบแค่ข้อ 2 ไม่ใช่ตอบข้อ 1 ซ้ำ'
        );
    }

    public function test_เลือกทั้งสองข้อตอนยังไม่ได้ตอบสักข้อ_คงพฤติกรรมเดิม(): void
    {
        $probe = $this->probe(['ก', 'ข'], []);
        $m = new ReflectionMethod($probe, 'resolveCelticSuggestionPickBoth');

        $this->assertSame(['answer' => 'ก', 'carry' => 'ข'], $m->invoke($probe, new FortuneReading, 'ทั้งสองข้อ'));
    }

    public function test_กดเลขข้อที่ตอบไปแล้ว_ชี้ขึ้นไปและบอกข้อที่เหลือ_ไม่ตอบซ้ำ(): void
    {
        $probe = $this->probe(
            ['เขาจะพัฒนาความสัมพันธ์กับลูกไหม', 'เขามีคนอื่นหรือยัง'],
            ['เขาจะพัฒนาความสัมพันธ์กับลูกไหม'],
        );
        $res = (new ReflectionMethod($probe, 'celticAlreadyAnsweredPickResponse'))->invoke($probe, new FortuneReading);

        $this->assertSame('celtic_invite_question', $res['action'], 'ต้องไม่ใช่เส้นถาม AI (ไม่กินสิทธิ์)');
        $this->assertStringContainsString('2️⃣ เขามีคนอื่นหรือยัง', $res['message'], 'เลขต้องตรงกับปุ่มเดิม');
        $this->assertStringNotContainsString('1️⃣', $res['message']);

        // ต่อสายแล้วจริงในเส้นรับข้อความ
        $src = $this->methodSource(FortuneConversationService::class, 'handleCelticAwaitingQuestion');
        $this->assertStringContainsString('celticAlreadyAnsweredPickResponse(', $src);
    }

    public function test_คำตอบที่ไม่มีคำถามแนะนำใหม่_ต้องไม่ล้างชุดเดิม(): void
    {
        // เดิม `} else { forgetCelticSuggestions }` ⇒ ปุ่ม 2 ของกล่องเดิมบนจอลูกค้าตาย
        $src = $this->methodSource(FortuneConversationService::class, 'finalizeCelticAnswer');

        $this->assertStringNotContainsString(
            "} else {\n            \$this->forgetCelticSuggestions(\$reading);",
            $src,
            'ห้ามล้างคำถามแนะนำชุดเดิมเมื่อคำตอบใหม่ไม่มีคำถามแนะนำ'
        );
        $this->assertStringContainsString(
            '} elseif ($remainingQ !== null && $remainingQ <= 0) {',
            $src,
            'ล้างได้เฉพาะตอนครบโควตาคำถาม'
        );
    }

    public function test_กดปุ่มระหว่างแม่หมอกำลังตอบ_ต่อสายเข้าคิว_ไม่ทิ้ง(): void
    {
        $guard = $this->methodSource(FortuneConversationService::class, 'processMessage');
        $this->assertStringContainsString('queueCelticPickDuringGeneration(', $guard, 'ด่าน in-prediction ต้องเรียกเข้าคิวก่อน silent_skip');

        $queue = $this->methodSource(FortuneConversationService::class, 'queueCelticPickDuringGeneration');
        $this->assertStringContainsString('->waitForIdle()', $queue, 'ต้องรอข้อก่อนหน้าส่งครบ ไม่งั้นแทรกกลางบับเบิ้ล');
    }

    public function test_job_โหมดรอคิว_ค่าเริ่มต้นปิด_และทนการ_unserialize(): void
    {
        // job ที่ค้างในคิวตอน deploy ถูก unserialize โดยไม่เรียก constructor
        $job = (new ReflectionClass(ProcessBufferedCelticMessageJob::class))->newInstanceWithoutConstructor();

        $this->assertFalse($job->waitForIdle);
        $this->assertSame(0, $job->idleWaits);
        $this->assertTrue((new ProcessBufferedCelticMessageJob(1, 'facebook', 'u', 10))->waitForIdle()->waitForIdle);
    }

    public function test_job_รอเสมอเมื่อข้อก่อนหน้ายัง_generating_ไม่ว่าจะนานแค่ไหน(): void
    {
        // ฝืนตอบระหว่าง generating = ชนเลขลำดับคำถาม → ลูกค้าได้ "พิมพ์คำถามเดิมส่งมาอีกครั้ง"
        $r = new FortuneReading;
        $r->conversation_status = FortuneReading::STATUS_CELTIC_GENERATING;

        $this->assertTrue(ProcessBufferedCelticMessageJob::previousAnswerInFlight($r));
    }

    public function test_job_รอคิว_ห้ามเขียนลง_reading_และ_cron_กู้ต้องไม่แย่งตอบกลางบับเบิ้ล(): void
    {
        // setConversationState() เขียน JSON ทั้งก้อนจากสำเนาเก่า = ทับ bubble_pending/pending_q ที่ job อื่นแก้
        $handle = $this->methodSource(ProcessBufferedCelticMessageJob::class, 'handle');
        $this->assertStringNotContainsString('->setConversationState(', $handle);

        $recover = $this->methodSource(\App\Console\Commands\FortuneCelticAnswerRecover::class, 'handle');
        $this->assertStringContainsString('ProcessBufferedCelticMessageJob::previousAnswerInFlight(', $recover);

        // ตอนกดระหว่าง generating ต้องจดสำรองแบบไม่แตะ updated_at / ไม่ทับคีย์อื่น
        $queue = $this->methodSource(FortuneConversationService::class, 'queueCelticPickDuringGeneration');
        $this->assertStringContainsString('rememberCelticPendingQuietly(', $queue);
        $this->assertStringNotContainsString('rememberPendingProSessionQuestion(', $queue);
    }

    // ─── 1) คำตอบรูปส่งซ้ำ ────────────────────────────────────────────────

    public function test_ป้ายตามส่งคำตอบ_ไม่โชว์_marker_ภายใน(): void
    {
        $cmd = (new ReflectionClass(FortuneCelticRedeliver::class))->newInstanceWithoutConstructor();
        $label = new ReflectionMethod($cmd, 'answerLabel');

        $this->assertSame("↩️ ตอบคำถาม: «📸 รูปที่ส่งมา»\n\n", $label->invoke($cmd, '[IMAGE_ATTACHED]'));
        $this->assertSame("↩️ ตอบคำถาม: «📸 รูปที่ส่งมา — คนนี้ใช่ไหม»\n\n", $label->invoke($cmd, '[IMAGE_ATTACHED] คนนี้ใช่ไหม'));
        $this->assertSame("↩️ ตอบคำถาม: «เขาจริงใจไหม»\n\n", $label->invoke($cmd, 'เขาจริงใจไหม'));
        $this->assertSame('', $label->invoke($cmd, '   '));
    }

    public function test_เส้นอ่านรูปทั้งเฟซบุ๊กและไลน์_mark_delivered_หลังส่งสำเร็จ(): void
    {
        foreach ([FacebookWebhookController::class, LineFortuneWebhookController::class] as $class) {
            $src = $this->methodSource($class, 'handleCelticVisionImage');
            $this->assertStringContainsString('->markDelivered()', $src, "{$class}: ไม่ mark = cron ส่งคำตอบรูปซ้ำ");
            $this->assertMatchesRegularExpression('/if \(\$sent && /', $src, "{$class}: mark ต้องขึ้นกับผลส่งจริง");
        }

        // LINE: คำตอบต้องลอง reply ก่อน (ฟรี) — กล่อง "กำลังดูภาพ" เดิมกิน token ทำให้ต้อง push
        $line = $this->methodSource(LineFortuneWebhookController::class, 'handleCelticVisionImage');
        $this->assertStringContainsString('showLoadingAnimation(', $line);
        $this->assertStringNotContainsString('$this->lineService->sendMessage(', $line, 'ห้าม push ตรง — ใช้ sendMessageWithReplyFallback');
    }

    // ─── helpers ─────────────────────────────────────────────────────────

    private function service(): FortuneConversationService
    {
        return (new ReflectionClass(FortuneConversationService::class))->newInstanceWithoutConstructor();
    }

    private function isGenericRequest(string $text): bool
    {
        return (new ReflectionMethod(FortuneConversationService::class, 'isGenericFortuneRequest'))
            ->invoke($this->service(), $text);
    }

    private function pickNumber(string $text): ?string
    {
        return (new ReflectionMethod(FortuneConversationService::class, 'celticPickNumber'))
            ->invoke($this->service(), $text);
    }

    /**
     * service ที่ป้อนชุดคำถามแนะนำ + คำถามที่ถามไปแล้วเองได้ (แทนการอ่าน cache/DB)
     *
     * @param  array<int,string>  $suggestions
     * @param  array<int,string>  $asked
     */
    private function probe(array $suggestions, array $asked): CelticJ8005SuggestionProbe
    {
        $probe = (new ReflectionClass(CelticJ8005SuggestionProbe::class))->newInstanceWithoutConstructor();
        $probe->suggestions = $suggestions;
        $probe->asked = $asked;

        return $probe;
    }

    private function methodSource(string $class, string $method): string
    {
        $m = new ReflectionMethod($class, $method);
        $lines = file((string) $m->getFileName());

        return implode('', array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1));
    }
}

/**
 * ตัวแทน service สำหรับเทสต์ — ตัดการอ่าน cache/DB ออก เหลือแต่ตรรกะเลือกข้อ
 */
class CelticJ8005SuggestionProbe extends FortuneConversationService
{
    /** @var array<int,string> */
    public array $suggestions = [];

    /** @var array<int,string> */
    public array $asked = [];

    protected function loadCelticSuggestions(FortuneReading $reading): ?array
    {
        return $this->suggestions === [] ? null : $this->suggestions;
    }

    protected function celticAskedQuestionTexts(FortuneReading $reading): array
    {
        return array_map(fn ($q) => $this->normalizeCelticQuestionText($q), $this->asked);
    }
}
