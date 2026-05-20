<?php

namespace App\Services\Fortune;

use App\Models\FortuneDailyHoroscopePost;
use App\Models\FortuneReading;
use Carbon\Carbon;

/**
 * 🌙 บริการสร้างคำทักทาย DM แบบ "ดวงประจำวันสั้นๆ"
 *
 * นโยบาย 2026-05-21 (แทน returning-user variants เดิม):
 * - ลูกค้าเก่า (มี birth_date ใน DB) → ส่งดวงประจำวันตาม day_of_birth + ชวน
 *   เปิดเชิงลึก ทักมาได้
 * - ลูกค้าใหม่ (ไม่มี birth_date) → ทักทาย + ชวนดูดวง 1 บิล + promise ส่ง
 *   ดวงประจำวันให้ฟรีทุกครั้งหลังจากนั้น
 *
 * ใช้ FortuneDailyHoroscopePost ที่ scheduler สร้างไว้ — ถ้าวันนี้ไม่มี post
 * → fallback generic by day name (ยังคงมี personalization)
 *
 * Used by:
 * - FacebookWebhookController::tryReactionDm (reaction → DM)
 * - FacebookWebhookController::sendTemplateEngagement (comment → DM)
 * - ProcessCommentEngagement (AI fallback)
 */
class FortuneGreetingService
{
    /**
     * สร้างคำทักทาย DM พร้อมดวงประจำวัน
     *
     * @param  string  $userId  facebook_user_id หรือ platform_user_id
     * @param  string  $name  ชื่อลูกค้า (placeholder substitution)
     * @return string  ข้อความ DM พร้อมส่ง (ใส่ {name} แทนที่แล้ว)
     */
    public function buildDailyHoroscopeGreeting(string $userId, string $name): string
    {
        $birthdate = FortuneReading::findLatestBirthdate($userId);
        $displayName = $this->normalizeName($name);

        if ($birthdate instanceof Carbon) {
            return $this->buildHoroscopeForKnownUser($displayName, $birthdate);
        }

        return $this->buildInviteForNewUser($displayName);
    }

    /**
     * คำทักทายสำหรับลูกค้าเก่า — มีวันเกิดใน DB
     */
    protected function buildHoroscopeForKnownUser(string $name, Carbon $birthdate): string
    {
        $dayOfBirth = $birthdate->dayOfWeekIso; // 1=จันทร์ ... 7=อาทิตย์
        $dayName = FortuneDailyHoroscopePost::DAY_NAMES[$dayOfBirth] ?? '?';
        $dayEmoji = FortuneDailyHoroscopePost::DAY_EMOJI[$dayOfBirth] ?? '✨';

        $post = FortuneDailyHoroscopePost::findTodayForDayOfBirth($dayOfBirth);

        if ($post !== null) {
            $teaser = $post->getShortCaption(140);
            if ($teaser !== '') {
                return "🌙 สวัสดีคุณ {$name}\n"
                    ."ดวงประจำวันสำหรับคนเกิดวัน{$dayName} {$dayEmoji}\n\n"
                    .$teaser."\n\n"
                    .'💫 อยากให้หมอเปิดเชิงลึก ทักมาบอกได้เลยค่ะ ✨';
            }
        }

        // วันนี้ยังไม่มี post → ใช้ teaser generic ตามวันเกิด
        return $this->buildGenericByDay($name, $dayName, $dayEmoji);
    }

    /**
     * Fallback ถ้าไม่มี post วันนี้ — generic ตามวัน
     */
    protected function buildGenericByDay(string $name, string $dayName, string $dayEmoji): string
    {
        return "🌙 สวัสดีคุณ {$name}\n"
            ."คนเกิดวัน{$dayName} {$dayEmoji} วันนี้พลังดวงดาวกำลังหมุนเวียนค่ะ\n\n"
            ."🔮 อยากรู้ดวงเชิงลึกวันนี้ ทักมาคุยกับหมอจันทราได้เลยนะคะ ✨";
    }

    /**
     * คำทักทายสำหรับลูกค้าใหม่ — ยังไม่มีข้อมูล (invite + promise)
     */
    protected function buildInviteForNewUser(string $name): string
    {
        return "🌙 สวัสดีค่ะคุณ {$name}\n\n"
            ."หมอจันทรายังไม่รู้จักดวงคุณเลยค่ะ ✨\n"
            ."ลองให้หมอเปิดไพ่ทำนายสักครั้งสิคะ\n\n"
            ."🎁 หลังจากนั้น หมอจะส่ง *ดวงประจำวันให้ฟรี* ทุกครั้งที่แวะมาเลย!";
    }

    /**
     * ป้องกัน name เป็น "FACEBOOK-XXXXX" หรือเปล่า — fallback "คุณ"
     */
    protected function normalizeName(?string $name): string
    {
        if (empty($name)) {
            return 'คุณ';
        }

        $name = trim($name);

        // กรอง code-pattern (raw FB id, empty, "คุณ" placeholder ที่เป็น default)
        if ($name === '' || $name === 'คุณ' || preg_match('/^FACEBOOK-/i', $name)) {
            return 'คุณ';
        }

        return $name;
    }
}
