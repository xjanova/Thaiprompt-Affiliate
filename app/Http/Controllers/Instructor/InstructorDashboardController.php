<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserArticleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * InstructorDashboardController
 *
 * แดชบอร์ดสำหรับครูผู้สอน (Instructor)
 * ใช้สำหรับจัดการคอร์ส, Quiz, ดูผลการเรียน และออกใบประกาศ
 * แยกจากแดชบอร์ด Admin ซึ่งใช้สำหรับอนุมัติ/บล็อค
 */
class InstructorDashboardController extends Controller
{
    /**
     * แสดงหน้า Dashboard หลักของ Instructor
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงคอร์สที่ instructor เป็นเจ้าของ
        $myCourses = LearningArticle::where('instructor_id', $user->id)
            ->orWhere('created_by', $user->id)
            ->with('category')
            ->latest()
            ->get();

        // สถิติภาพรวม
        $stats = [
            'total_courses' => $myCourses->count(),
            'published_courses' => $myCourses->where('is_published', true)->count(),
            'draft_courses' => $myCourses->where('is_published', false)->count(),
            'total_students' => $this->getTotalStudents($myCourses->pluck('id')),
            'total_completions' => $this->getTotalCompletions($myCourses->pluck('id')),
            'total_earnings' => $this->getTotalEarnings($myCourses->pluck('id')),
        ];

        // คอร์สล่าสุดที่มีผู้เรียน
        $recentActivity = UserArticleProgress::whereIn('article_id', $myCourses->pluck('id'))
            ->with(['user', 'article'])
            ->latest()
            ->limit(10)
            ->get();

        // สถิติ Quiz
        $quizStats = $this->getQuizStats($myCourses->pluck('id'));

        return view('instructor.dashboard', compact(
            'myCourses',
            'stats',
            'recentActivity',
            'quizStats'
        ));
    }

    /**
     * แสดงรายการคอร์สของ Instructor
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function courses(Request $request)
    {
        $user = Auth::user();

        $query = LearningArticle::where('instructor_id', $user->id)
            ->orWhere('created_by', $user->id);

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } else {
                $query->where('is_published', false);
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $courses = $query->with(['category', 'quizzes'])
            ->withCount(['userProgress', 'quizzes'])
            ->latest()
            ->paginate(20);

        $categories = LearningCategory::active()->ordered()->get();

        return view('instructor.courses.index', compact('courses', 'categories'));
    }

    /**
     * ฟอร์มสร้างคอร์สใหม่
     *
     * @return \Illuminate\View\View
     */
    public function createCourse()
    {
        $categories = LearningCategory::active()->ordered()->get();
        $articles = LearningArticle::published()->ordered()->get();

        return view('instructor.courses.create', compact('categories', 'articles'));
    }

    /**
     * บันทึกคอร์สใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCourse(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:learning_categories,id',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url',
            'estimated_duration' => 'nullable|integer|min:0',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'course_level' => 'nullable|integer|min:1|max:10',
            'require_quiz_pass' => 'boolean',
            'min_quiz_score' => 'nullable|integer|min:0|max:100',
            'points_reward' => 'nullable|integer|min:0',
            'coin_reward' => 'nullable|numeric|min:0',
            'money_reward' => 'nullable|numeric|min:0',
            'exp_reward' => 'nullable|integer|min:0',
            'pv_value' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Handle thumbnail
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('learning/thumbnails', 'public');
        }

        // Generate slug
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['title']);

        // Parse tags
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        // Set instructor and creator
        $validated['instructor_id'] = $user->id;
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;
        $validated['is_published'] = false; // Draft by default

        $article = LearningArticle::create($validated);

        return redirect()
            ->route('instructor.courses.edit', $article)
            ->with('success', 'สร้างคอร์สสำเร็จ! กรุณาเพิ่ม Quiz และส่งขออนุมัติ');
    }

    /**
     * ฟอร์มแก้ไขคอร์ส
     *
     * @param LearningArticle $article
     * @return \Illuminate\View\View
     */
    public function editCourse(LearningArticle $article)
    {
        $this->authorizeInstructor($article);

        $article->load(['category', 'quizzes.questions.answers', 'prerequisites']);
        $categories = LearningCategory::active()->ordered()->get();
        $allArticles = LearningArticle::published()
            ->where('id', '!=', $article->id)
            ->ordered()
            ->get();

        return view('instructor.courses.edit', compact('article', 'categories', 'allArticles'));
    }

