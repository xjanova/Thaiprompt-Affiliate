<?php

namespace App\Services\Fortune;

use App\Models\FortuneVoiceCharacter;
use App\Models\FortuneVoiceLine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * 🔈 (2026-11-04) คลังคลิปเสียงคำเติม/คำรับของห้องแชทเว็บจันทรา — ฝั่งที่ AI เลือกใช้ได้ในโหมดเสียง
 *
 * ทำไม: โหมดคุยด้วยเสียง (+฿100) ถ้าสร้างเสียงใหม่ทุกคำตอบจะเปลือง ElevenLabs มาก
 * คำตอบที่เป็นแค่การรับฟัง ชวนเล่า ถามทวน ขึ้นต้น ปิดท้าย ใช้คลิปที่อัดไว้แล้วแทน (ฟรีตอนใช้)
 *
 * ที่นี่คือ "บท" ของคลิป (id + ข้อความที่จะอัด พร้อมแท็กอารมณ์ v3) — ไฟล์เสียงอยู่ที่เว็บ
 * `public/media/oracle/{persona}/filler/{id}.mp3` สร้างครั้งเดียวด้วยสคริปต์ prod แล้วเจ้าของฟังคัด
 * เว็บส่ง `voice_clips` (id ที่มีไฟล์จริง) มากับ /chat/send → AI เห็นเฉพาะคลิปที่เล่นได้จริง
 *
 * id = "{หมวด}_{เลข}" — หน้าเว็บสลับแบบอื่นในหมวดเดียวกันได้ (ไม่ให้ได้ยิน "อืม" เดิมติดกัน)
 * ❌ ห้ามมีราคาในบท (ราคาเปลี่ยนได้) · คำเรียกลูกค้าตามห้อง (แม่หมอ/คุณไสย = ลูก · บุษบา = คุณ)
 */
final class JuntraVoiceLibrary
{
    /** หมวดที่ AI ใส่ใน pre/post ได้ — หมวดอื่น (soothe) ระบบเลือกเองเท่านั้น */
    public const AI_CATEGORIES = ['ack', 'listen', 'think', 'empathy', 'bright', 'prefix', 'call', 'mishear', 'ask_more', 'bye'];

    /** แท็กอารมณ์ของ ElevenLabs v3 ที่ทดสอบแล้ว — อย่างอื่นถูกลอกออกจากบทพูด */
    public const ALLOWED_TAGS = ['softly', 'calm', 'warmly', 'excited', 'whispers', 'sighs', 'serious'];

    /** คำเติมล้วน — บทพูดที่มีแต่คำเหล่านี้ไม่ต้องสร้างเสียงใหม่ */
    public const FILLER_WORDS = [
        'อืม', 'อือ', 'เอ่อ', 'อ๋อ', 'โอ้', 'โห', 'หืม', 'ค่ะ', 'คะ', 'ค่า', 'ขา', 'จ้ะ', 'จ๊ะ', 'จ้า', 'นะ', 'น้า',
        'ใช่', 'ใช่ค่ะ', 'ใช่จ้ะ', 'ใช่ลูก', 'ลูก', 'คุณ', 'ครับ', 'โถ', 'ว้าว', 'อ้อ', 'งั้น', 'เหรอ', 'จริงเหรอ',
    ];

