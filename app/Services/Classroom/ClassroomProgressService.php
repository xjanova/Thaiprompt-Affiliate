<?php

namespace App\Services\Classroom;

use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\ClassroomLessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ⏱️ นับเวลาดูบทเรียนแบบกันกดข้าม (anti-skip)
 *
 * แอป/เว็บส่ง heartbeat ทุก 10 วินาทีระหว่างเล่น: {position_seconds, playing}
 * เซิร์ฟเวอร์นับเวลาให้ = min(ตำแหน่งที่ขยับไปข้างหน้า, เวลาจริงตั้งแต่ heartbeat ก่อนหน้า + 3 วิ)
 *   - นับเฉพาะเมื่อขยับไปข้างหน้า (delta > 0) และ heartbeat ก่อนหน้าห่างไม่เกิน 30 วิ
 *   - กดข้ามไปข้างหน้าได้เวลาเพิ่มไม่เกินเพดานนั้น → เวลาที่นับรวมไม่มีทางเกินเวลาที่เปิดดูจริง
 *   - heartbeat ค้าง (หลุดเน็ต/พักนาน) = ตั้งจุดเริ่มใหม่ ไม่นับช่วงนั้น
 * จบบทเมื่อเวลาที่นับ ≥ 90% ของความยาวที่แอดมินกรอก · สอบได้เมื่อจบครบทุกบทที่เปิดอยู่
 */
class ClassroomProgressService
{
    /** heartbeat ก่อนหน้าเก่ากว่านี้ (วินาที) = ไม่นับช่วงนั้น */
    public const STALE_AFTER_SECONDS = 30;

    /** เผื่อเวลาเครือข่าย/นาฬิกาคลาด (วินาที) ต่อ heartbeat */
    public const GRACE_SECONDS = 3;

    /**
     * บันทึก heartbeat 1 ครั้ง
     *
     * @return array{watched_seconds: int, completed: bool, course_quiz_unlocked: bool, required_seconds: int, last_position_seconds: int}
     *
     * @throws ClassroomException บทเรียน/คอร์สปิดอยู่
     */
    public function record(User $user, ClassroomLesson $lesson, int $positionSeconds, bool $playing = true): array
    {
        $lesson->loadMissing('course');
        $course = $lesson->course;
        if (! $lesson->is_active || ! $course || ! $course->is_active) {
            throw ClassroomException::lessonNotFound();
        }

        $duration = max(0, (int) $lesson->duration_seconds);
        // ตำแหน่งเกินความยาวที่กรอกไว้ = นับถึงแค่ความยาวนั้น (กันส่งเลขใหญ่ๆ มา)
        $position = max(0, $duration > 0 ? min($positionSeconds, $duration) : $positionSeconds);

        // แถวแรกของผู้ใช้+บทเรียน (กดพร้อมกัน 2 ครั้งไม่ซ้ำ — unique index)
        $seed = ClassroomLessonProgress::createOrFirst(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['watched_seconds' => 0, 'last_position_seconds' => 0]
        );

        /** @var ClassroomLessonProgress $progress */
        $progress = DB::transaction(function () use ($seed, $lesson, $duration, $position) {
            /** @var ClassroomLessonProgress $row */
            $row = ClassroomLessonProgress::query()->whereKey($seed->id)->lockForUpdate()->firstOrFail();
            $now = now();

            $credit = $this->creditFor($row, $position, $now->getTimestamp());
            $watched = (int) $row->watched_seconds + $credit;
            if ($duration > 0) {
                $watched = min($watched, $duration);
            }

            $row->watched_seconds = $watched;
            $row->last_position_seconds = $position;
            $row->last_heartbeat_at = $now;

            if ($row->completed_at === null && $duration > 0 && $watched >= $lesson->requiredSeconds()) {
                $row->completed_at = $now;
            }

            $row->save();

            return $row;
        });

        return [
            'watched_seconds' => (int) $progress->watched_seconds,
            'completed' => $progress->completed_at !== null,
            'course_quiz_unlocked' => $this->quizUnlocked($user, $course),
            'required_seconds' => $lesson->requiredSeconds(),
            'last_position_seconds' => (int) $progress->last_position_seconds,
        ];
    }

    /**
     * เวลาที่นับให้จาก heartbeat นี้ (วินาที)
     */
    public function creditFor(ClassroomLessonProgress $row, int $position, int $nowTs): int
    {
        if ($row->last_heartbeat_at === null) {
            return 0; // heartbeat แรก = ตั้งจุดเริ่ม
        }

        $elapsed = $nowTs - $row->last_heartbeat_at->getTimestamp();
        if ($elapsed < 0 || $elapsed > self::STALE_AFTER_SECONDS) {
            return 0; // ค้างนานเกิน → ไม่นับช่วงนี้
        }

        $delta = $position - (int) $row->last_position_seconds;
        if ($delta <= 0) {
            return 0; // หยุด/ย้อนกลับ
        }

        // 🔒 (2026-10-16) เครดิตไม่เกินเวลาจริงที่ผ่านไป — เดิม +GRACE ทุก heartbeat ทำให้ยิงถี่ๆ ปั๊มเวลาดูได้ 2.5–4 เท่า
        //    เกณฑ์จบบท 90% เผื่อความคลาดเคลื่อนของจังหวะ heartbeat ไว้แล้ว
        return min($delta, $elapsed);
    }

    /**
     * ดูครบทุกบทที่เปิดอยู่แล้ว (คอร์สที่ยังไม่มีบทเรียน = ยังสอบไม่ได้)
     */
    public function quizUnlocked(User $user, ClassroomCourse $course): bool
    {
        $lessonIds = $course->activeLessons()->pluck('id');
        if ($lessonIds->isEmpty()) {
            return false;
        }

        $done = ClassroomLessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        return $done >= $lessonIds->count();
    }
}
