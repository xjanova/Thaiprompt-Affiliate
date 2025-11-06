<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\UserArticleProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InstructorDashboardController extends Controller
{
    /**
     * Display the instructor dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Check if user is an instructor or admin
        if (!$user->hasRole(['admin', 'instructor'])) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        // Get courses created by this instructor
        $courses = LearningArticle::where('created_by', $user->id)
            ->withCount(['progressRecords'])
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $totalCourses = $courses->count();
        $totalStudents = UserArticleProgress::whereIn('article_id', $courses->pluck('id'))
            ->distinct('user_id')
            ->count('user_id');

        $completedEnrollments = UserArticleProgress::whereIn('article_id', $courses->pluck('id'))
            ->where('status', 'completed')
            ->count();

        $totalViews = $courses->sum('views');

        // Get recent student activities
        $recentActivities = UserArticleProgress::whereIn('article_id', $courses->pluck('id'))
            ->with(['user', 'article'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Course performance data
        $coursePerformance = $courses->map(function ($course) {
            $totalEnrolled = $course->progress_records_count;
            $completed = UserArticleProgress::where('article_id', $course->id)
                ->where('status', 'completed')
                ->count();

            $completionRate = $totalEnrolled > 0 ? round(($completed / $totalEnrolled) * 100, 1) : 0;

            return [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category->name,
                'students' => $totalEnrolled,
                'completed' => $completed,
                'completion_rate' => $completionRate,
                'views' => $course->views,
                'rating' => 4.5, // TODO: Implement actual rating system
                'created_at' => $course->created_at,
            ];
        })->sortByDesc('students')->values();

        // Monthly statistics for chart
        $monthlyStats = UserArticleProgress::whereIn('article_id', $courses->pluck('id'))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(DISTINCT user_id) as students'),
                DB::raw('COUNT(*) as enrollments')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $stats = [
            'total_courses' => $totalCourses,
            'total_students' => $totalStudents,
            'completed_enrollments' => $completedEnrollments,
            'total_views' => $totalViews,
            'avg_rating' => 4.5, // TODO: Implement actual rating
            'total_revenue' => 125000, // TODO: Implement actual revenue tracking
        ];

        return view('admin.instructor.dashboard', compact(
            'stats',
            'courses',
            'coursePerformance',
            'recentActivities',
            'monthlyStats'
        ));
    }

    /**
     * Show course analytics
     */
    public function courseAnalytics($id)
    {
        $user = Auth::user();

        $course = LearningArticle::where('id', $id)
            ->where('created_by', $user->id)
            ->firstOrFail();

        // Get detailed analytics for this course
        $enrollments = UserArticleProgress::where('article_id', $course->id)
            ->with(['user'])
            ->get();

        $stats = [
            'total_enrolled' => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'in_progress' => $enrollments->where('status', 'in_progress')->count(),
            'not_started' => $enrollments->where('status', 'not_started')->count(),
            'avg_progress' => $enrollments->avg('progress_percentage'),
            'avg_time_spent' => $enrollments->avg('time_spent') / 3600, // Convert to hours
            'total_views' => $course->views,
        ];

        // Student list with progress
        $students = $enrollments->map(function ($progress) {
            return [
                'id' => $progress->user->id,
                'name' => $progress->user->name,
                'email' => $progress->user->email,
                'status' => $progress->status,
                'progress' => $progress->progress_percentage,
                'time_spent' => round($progress->time_spent / 3600, 1), // Hours
                'started_at' => $progress->started_at,
                'completed_at' => $progress->completed_at,
                'last_accessed' => $progress->last_accessed_at,
            ];
        });

        return view('admin.instructor.course-analytics', compact('course', 'stats', 'students'));
    }

    /**
     * Show earnings/revenue page
     */
    public function earnings()
    {
        $user = Auth::user();

        // TODO: Implement actual revenue tracking from payment system
        $earnings = [
            'total_earnings' => 125000,
            'this_month' => 18500,
            'last_month' => 22300,
            'pending_payout' => 5200,
            'lifetime_earnings' => 345000,
        ];

        $transactions = [
            // TODO: Get actual transaction data
        ];

        return view('admin.instructor.earnings', compact('earnings', 'transactions'));
    }
}