    /**
     * บทเริ่มต้น — (2026-11-04) ย้ายไปอยู่ในตาราง fortune_voice_lines แล้ว (แอดมินเพิ่ม/แก้/ลบได้)
     * ที่นี่เหลือไว้เป็นค่าตั้งต้นของ migration และใช้แทนตอนยังไม่มีตาราง
     */
    public const DEFAULT_CLIPS = [
        'busaba' => [
            'ack_1' => '[softly] อืม…',
            'ack_2' => '[softly] ค่ะ…',
            'ack_3' => '[calm] อ๋อ… ค่ะ',
            'ack_4' => '[softly] ใช่ค่ะ',
            'listen_1' => '[softly] เล่าต่อเลยค่ะ บุษบาฟังอยู่',
            'listen_2' => '[calm] ค่อย ๆ เล่านะคะ ไม่ต้องรีบ',
            'listen_3' => '[softly] แล้วยังไงต่อคะ',
            'think_1' => '[calm] อืม… ขอบุษบาดูแป๊บนะคะ',
            'think_2' => '[softly] เอ่อ… แป๊บนึงนะคะ',
            'think_3' => '[calm] เดี๋ยวนะคะ บุษบาขอคิดนิดนึง',
            'empathy_1' => '[softly] โถ… เข้าใจเลยค่ะ',
            'empathy_2' => '[softly] เหนื่อยเลยนะคะ บุษบาฟังอยู่ตรงนี้',
            'empathy_3' => '[sighs] อืม… ไม่ง่ายเลยนะคะ',
            'bright_1' => '[excited] โห! ดีจังเลยค่า',
            'bright_2' => '[excited] เย้ ดีใจด้วยนะคะ',
            'bright_3' => '[warmly] ดีมากเลยค่ะ',
            'prefix_1' => '[calm] บุษบาว่านะคะ…',
            'prefix_2' => '[softly] งั้นนะคะ…',
            'prefix_3' => '[calm] ฟังบุษบานะคะ…',
            'call_1' => '[softly] ขา?',
            'call_2' => '[warmly] ค่ะ บุษบาอยู่นี่ค่ะ',
            'mishear_1' => '[softly] ขอโทษค่ะ ได้ยินไม่ชัด พูดอีกทีได้ไหมคะ',
            'mishear_2' => '[softly] เมื่อกี้เสียงขาดไปนิดนึงค่ะ ขออีกรอบนะคะ',
            'ask_more_1' => '[softly] มีอะไรอยากถามอีกไหมคะ',
            'ask_more_2' => '[warmly] ตรงไหนยังไม่ชัด ถามบุษบาต่อได้เลยน้า',
            'ask_more_3' => '[calm] อยากให้บุษบาขยายตรงไหนอีกไหมคะ',
            'bye_1' => '[warmly] ขอบคุณที่แวะมาหาบุษบานะคะ ขอให้วันนี้เป็นวันดีของคุณค่า',
            'bye_2' => '[softly] ดูแลตัวเองด้วยนะคะ แล้วมาคุยกับบุษบาใหม่น้า',
            'soothe_1' => '[softly] บุษบาอยู่ตรงนี้นะคะ ค่อย ๆ หายใจลึก ๆ คุณไม่ได้อยู่คนเดียวนะคะ',
            // ⚠️ บทนี้พูดถึงสายด่วน — อัดไว้ก่อนกฎ 2026-11-04 ที่เลิกยื่นเบอร์ (หมวด soothe ไม่อยู่ในรายการที่ AI เลือกได้)
            'soothe_2' => '[softly] เรื่องนี้สำคัญกว่าดวงค่ะ ลองคุยกับคนที่ไว้ใจ หรือโทรสายด่วนที่บุษบาพิมพ์ไว้ให้นะคะ',
        ],
        'maemor' => [
            'ack_1' => '[softly] อืม…',
            'ack_2' => '[softly] จ้ะ…',
            'ack_3' => '[calm] อือ… ใช่ลูก',
            'ack_4' => '[softly] อ๋อ… จ้ะ',
            'listen_1' => '[warmly] ว่าต่อสิลูก แม่หมอฟังอยู่',
            'listen_2' => '[softly] ค่อย ๆ เล่านะลูก ไม่ต้องรีบ',
            'listen_3' => '[calm] แล้วยังไงต่อจ๊ะลูก',
            'think_1' => '[calm] อืม… ขอแม่หมอดูก่อนนะลูก',
            'think_2' => '[softly] เดี๋ยวนะลูก…',
            'think_3' => '[calm] ขอแม่หมอนิ่งคิดสักครู่นะ',
            'empathy_1' => '[softly] โถ ลูกเอ๋ย…',
            'empathy_2' => '[warmly] แม่หมอเข้าใจนะลูก',
            'empathy_3' => '[sighs] อืม… หนักใจอยู่นะลูก',
            'bright_1' => '[warmly] ดีแล้วลูก…',
            'bright_2' => '[warmly] ดีใจด้วยนะลูก',
            'bright_3' => '[excited] นั่นแหละลูก ดีมาก',
            'prefix_1' => '[calm] ฟังนะลูก…',
            'prefix_2' => '[warmly] แม่หมอว่า…',
            'prefix_3' => '[calm] แม่หมอบอกตรง ๆ นะลูก…',
            'call_1' => '[warmly] ว่าไงจ๊ะลูก',
            'call_2' => '[softly] จ้ะ แม่หมออยู่นี่',
            'mishear_1' => '[softly] หืม? พูดอีกทีซิลูก',
            'mishear_2' => '[calm] แม่หมอได้ยินไม่ชัด ว่าใหม่อีกทีนะลูก',
            'ask_more_1' => '[warmly] ค้างใจตรงไหนถามได้นะลูก',
            'ask_more_2' => '[softly] มีอะไรอยากถามแม่หมออีกไหมจ๊ะ',
            'ask_more_3' => '[calm] ตรงไหนยังไม่ชัด บอกแม่หมอนะลูก',
            'bye_1' => '[warmly] ไปเถอะลูก ทำตามที่ไพ่บอกนะ แม่หมออยู่ตรงนี้เสมอ',
            'bye_2' => '[softly] ดูแลตัวเองดี ๆ นะลูก มาเล่าให้แม่หมอฟังอีกจ้ะ',
            'soothe_1' => '[softly] แม่หมออยู่ตรงนี้นะลูก ค่อย ๆ หายใจลึก ๆ ลูกไม่ได้อยู่คนเดียว',
            // ⚠️ บทนี้พูดถึงสายด่วน — อัดไว้ก่อนกฎ 2026-11-04 ที่เลิกยื่นเบอร์ (หมวด soothe ไม่อยู่ในรายการที่ AI เลือกได้)
            'soothe_2' => '[softly] เรื่องนี้สำคัญกว่าดวงนะลูก คุยกับคนที่ไว้ใจ หรือโทรสายด่วนที่แม่หมอพิมพ์ไว้ให้นะ',
        ],
        'kunsai' => [
            'ack_1' => '[calm] อืม…',
            'ack_2' => '[softly] ค่ะ…',
            'ack_3' => '[calm] อือ…',
            'listen_1' => '[calm] ค่อย ๆ เล่านะลูก…',
            'listen_2' => '[softly] ว่าต่อมาตรง ๆ แม่หมอฟังอยู่',
            'listen_3' => '[calm] แล้วเกิดอะไรขึ้นอีกลูก',
            'think_1' => '[calm] อืม… ขอแม่หมอพิจารณาก่อนนะลูก',
            'think_2' => '[softly] เดี๋ยวนะลูก…',
            'think_3' => '[calm] นิ่งไว้ก่อน แม่หมอขอดูให้ชัด',
            'empathy_1' => '[softly] โถ… ไม่ต้องกลัวนะลูก',
            'empathy_2' => '[calm] ตั้งสติไว้ลูก แม่หมออยู่ตรงนี้',
            'empathy_3' => '[softly] แม่หมอเข้าใจ… ใจเย็น ๆ นะลูก',
            'bright_1' => '[calm] ดีแล้วนะลูก',
            'bright_2' => '[softly] วางใจได้นะลูก',
            'prefix_1' => '[calm] ฟังแม่หมอนะลูก…',
            'prefix_2' => '[serious] แม่หมอบอกตรง ๆ นะ…',
            'prefix_3' => '[calm] จำไว้นะลูก…',
            'call_1' => '[calm] ว่าไงลูก…',
            'call_2' => '[softly] แม่หมออยู่นี่ลูก',
            'mishear_1' => '[calm] แม่หมอได้ยินไม่ชัด พูดอีกครั้งนะลูก',
            'mishear_2' => '[softly] เสียงขาดไป ว่าใหม่อีกทีลูก',
            'ask_more_1' => '[calm] ยังค้างใจอะไรอีกไหมลูก',
            'ask_more_2' => '[softly] ตรงไหนยังไม่ชัด ถามแม่หมอต่อได้นะลูก',
            'bye_1' => '[softly] ดูแลใจตัวเองดี ๆ นะลูก… ขอให้บุญกุศลคุ้มครองค่ะ',
            'bye_2' => '[calm] ทำตามที่แม่หมอบอกทีละขั้นนะลูก แล้วจะผ่านไปได้',
            'soothe_1' => '[softly] พักเรื่องของไว้ก่อนนะลูก แม่หมออยู่ตรงนี้ ค่อย ๆ หายใจลึก ๆ',
            // ⚠️ บทนี้พูดถึงสายด่วน — อัดไว้ก่อนกฎ 2026-11-04 ที่เลิกยื่นเบอร์ (หมวด soothe ไม่อยู่ในรายการที่ AI เลือกได้)
            'soothe_2' => '[softly] ใจลูกสำคัญที่สุดตอนนี้ คุยกับคนที่ไว้ใจ หรือโทรสายด่วนที่แม่หมอพิมพ์ไว้ให้นะลูก',
        ],
    ];

