<?php

namespace App\Services\Eve;

use App\Models\FortuneCustomerPersona;
use App\Models\FortuneReading;
use App\Models\User;
use App\Services\Fortune\CustomerPersonaService;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * เชื่อม "ผู้ใช้เว็บ (User)" → "persona ระบบ RPG (FortuneCustomerPersona)"
 *
 * ทำไมต้องมีตัวนี้: persona ถูกสร้างจากบอทดูดวง FB/LINE จึงคีย์ด้วย (platform, platform_user_id)
 * ไม่ใช่ user_id → คนที่ล็อกอินหน้าเว็บต้อง "หาทางเชื่อม" เอง
 *
 * ยืนยันกับ prod แล้ว (2026-07-26): persona 7,783 · เชื่อมได้จริงผ่าน facebook_psid 614 คน,
 * line_user_id 13 คน, และ bridge ผ่านประวัติดูดวงอีก 742 คน → ทั้ง 3 เส้นทางใช้งานได้จริง
 *
 * 🔒 กฎความปลอดภัยที่ห้ามผิด:
 *    - ห้ามรับ platform / platform_user_id จาก request เด็ดขาด
 *      (FortuneCustomerPersona::findByPlatformUser() ไม่มีการตรวจสิทธิ์ — ถ้าเปิดรับ input
 *       ใครก็เดา/ขโมย PSID มาอ่าน persona ของคนอื่นได้)
 *    - resolve จาก "ผู้ใช้ที่ล็อกอินแล้ว" เท่านั้น
 */
class PersonaIdentityResolver
{
    /** อายุแคชการ map user → (platform,userId) — ตัวตนไม่เปลี่ยนบ่อย */
    private const MAP_TTL = 3600;

    public function __construct(private readonly CustomerPersonaService $personas) {}

    /**
     * หา persona ของผู้ใช้เว็บคนนี้ (null = ไม่มี → Eve ใช้โหมดทั่วไป)
     */
    public function forUser(?User $user): ?FortuneCustomerPersona
    {
        if (! $user) {
            return null;
        }

        $identity = $this->resolveIdentity($user);
        if (! $identity) {
            return null;
        }

        try {
            // ⚠️ ต้องอ่านผ่าน getCached() เสมอ — เป็นคีย์แคชเดียวกับฝั่งบอทดูดวง
            //    ถ้าอ่านจาก model ตรงๆ จะได้ข้อมูลคนละชุดกับที่ระบบอื่นเห็น
            return $this->personas->getCached($identity['platform'], $identity['user_id']);
        } catch (Throwable $e) {
            return null; // persona เป็นของเสริม — พังแล้วต้องไม่ทำให้ Eve ตอบไม่ได้
        }
    }

    /**
     * หา (platform, platform_user_id) ของผู้ใช้ — 3 เส้นทางเรียงตามความน่าเชื่อถือ
     *
     * @return array{platform:string,user_id:string}|null
     */
    private function resolveIdentity(User $user): ?array
    {
        return Cache::remember('eve:persona_identity:'.$user->id, self::MAP_TTL, function () use ($user) {
            // 1) Facebook PSID — คอลัมน์ unique ที่ backfill แล้ว (เส้นทางหลัก 614 คน)
            if (! empty($user->facebook_psid)) {
                return ['platform' => 'facebook', 'user_id' => (string) $user->facebook_psid];
            }

            // 2) LINE userId — ยืนยันแล้วว่า provider เดียวกัน (join ติด 13 คนบน prod)
            if (! empty($user->line_user_id)) {
                return ['platform' => 'line', 'user_id' => (string) $user->line_user_id];
            }

            // 3) Bridge ผ่านประวัติดูดวง — ผู้ใช้ที่เคยดูดวงแต่ไม่มีคอลัมน์เชื่อม
            try {
                $reading = FortuneReading::where('user_id', $user->id)
                    ->whereNotNull('platform_user_id')
                    ->whereNotNull('platform')
                    ->latest('id')
                    ->first(['platform', 'platform_user_id']);

                if ($reading && $reading->platform_user_id) {
                    return ['platform' => (string) $reading->platform, 'user_id' => (string) $reading->platform_user_id];
                }
            } catch (Throwable $e) {
                // ไม่มีตาราง/คอลัมน์ → ข้าม
            }

            return null;
        });
    }

