<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassroomAttempt;
use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\ClassroomLessonProgress;
use App\Models\User;
use App\Services\Classroom\ClassroomException;
use App\Services\Classroom\ClassroomPresenter;
use App\Services\Classroom\ClassroomProgressService;
use App\Services\Classroom\ClassroomQuizService;
use App\Support\SafeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * 🎓 ห้องเรียนบนเว็บ (/user/classroom) — ใช้ service ชุดเดียวกับแอป (ผลเหมือนกันทุกช่องทาง)
 *
 * - index      รายการคอร์ส 3 บทบาท + ความคืบหน้า + ป้ายที่ได้แล้ว
 * - course     บทเรียนในคอร์ส + สถานะสอบ (ล็อก / พร้อมสอบ / รอสอบใหม่ / ทำค้าง)
 * - lesson     เล่นวิดีโอด้วย YouTube IFrame API + ส่ง heartbeat ทุก 10 วิ (progress → JSON)
 * - startQuiz  เริ่มสอบ → พาไปหน้าข้อสอบ · quiz/submit/result
 */
class ClassroomController extends Controller
{
    public function __construct(
        private readonly ClassroomPresenter $presenter,
        private readonly ClassroomProgressService $progress,
        private readonly ClassroomQuizService $quiz,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('user.classroom.index', [
            'courses' => $this->presenter->courseList($user),
            'pageTitle' => 'ห้องเรียน',
        ]);
    }

    public function course(Request $request, int $course): View
    {
        /** @var User $user */
        $user = $request->user();
        $model = $this->activeCourseOr404($course);

        return view('user.classroom.course', [
            'course' => $this->presenter->courseDetail($user, $model),
            'pageTitle' => $model->title,
        ]);
    }

    public function lesson(Request $request, int $lesson): View
    {
        /** @var User $user */
        $user = $request->user();
        $model = ClassroomLesson::query()->with('course')->find($lesson);
        abort_if($model === null || ! $model->is_active || ! $model->course?->is_active, 404);

        $course = $model->course;
        $lessons = $course->activeLessons()->get();
        $position = $lessons->search(fn (ClassroomLesson $l) => (int) $l->id === (int) $model->id);
        $progress = ClassroomLessonProgress::query()->where('user_id', $user->id)->where('lesson_id', $model->id)->first();

        return view('user.classroom.lesson', [
            'course' => $course,
            'lesson' => $this->presenter->lessonPayload($model, $progress),
            'lessonNumber' => $position === false ? null : $position + 1,
            'lessonTotal' => $lessons->count(),
            'previousLesson' => $position !== false && $position > 0 ? $lessons[$position - 1] : null,
            'nextLesson' => $position !== false ? ($lessons[$position + 1] ?? null) : null,
            'quizUnlocked' => $this->progress->quizUnlocked($user, $course),
            'pageTitle' => $model->title,
        ]);
    }

