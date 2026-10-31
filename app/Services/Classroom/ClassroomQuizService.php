<?php

namespace App\Services\Classroom;

use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\ClassroomQuestion;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 📝 สอบท้ายคอร์ส: เริ่มสอบ (สุ่มข้อ + สลับตัวเลือก + เก็บเฉลยฝั่งเซิร์ฟเวอร์) → ส่งคำตอบ → ตรวจ → ออกใบรับรอง
 *
 * - เริ่มสอบได้เมื่อดูบทเรียนครบ (ไม่ครบ = QUIZ_LOCKED 423) และพ้นช่วงรอหลังสอบไม่ผ่าน (QUIZ_COOLDOWN 429)
 * - กดเริ่มซ้ำระหว่างชุดเดิมยังไม่หมดเวลา = ได้ชุดเดิม (ไม่สุ่มใหม่ — กันสุ่มหาข้อง่าย)
 * - ชุดข้อสอบหมดอายุใน 30 นาที (ATTEMPT_EXPIRED 410) · ส่งซ้ำ = ATTEMPT_SUBMITTED 409
 * - ปล่อยชุดให้หมดเวลาโดยไม่ส่ง = นับเหมือนสอบไม่ผ่าน (ต้องรอช่วงรอเหมือนกัน)
 */
class ClassroomQuizService
{
    public function __construct(
        private readonly ClassroomProgressService $progress,
        private readonly ClassroomCertifier $certifier,
    ) {}

    /**
     * เริ่มสอบ (หรือคืนชุดที่ยังทำค้างอยู่)
     *
     * @throws ClassroomException QUIZ_LOCKED | QUIZ_COOLDOWN | COURSE_NOT_FOUND | QUIZ_BUSY
     */
    public function start(User $user, ClassroomCourse $course): ClassroomAttempt
    {
        if (! $course->is_active) {
            throw ClassroomException::courseNotFound();
        }

        if (! $course->activeLessons()->exists()) {
            throw ClassroomException::locked('คอร์สนี้ยังไม่มีบทเรียน กรุณารอแอดมินเพิ่มบทเรียนก่อน');
        }

        if (! $this->progress->quizUnlocked($user, $course)) {
            throw ClassroomException::locked();
        }

        try {
            // กดเริ่มรัวๆ / สองเครื่องพร้อมกัน → ได้ชุดเดียว
            return Cache::lock('classroom:quiz-start:'.$user->id.':'.$course->id, 10)
                ->block(5, fn () => $this->startLocked($user, $course));
        } catch (LockTimeoutException) {
            throw ClassroomException::busy();
        }
    }

