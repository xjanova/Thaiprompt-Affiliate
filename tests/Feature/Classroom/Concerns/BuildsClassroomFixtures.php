<?php

namespace Tests\Feature\Classroom\Concerns;

use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\ClassroomLessonProgress;
use App\Models\ClassroomQuestion;
use App\Models\User;
use App\Services\Classroom\TrainingBadge;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * ตัวช่วยสร้างข้อมูลห้องเรียนในเทสต์
 *
 * ⚠️ บนเครื่อง dev migration 2026_10_13_150100 ใส่คอร์ส+ข้อสอบตั้งต้นไว้ในฐานเทสต์แล้ว แต่บน CI ไม่มี
 *    (schema dump ไม่เก็บข้อมูล) → ทุกเทสต์ล้างตาราง classroom_* ก่อนแล้วสร้างเอง ผลเหมือนกันทุกเครื่อง
 *    (DELETE เป็น DML อยู่ใน transaction ของ RefreshDatabase — ถูกย้อนกลับหลังจบเทสต์)
 */
trait BuildsClassroomFixtures
{
    protected function resetClassroom(): void
    {
        ClassroomCertification::query()->delete();
        ClassroomAttempt::query()->delete();
        ClassroomLessonProgress::query()->delete();
        ClassroomQuestion::query()->delete();
        ClassroomLesson::query()->delete();
        ClassroomCourse::query()->delete();
        TrainingBadge::forget();
    }

    /**
     * เวลานิ่งที่วินาทีเต็ม (heartbeat คิดจากวินาทีที่เก็บใน DB)
     */
    protected function freezeClock(string $at = '2026-10-13 10:00:00'): void
    {
        $this->travelTo(Carbon::parse($at));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeCourse(string $role = 'rider', array $overrides = []): ClassroomCourse
    {
        return ClassroomCourse::create(array_merge([
            'role' => $role,
            'title' => 'คอร์สทดสอบ '.$role,
            'description' => 'คำอธิบายทดสอบ',
            'rules_version' => '2026-10',
            'question_count' => 4,
            'pass_score' => 3,
            'points_reward' => 10,
            'retry_cooldown_seconds' => 60,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    protected function makeLesson(ClassroomCourse $course, int $duration = 100, array $overrides = []): ClassroomLesson
    {
        return ClassroomLesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'บทเรียน '.($course->lessons()->count() + 1),
            'youtube_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => $duration,
            'sort_order' => $course->lessons()->count() + 1,
            'is_active' => true,
        ], $overrides));
    }

    /**
     * ข้อสอบ n ข้อ — ตัวเลือกที่ถูกคือ "ถูก" เสมอ (ตำแหน่งในคลัง = 1) ใช้ข้อความหาเฉลยหลังสลับตัวเลือก
     *
     * @return array<int, ClassroomQuestion>
     */
    protected function makeQuestions(ClassroomCourse $course, int $count = 4): array
    {
        $out = [];
        for ($i = 1; $i <= $count; $i++) {
            $out[] = ClassroomQuestion::create([
                'course_id' => $course->id,
                'question' => 'คำถามที่ '.$i,
                'choices' => ['ผิด ก '.$i, 'ถูก', 'ผิด ข '.$i, 'ผิด ค '.$i],
                'correct_index' => 1,
                'explanation' => 'คำอธิบายข้อ '.$i,
                'is_active' => true,
            ]);
        }

        return $out;
    }

    /**
     * ทำให้ผู้ใช้ดูบทเรียนจบ (ข้ามขั้น heartbeat — ใช้ในเทสต์ที่ไม่ได้ทดสอบการนับเวลา)
     */
    protected function completeLesson(User $user, ClassroomLesson $lesson): void
    {
        ClassroomLessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['watched_seconds' => $lesson->duration_seconds, 'last_position_seconds' => $lesson->duration_seconds, 'completed_at' => now()]
        );
    }

    /**
     * คอร์สพร้อมสอบ: บทเรียน 1 บท (ดูจบแล้ว) + ข้อสอบ 4 ข้อ
     *
     * @return array{0: ClassroomCourse, 1: User}
     */
    protected function readyCourse(string $role = 'rider', array $courseOverrides = []): array
    {
        $course = $this->makeCourse($role, $courseOverrides);
        $lesson = $this->makeLesson($course);
        $this->makeQuestions($course, 4);
        $user = User::factory()->create();
        $this->completeLesson($user, $lesson);

        return [$course, $user];
    }

    protected function heartbeat(User $user, ClassroomLesson $lesson, int $position, bool $playing = true): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/v1/classroom/lessons/'.$lesson->id.'/progress', [
            'position_seconds' => $position,
            'playing' => $playing,
        ]);
    }

    protected function startQuiz(User $user, ClassroomCourse $course): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/v1/classroom/courses/'.$course->id.'/quiz/start');
    }

    /**
     * ตอบตามที่สั่ง: correct = จำนวนข้อที่ตอบถูก (ที่เหลือตอบผิด) — หาตำแหน่งจากข้อความ "ถูก" ในตัวเลือกที่สลับแล้ว
     *
     * @param  array<string, mixed>  $quiz  data จาก quiz/start
     * @return array<string, int>
     */
    protected function answersFor(array $quiz, int $correct): array
    {
        $answers = [];
        foreach ($quiz['questions'] as $n => $question) {
            $right = array_search('ถูก', $question['choices'], true);
            $answers[(string) $question['id']] = $n < $correct ? $right : ($right === 0 ? 1 : 0);
        }

        return $answers;
    }

    protected function submitQuiz(User $user, int $attemptId, array $answers): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/v1/classroom/attempts/'.$attemptId.'/submit', ['answers' => $answers]);
    }

    /**
     * สอบผ่านทั้งชุด (เริ่ม + ตอบถูกทุกข้อ)
     */
    protected function passQuiz(User $user, ClassroomCourse $course): TestResponse
    {
        $quiz = $this->startQuiz($user, $course)->assertOk()->json('data');

        return $this->submitQuiz($user, (int) $quiz['attempt_id'], $this->answersFor($quiz, count($quiz['questions'])));
    }
}
