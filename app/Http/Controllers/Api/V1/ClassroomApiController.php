<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\SellerAppResponses;
use App\Http\Controllers\Controller;
use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\User;
use App\Services\Classroom\ClassroomException;
use App\Services\Classroom\ClassroomPresenter;
use App\Services\Classroom\ClassroomProgressService;
use App\Services\Classroom\ClassroomQuizService;
use App\Support\SafeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🎓 ห้องเรียน (แอป) — คำตอบ {success, data, message, code?} ข้อความไทยทั้งหมด
 *
 * - GET  /classroom/courses                    → คอร์สที่เปิดอยู่ (1 ต่อบทบาท) + ความคืบหน้า + สถานะใบรับรอง
 * - GET  /classroom/courses/{id}               → บทเรียน + quiz_unlocked + ผลสอบล่าสุด + เวลาที่สอบใหม่ได้
 * - POST /classroom/lessons/{id}/progress      {position_seconds, playing} → heartbeat ทุก 10 วิ (กันกดข้ามฝั่งเซิร์ฟเวอร์)
 * - POST /classroom/courses/{id}/quiz/start    → ชุดข้อสอบ (ไม่มีเฉลย) · 423 QUIZ_LOCKED · 429 QUIZ_COOLDOWN
 * - POST /classroom/attempts/{id}/submit       {answers: {"<question_id>": <choice_index>}} → ผล + ใบรับรอง
 *     410 ATTEMPT_EXPIRED · 409 ATTEMPT_SUBMITTED · ชุดของคนอื่น = 404
 */
class ClassroomApiController extends Controller
{
    use SellerAppResponses;

    public function __construct(
        private readonly ClassroomPresenter $presenter,
        private readonly ClassroomProgressService $progress,
        private readonly ClassroomQuizService $quiz,
    ) {}

    /**
     * GET /classroom/courses
     */
    public function courses(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->run(fn () => $this->ok($this->presenter->courseList($user)));
    }

    /**
     * GET /classroom/courses/{id}
     */
    public function course(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->run(fn () => $this->ok($this->presenter->courseDetail($user, $this->activeCourse($id))));
    }

    /**
     * POST /classroom/lessons/{id}/progress {position_seconds:int, playing:bool}
     */
    public function progress(Request $request, int $id): JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'position_seconds' => 'required|integer|min:0|max:86400',
            'playing' => 'sometimes|boolean',
        ], [
            'position_seconds.required' => 'กรุณาส่งตำแหน่งวิดีโอ (วินาที)',
            'position_seconds.integer' => 'ตำแหน่งวิดีโอต้องเป็นตัวเลขวินาที',
            'position_seconds.min' => 'ตำแหน่งวิดีโอต้องไม่ติดลบ',
            'position_seconds.max' => 'ตำแหน่งวิดีโอไม่ถูกต้อง',
            'playing.boolean' => 'สถานะการเล่นต้องเป็น true หรือ false',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        /** @var User $user */
        $user = $request->user();

        return $this->run(function () use ($user, $id, $data) {
            $lesson = ClassroomLesson::query()->find($id);
            if ($lesson === null) {
                throw ClassroomException::lessonNotFound();
            }

            return $this->ok($this->progress->record($user, $lesson, (int) $data['position_seconds'], (bool) ($data['playing'] ?? true)));
        });
    }

    /**
     * POST /classroom/courses/{id}/quiz/start
     */
    public function startQuiz(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->run(function () use ($user, $id) {
            $attempt = $this->quiz->start($user, $this->activeCourse($id));

            return $this->ok($this->presenter->attemptQuestions($attempt), 'เริ่มสอบได้เลย มีเวลา 30 นาที');
        });
    }

    /**
     * POST /classroom/attempts/{id}/submit {answers: {"<question_id>": <choice_index>}}
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $data = $this->validateOrFail($request, [
            'answers' => 'present|array',
        ], [
            'answers.present' => 'กรุณาส่งคำตอบ',
            'answers.array' => 'รูปแบบคำตอบไม่ถูกต้อง',
        ]);
        if ($data instanceof JsonResponse) {
            return $data;
        }

        /** @var User $user */
        $user = $request->user();

        return $this->run(function () use ($user, $id, $data) {
            $result = $this->quiz->submit($user, $id, (array) $data['answers']);
            $payload = $this->presenter->attemptResult($result['attempt'], $result['certification']);

            $message = $payload['passed']
                ? (($payload['certification']['newly_certified'] ?? false) ? 'ยินดีด้วย! คุณผ่านการอบรมแล้ว' : 'สอบผ่านแล้ว')
                : 'ยังไม่ผ่าน ลองทบทวนบทเรียนแล้วสอบใหม่ได้';

            return $this->ok($payload, $message);
        });
    }

    private function activeCourse(int $id): ClassroomCourse
    {
        $course = ClassroomCourse::query()->active()->find($id);
        if ($course === null) {
            throw ClassroomException::courseNotFound();
        }

        return $course;
    }

    private function run(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (ClassroomException $e) {
            return $this->fail($e->errorCode, $e->getMessage(), $e->status, $e->data);
        } catch (\Throwable $e) {
            Log::error('ClassroomApi: failed', ['error' => SafeLog::exceptionMessage($e)]);

            return $this->fail('CLASSROOM_ERROR', 'ระบบห้องเรียนขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง', 500);
        }
    }
}
