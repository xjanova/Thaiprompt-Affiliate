<?php

namespace App\Events\Marketplace;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * 🎓 ผู้ใช้ผ่านการอบรมของบทบาทหนึ่ง (ห้องเรียน) — ระบบคะแนนชื่อเสียง/ความประพฤติจะมาฟัง event นี้
 *
 * ยิงหลังบันทึกใบรับรอง commit แล้วเท่านั้น · 1 ครั้งต่อ ผู้ใช้+บทบาท
 * อบรมทบทวนหลังเปลี่ยนรุ่นกติกา (rules_version) ยิงอีกครั้งด้วย pointsAwarded = 0 (ไม่ให้คะแนนซ้ำ)
 * payload เป็น id ล้วน (ไม่ผูกโมเดล — ฟังผ่านคิวได้)
 *
 * @example TrainingCertified::dispatch($user->id, 'rider', $course->id, 10);
 */
class TrainingCertified
{
    use Dispatchable;

    /**
     * @param  int  $userId  users.id
     * @param  string  $role  seller|rider|buyer
     * @param  int  $courseId  classroom_courses.id
     * @param  int  $pointsAwarded  คะแนนที่ต้องให้ (ผ่านครั้งแรก = points_reward ของคอร์ส · ทบทวน = 0)
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $role,
        public readonly int $courseId,
        public readonly int $pointsAwarded,
    ) {}
}
