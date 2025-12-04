<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\UserArticleProgress;
use App\Services\CourseProgressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AcademyController - ศูนย์การเรียนรู้สำหรับผู้ใช้
 *
 * จัดการการแสดงคอร์ส บทเรียน และติดตามความก้าวหน้าการเรียน
 *
 * @package App\Http\Controllers\User
 */
class AcademyController extends Controller
{
    /**
     * @var CourseProgressionService
     */
    protected CourseProgressionService $progressionService;

    /**
     * Constructor
     *
     * @param CourseProgressionService $progressionService
     */
    public function __construct(CourseProgressionService $progressionService)
    {
        $this->progressionService = $progressionService;
    }

    /**
     * แสดงหน้าหลักศูนย์การเรียนรู้
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงหมวดหมู่ที่ active พร้อมนับบทความ
        $categories = LearningCategory::active()
            ->ordered()
            ->withCount(['publishedArticles'])
            ->get()
            ->map(function ($category) use ($user) {
                // ดึงข้อมูล progress ของ category
                $categoryProgress = $this->progressionService->getCategoryProgress($user, $category);

                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'description' => $category->description,
                    'icon' => $category->icon ?? '📚',
                    'color' => $category->color,
                    'articles_count' => $category->published_articles_count,
                    'progress' => $categoryProgress,
                ];
            });

        // ดึงบทความยอดนิยม (ตาม views)
        $popularArticles = LearningArticle::published()
            ->with(['category'])
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($article) use ($user) {
                $progress = $article->progressForUser($user->id);
                $accessInfo = $this->progressionService->canAccessArticle($user, $article);

                return [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'category' => $article->category->name ?? '-',
                    'category_slug' => $article->category->slug ?? '',
                    'views' => $article->views,
                    'duration' => $article->formatted_duration ?? '-',
                    'difficulty' => $article->difficulty_label ?? 'เริ่มต้น',
                    'course_level' => $article->course_level ?? 1,
                    'progress' => $progress ? $progress->progress_percentage : 0,
                    'status' => $progress ? $progress->status : 'not_started',
                    'can_access' => $accessInfo['can_access'],
                    'lock_reason' => $accessInfo['reason'] ?? null,
                ];
            });

        // ดึงบทความแนะนำ
        $featuredArticles = LearningArticle::published()
            ->featured()
            ->with(['category'])
            ->limit(3)
            ->get();

        // ดึงสถิติการเรียนของผู้ใช้
        $learningStats = $this->progressionService->getUserLearningStats($user);

        // สถิติสำหรับแสดงใน dashboard
        $userStats = [
            'completed_courses' => $learningStats['courses_completed'],
            'in_progress' => $learningStats['courses_in_progress'],
            'total_hours' => $learningStats['total_time_spent'] > 0
                ? round($learningStats['total_time_spent'] / 3600, 1)
                : 0,
            'certificates' => $learningStats['courses_completed'],
            'quiz_stats' => $learningStats['quiz_stats'] ?? [],
        ];

        return view('user.academy.index', compact(
            'categories',
            'popularArticles',
            'featuredArticles',
            'userStats'
        ));
    }

    /**
     * แสดงบทความในหมวดหมู่
     *
     * @param string $slug Slug ของหมวดหมู่
     * @return \Illuminate\View\View
     */
    public function category($slug)
    {
        $user = Auth::user();

        $category = LearningCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // ดึงบทความพร้อมข้อมูล access
        $articles = LearningArticle::published()
            ->where('category_id', $category->id)
            ->orderedByLevel()
            ->with(['prerequisites', 'requiredQuizzes'])
            ->get()
            ->map(function ($article) use ($user) {
                $progress = $article->progressForUser($user->id);
                $accessInfo = $this->progressionService->canAccessArticle($user, $article);
                $quizResult = $this->progressionService->checkQuizPassForArticle($user, $article);

                return [
                    'article' => $article,
                    'progress' => $progress,
                    'can_access' => $accessInfo['can_access'],
                    'is_locked' => !$accessInfo['can_access'],
                    'lock_reason' => $accessInfo['reason'] ?? null,
                    'requirements' => $accessInfo['requirements'] ?? [],
                    'quiz_passed' => $quizResult['passed'] ?? false,
                    'quiz_best_score' => $quizResult['best_score'] ?? 0,
                    'course_level' => $article->course_level ?? 1,
                ];
            });

        // จัดกลุ่มบทความตามระดับ
        $articlesByLevel = $articles->groupBy('course_level');

        // ดึงข้อมูล progress ของหมวดหมู่
        $categoryProgress = $this->progressionService->getCategoryProgress($user, $category);

        return view('user.academy.category', compact(
            'category',
            'articles',
            'articlesByLevel',
            'categoryProgress'
        ));
    }

