<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KeywordActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Keyword Activity Logs Admin Controller
 *
 * จัดการบันทึกการใช้งาน keywords
 */
class KeywordActivityLogController extends Controller
{
    private KeywordActivityLogService $activityLogService;

    public function __construct(KeywordActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * แสดงรายการ activity logs
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = DB::table('keyword_activity_logs');

        // Filter by keyword
        if ($request->has('keyword') && $request->input('keyword')) {
            $query->where('keyword_name', 'like', '%' . $request->input('keyword') . '%');
        }

        // Filter by action type
        if ($request->has('action_type') && $request->input('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        // Filter by category
        if ($request->has('category') && $request->input('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by date range
        if ($request->has('date_from') && $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to') && $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Filter by LINE user ID
        if ($request->has('line_user_id') && $request->input('line_user_id')) {
            $query->where('line_user_id', $request->input('line_user_id'));
        }

        // Get logs with pagination
        $logs = $query->orderBy('timestamp', 'desc')->paginate(20);

        // Get statistics
        $stats = $this->getStatistics();

        // Get unique keywords for filter
        $keywords = DB::table('keyword_activity_logs')
            ->distinct()
            ->whereNotNull('keyword_name')
            ->pluck('keyword_name')
            ->sort()
            ->values();

        // Get unique categories for filter
        $categories = DB::table('keyword_activity_logs')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->sort()
            ->values();

        return view('admin.line-bot.keywords.activity-logs', [
            'logs' => $logs,
            'stats' => $stats,
            'keywords' => $keywords,
            'categories' => $categories,
            'pageTitle' => 'บันทึกกิจกรรม Keywords',
        ]);
    }

    /**
     * ดึง statistics
     *
     * @return array
     */
    private function getStatistics(): array
    {
        $today = now()->startOfDay();

        return [
            'total_logs' => DB::table('keyword_activity_logs')->count(),
            'total_matches' => DB::table('keyword_activity_logs')
                ->where('action_type', 'matched')->count(),
            'total_no_match' => DB::table('keyword_activity_logs')
                ->where('action_type', 'no_match')->count(),
            'today_logs' => DB::table('keyword_activity_logs')
                ->where('created_at', '>=', $today)->count(),
            'today_matches' => DB::table('keyword_activity_logs')
                ->where('created_at', '>=', $today)
                ->where('action_type', 'matched')->count(),
            'unique_users' => DB::table('keyword_activity_logs')
                ->distinct('line_user_id')->count('line_user_id'),
            'most_used_keyword' => $this->getMostUsedKeyword(),
            'match_rate' => $this->getMatchRate(),
        ];
    }

    /**
     * ดึง most used keyword
     *
     * @return array|null
     */
    private function getMostUsedKeyword(): ?array
    {
        $keyword = DB::table('keyword_activity_logs')
            ->where('action_type', 'matched')
            ->select('keyword_name', DB::raw('COUNT(*) as count'))
            ->groupBy('keyword_name')
            ->orderBy('count', 'desc')
            ->first();

        if (!$keyword) {
            return null;
        }

        return [
            'name' => $keyword->keyword_name,
            'count' => $keyword->count,
        ];
    }

    /**
     * ดึง match rate (%)
     *
     * @return float
     */
    private function getMatchRate(): float
    {
        $total = DB::table('keyword_activity_logs')->count();

        if ($total === 0) {
            return 0;
        }

        $matches = DB::table('keyword_activity_logs')
            ->where('action_type', 'matched')->count();

        return round(($matches / $total) * 100, 2);
    }

    /**
     * Export logs to CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $days = $request->input('days', 30);
        $csv = $this->activityLogService->exportToCSV($days);

        $filename = 'keyword-activity-logs-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * ดึง user conversation history
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserHistory(Request $request)
    {
        $validated = $request->validate([
            'line_user_id' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 20;
        $history = $this->activityLogService->getUserHistory(
            $validated['line_user_id'],
            $limit
        );

        return response()->json([
            'success' => true,
            'data' => $history,
            'message' => 'ดึงประวัติสนทนาสำเร็จ',
        ]);
    }

    /**
     * Clear old logs
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearOldLogs(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $deleted = $this->activityLogService->clearOldLogs($validated['days']);

        return redirect()->back()
            ->with('success', "ลบบันทึกรายการเก่า {$deleted} รายการ");
    }

    /**
     * Get keyword statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKeywordStats()
    {
        $keywords = DB::table('line_bot_keywords')
            ->where('is_active', true)
            ->select('id', 'keyword', 'times_matched', 'last_matched_at', 'priority')
            ->orderBy('times_matched', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $keywords,
            'message' => 'ดึงสถิติ keywords สำเร็จ',
        ]);
    }

    /**
     * Get daily activity chart data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyActivityChart(Request $request)
    {
        $days = $request->input('days', 30);

        $data = DB::table('keyword_activity_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                'action_type',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'action_type')
            ->orderBy('date', 'asc')
            ->get();

        // Format for Chart.js
        $dates = [];
        $matches = [];
        $noMatches = [];

        foreach ($data as $item) {
            if (!in_array($item->date, $dates)) {
                $dates[] = $item->date;
            }
        }

        foreach ($dates as $date) {
            $matchCount = $data
                ->where('date', $date)
                ->where('action_type', 'matched')
                ->first()?->count ?? 0;

            $noMatchCount = $data
                ->where('date', $date)
                ->where('action_type', 'no_match')
                ->first()?->count ?? 0;

            $matches[] = $matchCount;
            $noMatches[] = $noMatchCount;
        }

        return response()->json([
            'success' => true,
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Keyword Matches',
                    'data' => $matches,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => 'No Match (AI Fallback)',
                    'data' => $noMatches,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.4,
                ],
            ],
        ]);
    }
}