    /** ชื่อตัวละครที่มีคลัง (ไม่รู้จัก = แม่หมอ) */
    private static function persona(?string $persona): string
    {
        $known = self::hasTable() ? isset(FortuneVoiceCharacter::catalog()[$persona]) : isset(self::DEFAULT_CLIPS[$persona]);

        return is_string($persona) && $known ? $persona : JuntraChatService::DEFAULT_PERSONA;
    }

    /** บทของตัวละครจากตาราง (เฉพาะที่เปิดใช้ เรียงตามลำดับ) — แคชจนกว่าแอดมินจะแก้ */
    private static function stored(string $persona): array
    {
        if (! self::hasTable()) {
            return self::DEFAULT_CLIPS[$persona] ?? [];
        }

        return Cache::rememberForever(FortuneVoiceLine::CACHE_PREFIX.$persona, fn () => FortuneVoiceLine::where('persona', $persona)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->pluck('script', 'line_key')
            ->all());
    }

    /** ล้างแคชบทของตัวละคร (ลบทีละหลายแถวไม่ผ่าน event ของโมเดล) */
    public static function forget(string $persona): void
    {
        Cache::forget(FortuneVoiceLine::CACHE_PREFIX.$persona);
    }

    private static function hasTable(): bool
    {
        // ไม่มีแอป/ฐานข้อมูล (เทสต์ระดับ unit) — ใช้บทในโค้ด
        if (! app()->bound('db')) {
            return false;
        }

        return Schema::hasTable('fortune_voice_lines');
    }

