<?php

namespace App\Services\Fortune;

use App\Jobs\RetryFailedChatReplyJob;
use App\Models\FortuneSavedQuestion;
use App\Services\FortuneChannelManager;
use App\Services\FortuneConversationService;
use App\Support\SafeLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🌙 (2026-10-30) กู้เทิร์นแชทที่ AI ตอบไม่ทัน — ลูกค้าต้องไม่เจอความเงียบ
 *
 * ## ทำไมต้องมี
 * prod ราว 5% ของแชทจบด้วย AI timeout → ระบบเก็บคำถามให้แอดมินแต่ลูกค้าไม่ได้อะไรกลับไปเลย
 *
 * ## แต่ละแพลตฟอร์มทำไม่เหมือนกัน เพราะต้นทุนการส่งไม่เหมือนกัน
 * - Facebook/Telegram ส่งได้ตรง: กล่องแจ้ง → ลองใหม่ด้วย RetryFailedChatReplyJob → ครบรอบแล้วใช้กล่อง fallback เดิม
 * - LINE: คำตอบตามหลังไม่มีทางส่งฟรี (ไม่มี replyToken) ⇒ ไม่มี retry job
 *   ใช้ token ของเทิร์นนี้ส่งกล่องแจ้งพร้อมปุ่ม ลูกค้ากดหรือทักมาเมื่อไร ได้ token ใหม่ แล้วตอบรวมคำถามค้างให้
 *   ไม่มี token = ไม่ส่งอะไร รอรอบหน้า (ห้าม push กล่องแจ้ง — LINE_MESSAGING_RULES ข้อ 1)
 *
 * ## กันตอบซ้ำ
 * HTTP ที่ timeout ถูกตัดไปแล้ว คำตอบเดิมมาทีหลังไม่ได้ เหลือแค่ผู้แย่งคำถามค้างกันเอง
 * ⇒ ทุกคนต้องผ่าน claim() ซึ่งมีผู้ชนะคนเดียว
 */
class ChatReplyRecovery
{
    /** เวลารอก่อนลองใหม่ (วินาที) แต่ละรอบ — รอบเกินนี้ถือว่ายอมแพ้ */
    public const RETRY_DELAYS = [45, 120];

    /** งบเวลารอบแรกของเส้น job: 3 คีย์ คีย์ละ 8 วิ · เดิมแย่สุด 30 วิ */
    public const FIRST_BUDGET_MS = 24000;

    /** งบเวลารอบกู้: 2 คีย์ — worker มีตัวเดียว ห้ามให้รอบกู้ไปขวางแชทของคนอื่นนาน */
    public const RETRY_BUDGET_MS = 16000;

    /** ข้อความของปุ่มบน LINE — ต้องไม่มีคำที่ไม่เข้า buffer (ดู shouldBufferChatMessage) */
    public const LINE_RETRY_TEXT = 'ขอคำตอบจากแม่หมอ';

    protected const LINE_RETRY_LABEL = 'รับคำตอบจากแม่หมอ';

    protected const HOLD_MESSAGE = '🌙 แม่หมอได้รับข้อความแล้วนะคะ ตอนนี้กำลังตั้งจิตดูให้อยู่ รอสักครู่นะคะ เดี๋ยวแม่หมอตอบกลับค่ะ 🙏';

    protected const LINE_HOLD_MESSAGE = '🌙 แม่หมอได้รับข้อความแล้วนะคะ ขอเวลาตั้งจิตสักครู่ แล้วกดปุ่มด้านล่างหรือทักแม่หมอมาอีกครั้ง แม่หมอจะตอบให้เลยค่ะ 🙏';

    /** อายุคำถามค้าง — LINE รอลูกค้าทักกลับ จึงต้องอยู่นานกว่ารอบ retry */
    protected const PENDING_TTL_SECONDS = 21600;

    protected const CLAIM_TTL_SECONDS = 300;

    /** กล่องแจ้งบน FB/Telegram เป็นระบบส่งเอง — จำกัดความถี่ ไม่ให้ลูกค้าเห็นซ้ำ ๆ ตอน AI ล่มนาน */
    protected const HOLD_COOLDOWN_SECONDS = 600;

    protected function key(string $platform, string $userId): string
    {
        return "fortune:chat_pending:{$platform}:{$userId}";
    }

    /**
     * จัดการเทิร์นที่ AI ล้มหมดงบ
     *
     * @param  array{text:string,count:int,saved_question_ids:array<int,int>}  $turn
     * @param  int  $attempt  รอบที่ล้มไปแล้ว (0 = รอบแรกจาก ProcessBufferedChatMessageJob)
     */
    public function recover(FortuneConversationService $service, string $platform, string $userId, array $turn, int $attempt = 0): void
    {
        if ($platform === 'line') {
            $this->recoverLine($service, $userId, $turn);

            return;
        }

        if ($attempt >= count(self::RETRY_DELAYS) || $service->isAiOutageActive()) {
            $this->giveUp($service, $platform, $userId, $turn);

            return;
        }

        $id = $this->remember($platform, $userId, $turn);
        $this->sendHold($platform, $userId);

        RetryFailedChatReplyJob::dispatch($platform, $userId, $id, $attempt + 1)
            ->delay(now()->addSeconds(self::RETRY_DELAYS[$attempt]));
    }

