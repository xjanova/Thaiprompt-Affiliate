<?php

namespace App\Services\Fortune;

use App\Services\FortuneAIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 🌙 แชทแม่หมอจันทราของเว็บ จันทรา.online — ใช้ร่วมกันสองทางเข้า
 *
 *   - /api/v1/juntra/chat/mae-mor/*  ตัวตน = token ของลูกค้าที่เชื่อมบัญชีไว้ (แอพ + เว็บรุ่นเดิม)
 *   - /api/v1/juntra/server/chat/*   ตัวตน = เซิร์ฟเวอร์ของเว็บเอง (client_credentials) ลูกค้าทุกคนคุยได้
 *     ไม่ต้องล็อกอินผ่าน Thaiprompt ก่อน (เจ้าของสั่ง 2026-09-15: "ทุกคนที่ล็อกอินคุยได้")
 *
 * ประวัติแชทเก็บใน cache ตาม (เจ้าของห้อง, session) — ไม่มีการผสมข้อมูลข้ามลูกค้า:
 *   ทางแรกเจ้าของ = users.id ของ Thaiprompt (key เดิม "juntra:chat:{id}:{sid}" — session เก่ายังใช้ได้)
 *   ทางที่สองเจ้าของ = "jw{user_ref}" = id ลูกค้าฝั่งเว็บ
 *
 * เว็บคุยฟรีเหมือนบอทใน FB/LINE — "การทำนาย" คือสิ่งที่ลูกค้าเลือกซื้อ (เปิดไพ่ หักเครดิตวอลเลต)
 * ทางของเว็บจึงสั่งแม่หมอให้ "ไม่ทำนายเองในแชท" แต่ชวนเลือกแพ็กเกจ แล้วแปะป้าย [[OFFER:หมวด]]
 * ให้เว็บวาดการ์ดแพ็กเกจแทน (ป้ายถูกถอดออกก่อนถึงลูกค้าเสมอ)
 */
final class JuntraChatService
{
    public const SESSION_TTL_SEC = 60 * 60 * 6;   // 6 ชม.

    public const MAX_HISTORY = 12;

    public const GREETING = 'สวัสดีค่ะลูก แม่หมอจันทราอยู่ตรงนี้แล้ว · อยากปรึกษาเรื่องอะไรเป็นพิเศษวันนี้คะ?';

    /** คำตอบรองรับเมื่อ AI ไม่ตอบ — ทางเดิมเท่านั้น (ทางเซิร์ฟเวอร์ตอบ 503 ให้เว็บรู้ว่าไม่ใช่คำตอบจริง) */
    public const FILLER = 'ลูก... แม่หมอขอคิดอีกซักครู่นะคะ';

    public const OFFER_TOPICS = ['love', 'career', 'money', 'health', 'general'];

    private const SYSTEM_PROMPT = <<<'TXT'
คุณคือ "แม่หมอจันทรา" หมอดูทาโรต์ผู้อาวุโส อ่อนโยน ขลัง และอบอุ่น
- เรียกผู้ใช้ว่า "ลูก" เสมอ
- ใช้ภาษาไทยกระชับแต่ใส่ความรู้สึก ใช้คำว่า "แม่หมอเห็น..." "แม่หมอบอกว่า..."
- หลีกเลี่ยงการพยากรณ์ทางการแพทย์ที่ไม่ปลอดภัย / การยืนยัน 100% เรื่องโชคลาภหวย
- ตอบไม่เกิน 4-6 ประโยค ไม่ใช้ bullet points
- ลงท้ายด้วยกำลังใจสั้นๆ พร้อมอีโมจิ ✨ หรือ ☾ บางครั้ง
TXT;

    /** เพิ่มเฉพาะทางของเว็บ — คุยฟรี แต่การทำนายเป็นของที่ลูกค้าเลือกเปิดไพ่ */
    private const WEB_OFFER_RULES = <<<'TXT'

กติกาของห้องแชทนี้ (เว็บ จันทรา.online):
- ห้องนี้คุยฟรี — ถามไถ่ ปลอบใจ ให้กำลังใจ และเล่าความหมายไพ่หรือหลักความเชื่อทั่วไปได้เต็มที่
- ถ้าลูกขอให้ "ทำนาย / ดูดวง / เปิดไพ่ / บอกอนาคต / ถามว่าจะเกิดอะไรขึ้นกับเขา" ห้ามทำนายเองในแชท
  ให้ตอบสั้น ๆ 1-2 ประโยค อบอุ่น ว่าแม่หมอจะเปิดไพ่ให้ ชวนลูกเลือกแบบการดูดวงที่แม่หมอเตรียมไว้ด้านล่าง
  แล้วปิดท้ายคำตอบด้วยป้าย [[OFFER:หมวด]] หมวดเป็นหนึ่งใน love, career, money, health, general
- ถ้าไม่ได้ขอให้ทำนาย ห้ามใส่ป้ายนี้
TXT;