    /**
     * อัปเดตคอร์ส
     *
     * @param Request $request
     * @param LearningArticle $article
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCourse(Request $request, LearningArticle $article)
    {
        $this->authorizeInstructor($article);

        $validated = $request->validate([
            'category_id' => 'required|exists:learning_categories,id',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url',
            'estimated_duration' => 'nullable|integer|min:0',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'course_level' => 'nullable|integer|min:1|max:10',
            'require_quiz_pass' => 'boolean',
            'min_quiz_score' => 'nullable|integer|min:0|max:100',
            'points_reward' => 'nullable|integer|min:0',
            'coin_reward' => 'nullable|numeric|min:0',
            'money_reward' => 'nullable|numeric|min:0',
            'exp_reward' => 'nullable|integer|min:0',
            'pv_value' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Handle thumbnail
        if ($request->hasFile('thumbnail')) {
            if ($article->thumbnail) {
                \Storage::disk('public')->delete($article->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('learning/thumbnails', 'public');
        }

        // Parse tags
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } else {
            $validated['tags'] = [];
        }

        $validated['updated_by'] = $user->id;

        $article->update($validated);

        return redirect()
            ->route('instructor.courses.edit', $article)
            ->with('success', 'อัปเดตคอร์สสำเร็จ!');
    }

    /**
     * ดูสถิติผู้เรียนของคอร์ส
     *
     * @param LearningArticle $article
     * @return \Illuminate\View\View
     */
    public function courseStats(LearningArticle $article)
    {
        $this->authorizeInstructor($article);

        $article->load(['category', 'quizzes']);

        // ดึง progress ของผู้เรียน
        $students = UserArticleProgress::where('article_id', $article->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // สถิติภาพรวม
        $stats = [
            'total_students' => UserArticleProgress::where('article_id', $article->id)->count(),
            'completed' => UserArticleProgress::where('article_id', $article->id)
                ->where('status', 'completed')->count(),
            'in_progress' => UserArticleProgress::where('article_id', $article->id)
                ->where('status', 'in_progress')->count(),
            'avg_progress' => UserArticleProgress::where('article_id', $article->id)
                ->avg('progress_percentage') ?? 0,
            'avg_time_spent' => UserArticleProgress::where('article_id', $article->id)
                ->avg('time_spent') ?? 0,
        ];

        // สถิติ Quiz
        $quizStats = [];
        foreach ($article->quizzes as $quiz) {
            $attempts = QuizAttempt::where('quiz_id', $quiz->id);
            $quizStats[$quiz->id] = [
                'title' => $quiz->title,
                'total_attempts' => $attempts->count(),
                'passed' => (clone $attempts)->where('passed', true)->count(),
                'avg_score' => $attempts->avg('percentage') ?? 0,
                'best_score' => $attempts->max('percentage') ?? 0,
            ];
        }

        return view('instructor.courses.stats', compact('article', 'students', 'stats', 'quizStats'));
    }

    /**
     * จัดการ Quiz ของคอร์ส
     *
     * @param LearningArticle $article
     * @return \Illuminate\View\View
     */
    public function manageQuiz(LearningArticle $article)
    {
        $this->authorizeInstructor($article);

        $article->load(['quizzes.questions.answers']);

        return view('instructor.courses.quiz', compact('article'));
    }

    /**
     * ส่งคอร์สเพื่อขออนุมัติ
     *
     * @param LearningArticle $article
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitForApproval(LearningArticle $article)
    {
        $this->authorizeInstructor($article);

        // ตรวจสอบว่าคอร์สมี Quiz หรือไม่ (ถ้า require_quiz_pass = true)
        if ($article->require_quiz_pass && $article->quizzes()->count() === 0) {
            return back()->with('error', 'กรุณาเพิ่ม Quiz ก่อนส่งขออนุมัติ');
        }

        // บันทึกว่าส่งขออนุมัติแล้ว (อาจเพิ่ม field approval_status ถ้าต้องการ)
        $article->update([
            'metadata' => array_merge($article->metadata ?? [], [
                'pending_approval' => true,
                'submitted_at' => now()->toIso8601String(),
            ]),
        ]);

        return back()->with('success', 'ส่งคอร์สเพื่อขออนุมัติเรียบร้อยแล้ว! กรุณารอ Admin ตรวจสอบ');
    }

    /**
     * ดูรายได้จากคอร์ส
     *
     * @return \Illuminate\View\View
     */
    public function earnings()
    {
        $user = Auth::user();

        $myCourses = LearningArticle::where('instructor_id', $user->id)
            ->orWhere('created_by', $user->id)
            ->get();

        // คำนวณรายได้รายเดือน
        $monthlyEarnings = [];
        foreach ($myCourses as $course) {
            $completions = UserArticleProgress::where('article_id', $course->id)
                ->where('status', 'completed')
                ->selectRaw('YEAR(completed_at) as year, MONTH(completed_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->get();

            foreach ($completions as $completion) {
                $key = $completion->year . '-' . str_pad($completion->month, 2, '0', STR_PAD_LEFT);
                if (!isset($monthlyEarnings[$key])) {
                    $monthlyEarnings[$key] = 0;
                }

                // คำนวณค่าคอมมิชชั่น (instructor_commission%)
                $rewardPerCompletion = ($course->coin_reward ?? 0) + ($course->money_reward ?? 0);
                $commission = $rewardPerCompletion * ($course->instructor_commission ?? 10) / 100;
                $monthlyEarnings[$key] += $commission * $completion->count;
            }
        }

        ksort($monthlyEarnings);

        return view('instructor.earnings', compact('myCourses', 'monthlyEarnings'));
    }

    /**
     * ตรวจสอบสิทธิ์ Instructor
     *
     * @param LearningArticle $article
     * @return void
     */
    protected function authorizeInstructor(LearningArticle $article): void
    {
        $user = Auth::user();

        if ($article->instructor_id !== $user->id && $article->created_by !== $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์จัดการคอร์สนี้');
        }
    }

    /**
     * นับจำนวนนักเรียนทั้งหมด
     */
    protected function getTotalStudents($courseIds): int
    {
        return UserArticleProgress::whereIn('article_id', $courseIds)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * นับจำนวนการจบคอร์สทั้งหมด
     */
    protected function getTotalCompletions($courseIds): int
    {
        return UserArticleProgress::whereIn('article_id', $courseIds)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * คำนวณรายได้รวม (จากค่าคอมมิชชั่น)
     */
    protected function getTotalEarnings($courseIds): float
    {
        $total = 0;
        $courses = LearningArticle::whereIn('id', $courseIds)->get();

        foreach ($courses as $course) {
            $completions = UserArticleProgress::where('article_id', $course->id)
                ->where('status', 'completed')
                ->count();

            $rewardPerCompletion = ($course->coin_reward ?? 0) + ($course->money_reward ?? 0);
            $commission = $rewardPerCompletion * ($course->instructor_commission ?? 10) / 100;
            $total += $commission * $completions;
        }

        return $total;
    }

    /**
     * ดึงสถิติ Quiz
     */
    protected function getQuizStats($courseIds): array
    {
        $quizzes = Quiz::whereIn('article_id', $courseIds)->pluck('id');

        return [
            'total_quizzes' => $quizzes->count(),
            'total_attempts' => QuizAttempt::whereIn('quiz_id', $quizzes)->count(),
            'pass_rate' => QuizAttempt::whereIn('quiz_id', $quizzes)->count() > 0
                ? round(QuizAttempt::whereIn('quiz_id', $quizzes)->where('passed', true)->count() /
                    QuizAttempt::whereIn('quiz_id', $quizzes)->count() * 100, 1)
                : 0,
            'avg_score' => QuizAttempt::whereIn('quiz_id', $quizzes)->avg('percentage') ?? 0,
        ];
    }
}