    /**
     * แสดงบทความ/บทเรียน
     *
     * @param string $slug Slug ของบทความ
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function article($slug)
    {
        $user = Auth::user();

        $article = LearningArticle::published()
            ->where('slug', $slug)
            ->with(['category', 'prerequisites', 'dependentArticles', 'quizzes'])
            ->firstOrFail();

        // ตรวจสอบสิทธิ์การเข้าถึง
        $accessInfo = $this->progressionService->canAccessArticle($user, $article);

        if (!$accessInfo['can_access']) {
            // ถ้าไม่มีสิทธิ์ แสดงหน้าแจ้งเตือน
            return view('user.academy.locked', [
                'article' => $article,
                'accessInfo' => $accessInfo,
            ]);
        }

        // สร้างหรือดึง progress
        $progress = UserArticleProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'article_id' => $article->id,
            ],
            [
                'status' => 'not_started',
                'progress_percentage' => 0,
            ]
        );

        // อัพเดทสถานะเป็น in_progress ถ้ายังไม่เริ่ม
        if ($progress->status === 'not_started') {
            $progress->markAsStarted();
        } else {
            $progress->update(['last_accessed_at' => now()]);
        }

        // เพิ่ม views
        $article->incrementViews();

        // ดึงบทความที่เกี่ยวข้อง
        $relatedArticles = LearningArticle::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->limit(3)
            ->get();

        // ดึงบทความก่อนหน้าและถัดไป
        $previousArticle = $article->getPreviousArticle();
        $nextArticle = $article->getNextArticle();

        // ดึงสถานะ quiz
        $quizResult = $this->progressionService->checkQuizPassForArticle($user, $article);

        // ตรวจสอบว่าสามารถขอใบประกาศได้หรือไม่
        $canRequestCertificate = $progress->status === 'completed' && ($quizResult['passed'] ?? true);

        return view('user.academy.article', compact(
            'article',
            'progress',
            'relatedArticles',
            'previousArticle',
            'nextArticle',
            'quizResult',
            'canRequestCertificate'
        ));
    }

    /**
     * จบบทความ/บทเรียน
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request, $slug)
    {
        $user = Auth::user();

        $article = LearningArticle::published()
            ->where('slug', $slug)
            ->firstOrFail();

        // ตรวจสอบว่าต้องผ่าน quiz ก่อนหรือไม่
        if ($article->require_quiz_pass) {
            $quizResult = $this->progressionService->checkQuizPassForArticle($user, $article);

            if (!$quizResult['passed']) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณต้องผ่านแบบทดสอบก่อนจึงจะเรียนจบได้',
                    'min_score' => $article->min_quiz_score ?? 70,
                    'your_score' => $quizResult['best_score'],
                ], 400);
            }
        }

        // จบคอร์สและให้รางวัล
        $result = $this->progressionService->completeArticle($user, $article);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'rewards' => $result['rewards'] ?? [],
            'next_article' => $result['next_article'] ? [
                'id' => $result['next_article']->id,
                'slug' => $result['next_article']->slug,
                'title' => $result['next_article']->title,
            ] : null,
        ]);
    }

    /**
     * อัพเดท progress ของบทความ
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProgress(Request $request, $slug)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        $user = Auth::user();

        $article = LearningArticle::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $progress = UserArticleProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'article_id' => $article->id,
            ],
            [
                'status' => 'not_started',
                'progress_percentage' => 0,
            ]
        );

        $progress->updateProgress(
            $validated['progress'],
            $validated['time_spent'] ?? null
        );

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * ดึงสถิติการเรียนของผู้ใช้
     *
     * @return \Illuminate\View\View
     */
    public function myProgress()
    {
        $user = Auth::user();
        $stats = $this->progressionService->getUserLearningStats($user);

        // ดึงบทความที่กำลังเรียน
        $inProgressArticles = UserArticleProgress::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->with(['article.category'])
            ->latest('last_accessed_at')
            ->get();

        // ดึงบทความที่เรียนจบแล้ว
        $completedArticles = UserArticleProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['article.category'])
            ->latest('completed_at')
            ->get();

        return view('user.academy.my-progress', compact(
            'stats',
            'inProgressArticles',
            'completedArticles'
        ));
    }
}