    /**
     * หยิบคำถามค้างออกมาตอบ — มีผู้ชนะคนเดียว
     *
     * @param  string|null  $id  ใส่เมื่อเป็น retry job: id ไม่ตรง = คำถามนี้ถูกตอบหรือถูกรวมไปแล้ว
     * @return array|null null = ไม่มีของให้ทำ
     */
    public function claim(string $platform, string $userId, ?string $id = null): ?array
    {
        $pending = Cache::get($this->key($platform, $userId));

        if (! is_array($pending) || ($id !== null && ($pending['id'] ?? null) !== $id)) {
            return null;
        }

        if (! Cache::add("fortune:chat_pending_claim:{$pending['id']}", true, self::CLAIM_TTL_SECONDS)) {
            return null;
        }

        Cache::forget($this->key($platform, $userId));

        return $pending;
    }

    /**
     * รวมคำถามค้างกับข้อความใหม่ — ข้อความจากปุ่มไม่ใช่คำถาม จึงตัดทิ้ง
     */
    public function mergeText(array $pending, string $newText): string
    {
        $new = trim(str_replace(self::LINE_RETRY_TEXT, '', $newText));

        return $new === '' ? (string) $pending['text'] : $pending['text']."\n".$new;
    }

    /**
     * แอดมินตอบคำถามนี้ไปแล้วหรือยัง — ตอบแล้วห้ามให้ AI ตอบซ้ำ
     */
    public function adminAlreadyAnswered(array $pending): bool
    {
        $ids = $pending['saved_question_ids'] ?? [];

        return $ids !== [] && FortuneSavedQuestion::whereIn('id', $ids)->where('is_replied', true)->exists();
    }

    /**
     * ปิดรายการรอแอดมินหลังส่งคำตอบถึงลูกค้าสำเร็จจริง
     */
    public function markAnswered(array $pending, string $answer): void
    {
        try {
            FortuneSavedQuestion::whereIn('id', $pending['saved_question_ids'] ?? [])
                ->where('is_replied', false)
                ->get()
                ->each(fn (FortuneSavedQuestion $question) => $question->markAnsweredByAi($answer));
        } catch (\Throwable $e) {
            // ลูกค้าได้คำตอบแล้ว รายการค้างแค่ทำให้แอดมินเห็นเกิน ไม่กระทบลูกค้า
            Log::warning('ChatReplyRecovery: ปิดรายการรอแอดมินไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    protected function recoverLine(FortuneConversationService $service, string $userId, array $turn): void
    {
        $token = ReplyTokenVault::take('line', $userId);

        $this->remember('line', $userId, $turn);

        if ($token === null) {
            Log::info('ChatReplyRecovery: LINE ไม่มี replyToken — เก็บคำถามรอลูกค้าทักกลับ ไม่ push', ['user_id' => $userId]);

            return;
        }

        $response = $service->isAiOutageActive()
            ? $service->aiChatGiveUpResponse($userId, $turn['text'])
            : $this->holdResponse('line');

        $this->deliver('line', $userId, $response, ['reply_token' => $token]);
    }

    protected function giveUp(FortuneConversationService $service, string $platform, string $userId, array $turn): void
    {
        Log::warning('ChatReplyRecovery: กู้แชทไม่สำเร็จ — ใช้กล่อง fallback', [
            'platform' => $platform,
            'user_id' => $userId,
        ]);

        $this->deliver($platform, $userId, $service->aiChatGiveUpResponse($userId, $turn['text']));
    }

    /**
     * เก็บคำถามค้าง — ถ้ามีของเดิมอยู่ให้รวมกัน คำถามเก่าจะได้ไม่หาย
     *
     * @return string id ของคำถามค้าง (retry job ใช้ยืนยันว่ายังเป็นก้อนเดิม)
     */
    protected function remember(string $platform, string $userId, array $turn): string
    {
        $existing = Cache::get($this->key($platform, $userId));
        $existing = is_array($existing) ? $existing : [];
        $id = (string) Str::uuid();

        Cache::put($this->key($platform, $userId), [
            'id' => $id,
            'text' => isset($existing['text']) ? $existing['text']."\n".$turn['text'] : $turn['text'],
            'count' => (int) ($existing['count'] ?? 0) + (int) ($turn['count'] ?? 1),
            'saved_question_ids' => array_values(array_unique(array_merge(
                $existing['saved_question_ids'] ?? [],
                $turn['saved_question_ids'] ?? []
            ))),
            'at' => now()->timestamp,
        ], self::PENDING_TTL_SECONDS);

        return $id;
    }

    protected function sendHold(string $platform, string $userId): void
    {
        if (! Cache::add("fortune:chat_hold_sent:{$platform}:{$userId}", true, self::HOLD_COOLDOWN_SECONDS)) {
            return;
        }

        $this->deliver($platform, $userId, $this->holdResponse($platform));
    }

    protected function holdResponse(string $platform): array
    {
        if ($platform === 'line') {
            return [
                'action' => 'ai_chat_hold',
                'message' => self::LINE_HOLD_MESSAGE,
                'reading' => null,
                'quick_reply_options' => [
                    ['label' => self::LINE_RETRY_LABEL, 'text' => self::LINE_RETRY_TEXT],
                ],
            ];
        }

        return ['action' => 'ai_chat_hold', 'message' => self::HOLD_MESSAGE, 'reading' => null];
    }

    /**
     * ส่งแบบไม่ให้ล้มกลางทาง — กล่องส่งไม่ออกต้องไม่ขวางการตั้ง retry
     */
    protected function deliver(string $platform, string $userId, array $response, array $extra = []): void
    {
        try {
            app(FortuneChannelManager::class)->sendResponse($platform, $userId, $response, $extra);
        } catch (\Throwable $e) {
            Log::warning('ChatReplyRecovery: ส่งกล่องไม่สำเร็จ', [
                'platform' => $platform,
                'user_id' => $userId,
                'action' => $response['action'] ?? null,
                'error' => SafeLog::redactSecrets($e->getMessage()),   // URL ของ Telegram มี bot token
            ]);
        }
    }
}
