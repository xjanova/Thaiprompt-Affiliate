<?php

namespace App\Services\Fortune;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\FortuneWebHandoff;
use App\Models\JuntraAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 🌙 (2026-11-03 FB web-only) ความต่อเนื่องของแชท: ลูกค้าที่ย้ายจากแชทเพจ FB มาห้องแม่หมอบนเว็บจันทรา
 *
 * เจ้าของสั่ง: "แม่หมอบนเว็บต้องรู้จักลูกค้า และคุยต่อจาก FB ได้" — ไม่ใช่เริ่มนับหนึ่งใหม่
 *
 * งานของ service นี้ (ใช้เฉพาะเส้นเซิร์ฟเวอร์ /api/v1/juntra/server/chat/* — เส้น token ห้ามใช้เด็ดขาด):
 *   1. หา users.id ฝั่ง Thaiprompt ของลูกค้าเว็บ — JuntraAccount เป็นหลัก ค่าที่เว็บอ้างมาเป็นรอง
 *      (เชื่อค่าที่อ้างมาเฉพาะเมื่อยังไม่มี JuntraAccount และ user นั้นมี PSID จริง และไม่ใช่แอดมิน/ทีมงาน)
 *   2. หา handoff (nonce จากลิงก์ /chat/continue/{nonce}) แล้ว consume ครั้งเดียว (lockForUpdate)
 *   3. รวม PSID ของ FB (สูงสุด 5) — handoff, users.facebook_psid, alias, บิล FB ของ user, persona สาขาอื่น
 *   4. ประกอบ "บล็อกความจำ" (≤ 5,000 ตัว) — วางใน system message เท่านั้น (JuntraChatService ใส่รั้ว ====):
 *        intent ที่ทักมาในเพจ · timeline การเปิดไพ่ (ไม่มีราคา) · persona (โหมดเว็บ) ·
 *        บทสนทนา FB 12 ข้อความล่าสุดใน 14 วัน · คำทำนายล่าสุดที่จ่ายแล้วใน 30 วัน
 *   5. ไม่ส่งบทสนทนา FB ไปเก็บที่ juntraweb — ความจำอยู่ใน cache ฝั่ง Thaiprompt เท่านั้น
 *
 * ทุกขั้นห่อ try/catch — ความจำเป็นของแถม ห้ามทำให้เปิดห้องแชทไม่ได้
 */
class WebChatContinuityService
{
    public const MEMORY_MAX_CHARS = 5000;

    public const MAX_PSIDS = 5;

    public const CONVERSATION_DAYS = 14;

    public const CONVERSATION_MAX_MESSAGES = 12;

    public const MESSAGE_MAX_CHARS = 280;

    public const CONVERSATION_MAX_CHARS = 3000;

    public const PERSONA_MAX_CHARS = 1200;

    /** ชื่อลูกค้าที่ยาวกว่านี้ไม่ใส่ในคำทักทาย */
    public const GREETING_NAME_MAX_CHARS = 20;

    /** role ของบัญชีที่ห้ามมีความจำลูกค้า (แอดมิน/ทีมงาน) */
    private const PRIVILEGED_ROLES = ['admin', 'super_admin', 'staff'];

    public function __construct(private FortuneLastReadingRecallService $recall) {}

    /**
     * เปิดห้องแชท: หา user, consume handoff, ประกอบความจำ
     *
     * @param  string  $userRef  users.id ฝั่งเว็บจันทรา
     * @param  int|null  $claimedUserId  thaiprompt_user_id ที่เว็บอ้างมา (เชื่อแบบมีเงื่อนไข)
     * @param  string|null  $handoffNonce  uuid จากลิงก์ /chat/continue/{nonce}
     * @return array{memory:?string,intent_topic:?string,intent_text:?string,customer_name:?string,returning:bool,recent_conversation:bool,continued_from:?string,handoff_id:?string,handoff_consumed:bool}
     *
     * 🔐 (รีวิว) handoff_consumed = true เฉพาะเมื่อ "การเรียกครั้งนี้" consume handoff ได้
     *    (ส่ง nonce มา = ต้องเป็นแถวนั้นเท่านั้น) · เว็บใช้ค่านี้ตัดสินว่าจะเปิดห้องใหม่/ทักทายไหม
     *    ลิงก์เดิมที่ถูกเปิดซ้ำ → false → เว็บพากลับห้องเดิม ไม่เปิดห้องซ้ำ/ไม่ทักซ้ำ
     */
    public function begin(string $userRef, ?int $claimedUserId = null, ?string $handoffNonce = null): array
    {
        $out = self::emptyResult();

        try {
            $user = $this->resolveUser($userRef, $claimedUserId);
            if (! $user) {
                return $out;
            }

            $userPsids = $this->userPsids($user);
            $handoff = $this->consumeHandoff($user, $userPsids, $handoffNonce, $userRef);

            $psids = $this->collectPsids($user, $userPsids, $handoff);
            $intentText = $handoff ? WebChatContinuitySanitizer::sanitizeInbound((string) $handoff->intent_text, 'user') : '';
            $built = $this->buildMemory($user, $psids, $intentText !== '' ? $intentText : null);

            $out['memory'] = $built['memory'];
            $out['returning'] = $built['memory'] !== null;
            $out['recent_conversation'] = $built['recent'];
            $out['customer_name'] = $this->customerName($user, $psids);

            if ($handoff) {
                $out['handoff_consumed'] = true;
                $out['handoff_id'] = (string) $handoff->nonce;
                $out['intent_text'] = $intentText !== '' ? $intentText : null;
                // 🔐 (รีวิว) หัวข้อ 'general' ที่ไม่มีข้อความประกอบ = ไม่มีเรื่องจริงให้อ้าง (แถวเก่าจากการกดปุ่ม)
                $topic = $handoff->intent_topic;
                if ($intentText !== '' || ($topic !== null && $topic !== 'general')) {
                    $out['intent_topic'] = $topic ?? 'general';
                }
            }
            if ($handoff || $built['memory'] !== null) {
                $out['continued_from'] = 'facebook';
            }
        } catch (\Throwable $e) {
            Log::warning('WebChatContinuity: begin ล้มเหลว (non-blocking)', [
                'user_ref' => $userRef,
                'error' => $e->getMessage(),
            ]);
        }

        return $out;
    }

    /**
     * สร้างความจำใหม่จากฐานข้อมูล (ห้องหมดอายุ / cache ถูกล้าง) — ไม่แตะ handoff
     *
     * @return string|null null = ไม่มีอะไรให้จำ
     */
    public function rebuildMemory(string $userRef, ?int $claimedUserId = null): ?string
    {
        try {
            $user = $this->resolveUser($userRef, $claimedUserId);
            if (! $user) {
                return null;
            }

            $psids = $this->collectPsids($user, $this->userPsids($user), null);

            return $this->buildMemory($user, $psids, null)['memory'];
        } catch (\Throwable $e) {
            Log::warning('WebChatContinuity: rebuild ล้มเหลว (non-blocking)', [
                'user_ref' => $userRef,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * หา users.id ฝั่ง Thaiprompt ของลูกค้าเว็บ
     *
     * - มี JuntraAccount → ใช้ค่านั้นเสมอ (ค่าที่อ้างมาไม่ตรง = log แล้วใช้ของ JuntraAccount)
     * - ไม่มี → เชื่อค่าที่อ้างมาเฉพาะเมื่อ user นั้นมี PSID/alias จริง
     * - แอดมิน/ทีมงาน/บัญชีถูกระงับ → null (ไม่มีความจำลูกค้า)
     */
    public function resolveThaipromptUserId(string $userRef, ?int $claimedUserId = null): ?int
    {
        return $this->resolveUser($userRef, $claimedUserId)?->id;
    }

    /** ค่าว่างของผลลัพธ์ begin() */
    public static function emptyResult(): array
    {
        return [
            'memory' => null,
            'intent_topic' => null,
            'intent_text' => null,
            'customer_name' => null,
            'returning' => false,
            'recent_conversation' => false,
            'continued_from' => null,
            'handoff_id' => null,
            'handoff_consumed' => false,
        ];
    }

    /* ───────────────────────────── ตัวตน ───────────────────────────── */

    protected function resolveUser(string $userRef, ?int $claimedUserId): ?User
    {
        $userId = null;

        $account = ctype_digit($userRef) && Schema::hasTable('juntra_accounts')
            ? JuntraAccount::where('juntra_user_id', (int) $userRef)->first()
            : null;

        if ($account) {
            $userId = (int) $account->user_id;
            if ($claimedUserId !== null && $claimedUserId !== $userId) {
                Log::warning('WebChatContinuity: thaiprompt_user_id ที่เว็บอ้างไม่ตรง JuntraAccount — ใช้ของ JuntraAccount', [
                    'user_ref' => $userRef,
                    'claimed' => $claimedUserId,
                    'account_user_id' => $userId,
                ]);
            }
        } elseif ($claimedUserId !== null && $claimedUserId > 0) {
            $userId = $claimedUserId;
        }

        if ($userId === null) {
            return null;
        }

        $user = User::find($userId);
        if (! $user || $this->isPrivileged($user)) {
            return null;
        }

        // ค่าที่อ้างมาโดยไม่มี JuntraAccount — ต้องเป็นลูกค้าบอทที่มี PSID จริงเท่านั้น
        if (! $account && $this->userPsids($user) === []) {
            return null;
        }

        return $user;
    }

    protected function isPrivileged(User $user): bool
    {
        $attrs = $user->getAttributes();

        if (in_array((string) ($attrs['role'] ?? ''), self::PRIVILEGED_ROLES, true)) {
            return true;
        }
        // 🛡️ เกณฑ์กลาง: role_id ของบทบาทลูกค้า (seller ฯลฯ) ไม่ใช่ทีมงาน
        if (\App\Support\FortuneBackOfficeAccount::is($user)) {
            return true;
        }

        return ! empty($attrs['blocked_at']);
    }

    /**
     * PSID ที่ผูกกับ user โดยตรง: users.facebook_psid + ตาราง alias (fortune_messenger_psids ถ้ามี)
     *
     * @return array<int,string>
     */
    protected function userPsids(User $user): array
    {
        $psids = [];

        $own = (string) ($user->getAttributes()['facebook_psid'] ?? '');
        if ($own !== '') {
            $psids[] = $own;
        }

        try {
            if (Schema::hasTable('fortune_messenger_psids') && Schema::hasColumn('fortune_messenger_psids', 'psid')) {
                foreach (DB::table('fortune_messenger_psids')->where('user_id', $user->id)->orderByDesc('id')->limit(self::MAX_PSIDS)->pluck('psid') as $p) {
                    $psids[] = (string) $p;
                }
            }
        } catch (\Throwable $e) {
            // ตาราง alias ยังไม่มี (ก่อน T14 deploy) — ข้าม
        }

        return $this->onlyFacebook($psids);
    }

    /**
     * รวม PSID ทั้งหมดของลูกค้า (FB เท่านั้น สูงสุด 5) — ลำดับ = ความสำคัญ
     *
     * @param  array<int,string>  $userPsids
     * @return array<int,string>
     */
    protected function collectPsids(User $user, array $userPsids, ?FortuneWebHandoff $handoff): array
    {
        $psids = [];
        if ($handoff && $handoff->platform === 'facebook') {
            $psids[] = (string) $handoff->platform_user_id;
        }
        $psids = array_merge($psids, $userPsids);

        try {
            $fromReadings = FortuneReading::query()
                ->where('user_id', $user->id)
                ->where('platform', 'facebook')
                ->orderByDesc('id')
                ->limit(20)
                ->get(['facebook_user_id', 'platform_user_id']);
            foreach ($fromReadings as $r) {
                $psids[] = (string) ($r->platform_user_id ?: $r->facebook_user_id);
            }
        } catch (\Throwable $e) {
            // ไม่มีคอลัมน์ platform/user_id — ข้าม
        }

        $psids = $this->onlyFacebook($psids);

        // persona สาขาอื่นของคนเดียวกัน (ผูกด้วย ASID) — ไม่ยิงคิวรีถ้ายังไม่เคยผูก
        try {
            $identity = app(CrossPageIdentityService::class);
            foreach (FortuneCustomerPersona::query()->where('platform', 'facebook')->whereIn('platform_user_id', $psids)->limit(self::MAX_PSIDS)->get() as $persona) {
                foreach ($identity->siblings($persona) as $sib) {
                    if ($sib->platform === 'facebook') {
                        $psids[] = (string) $sib->platform_user_id;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ความจำข้ามสาขาเป็นของแถม
        }

        return array_slice($this->onlyFacebook($psids), 0, self::MAX_PSIDS);
    }

    /**
     * เก็บเฉพาะ PSID ของ FB (ตัวเลขล้วน) — ทิ้ง LINE (U…) / Telegram (tg_…) / ค่าว่าง และตัดตัวซ้ำ
     *
     * @param  array<int,string>  $ids
     * @return array<int,string>
     */
    protected function onlyFacebook(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $id = trim((string) $id);
            if ($id !== '' && preg_match('/^\d{5,32}$/', $id) && ! in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /* ───────────────────────────── handoff ───────────────────────────── */

    /**
     * หา handoff ของ user แล้ว consume ครั้งเดียว
     *
     * nonce ที่ตรงกับ user ก่อน → ไม่มี/ไม่ตรง = แถวล่าสุดที่ยังใช้ได้ใน 72 ชม.
     * "ตรงกับ user" = thaiprompt_user_id ตรง หรือยังไม่มี thaiprompt_user_id แต่ PSID เป็นของ user
     *
     * @param  array<int,string>  $userPsids
     */
    protected function consumeHandoff(User $user, array $userPsids, ?string $nonce, string $userRef): ?FortuneWebHandoff
    {
        if (! Schema::hasTable('fortune_web_handoffs')) {
            return null;
        }

        $owned = function () use ($user, $userPsids) {
            return FortuneWebHandoff::query()
                ->usable()
                ->where('platform', 'facebook')
                ->where('created_at', '>=', now()->subHours(FortuneWebHandoff::TTL_HOURS))
                ->where(function ($q) use ($user, $userPsids) {
                    $q->where('thaiprompt_user_id', $user->id);
                    if ($userPsids !== []) {
                        $q->orWhere(function ($q2) use ($userPsids) {
                            $q2->whereNull('thaiprompt_user_id')->whereIn('platform_user_id', $userPsids);
                        });
                    }
                });
        };

        return DB::transaction(function () use ($owned, $nonce, $userRef) {
            // 🔐 (รีวิว) ส่ง nonce มา = ต้องเป็นแถวนั้นเท่านั้น (ใช้แล้ว/หมดอายุ/ของคนอื่น = null)
            //    เดิมตกไปหยิบ handoff ล่าสุดอีกใบ → เปิดลิงก์เก่าซ้ำแล้วกิน handoff ใบใหม่ที่ยังไม่ได้กด
            //    ไม่ส่ง nonce (เปิด /chat เอง) เท่านั้นที่ใช้แถวล่าสุดที่ยังไม่ใช้
            if ($nonce !== null && $nonce !== '') {
                if (! \Illuminate\Support\Str::isUuid($nonce)) {
                    return null;
                }
                $row = $owned()->where('nonce', strtolower($nonce))->lockForUpdate()->first();
            } else {
                $row = $owned()->orderByDesc('created_at')->orderByDesc('id')->lockForUpdate()->first();
            }

            if (! $row || ! $row->consume($userRef)) {
                return null;
            }

            return $row;
        });
    }

    /* ───────────────────────────── ความจำ ───────────────────────────── */

    /**
     * ประกอบบล็อกความจำ (≤ MEMORY_MAX_CHARS) ตามลำดับความสำคัญ
     *
     * @param  array<int,string>  $psids
     * @return array{memory:?string,recent:bool}
     */
    protected function buildMemory(User $user, array $psids, ?string $intentText): array
    {
        $sections = [];
        $recent = false;

        if ($intentText !== null && $intentText !== '') {
            $sections[] = "— เรื่องที่ลูกทักมาในเพจก่อนย้ายมาห้องนี้ —\n".mb_substr($intentText, 0, FortuneWebHandoff::INTENT_MAX_LENGTH);
        }

        if ($psids !== []) {
            $timeline = $this->timelineSection($user, $psids);
            if ($timeline !== null) {
                $sections[] = $timeline;
            }

            $persona = $this->personaSection($psids);
            if ($persona !== null) {
                $sections[] = $persona;
            }

            $conversation = $this->conversationSection($psids);
            if ($conversation !== null) {
                $sections[] = $conversation;
                // 🔐 (รีวิว) "คุยค้างไว้" ต้องมีบรรทัดที่ลูกค้าพิมพ์จริง — ข้อความบอทฝ่ายเดียว (บรอดแคสต์/ชวน) ไม่นับ
                $recent = str_contains($conversation, 'ลูก: ');
            }

            $recall = $this->recall->build($psids, forWeb: true);
            if ($recall !== null) {
                $sections[] = $recall;
            }
        }

        if ($sections === []) {
            return ['memory' => null, 'recent' => false];
        }

        // ใส่ตามลำดับจนเต็มเพดาน — section ที่ล้นตัดท้าย (ถ้าเหลือที่น้อยเกินไปก็ทิ้ง)
        $memory = '';
        foreach ($sections as $section) {
            $section = WebChatContinuitySanitizer::neutralizeFences($section);
            $room = self::MEMORY_MAX_CHARS - mb_strlen($memory) - 2;
            if ($room < 200) {
                break;
            }
            $memory .= ($memory === '' ? '' : "\n\n").mb_substr($section, 0, $room);
        }

        return ['memory' => $memory, 'recent' => $recent];
    }

    /**
     * timeline การเปิดไพ่ที่จ่ายแล้วจากทุก PSID — นับครั้ง แยกประเภท ไม่มีราคา
     *
     * @param  array<int,string>  $psids
     */
    protected function timelineSection(User $user, array $psids): ?string
    {
        try {
            $rows = FortuneReading::query()
                ->where('is_paid', true)
                ->where(function ($q) use ($psids, $user) {
                    $q->whereIn('facebook_user_id', $psids)
                        ->orWhereIn('platform_user_id', $psids)
                        ->orWhere(function ($q2) use ($user) {
                            $q2->where('user_id', $user->id)->where('platform', 'facebook');
                        });
                })
                ->where(function ($q) {
                    $q->whereNull('reading_type')->orWhere('reading_type', '!=', FortuneReading::READING_TYPE_JUNTRA);
                })
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(['id', 'reading_type', 'created_at']);
        } catch (\Throwable $e) {
            return null;
        }

        if ($rows->isEmpty()) {
            return null;
        }

        $labels = [
            FortuneReading::READING_TYPE_DEEP => 'ดูดวงเชิงลึก',
            FortuneReading::READING_TYPE_CELTIC_CROSS => 'Celtic Cross',
            FortuneReading::READING_TYPE_BASIC => 'ดูดวงพื้นฐาน',
        ];
        $byType = [];
        foreach ($rows as $r) {
            $label = $labels[$r->reading_type] ?? 'ดูดวง';
            $byType[$label] = ($byType[$label] ?? 0) + 1;
        }
        $parts = [];
        foreach ($byType as $label => $n) {
            $parts[] = "{$label} {$n}";
        }

        $last = $rows->first()->created_at;
        $days = $last ? (int) $last->diffInDays(now()) : null;
        $when = match (true) {
            $days === null => '',
            $days === 0 => ' · ครั้งล่าสุดวันนี้',
            $days === 1 => ' · ครั้งล่าสุดเมื่อวาน',
            default => " · ครั้งล่าสุด {$days} วันก่อน",
        };

        return "— ประวัติการเปิดไพ่กับแม่หมอในเพจ —\n"
            .'• เปิดไพ่แล้ว '.$rows->count().' ครั้ง ('.implode(' · ', $parts).')'.$when;
    }

    /**
     * persona ของ PSID ที่อัปเดตล่าสุด (โหมดเว็บ — เก็บ risk guidance ทิ้ง time-waster)
     *
     * @param  array<int,string>  $psids
     */
    protected function personaSection(array $psids): ?string
    {
        try {
            $persona = FortuneCustomerPersona::query()
                ->where('platform', 'facebook')
                ->whereIn('platform_user_id', $psids)
                ->orderByDesc('updated_at')
                ->first();
            if (! $persona) {
                return null;
            }

            $block = trim($persona->toAiContextBlock(null, forWeb: true));

            return $block === '' ? null : mb_substr($block, 0, self::PERSONA_MAX_CHARS);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * บทสนทนาในเพจ 12 ข้อความล่าสุดใน 14 วัน (ข้อความละ ≤ 280 · รวม ≤ 3,000) — ผ่านตัวกรองขาเข้าทุกข้อความ
     *
     * @param  array<int,string>  $psids
     */
    protected function conversationSection(array $psids): ?string
    {
        if (! Schema::hasTable('line_bot_conversations') || ! Schema::hasTable('line_bot_messages')) {
            return null;
        }

        try {
            $convQuery = DB::table('line_bot_conversations')->whereIn('line_user_id', $psids);
            if (Schema::hasColumn('line_bot_conversations', 'platform')) {
                $convQuery->where('platform', 'facebook');
            }
            if (Schema::hasColumn('line_bot_conversations', 'deleted_at')) {
                $convQuery->whereNull('deleted_at');
            }
            $convIds = $convQuery->pluck('id')->all();
            if ($convIds === []) {
                return null;
            }

            $rows = DB::table('line_bot_messages')
                ->whereIn('conversation_id', $convIds)
                ->whereIn('role', ['user', 'assistant'])
                ->where('created_at', '>=', now()->subDays(self::CONVERSATION_DAYS))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::CONVERSATION_MAX_MESSAGES)
                ->get(['role', 'message']);
        } catch (\Throwable $e) {
            return null;
        }

        $lines = [];
        $total = 0;
        // ล่าสุดก่อน — เติมจนเต็มเพดาน แล้วค่อยกลับลำดับเป็นเก่า → ใหม่
        foreach ($rows as $row) {
            $text = WebChatContinuitySanitizer::sanitizeInbound((string) $row->message, (string) $row->role);
            if ($text === '') {
                continue;
            }
            $text = mb_substr(preg_replace('/\s+/u', ' ', $text) ?? $text, 0, self::MESSAGE_MAX_CHARS);
            $line = ($row->role === 'user' ? 'ลูก: ' : 'แม่หมอ: ').$text;
            if ($total + mb_strlen($line) > self::CONVERSATION_MAX_CHARS) {
                break;
            }
            $total += mb_strlen($line) + 1;
            $lines[] = $line;
        }

        if ($lines === []) {
            return null;
        }

        return "— บทสนทนาล่าสุดในเพจ (เก่า → ใหม่) —\n".implode("\n", array_reverse($lines));
    }

    /**
     * ชื่อลูกค้าสำหรับคำทักทาย — users.name ก่อน แล้ว persona.display_name (ไม่ใช่ 'User')
     *
     * @param  array<int,string>  $psids
     */
    protected function customerName(User $user, array $psids): ?string
    {
        $candidates = [(string) $user->name];

        try {
            if ($psids !== []) {
                $candidates[] = (string) FortuneCustomerPersona::query()
                    ->where('platform', 'facebook')
                    ->whereIn('platform_user_id', $psids)
                    ->whereNotNull('display_name')
                    ->orderByDesc('updated_at')
                    ->value('display_name');
            }
        } catch (\Throwable $e) {
            // ไม่มี persona — ข้าม
        }

        foreach ($candidates as $name) {
            $name = trim((string) preg_replace('/[\x00-\x1F\x7F\[\]\{\}<>=]+/u', ' ', $name));
            $name = trim((string) preg_replace('/\s+/u', ' ', $name));
            if ($name !== '' && mb_strtolower($name) !== 'user') {
                return mb_substr($name, 0, 80);
            }
        }

        return null;
    }
}