    /**
     * 🎭 คลิปที่สร้างไฟล์แล้วของตัวละคร (id => url) — เว็บจันทราดึงไปเล่นแทนไฟล์ที่เคยวางไว้ในเว็บเอง
     *
     * @return array<string, array{text: string, category: string, url: string}>
     */
    public static function playable(?string $persona): array
    {
        if (! self::hasTable()) {
            return [];
        }

        return FortuneVoiceLine::with('clip')
            ->where('persona', self::persona($persona))
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->filter(fn (FortuneVoiceLine $l) => $l->clip?->isCompleted())
            ->mapWithKeys(fn (FortuneVoiceLine $l) => [$l->line_key => [
                'text' => self::plain($l->script),
                'category' => $l->category,
                'url' => $l->clip->audio_url,
            ]])
            ->all();
    }

    /**
     * คลิปทั้งหมดของห้อง (id => บทพร้อมแท็ก)
     *
     * @param  array<int,string>|null  $available  id ที่เว็บมีไฟล์จริง (null = เว็บไม่ได้บอก ถือว่ามีครบ)
     * @return array<string,string>
     */
    public static function clips(?string $persona, ?array $available = null): array
    {
        $clips = self::stored(self::persona($persona));
        if ($available === null) {
            return $clips;
        }

        return array_intersect_key($clips, array_flip(array_map('strval', $available)));
    }

