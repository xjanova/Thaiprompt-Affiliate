<?php

namespace App\Services\Classroom;

use App\Events\Marketplace\TrainingCertified;
use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * 🏅 ออกใบรับรอง "ผ่านการอบรม" (1 ใบต่อ ผู้ใช้+บทบาท)
 *
 * - ผ่านครั้งแรก → สร้างใบ + points_awarded = points_reward ของคอร์ส → ยิง TrainingCertified(points)
 * - ใบเดิมเป็นรุ่นกติกาเก่า (needs_refresh) → อัปเดตรุ่น + เวลา → ยิง TrainingCertified(0) (ไม่ให้คะแนนซ้ำ)
 * - ใบเดิมเป็นรุ่นปัจจุบันแล้ว → ไม่เปลี่ยนอะไร ไม่ยิง event
 * ปลอดภัยเมื่อส่งพร้อมกัน: unique(user_id, role) + อัปเดตแบบมีเงื่อนไข (แถวเดียวเท่านั้นที่ "ชนะ")
 * event ยิงหลัง commit เท่านั้น (DB::afterCommit)
 */
class ClassroomCertifier
{
    /**
     * @return array{certification: ClassroomCertification|null, newly_certified: bool, refreshed: bool, points_awarded: int}
     */
    public function certify(User $user, ClassroomCourse $course, ?ClassroomAttempt $attempt = null): array
    {
        $role = (string) $course->role;
        $now = now();

        $existing = ClassroomCertification::query()->where('user_id', $user->id)->where('role', $role)->first();

        if ($existing === null) {
            try {
                $cert = ClassroomCertification::create([
                    'user_id' => $user->id,
                    'role' => $role,
                    'course_id' => $course->id,
                    'rules_version' => (string) $course->rules_version,
                    'attempt_id' => $attempt?->id,
                    'points_awarded' => max(0, (int) $course->points_reward),
                    'certified_at' => $now,
                ]);

                $this->fire($user->id, $role, (int) $course->id, (int) $cert->points_awarded);

                return ['certification' => $cert, 'newly_certified' => true, 'refreshed' => false, 'points_awarded' => (int) $cert->points_awarded];
            } catch (UniqueConstraintViolationException) {
                // อีกคำขอสร้างไปก่อนแล้ว → อ่านแถวล่าสุดแบบล็อก (เห็นค่าที่ commit แล้ว)
                $existing = ClassroomCertification::query()->where('user_id', $user->id)->where('role', $role)->lockForUpdate()->first();
                if ($existing === null) {
                    return ['certification' => null, 'newly_certified' => false, 'refreshed' => false, 'points_awarded' => 0];
                }
            }
        }

        $refreshed = false;
        if ((string) $existing->rules_version !== (string) $course->rules_version) {
            $affected = ClassroomCertification::query()
                ->whereKey($existing->id)
                ->where('rules_version', '!=', (string) $course->rules_version)
                ->update([
                    'rules_version' => (string) $course->rules_version,
                    'course_id' => $course->id,
                    'attempt_id' => $attempt?->id,
                    'certified_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 1) {
                $refreshed = true;
                $this->fire($user->id, $role, (int) $course->id, 0);
            }

            $existing->refresh();
        }

        return ['certification' => $existing, 'newly_certified' => false, 'refreshed' => $refreshed, 'points_awarded' => 0];
    }

    private function fire(int $userId, string $role, int $courseId, int $points): void
    {
        TrainingBadge::forget($userId);

        DB::afterCommit(static function () use ($userId, $role, $courseId, $points) {
            TrainingCertified::dispatch($userId, $role, $courseId, $points);
        });
    }
}
