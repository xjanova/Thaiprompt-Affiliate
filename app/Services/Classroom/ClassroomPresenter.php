<?php

namespace App\Services\Classroom;

use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\ClassroomLessonProgress;
use App\Models\ClassroomQuestion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 🎓 แปลงข้อมูลห้องเรียนเป็น payload เดียวกันทั้งแอป (API) และเว็บ (Blade)
 *
 * ห้ามส่งเฉลย (correct_index / answer_key) ออกไปก่อนส่งคำตอบเด็ดขาด
 */
class ClassroomPresenter
{
    public function __construct(private readonly ClassroomQuizService $quiz) {}

    /**
     * คอร์สที่เปิดอยู่ (1 คอร์สต่อบทบาท) + ความคืบหน้าของผู้ใช้ — query คงที่ไม่ขึ้นกับจำนวนคอร์ส
     *
     * @return array<int, array<string, mixed>>
     */
    public function courseList(User $user): array
    {
        $courses = self::activeCourses();
        $lessonIds = $courses->flatMap(fn (ClassroomCourse $c) => $c->activeLessons->pluck('id'))->all();
        $progress = $this->progressFor($user, $lessonIds);
        $certs = ClassroomCertification::query()->where('user_id', $user->id)->get()->keyBy('role');

        return $courses
            ->map(fn (ClassroomCourse $course) => $this->courseSummary($course, $progress, $certs->get($course->role)))
            ->values()
            ->all();
    }

    /**
     * รายละเอียดคอร์ส + บทเรียน + สถานะสอบของผู้ใช้
     *
     * @return array<string, mixed>
     */
    public function courseDetail(User $user, ClassroomCourse $course): array
    {
        $course->loadMissing('activeLessons');
        $lessons = $course->activeLessons;
        $progress = $this->progressFor($user, $lessons->pluck('id')->all());
        $cert = ClassroomCertification::query()->where('user_id', $user->id)->where('role', $course->role)->first();

        $summary = $this->courseSummary($course, $progress, $cert);
        $last = $this->quiz->lastSubmitted($user, $course);
        $open = $this->quiz->openAttempt($user, $course);
        $retryAt = $this->quiz->retryAvailableAt($user, $course);

        return array_merge($summary, [
            'lessons' => $lessons->map(fn (ClassroomLesson $lesson) => $this->lessonPayload($lesson, $progress->get($lesson->id)))->values()->all(),
            'quiz_unlocked' => $summary['lesson_count'] > 0 && $summary['completed_lessons'] >= $summary['lesson_count'],
            'last_attempt' => $last ? [
                'score' => (int) $last->score,
                'total' => (int) $last->total,
                'passed' => (bool) $last->passed,
                'submitted_at' => $last->submitted_at?->toIso8601String(),
            ] : null,
            'retry_available_at' => $retryAt?->toIso8601String(),
            'open_attempt' => $open ? [
                'attempt_id' => (int) $open->id,
                'expires_at' => $open->expires_at?->toIso8601String(),
            ] : null,
            'training_badge' => TrainingBadge::for($user, (string) $course->role),
        ]);
    }