    private function startLocked(User $user, ClassroomCourse $course): ClassroomAttempt
    {
        $open = $this->openAttempt($user, $course);
        if ($open !== null) {
            return $open;
        }

        $retryAt = $this->retryAvailableAt($user, $course);
        if ($retryAt !== null) {
            throw ClassroomException::cooldown($retryAt);
        }

        $questions = ClassroomQuestion::query()
            ->where('course_id', $course->id)
            ->active()
            ->inRandomOrder()
            ->limit(max(1, (int) $course->question_count))
            ->get();

        $questions = $questions->filter(fn (ClassroomQuestion $q) => count($q->choiceList()) >= ClassroomQuestion::MIN_CHOICES
            && isset($q->choiceList()[$q->correct_index]))->values();

        if ($questions->isEmpty()) {
            throw ClassroomException::locked('ยังไม่มีข้อสอบในคอร์สนี้ กรุณาลองใหม่ภายหลัง');
        }

        $choiceOrder = [];
        $answerKey = [];
        foreach ($questions as $question) {
            $order = array_keys($question->choiceList());
            shuffle($order);
            $choiceOrder[(string) $question->id] = $order;
            // ตำแหน่งที่ถูก "ตามลำดับที่แสดง"
            $answerKey[(string) $question->id] = (int) array_search((int) $question->correct_index, $order, true);
        }

        $total = $questions->count();
        $now = now();

        return ClassroomAttempt::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'question_ids' => $questions->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'choice_order' => $choiceOrder,
            'answer_key' => $answerKey,
            'total' => $total,
            'pass_score' => self::requiredScore($course, $total),
            'passed' => false,
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes(ClassroomAttempt::TTL_MINUTES),
        ]);
    }

    /**
     * เกณฑ์ผ่านของชุดนี้ — คลังข้อสอบมีน้อยกว่าจำนวนข้อที่ตั้งไว้ = ลดเกณฑ์ตามสัดส่วน (ปัดขึ้น)
     */
    public static function requiredScore(ClassroomCourse $course, int $total): int
    {
        $configured = max(1, (int) $course->question_count);
        $pass = max(1, (int) $course->pass_score);

        if ($total >= $configured) {
            return min($pass, $total);
        }

        return max(1, min($total, (int) ceil($total * $pass / $configured)));
    }

    /**
     * ส่งคำตอบ + ตรวจ + ออกใบรับรองเมื่อผ่าน
     *
     * @param  array<string|int, mixed>  $answers  {"<question_id>": <choice_index>}
     * @return array{attempt: ClassroomAttempt, certification: array{certification: ClassroomCertification|null, newly_certified: bool, refreshed: bool, points_awarded: int}|null}
     *
     * @throws ClassroomException ATTEMPT_NOT_FOUND | ATTEMPT_SUBMITTED | ATTEMPT_EXPIRED
     */
    public function submit(User $user, int $attemptId, array $answers): array
    {
        return DB::transaction(function () use ($user, $attemptId, $answers) {
            /** @var ClassroomAttempt|null $attempt */
            $attempt = ClassroomAttempt::query()
                ->whereKey($attemptId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($attempt === null) {
                throw ClassroomException::attemptNotFound();
            }
            if ($attempt->isSubmitted()) {
                throw ClassroomException::submitted();
            }
            if ($attempt->isExpired()) {
                throw ClassroomException::expired();
            }

            $key = is_array($attempt->answer_key) ? $attempt->answer_key : [];
            $orders = is_array($attempt->choice_order) ? $attempt->choice_order : [];
            $clean = [];
            $score = 0;

            foreach ($attempt->questionIds() as $questionId) {
                $raw = $answers[(string) $questionId] ?? $answers[$questionId] ?? null;
                $choiceCount = count((array) ($orders[(string) $questionId] ?? []));
                $chosen = self::normalizeChoice($raw, $choiceCount);
                $clean[(string) $questionId] = $chosen;

                if ($chosen !== null && array_key_exists((string) $questionId, $key) && $chosen === (int) $key[(string) $questionId]) {
                    $score++;
                }
            }

            $attempt->answers = $clean;
            $attempt->score = $score;
            $attempt->passed = $score >= (int) $attempt->pass_score;
            $attempt->submitted_at = now();
            $attempt->save();

            $certification = null;
            if ($attempt->passed) {
                $course = ClassroomCourse::query()->find($attempt->course_id);
                if ($course !== null) {
                    $certification = $this->certifier->certify($user, $course, $attempt);
                }
            }

            return ['attempt' => $attempt, 'certification' => $certification];
        });
    }

    /**
     * ชุดข้อสอบที่ยังทำค้าง (ยังไม่ส่ง + ยังไม่หมดเวลา)
     */
    public function openAttempt(User $user, ClassroomCourse $course): ?ClassroomAttempt
    {
        return ClassroomAttempt::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNull('submitted_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * สอบใหม่ได้ตอนไหน (null = ได้เลย)
     *
     * นับจากชุดล่าสุดที่จบแล้ว: สอบไม่ผ่าน (นับจากเวลาส่ง) หรือปล่อยหมดเวลา (นับจากเวลาหมดอายุ)
     */
    public function retryAvailableAt(User $user, ClassroomCourse $course): ?Carbon
    {
        $cooldown = max(0, (int) $course->retry_cooldown_seconds);
        if ($cooldown === 0) {
            return null;
        }

        /** @var ClassroomAttempt|null $last */
        $last = ClassroomAttempt::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where(fn ($q) => $q->whereNotNull('submitted_at')->orWhere('expires_at', '<=', now()))
            ->latest('id')
            ->first();

        if ($last === null || $last->passed) {
            return null;
        }

        $endedAt = $last->submitted_at ?? $last->expires_at;
        if ($endedAt === null) {
            return null;
        }

        $retryAt = $endedAt->copy()->addSeconds($cooldown);

        return $retryAt->isFuture() ? $retryAt : null;
    }

    /**
     * ชุดล่าสุดที่ส่งคำตอบแล้ว
     */
    public function lastSubmitted(User $user, ClassroomCourse $course): ?ClassroomAttempt
    {
        return ClassroomAttempt::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->latest('id')
            ->first();
    }

    /**
     * คำตอบที่ส่งมา → ตำแหน่งตัวเลือก (ผิดรูปแบบ/นอกช่วง = ไม่ตอบ)
     */
    private static function normalizeChoice(mixed $raw, int $choiceCount): ?int
    {
        if (is_bool($raw) || $raw === null || $raw === '') {
            return null;
        }
        if (is_string($raw) && ! ctype_digit($raw)) {
            return null;
        }
        if (! is_int($raw) && ! is_string($raw)) {
            return null;
        }

        $index = (int) $raw;

        return ($index >= 0 && $index < $choiceCount) ? $index : null;
    }
}