    /** หมวดของ id ("ask_more_2" → "ask_more") */
    public static function categoryOf(string $id): string
    {
        return (string) preg_replace('/_\d+$/', '', $id);
    }

    /** id ที่ AI ใช้ใน pre/post ได้ (อยู่ในคลังห้องนี้ มีไฟล์ และอยู่ในหมวดที่ AI เลือกได้) */
    public static function isAiClip(?string $persona, ?array $available, string $id): bool
    {
        return isset(self::clips($persona, $available)[$id])
            && in_array(self::categoryOf($id), self::AI_CATEGORIES, true);
    }

    /** คลิปแรกที่มีของหมวดนี้ (ไม่มีไฟล์เลย = null) */
    public static function first(?string $persona, ?array $available, string $category): ?string
    {
        foreach (array_keys(self::clips($persona, $available)) as $id) {
            if (self::categoryOf($id) === $category) {
                return $id;
            }
        }

        return null;
    }

    /** บทไม่มีแท็ก — ใช้ทั้งรายการที่ส่งให้ AI และเทียบข้อความ */
    public static function plain(string $script): string
    {
        // แท็กมาได้ทั้ง [softly] (บทในคลัง) และ {softly} (ที่ AI เขียน — ดู VoicePlanParser::cleanSay)
        return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\[\{][a-z_ ]+[\]\}]/iu', ' ', $script)));
    }

    /**
     * รายการคลังสำหรับ system prompt — "id: ข้อความ" บรรทัดละคลิป (ไม่มี soothe)
     */
    public static function promptList(?string $persona, ?array $available): string
    {
        $lines = [];
        foreach (self::clips($persona, $available) as $id => $script) {
            if (in_array(self::categoryOf($id), self::AI_CATEGORIES, true)) {
                $lines[] = $id.': '.self::plain($script);
            }
        }

        return implode("\n", $lines);
    }

    /** บทพูดตรงกับคลิปในคลัง (ไม่สนแท็ก วรรค จุดไข่ปลา) → id ของคลิปนั้น */
    public static function matchText(?string $persona, ?array $available, string $text): ?string
    {
        $needle = self::normalize($text);
        if ($needle === '') {
            return null;
        }
        foreach (self::clips($persona, $available) as $id => $script) {
            if (in_array(self::categoryOf($id), self::AI_CATEGORIES, true) && self::normalize($script) === $needle) {
                return $id;
            }
        }

        return null;
    }

    /** บทพูดมีแต่คำเติม (อืม เอ่อ ค่ะ ใช่ลูก …) หรือไม่ */
    public static function isFillerOnly(string $text): bool
    {
        $rest = self::normalize($text);
        if ($rest === '') {
            return true;
        }
        // ตัดคำเติมยาวก่อนสั้น ("ใช่ค่ะ" ก่อน "ใช่") ภาษาไทยไม่มีวรรคระหว่างคำ
        $words = self::FILLER_WORDS;
        usort($words, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $rest = str_replace($words, '', $rest);

        return $rest === '';
    }

    /** ตัดแท็ก วรรค เครื่องหมาย ตัวซ้ำท้ายคำ ("อืมมม") ให้เหลือแต่ตัวอักษร */
    private static function normalize(string $text): string
    {
        $text = self::plain($text);
        $text = (string) preg_replace('/[\s\p{P}\p{S}]+/u', '', $text);
        $text = (string) preg_replace('/(\p{L})\1{2,}/u', '$1', $text);

        return mb_strtolower($text);
    }
}
