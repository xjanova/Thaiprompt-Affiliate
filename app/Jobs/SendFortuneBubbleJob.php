<?php

namespace App\Jobs;

use App\Models\FortuneTellingSetting;
use App\Services\FacebookWebhookService;
use App\Services\LineFortuneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 💬 SendFortuneBubbleJob — ส่งคำทำนายทีละกล่อง เว้นระยะเหมือนคนพิมพ์
 *
 * เจ้าของสั่ง (2026-08-28): "แยกกล่องบับเบิ้ลตอบ อย่าตอบยาว ๆ ค่อย ๆ ส่งห่างกัน
 * อย่างน้อย 5-10 วินาที แต่ละกล่องบับเบิ้ล"
 *
 * 🔗 **ต่อคิวตัวเอง ไม่ใช่ dispatch ทีเดียวหลายตัว** — เหตุผลสำคัญ:
 *   ถ้า dispatch 3 ตัวพร้อมกันแล้วให้ delay ต่างกัน ลำดับขึ้นกับ worker ล้วน ๆ
 *   worker 2 ตัว/คิวแน่น = กล่อง 3 แซงกล่อง 2 ⇒ **คำทำนายสลับหน้าหลัง**
 *   ลูกค้าที่จ่ายเงินมาอ่านแล้วงง และเราไม่มีทางรู้เลยเพราะไม่มี error
 *   ต่อคิวตัวเอง = กล่องถัดไปเกิดหลังกล่องก่อนหน้าส่งสำเร็จแล้วเท่านั้น เรียงแน่นอน
 *
 * 🛟 **ตาข่ายกันของหาย** — คนกลุ่มนี้ **จ่ายเงินมาแล้ว**
 *   กล่องแรกถูกส่งแบบ sync ที่ FortuneChannelManager (เพื่อคง markDelivered/redeliver เดิม)
 *   ⇒ ถ้าคิวตาย/worker ล่ม กล่อง 2..N หาย ลูกค้าได้คำทำนายครึ่งเดียว **โดยไม่มีใครรู้**
 *   failed() จึงเทที่เหลือรวมเป็นกล่องเดียวส่งไป — ครบดีกว่าสวย
 *
 * 🚨 **LINE รองรับแล้ว แต่สวิตช์ default ปิด** (`fortune_chat_bubbles_line`)
 *    LINE นับโควตา **ต่อ message object** แพลนปัจจุบัน 300 push/เดือน
 *    (หมดเกลี้ยงจนบอทเงียบทั้งช่องทางมาแล้ว 2026-08-26 — 429 ถูกมองเป็น rate limit ปิดปาก webhook)
 *    กล่องแรกยังใช้ replyToken (ฟรี) แต่กล่อง 2..N ที่วิ่งผ่าน job นี้ต้อง push ใบละ 1 โควตา
 *    ⇒ เจ้าของอัปแพลนเมื่อไหร่ ค่อยติ๊กเปิดในหลังบ้าน ไม่ต้อง deploy
 */
class SendFortuneBubbleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** ลูกค้ารออยู่ — retry เยอะ = ยิ่งช้า ยิ่งแย่ */
    public int $tries = 2;

    public int $timeout = 60;

    /**
     * @param  string  $platform  ตอนนี้รองรับ 'facebook' เท่านั้น
     * @param  array<int, string>  $bubbles  กล่องที่ "ยังไม่ได้ส่ง" (กล่องแรกส่ง sync ไปแล้ว)
     * @param  string|null  $tailMessage  กล่องปิดท้าย เช่น กล่องคำถามแนะนำ (ส่งหลังบับเบิ้ลหมด)
     * @param  array<int, array<string, mixed>>  $tailQuickReplies  ปุ่มของกล่องปิดท้าย
     * @param  int  $gapMin  ระยะห่างต่ำสุด (วินาที)
     * @param  int  $gapMax  ระยะห่างสูงสุด (วินาที)
     */
    public function __construct(
        public string $platform,
        public string $userId,
        public array $bubbles,
        public ?string $tailMessage = null,
        public array $tailQuickReplies = [],
        public int $gapMin = 5,
        public int $gapMax = 10,
        public ?int $readingId = null,
    ) {}

    public function handle(): void
    {
        if (! in_array($this->platform, ['facebook', 'line'], true)) {
            return;
        }

        $settings = FortuneTellingSetting::getSettings();

        if ($this->platform === 'line') {
            $this->handleLine($settings);

            return;
        }

        $fb = new FacebookWebhookService($settings);

        $bubble = array_shift($this->bubbles);

        if ($bubble !== null && trim($bubble) !== '') {
            // ยังมีกล่องถัดไป → ปล่อยจุดไข่ปลาค้างไว้ให้รู้สึกว่า "แม่หมอกำลังพิมพ์ต่อ"
            // กล่องสุดท้ายค่อยปิด (ปิดทันทีตอนยังมีต่อ = จุดไข่ปลากะพริบ ดูเหมือนบอทค้าง)
            try {
                $fb->sendTypingIndicator($this->userId, true);
            } catch (Throwable $e) {
                // ตัวช่วยล้วน ๆ — ล้มแล้วต้องไม่กันคำทำนาย
            }

            // allow_duplicate: คำทำนายบางช่วงอาจซ้ำถ้อยคำกับกล่องก่อนหน้าโดยธรรมชาติ
            // ห้ามให้ตัวกันทักทายซ้ำ (isThrottleableGreeting) กลืนกล่องกลางคำทำนายทิ้ง
            $fb->sendMessage($this->userId, $bubble, [
                'allow_duplicate' => true,
                'no_default_qr' => true,
            ]);
        }

        if ($this->bubbles !== []) {
            self::dispatch(
                $this->platform,
                $this->userId,
                $this->bubbles,
                $this->tailMessage,
                $this->tailQuickReplies,
                $this->gapMin,
                $this->gapMax,
                $this->readingId,
            )->delay(now()->addSeconds($this->gapSeconds()));

            return;
        }

        // หมดบับเบิ้ลแล้ว → ปิดจุดไข่ปลา + ส่งกล่องปิดท้าย (ถ้ามี)
        try {
            $fb->sendTypingIndicator($this->userId, false);
        } catch (Throwable $e) {
            // ignore
        }

        if ($this->tailMessage !== null && trim($this->tailMessage) !== '') {
            try {
                $fb->sendQuickReplies($this->userId, $this->tailMessage, $this->tailQuickReplies);
            } catch (Throwable $e) {
                Log::debug('💬 Bubble: กล่องปิดท้ายส่งไม่สำเร็จ (ไม่บล็อก)', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 💬 ฝั่ง LINE — ไม่มี replyToken แล้ว (ใช้ครั้งเดียว + หมดอายุ ~1 นาที) ⇒ push อย่างเดียว
     *
     * 🚨 push ใบละ 1 โควตา · แพลนนี้ 300/เดือน ⇒ เปิดเฉพาะเมื่อโควตาพร้อมจริง
     *    (สวิตช์ fortune_chat_bubbles_line default ปิด — ตรวจที่ FortuneChannelManager แล้ว)
     *
     * LINE ไม่มี typing indicator ให้ยิง จึงไม่มีท่อนนั้นเหมือนฝั่ง FB
     */
    private function handleLine(FortuneTellingSetting $settings): void
    {
        $line = new LineFortuneService($settings);

        $bubble = array_shift($this->bubbles);

        if ($bubble !== null && trim($bubble) !== '') {
            $line->sendMessage($this->userId, $bubble);
        }

        if ($this->bubbles !== []) {
            self::dispatch(
                $this->platform,
                $this->userId,
                $this->bubbles,
                $this->tailMessage,
                $this->tailQuickReplies,
                $this->gapMin,
                $this->gapMax,
                $this->readingId,
            )->delay(now()->addSeconds($this->gapSeconds()));

            return;
        }

        if ($this->tailMessage !== null && trim($this->tailMessage) !== '') {
            try {
                $line->sendMessage($this->userId, $this->tailMessage, [
                    'quick_replies' => $this->tailQuickReplies,
                ]);
            } catch (Throwable $e) {
                Log::debug('💬 Bubble LINE: กล่องปิดท้ายส่งไม่สำเร็จ (ไม่บล็อก)', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 🛟 คิวตายกลางทาง → เทที่เหลือรวมเป็นกล่องเดียว
     *
     * ลูกค้าจ่ายเงินมาแล้ว การได้คำทำนายครบแบบกล่องเดียว ดีกว่าได้ครึ่งเดียวแบบสวย ๆ
     */
    public function failed(?Throwable $e): void
    {
        Log::critical('💬 Bubble: ส่งไม่สำเร็จ — เทที่เหลือรวมเป็นกล่องเดียว', [
            'user_id' => $this->userId,
            'reading_id' => $this->readingId,
            'remaining' => count($this->bubbles),
            'error' => $e?->getMessage(),
        ]);

        if ($this->bubbles === [] && $this->tailMessage === null) {
            return;
        }

        try {
            $settings = FortuneTellingSetting::getSettings();

            $rest = trim(implode("\n\n", array_filter($this->bubbles, static fn ($b) => trim((string) $b) !== '')));

            if ($this->platform === 'line') {
                $line = new LineFortuneService($settings);

                if ($rest !== '') {
                    $line->sendMessage($this->userId, $rest);
                }

                if ($this->tailMessage !== null && trim($this->tailMessage) !== '') {
                    $line->sendMessage($this->userId, $this->tailMessage, [
                        'quick_replies' => $this->tailQuickReplies,
                    ]);
                }

                return;
            }

            $fb = new FacebookWebhookService($settings);

            if ($rest !== '') {
                $fb->sendMessage($this->userId, $rest, [
                    'allow_duplicate' => true,
                    'no_default_qr' => true,
                ]);
            }

            if ($this->tailMessage !== null && trim($this->tailMessage) !== '') {
                $fb->sendQuickReplies($this->userId, $this->tailMessage, $this->tailQuickReplies);
            }
        } catch (Throwable $inner) {
            Log::critical('💬 Bubble: ตาข่ายสุดท้ายก็ล้ม — ลูกค้าได้คำทำนายไม่ครบ', [
                'user_id' => $this->userId,
                'reading_id' => $this->readingId,
                'error' => $inner->getMessage(),
            ]);
        }
    }

    /** ระยะห่างแบบสุ่ม — เท่ากันเป๊ะทุกกล่อง = จับได้ว่าเป็นเครื่อง */
    private function gapSeconds(): int
    {
        $min = max(1, $this->gapMin);
        $max = max($min, $this->gapMax);

        return random_int($min, $max);
    }
}