    /**
     * สร้างบล็อก persona ที่ "ปลอดภัยพอจะส่งให้ Eve ฝั่งลูกค้า"
     *
     * ⚠️⚠️ ห้ามใช้ FortuneCustomerPersona::toAiContextBlock() ที่นี่เด็ดขาด
     *    ตรวจโค้ดจริงแล้ว (2026-07-26): เมธอดนั้นเรียก buildRiskGuidanceLines() ต่อท้าย
     *    ซึ่ง "พ่นชื่อธงความเสี่ยงเป็นข้อความตรงๆ" ลงใน prompt เช่น
     *      "⚠️ MENTAL_FRAGILE: ลูกค้าเคยพูดเรื่องวิกฤต/ทำร้ายตัวเอง…"
     *    และยัง inject time_waster_score ด้วย → ถ้าหลุดถึงลูกค้า = ละเมิดความเป็นส่วนตัวร้ายแรง
     *    (เป็นข้อมูลสุขภาพจิตที่ระบบ "อนุมานเอง" ไม่ใช่สิ่งที่ลูกค้าบอก)
     *
     * ที่นี่จึงประกอบเองเฉพาะฟิลด์ที่ปลอดภัย: ชอบ/ไม่ชอบ/บุคลิก/เรื่องที่เคยคุย/สไตล์การพูด
     * และ "ห้าม" แตะ: risk_flags, time_waster_score, silence_*, note_markdown, gender_hint
     */
    public function buildSafeContextBlock(?FortuneCustomerPersona $persona): string
    {
        if (! $persona) {
            return '';
        }

        $lines = [];

        // อายุ/อาชีพ (ข้ามถ้า unknown) — 🚫 ไม่เอา gender_hint
        //    เหตุผล: เคยทำให้ AI มิเรอร์คำลงท้ายเป็น "ครับ" ทั้งที่ Eve เป็นผู้หญิง
        $demo = $persona->demographics ?? [];
        $demoParts = [];
        if (! empty($demo['age_range']) && $demo['age_range'] !== 'unknown') {
            $demoParts[] = "อายุ ~{$demo['age_range']}";
        }
        if (! empty($demo['job_hint']) && $demo['job_hint'] !== 'unknown') {
            $demoParts[] = "งาน: {$demo['job_hint']}";
        }
        if ($demoParts) {
            $lines[] = '• '.implode(' / ', $demoParts);
        }

        if (! empty($persona->traits)) {
            $lines[] = '• บุคลิก: '.implode(', ', array_slice((array) $persona->traits, -5));
        }
        if (! empty($persona->likes)) {
            $lines[] = '• ชอบ: '.implode(', ', array_slice((array) $persona->likes, -5));
        }
        if (! empty($persona->dislikes)) {
            $lines[] = '• ไม่ชอบ: '.implode(', ', array_slice((array) $persona->dislikes, -3));
        }
        if (! empty($persona->conversation_themes)) {
            $lines[] = '• เคยสนใจเรื่อง: '.implode(', ', array_slice((array) $persona->conversation_themes, -3));
        }

        // สไตล์การพูด → แปลงเป็นคำสั่งที่ AI ทำตามได้จริง
        $style = $persona->communication_style ?? [];
        $styleParts = [];
        $toneMap = [
            'warm' => 'อบอุ่นเป็นกันเอง',
            'casual' => 'คุยสบายๆ ภาษาพูด',
            'formal' => 'สุภาพเรียบร้อย',
            'emotional' => 'ใช้คำนุ่มนวล ไม่กดดัน',
            'reserved' => 'ตอบกระชับ ไม่ถามรัว',
        ];
        if (! empty($style['tone'])) {
            $styleParts[] = $toneMap[$style['tone']] ?? '';
        }
        if (! empty($style['emoji_usage']) && $style['emoji_usage'] === 'none') {
            $styleParts[] = 'ไม่ต้องใช้อิโมจิ';
        }
        $styleParts = array_values(array_filter($styleParts));
        if ($styleParts) {
            $lines[] = '• 🪞 ปรับสไตล์ให้เข้ากับลูกค้า: '.implode(' / ', $styleParts);
        }

        if (! $lines) {
            return '';
        }

        return "[👤 โปรไฟล์ลูกค้า — ใช้ปรับ \"น้ำเสียงและของที่แนะนำ\" เท่านั้น]\n"
            .implode("\n", $lines)
            ."\n⚠️ ห้ามเอ่ยถึงข้อมูลนี้ตรงๆ (ห้ามพูดว่า \"จำได้ว่าคุณชอบ...\") ให้ใช้แบบแนบเนียนเท่านั้น";
    }
}