    /**
     * POST heartbeat จากหน้าเล่นวิดีโอ (session + CSRF) → JSON เดียวกับแอป
     */
    public function progress(Request $request, int $lesson): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'position_seconds' => 'required|integer|min:0|max:86400',
            'playing' => 'sometimes|boolean',
        ], [
            'position_seconds.required' => 'กรุณาส่งตำแหน่งวิดีโอ (วินาที)',
            'position_seconds.integer' => 'ตำแหน่งวิดีโอต้องเป็นตัวเลขวินาที',
            'position_seconds.min' => 'ตำแหน่งวิดีโอต้องไม่ติดลบ',
            'position_seconds.max' => 'ตำแหน่งวิดีโอไม่ถูกต้อง',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'code' => 'VALIDATION_ERROR', 'message' => (string) $validator->errors()->first()], 422);
        }

        /** @var User $user */
        $user = $request->user();

        try {
            $model = ClassroomLesson::query()->find($lesson);
            if ($model === null) {
                throw ClassroomException::lessonNotFound();
            }
            $data = $this->progress->record($user, $model, (int) $request->input('position_seconds'), $request->boolean('playing', true));

            return response()->json(['success' => true, 'message' => 'สำเร็จ', 'data' => $data]);
        } catch (ClassroomException $e) {
            return response()->json(['success' => false, 'code' => $e->errorCode, 'message' => $e->getMessage()], $e->status);
        } catch (\Throwable $e) {
            Log::error('Classroom web progress failed', ['error' => SafeLog::exceptionMessage($e)]);

            return response()->json(['success' => false, 'code' => 'CLASSROOM_ERROR', 'message' => 'บันทึกเวลาดูไม่สำเร็จ กรุณาลองใหม่'], 500);
        }
    }

    public function startQuiz(Request $request, int $course): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $model = $this->activeCourseOr404($course);

        try {
            $attempt = $this->quiz->start($user, $model);
        } catch (ClassroomException $e) {
            return redirect()->route('user.classroom.course', $model->id)->with('error', $e->getMessage());
        }

        return redirect()->route('user.classroom.attempt', $attempt->id);
    }

    public function attempt(Request $request, int $attempt): View|RedirectResponse
    {
        $model = $this->ownAttemptOr404($request, $attempt);

        if ($model->isSubmitted()) {
            return redirect()->route('user.classroom.result', $model->id);
        }
        if ($model->isExpired()) {
            return redirect()->route('user.classroom.course', $model->course_id)
                ->with('error', 'หมดเวลาทำข้อสอบชุดนี้แล้ว กรุณาเริ่มสอบใหม่');
        }

        return view('user.classroom.quiz', [
            'course' => $model->course,
            'quiz' => $this->presenter->attemptQuestions($model),
            'pageTitle' => 'สอบ: '.$model->course?->title,
        ]);
    }

    public function submit(Request $request, int $attempt): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $answers = $request->input('answers', []);

        try {
            $result = $this->quiz->submit($user, $attempt, is_array($answers) ? $answers : []);
        } catch (ClassroomException $e) {
            if ($e->errorCode === 'ATTEMPT_SUBMITTED') {
                return redirect()->route('user.classroom.result', $attempt);
            }
            if ($e->errorCode === 'ATTEMPT_NOT_FOUND') {
                abort(404);
            }

            $courseId = ClassroomAttempt::query()->whereKey($attempt)->where('user_id', $user->id)->value('course_id');

            return $courseId
                ? redirect()->route('user.classroom.course', $courseId)->with('error', $e->getMessage())
                : redirect()->route('user.classroom.index')->with('error', $e->getMessage());
        }

        $certification = $result['certification'];

        return redirect()->route('user.classroom.result', $result['attempt']->id)->with('classroom_outcome', [
            'newly_certified' => (bool) ($certification['newly_certified'] ?? false),
            'refreshed' => (bool) ($certification['refreshed'] ?? false),
            'points_awarded' => (int) ($certification['points_awarded'] ?? 0),
        ]);
    }

    public function result(Request $request, int $attempt): View|RedirectResponse
    {
        $model = $this->ownAttemptOr404($request, $attempt);
        if (! $model->isSubmitted()) {
            return redirect()->route('user.classroom.attempt', $model->id);
        }

        $flash = $request->session()->get('classroom_outcome');
        $outcome = is_array($flash) ? array_merge(['certification' => null], $flash) : null;

        return view('user.classroom.result', [
            'course' => $model->course,
            'result' => $this->presenter->attemptResult($model, $outcome),
            'pageTitle' => 'ผลสอบ: '.$model->course?->title,
        ]);
    }

    private function activeCourseOr404(int $id): ClassroomCourse
    {
        $course = ClassroomCourse::query()->active()->find($id);
        abort_if($course === null, 404);

        return $course;
    }

    private function ownAttemptOr404(Request $request, int $id): ClassroomAttempt
    {
        $attempt = ClassroomAttempt::query()->with('course')->whereKey($id)->where('user_id', $request->user()->id)->first();
        abort_if($attempt === null, 404);

        return $attempt;
    }
}
