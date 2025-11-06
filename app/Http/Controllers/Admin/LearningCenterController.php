<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use App\Models\UserArticleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LearningCenterController extends Controller
{
    /**
     * Display the learning center index page
     */
    public function index()
    {
        $user = Auth::user();

        // Get active categories with published articles count
        $categories = LearningCategory::active()
            ->ordered()
            ->withCount(['publishedArticles'])
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'description' => $category->description,
                    'icon' => $category->icon ?? '📚',
                    'color' => $category->color,
                    'articles_count' => $category->published_articles_count,
                ];
            });

        // Get popular articles (by views)
        $popular_articles = LearningArticle::published()
            ->with(['category'])
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($article) use ($user) {
                $progress = $article->progressForUser($user->id);

                return [
                    'id' => $article->id,
                    'slug' => $article->slug,
                    'title' => $article->title,
                    'category' => $article->category->name,
                    'views' => $article->views,
                    'duration' => $article->formatted_duration,
                    'progress' => $progress ? $progress->progress_percentage : 0,
                    'status' => $progress ? $progress->status : 'not_started',
                ];
            });

        // Get featured articles
        $featured_articles = LearningArticle::published()
            ->featured()
            ->with(['category'])
            ->limit(3)
            ->get();

        // Get user's stats
        $completedCount = UserArticleProgress::where('user_id', $user->id)
            ->completed()
            ->count();

        $inProgressCount = UserArticleProgress::where('user_id', $user->id)
            ->inProgress()
            ->count();

        $totalTimeSpent = UserArticleProgress::where('user_id', $user->id)
            ->sum('time_spent');

        // Convert total time to hours
        $totalHours = $totalTimeSpent > 0 ? round($totalTimeSpent / 3600, 1) : 0;

        // User stats for dashboard
        $user_stats = [
            'completed_courses' => $completedCount,
            'in_progress' => $inProgressCount,
            'total_hours' => $totalHours,
            'certificates' => $completedCount, // For now, each completed course = 1 certificate
        ];

        return view('admin.learning-center.index', compact(
            'categories',
            'popular_articles',
            'featured_articles',
            'user_stats'
        ));
    }

    /**
     * Display articles in a specific category
     */
    public function category($slug)
    {
        $user = Auth::user();

        $category = LearningCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $articles = LearningArticle::published()
            ->where('category_id', $category->id)
            ->ordered()
            ->with(['prerequisites'])
            ->get()
            ->map(function ($article) use ($user) {
                $progress = $article->progressForUser($user->id);
                $canUnlock = $article->canUnlock($user);

                return [
                    'article' => $article,
                    'progress' => $progress,
                    'can_unlock' => $canUnlock,
                    'is_locked' => !$canUnlock,
                ];
            });

        return view('admin.learning-center.category', compact('category', 'articles'));
    }

    /**
     * Display a specific article
     */
    public function article($slug)
    {
        $user = Auth::user();

        $article = LearningArticle::published()
            ->where('slug', $slug)
            ->with(['category', 'prerequisites', 'dependentArticles'])
            ->firstOrFail();

        // Check access
        if (!$article->userHasAccess($user)) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงบทความนี้');
        }

        // Check if can unlock
        if (!$article->canUnlock($user)) {
            return redirect()
                ->route('admin.learning-center.category', $article->category->slug)
                ->with('error', 'คุณต้องเรียนบทความที่จำเป็นก่อน');
        }

        // Get or create progress
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

        // Mark as started if not yet
        if ($progress->status === 'not_started') {
            $progress->markAsStarted();
        } else {
            $progress->update(['last_accessed_at' => now()]);
        }

        // Increment views
        $article->incrementViews();

        // Get related articles
        $relatedArticles = LearningArticle::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->limit(3)
            ->get();

        return view('admin.learning-center.article', compact(
            'article',
            'progress',
            'relatedArticles'
        ));
    }

    /**
     * Mark article as completed
     */
    public function complete(Request $request, $slug)
    {
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

        $progress->markAsCompleted();

        return response()->json([
            'success' => true,
            'message' => 'เรียนจบบทความนี้แล้ว!',
            'progress' => $progress,
        ]);
    }

    /**
     * Update article progress
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
}
