<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassroomAttempt;
use App\Models\ClassroomCertification;
use App\Models\ClassroomCourse;
use App\Models\ClassroomLesson;
use App\Models\ClassroomLessonProgress;
use App\Models\ClassroomQuestion;
use App\Services\Classroom\TrainingBadge;
use App\Services\Classroom\YouTubeId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 🎓 หลังบ้านห้องเรียน (/admin/classroom)
 *
 * - คอร์ส 3 บทบาท: แก้ชื่อ/คำอธิบาย/รุ่นกติกา/จำนวนข้อ/เกณฑ์ผ่าน/คะแนน/เวลารอสอบใหม่ (เปิดได้ทีละ 1 คอร์สต่อบทบาท)
 *   เปลี่ยนรุ่นกติกา → ใบรับรองรุ่นเก่ากลายเป็น "ควรทบทวน" อัตโนมัติ (คะแนนไม่หาย)
 * - บทเรียน: วางลิงก์ YouTube แบบไหนก็ได้ (แยกรหัส 11 ตัวเอง) + ความยาว mm:ss · จัดลำดับ · เปิด/ปิด · ลบ (ยืนยันก่อน)
 * - คลังข้อสอบ: เพิ่ม/แก้/เปิดปิด/ลบ (ตัวเลือก 2–6 ข้อ ถูก 1 ข้อ + คำอธิบาย)
 * - สถิติ: ผู้เรียน บทที่จบ การสอบ อัตราผ่าน รายชื่อผู้ผ่านการอบรม
 */
class ClassroomAdminController extends Controller
{
    // =====================================================
    // คอร์ส
    // =====================================================