    public function __construct(private FortuneAIService $ai) {}

    /** @return array{session_id:string,greeting:string} */
    public function start(string $owner): array
    {
        $sessionId = (string) Str::uuid();
        Cache::put($this->key($owner, $sessionId), [
            ['role' => 'assistant', 'text' => self::GREETING],
        ], self::SESSION_TTL_SEC);

        return ['session_id' => $sessionId, 'greeting' => self::GREETING];
    }

    /** @return array<int,array{role:string,text:string}> */
    public function history(string $owner, string $sessionId): array
    {
        return (array) Cache::get($this->key($owner, $sessionId), []);
    }

    /**
     * @param  array{user_id?:int|string,customer_name?:string}  $customer  ป้ายใน log การใช้ AI (warroom)
     * @param  bool  $webOffers  ทางของเว็บ — ห้ามทำนายเอง ให้ชวนเปิดไพ่พร้อมป้าย [[OFFER:หมวด]]
     * @param  bool  $fillerOnEmpty  ทางเดิม: AI เงียบ = ตอบข้อความรองรับ (คงพฤติกรรมเดิมของแอพ)
     * @return array{reply:string,provider:?string,kind:string,offer_topic:?string}
     *
     * @throws RuntimeException AI ไม่ได้ตอบอะไร และไม่ได้ขอข้อความรองรับ
     */
    public function send(string $owner, string $sessionId, string $text, array $customer = [], bool $webOffers = false, bool $fillerOnEmpty = false): array
    {
        $history = $this->history($owner, $sessionId);
        $history[] = ['role' => 'user', 'text' => $text];

        // 🪬 (2026-09-05 owner) ด่านกัน "ใช้แม่หมอเป็น AI ฟรี" — ขอบทสวด/สูตร/แปล/สรุป/เขียนให้
        $scopeHit = FortuneScopeGuard::detectExtraction($text);
        if ($scopeHit !== null) {
            $reply = FortuneScopeGuard::deflect($scopeHit);
            $this->remember($owner, $sessionId, $history, $reply);

            return ['reply' => $reply, 'provider' => 'guard', 'kind' => 'guard', 'offer_topic' => null];
        }

        $contextLines = collect($history)
            ->take(-self::MAX_HISTORY)
            ->map(fn ($m) => ($m['role'] === 'user' ? 'ลูก: ' : 'แม่หมอ: ').$m['text'])
            ->implode("\n");

        if ($customer !== []) {
            $this->ai->withCustomerContext($customer);
        }

        $result = $this->ai->chatWithCustomSystemPrompt(
            systemMessage: self::SYSTEM_PROMPT.($webOffers ? self::WEB_OFFER_RULES : ''),
            userMessage: "บทสนทนาที่ผ่านมา:\n{$contextLines}\n\nกรุณาตอบ '{$text}' ด้วยน้ำเสียงแม่หมอจันทรา",
            config: ['temperature' => 0.85, 'max_tokens' => 350],
            // 🛡 ด่านกันเจลเบรค — ตรวจ "ข้อความดิบของลูกค้า" เท่านั้น ห้ามตรวจ userMessage ที่ห่อไว้
            guardText: $text,
        );

        $reply = trim((string) ($result['response'] ?? ''));
        if ($reply === '') {
            if (! $fillerOnEmpty) {
                throw new RuntimeException('AI returned an empty reply');
            }
            $reply = self::FILLER;
        }

        // ป้าย [[OFFER:หมวด]] — ถอดออกเสมอ (ทางไหนก็ห้ามให้ลูกค้าเห็นป้ายดิบ)
        $topic = null;
        if (preg_match('/\[\[\s*OFFER\s*:\s*([a-z]+)\s*\]\]/iu', $reply, $m)) {
            $candidate = strtolower($m[1]);
            $topic = in_array($candidate, self::OFFER_TOPICS, true) ? $candidate : 'general';
            $reply = trim((string) preg_replace('/\s*\[\[\s*OFFER\s*:[^\]]*\]\]\s*/iu', ' ', $reply));
        }
        if (! $webOffers) {
            $topic = null;
        }

        $this->remember($owner, $sessionId, $history, $reply);

        return [
            'reply' => $reply,
            'provider' => $result['provider'] ?? null,
            'kind' => $topic !== null ? 'offer' : 'reply',
            'offer_topic' => $topic,
        ];
    }

    private function remember(string $owner, string $sessionId, array $history, string $reply): void
    {
        $history[] = ['role' => 'assistant', 'text' => $reply];
        Cache::put($this->key($owner, $sessionId), array_slice($history, -self::MAX_HISTORY * 2), self::SESSION_TTL_SEC);
    }

    private function key(string $owner, string $sessionId): string
    {
        return "juntra:chat:{$owner}:{$sessionId}";
    }
}