    /**
     * บทเรียน 1 บท + ความคืบหน้า
     *
     * @return array<string, mixed>
     */
    public function lessonPayload(ClassroomLesson $lesson, ?ClassroomLessonProgress $progress): array
    {
        return [
            'id' => (int) $lesson->id,
            'title' => (string) $lesson->title,
            'youtube_id' => (string) $lesson->youtube_id,
            'duration_seconds' => (int) $lesson->duration_seconds,
            'watched_seconds' => (int) ($progress?->watched_seconds ?? 0),
            'completed' => $progress?->completed_at !== null,
            'required_seconds' => $lesson->requiredSeconds(),
            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),
            'thumbnail_url' => $lesson->thumbnailUrl(),
        ];
    }

    /**
     * ชุดข้อสอบที่ส่งให้ผู้สอบ (ตัวเลือกตามลำดับที่สลับแล้ว ไม่มีเฉลย)
     *
     * @return array<string, mixed>
     */
    public function attemptQuestions(ClassroomAttempt $attempt): array
    {
        $questions = $this->questionsOf($attempt);
        $orders = is_array($attempt->choice_order) ? $attempt->choice_order : [];

        $items = [];
        foreach ($attempt->questionIds() as $id) {
            $question = $questions->get($id);
            if ($question === null) {
                continue; // แอดมินลบข้อนี้ไประหว่างสอบ
            }
            $items[] = [
                'id' => $id,
                'question' => (string) $question->question,
                'choices' => self::displayedChoices($question, $orders[(string) $id] ?? null),
            ];
        }

        return [
            'attempt_id' => (int) $attempt->id,
            'course_id' => (int) $attempt->course_id,
            'expires_at' => $attempt->expires_at?->toIso8601String(),
            'time_left_seconds' => $attempt->expires_at ? max(0, $attempt->expires_at->getTimestamp() - now()->getTimestamp()) : 0,
            'total' => (int) $attempt->total,
            'pass_score' => (int) $attempt->pass_score,
            'questions' => $items,
        ];
    }

    /**
     * ผลสอบ (หลังส่งแล้วเท่านั้น) — เฉลย + คำอธิบายรายข้อ + ใบรับรอง + เวลาที่สอบใหม่ได้
     *
     * @param  array{certification: ClassroomCertification|null, newly_certified: bool, refreshed: bool, points_awarded: int}|null  $outcome  ผลจากการส่งครั้งนี้ (null = เปิดดูทีหลัง)
     * @return array<string, mixed>
     */
    public function attemptResult(ClassroomAttempt $attempt, ?array $outcome = null): array
    {
        $questions = $this->questionsOf($attempt);
        $orders = is_array($attempt->choice_order) ? $attempt->choice_order : [];
        $key = is_array($attempt->answer_key) ? $attempt->answer_key : [];
        $answers = is_array($attempt->answers) ? $attempt->answers : [];

        $results = [];
        foreach ($attempt->questionIds() as $id) {
            $question = $questions->get($id);
            $correctIndex = isset($key[(string) $id]) ? (int) $key[(string) $id] : null;
            $chosen = $answers[(string) $id] ?? null;
            $chosen = $chosen === null ? null : (int) $chosen;

            $results[] = [
                'question_id' => $id,
                'question' => $question ? (string) $question->question : 'ข้อนี้ถูกลบออกจากคลังแล้ว',
                'choices' => $question ? self::displayedChoices($question, $orders[(string) $id] ?? null) : [],
                'chosen_index' => $chosen,
                'correct' => $chosen !== null && $chosen === $correctIndex,
                'correct_index' => $correctIndex,
                'explanation' => $question?->explanation,
            ];
        }

        $course = $attempt->relationLoaded('course') ? $attempt->course : ClassroomCourse::query()->find($attempt->course_id);
        $role = (string) ($course?->role ?? '');

        $certification = null;
        if ($attempt->passed && $role !== '') {
            $cert = $outcome['certification']
                ?? ClassroomCertification::query()->where('user_id', $attempt->user_id)->where('role', $role)->first();
            if ($cert !== null) {
                $certification = [
                    'role' => $role,
                    'label' => TrainingBadge::label($role),
                    'certified_at' => $cert->certified_at?->toIso8601String(),
                    'points_awarded' => (int) ($outcome['points_awarded'] ?? 0),
                    'newly_certified' => (bool) ($outcome['newly_certified'] ?? false),
                    'refreshed' => (bool) ($outcome['refreshed'] ?? false),
                ];
            }
        }

        $retryAt = null;
        if (! $attempt->passed && $attempt->submitted_at !== null && $course !== null) {
            $at = $attempt->submitted_at->copy()->addSeconds(max(0, (int) $course->retry_cooldown_seconds));
            $retryAt = $at->isFuture() ? $at->toIso8601String() : null;
        }

        return [
            'attempt_id' => (int) $attempt->id,
            'course_id' => (int) $attempt->course_id,
            'score' => (int) $attempt->score,
            'total' => (int) $attempt->total,
            'pass_score' => (int) $attempt->pass_score,
            'passed' => (bool) $attempt->passed,
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'results' => $results,
            'certification' => $certification,
            'retry_available_at' => $retryAt,
            'training_badge' => $role !== '' ? TrainingBadge::for((int) $attempt->user_id, $role) : null,
        ];
    }

    /**
     * คอร์สที่เปิดอยู่ 1 ตัวต่อบทบาท (ตัวแรกตามลำดับ) พร้อมบทเรียนที่เปิดอยู่
     *
     * @return Collection<int, ClassroomCourse>
     */
    public static function activeCourses(): Collection
    {
        return ClassroomCourse::query()
            ->active()
            ->ordered()
            ->with('activeLessons')
            ->get()
            ->unique('role')
            ->values();
    }

    /**
     * @param  array<int, int>  $lessonIds
     * @return Collection<int, ClassroomLessonProgress>
     */
    private function progressFor(User $user, array $lessonIds): Collection
    {
        if ($lessonIds === []) {
            return collect();
        }

        return ClassroomLessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id');
    }

    /**
     * @param  Collection<int, ClassroomLessonProgress>  $progress
     * @return array<string, mixed>
     */
    private function courseSummary(ClassroomCourse $course, Collection $progress, ?ClassroomCertification $cert): array
    {
        $lessons = $course->activeLessons;
        $count = $lessons->count();
        $completed = $lessons->filter(fn (ClassroomLesson $l) => $progress->get($l->id)?->completed_at !== null)->count();

        return [
            'id' => (int) $course->id,
            'role' => (string) $course->role,
            'role_label' => $course->roleLabel(),
            'title' => (string) $course->title,
            'description' => $course->description,
            'rules_version' => (string) $course->rules_version,
            'lesson_count' => $count,
            'total_seconds' => (int) $lessons->sum('duration_seconds'),
            'completed_lessons' => $completed,
            'progress_pct' => $count > 0 ? (int) floor($completed * 100 / $count) : 0,
            'question_count' => (int) $course->question_count,
            'pass_score' => (int) $course->pass_score,
            'points_reward' => (int) $course->points_reward,
            'certified' => $cert !== null,
            'certified_at' => $cert?->certified_at?->toIso8601String(),
            'needs_refresh' => $cert !== null && (string) $cert->rules_version !== (string) $course->rules_version,
        ];
    }

    /**
     * @return Collection<int, ClassroomQuestion>
     */
    private function questionsOf(ClassroomAttempt $attempt): Collection
    {
        $ids = $attempt->questionIds();
        if ($ids === []) {
            return collect();
        }

        return ClassroomQuestion::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * ตัวเลือกตามลำดับที่แสดงให้ผู้สอบคนนี้
     *
     * @param  array<int, int>|null  $order
     * @return array<int, string>
     */
    private static function displayedChoices(ClassroomQuestion $question, ?array $order): array
    {
        $choices = $question->choiceList();
        if (! is_array($order) || $order === []) {
            return $choices;
        }

        $out = [];
        foreach ($order as $originalIndex) {
            if (array_key_exists((int) $originalIndex, $choices)) {
                $out[] = $choices[(int) $originalIndex];
            }
        }

        return $out;
    }
}