    public function index(): View
    {
        $courses = ClassroomCourse::query()
            ->ordered()
            ->withCount([
                'lessons',
                'lessons as active_lessons_count' => fn ($q) => $q->where('is_active', true),
                'questions as active_questions_count' => fn ($q) => $q->where('is_active', true),
                'attempts as submitted_attempts_count' => fn ($q) => $q->whereNotNull('submitted_at'),
                'attempts as passed_attempts_count' => fn ($q) => $q->whereNotNull('submitted_at')->where('passed', true),
            ])
            ->get();

        $certCounts = ClassroomCertification::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.classroom.index', [
            'courses' => $courses,
            'certCounts' => $certCounts,
        ]);
    }

    public function editCourse(ClassroomCourse $course): View
    {
        $course->load(['lessons' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);

        $completedByLesson = ClassroomLessonProgress::query()
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->select('lesson_id', DB::raw('COUNT(*) as total'))
            ->groupBy('lesson_id')
            ->pluck('total', 'lesson_id');

        return view('admin.classroom.edit', [
            'course' => $course,
            'completedByLesson' => $completedByLesson,
            'certifiedCount' => ClassroomCertification::query()->where('role', $course->role)->count(),
        ]);
    }

    public function updateCourse(Request $request, ClassroomCourse $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:3000',
            'rules_version' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/'],
            'question_count' => 'required|integer|min:1|max:100',
            'pass_score' => 'required|integer|min:1|lte:question_count',
            'points_reward' => 'required|integer|min:0|max:1000',
            'retry_cooldown_seconds' => 'required|integer|min:0|max:86400',
            'is_active' => 'sometimes|boolean',
        ], [
            'title.required' => 'กรุณากรอกชื่อคอร์ส',
            'title.max' => 'ชื่อคอร์สยาวเกิน 150 ตัวอักษร',
            'rules_version.required' => 'กรุณากรอกรุ่นกติกา เช่น 2026-10',
            'rules_version.regex' => 'รุ่นกติกาใช้ได้เฉพาะตัวอักษรอังกฤษ ตัวเลข จุด ขีด เช่น 2026-10',
            'question_count.min' => 'จำนวนข้อสอบต้องอย่างน้อย 1 ข้อ',
            'question_count.max' => 'จำนวนข้อสอบต้องไม่เกิน 100 ข้อ',
            'pass_score.lte' => 'เกณฑ์ผ่านต้องไม่มากกว่าจำนวนข้อสอบ',
            'pass_score.min' => 'เกณฑ์ผ่านต้องอย่างน้อย 1 ข้อ',
            'points_reward.max' => 'คะแนนรางวัลต้องไม่เกิน 1,000',
            'retry_cooldown_seconds.max' => 'เวลารอสอบใหม่ต้องไม่เกิน 86,400 วินาที (1 วัน)',
        ]);

        $oldVersion = (string) $course->rules_version;
        $active = $request->boolean('is_active');

        DB::transaction(function () use ($course, $data, $active) {
            $course->fill(collect($data)->except('is_active')->all());
            $course->is_active = $active;
            $course->save();

            // เปิดได้ทีละ 1 คอร์สต่อบทบาท
            if ($active) {
                ClassroomCourse::query()->where('role', $course->role)->whereKeyNot($course->id)->update(['is_active' => false]);
            }
        });

        TrainingBadge::forget();

        $message = 'บันทึกคอร์สเรียบร้อยแล้ว';
        if ($oldVersion !== (string) $course->rules_version) {
            $stale = ClassroomCertification::query()
                ->where('role', $course->role)
                ->where('rules_version', '!=', (string) $course->rules_version)
                ->count();
            $message .= ' · เปลี่ยนรุ่นกติกาเป็น '.$course->rules_version.' แล้ว ผู้ผ่านการอบรม '.number_format($stale).' คนจะขึ้นว่า "ควรทบทวน" (คะแนนเดิมไม่หาย)';
        }

        return redirect()->route('admin.classroom.courses.edit', $course)->with('success', $message);
    }

    // =====================================================
    // บทเรียน
    // =====================================================

    public function storeLesson(Request $request, ClassroomCourse $course): RedirectResponse
    {
        $data = $this->validatedLesson($request);
        $data['course_id'] = $course->id;
        $data['sort_order'] = (int) $course->lessons()->max('sort_order') + 1;

        ClassroomLesson::create($data);

        return redirect()->route('admin.classroom.courses.edit', $course)->with('success', 'เพิ่มบทเรียน "'.$data['title'].'" แล้ว');
    }

    public function updateLesson(Request $request, ClassroomLesson $lesson): RedirectResponse
    {
        $lesson->update($this->validatedLesson($request, 'lesson_'.$lesson->id));

        return redirect()->route('admin.classroom.courses.edit', $lesson->course_id)->with('success', 'แก้ไขบทเรียนเรียบร้อยแล้ว');
    }

    public function toggleLesson(ClassroomLesson $lesson): RedirectResponse
    {
        $lesson->update(['is_active' => ! $lesson->is_active]);

        return redirect()->route('admin.classroom.courses.edit', $lesson->course_id)
            ->with('success', $lesson->is_active ? 'เปิดบทเรียนแล้ว' : 'ปิดบทเรียนแล้ว (ไม่นับในเงื่อนไขปลดล็อกสอบ)');
    }

    public function moveLesson(Request $request, ClassroomLesson $lesson): RedirectResponse
    {
        $direction = $request->input('direction') === 'up' ? 'up' : 'down';

        DB::transaction(function () use ($lesson, $direction) {
            $lessons = ClassroomLesson::query()->where('course_id', $lesson->course_id)
                ->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get()->values();
            $index = $lessons->search(fn (ClassroomLesson $l) => (int) $l->id === (int) $lesson->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;
            if ($index === false || $target < 0 || $target >= $lessons->count()) {
                return;
            }

            $ordered = $lessons->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];
            foreach ($ordered as $position => $row) {
                if ((int) $row->sort_order !== $position + 1) {
                    $row->update(['sort_order' => $position + 1]);
                }
            }
        });

        return redirect()->route('admin.classroom.courses.edit', $lesson->course_id)->with('success', 'จัดลำดับบทเรียนแล้ว');
    }

    public function destroyLesson(ClassroomLesson $lesson): RedirectResponse
    {
        $courseId = $lesson->course_id;
        $title = $lesson->title;
        $lesson->delete();

        return redirect()->route('admin.classroom.courses.edit', $courseId)->with('success', 'ลบบทเรียน "'.$title.'" แล้ว');
    }

    // =====================================================
    // คลังข้อสอบ
    // =====================================================

    public function questions(Request $request, ClassroomCourse $course): View
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $questions = $course->questions()
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($search !== '', fn ($q) => $q->where('question', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.classroom.questions', [
            'course' => $course,
            'questions' => $questions,
            'status' => $status,
            'search' => $search,
            'activeCount' => $course->questions()->where('is_active', true)->count(),
        ]);
    }

    public function createQuestion(ClassroomCourse $course): View
    {
        return view('admin.classroom.question-form', [
            'course' => $course,
            'question' => new ClassroomQuestion(['choices' => ['', '', '', ''], 'correct_index' => 0, 'is_active' => true]),
        ]);
    }

    public function storeQuestion(Request $request, ClassroomCourse $course): RedirectResponse
    {
        $data = $this->validatedQuestion($request);
        $data['course_id'] = $course->id;
        ClassroomQuestion::create($data);

        $next = $request->input('after') === 'new'
            ? redirect()->route('admin.classroom.questions.create', $course)
            : redirect()->route('admin.classroom.questions.index', $course);

        return $next->with('success', 'เพิ่มข้อสอบแล้ว');
    }

    public function editQuestion(ClassroomQuestion $question): View
    {
        return view('admin.classroom.question-form', [
            'course' => $question->course,
            'question' => $question,
        ]);
    }

    public function updateQuestion(Request $request, ClassroomQuestion $question): RedirectResponse
    {
        $question->update($this->validatedQuestion($request));

        return redirect()->route('admin.classroom.questions.index', $question->course_id)->with('success', 'แก้ไขข้อสอบแล้ว');
    }

    public function toggleQuestion(ClassroomQuestion $question): RedirectResponse
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', $question->is_active ? 'เปิดใช้ข้อสอบแล้ว' : 'ปิดข้อสอบแล้ว (ไม่ถูกสุ่มออกสอบ)');
    }

    public function destroyQuestion(ClassroomQuestion $question): RedirectResponse
    {
        $courseId = $question->course_id;
        $question->delete();

        return redirect()->route('admin.classroom.questions.index', $courseId)->with('success', 'ลบข้อสอบแล้ว');
    }

    // =====================================================
    // สถิติ
    // =====================================================

    public function stats(ClassroomCourse $course): View
    {
        $course->load(['lessons' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);
        $lessonIds = $course->lessons->pluck('id');
        $activeLessonIds = $course->lessons->where('is_active', true)->pluck('id');

        // ผู้เรียน = มีความคืบหน้าบทใดบทหนึ่ง หรือเคยเริ่มสอบ
        $learners = DB::table('classroom_lesson_progress')
            ->whereIn('lesson_id', $lessonIds->isEmpty() ? [0] : $lessonIds)
            ->select('user_id')
            ->union(DB::table('classroom_attempts')->where('course_id', $course->id)->select('user_id'));
        $enrolled = (int) DB::query()->fromSub($learners, 'learners')->count();

        $perLesson = ClassroomLessonProgress::query()
            ->whereIn('lesson_id', $lessonIds->isEmpty() ? [0] : $lessonIds)
            ->select('lesson_id', DB::raw('COUNT(*) as started'), DB::raw('SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed'))
            ->groupBy('lesson_id')
            ->get()
            ->keyBy('lesson_id');

        $finishedAll = 0;
        if ($activeLessonIds->isNotEmpty()) {
            $finishedAll = (int) DB::query()->fromSub(
                ClassroomLessonProgress::query()
                    ->whereIn('lesson_id', $activeLessonIds)
                    ->whereNotNull('completed_at')
                    ->select('user_id')
                    ->groupBy('user_id')
                    ->havingRaw('COUNT(*) >= ?', [$activeLessonIds->count()]),
                'done'
            )->count();
        }

        $attempts = ClassroomAttempt::query()->where('course_id', $course->id)->whereNotNull('submitted_at');
        $submitted = (clone $attempts)->count();
        $passed = (clone $attempts)->where('passed', true)->count();
        $avgScore = $submitted > 0 ? round((float) (clone $attempts)->avg('score'), 1) : 0.0;

        $certified = ClassroomCertification::query()
            ->with('user:id,name,email')
            ->where('role', $course->role)
            ->latest('certified_at')
            ->latest('id')
            ->paginate(25);

        $needsRefresh = ClassroomCertification::query()
            ->where('role', $course->role)
            ->where('rules_version', '!=', (string) $course->rules_version)
            ->count();

        return view('admin.classroom.stats', [
            'course' => $course,
            'enrolled' => $enrolled,
            'finishedAll' => $finishedAll,
            'perLesson' => $perLesson,
            'submitted' => $submitted,
            'passed' => $passed,
            'passRate' => $submitted > 0 ? round($passed * 100 / $submitted, 1) : 0.0,
            'avgScore' => $avgScore,
            'certified' => $certified,
            'certifiedTotal' => $certified->total(),
            'needsRefresh' => $needsRefresh,
        ]);
    }

    // =====================================================
    // ตรวจข้อมูล
    // =====================================================

    /**
     * @return array{title: string, youtube_id: string, duration_seconds: int, is_active: bool}
     */
    private function validatedLesson(Request $request, string $bag = 'lesson'): array
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:200',
            'youtube_url' => 'required|string|max:500',
            'duration' => 'required|string|max:12',
            'is_active' => 'sometimes|boolean',
        ], [
            'title.required' => 'กรุณากรอกชื่อบทเรียน',
            'title.max' => 'ชื่อบทเรียนยาวเกิน 200 ตัวอักษร',
            'youtube_url.required' => 'กรุณาวางลิงก์ YouTube',
            'duration.required' => 'กรุณากรอกความยาววิดีโอ เช่น 5:30',
        ]);

        $youtubeId = YouTubeId::parse((string) $request->input('youtube_url'));
        $duration = ClassroomLesson::parseDuration((string) $request->input('duration'));

        $validator->after(function ($v) use ($youtubeId, $duration, $request) {
            if ($request->filled('youtube_url') && $youtubeId === null) {
                $v->errors()->add('youtube_url', 'ลิงก์ YouTube ไม่ถูกต้อง — วางลิงก์วิดีโอ เช่น https://youtu.be/xxxxxxxxxxx');
            }
            if ($request->filled('duration') && ($duration === null || $duration < 1 || $duration > 86400)) {
                $v->errors()->add('duration', 'ความยาววิดีโอต้องเป็น นาที:วินาที เช่น 5:30 (หรือ ชั่วโมง:นาที:วินาที)');
            }
        });

        if ($validator->fails()) {
            throw (new ValidationException($validator))->errorBag($bag);
        }

        return [
            'title' => (string) $request->input('title'),
            'youtube_id' => (string) $youtubeId,
            'duration_seconds' => (int) $duration,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    /**
     * ตัวเลือกช่องว่างถูกตัดทิ้ง และตำแหน่งคำตอบที่ถูกถูกเลื่อนตามให้ตรงกับรายการที่เหลือ
     *
     * @return array{question: string, choices: array<int, string>, correct_index: int, explanation: string|null, is_active: bool}
     */
    private function validatedQuestion(Request $request): array
    {
        $raw = (array) $request->input('choices', []);
        $correctRaw = $request->input('correct_index');
        $choices = [];
        $correct = null;
        foreach ($raw as $index => $text) {
            $text = trim((string) $text);
            if ($text === '') {
                continue;
            }
            if ((string) $index === (string) $correctRaw) {
                $correct = count($choices);
            }
            $choices[] = $text;
        }

        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'choices' => 'required|array|max:'.ClassroomQuestion::MAX_CHOICES,
            'choices.*' => 'nullable|string|max:300',
            'correct_index' => 'required',
            'explanation' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ], [
            'question.required' => 'กรุณากรอกคำถาม',
            'question.max' => 'คำถามยาวเกิน 1,000 ตัวอักษร',
            'choices.required' => 'กรุณากรอกตัวเลือก',
            'choices.max' => 'ตัวเลือกได้ไม่เกิน '.ClassroomQuestion::MAX_CHOICES.' ข้อ',
            'choices.*.max' => 'ตัวเลือกแต่ละข้อยาวไม่เกิน 300 ตัวอักษร',
            'correct_index.required' => 'กรุณาเลือกคำตอบที่ถูก',
            'explanation.max' => 'คำอธิบายยาวเกิน 1,000 ตัวอักษร',
        ]);

        $validator->after(function ($v) use ($choices, $correct) {
            if (count($choices) < ClassroomQuestion::MIN_CHOICES) {
                $v->errors()->add('choices', 'ต้องมีตัวเลือกอย่างน้อย '.ClassroomQuestion::MIN_CHOICES.' ข้อ');
            }
            if ($correct === null) {
                $v->errors()->add('correct_index', 'คำตอบที่ถูกต้องเป็นตัวเลือกที่มีข้อความ');
            }
        });

        $validator->validate();

        return [
            'question' => trim((string) $request->input('question')),
            'choices' => $choices,
            'correct_index' => (int) $correct,
            'explanation' => $request->filled('explanation') ? trim((string) $request->input('explanation')) : null,
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
