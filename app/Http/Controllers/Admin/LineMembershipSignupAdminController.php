<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineSignupAnalytic;
use App\Models\LineSignupInvitation;
use App\Models\LineSignupReward;
use App\Models\LineSignupSession;
use App\Models\LineSignupTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LineMembershipSignupAdminController extends Controller
{
    /**
     * Show signup dashboard
     */
    public function index(Request $request)
    {
        $dateRange = $request->input('range', '30');
        $startDate = now()->subDays((int)$dateRange);

        // Get overview statistics
        $stats = [
            'total_sessions' => LineSignupSession::where('created_at', '>=', $startDate)->count(),
            'completed' => LineSignupSession::where('status', LineSignupSession::STATUS_COMPLETED)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'active' => LineSignupSession::where('status', LineSignupSession::STATUS_ACTIVE)->count(),
            'abandoned' => LineSignupSession::where('status', LineSignupSession::STATUS_ABANDONED)
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];

        $stats['completion_rate'] = $stats['total_sessions'] > 0
            ? round(($stats['completed'] / $stats['total_sessions']) * 100, 2)
            : 0;

        // Get daily signups for chart
        $dailySignups = LineSignupSession::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get step funnel data
        $stepFunnel = DB::table('line_signup_step_logs')
            ->join('line_signup_sessions', 'line_signup_step_logs.session_id', '=', 'line_signup_sessions.id')
            ->where('line_signup_sessions.created_at', '>=', $startDate)
            ->select(
                'step_name',
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completions')
            )
            ->groupBy('step_name')
            ->get();

        // Get recent sessions
        $recentSessions = LineSignupSession::with(['user', 'affiliate'])
            ->latest()
            ->limit(10)
            ->get();

        // Get top performers (affiliates with most completed signups)
        $topPerformers = DB::table('line_signup_sessions')
            ->join('users', 'line_signup_sessions.user_id', '=', 'users.id')
            ->join('affiliates', 'line_signup_sessions.affiliate_id', '=', 'affiliates.id')
            ->where('line_signup_sessions.status', LineSignupSession::STATUS_COMPLETED)
            ->where('line_signup_sessions.created_at', '>=', $startDate)
            ->select(
                'affiliates.referral_code',
                'users.name',
                DB::raw('COUNT(*) as signup_count')
            )
            ->groupBy('affiliates.id', 'affiliates.referral_code', 'users.name')
            ->orderByDesc('signup_count')
            ->limit(10)
            ->get();

        return view('admin.line-membership-signup.index', compact(
            'stats',
            'dailySignups',
            'stepFunnel',
            'recentSessions',
            'topPerformers',
            'dateRange'
        ));
    }

    /**
     * แสดงรายการ signup sessions ทั้งหมด
     */
    public function sessions(Request $request)
    {
        $query = LineSignupSession::with(['user', 'affiliate'])->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('line_user_id', 'like', "%{$search}%")
                    ->orWhere('session_token', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sessions = $query->paginate(20);

        return view('admin.line-membership-signup.sessions', compact('sessions'));
    }

    /**
     * แสดงรายละเอียด session
     */
    public function showSession(LineSignupSession $session)
    {
        $session->load([
            'user',
            'affiliate',
            'stepLogs' => function ($query) {
                $query->orderBy('created_at');
            },
            'conversations' => function ($query) {
                $query->orderBy('created_at');
            },
            'rewards',
        ]);

        return view('admin.line-membership-signup.session-details', compact('session'));
    }

    /**
     * จัดการ Flex Message templates
     */
    public function templates(Request $request)
    {
        $templates = LineSignupTemplate::latest()->get();

        return view('admin.line-membership-signup.templates', compact('templates'));
    }

    /**
     * Create new template
     */
    public function createTemplate(Request $request)
    {
        $request->validate([
            'template_key' => 'required|string|unique:line_signup_templates,template_key',
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flex_message_json' => 'required|array',
            'variables' => 'nullable|array',
        ]);

        $template = LineSignupTemplate::create($request->all());

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Update template
     */
    public function updateTemplate(Request $request, LineSignupTemplate $template)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flex_message_json' => 'required|array',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $template->update($request->all());

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Delete template
     */
    public function deleteTemplate(LineSignupTemplate $template)
    {
        $template->delete();

        return response()->json(['success' => true]);
    }

    /**
     * จัดการลิงก์เชิญ (invitations)
     */
    public function invitations(Request $request)
    {
        $query = LineSignupInvitation::with('inviter')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $invitations = $query->paginate(20);

        return view('admin.line-membership-signup.invitations', compact('invitations'));
    }

    /**
     * จัดการรางวัล (rewards)
     */
    public function rewards(Request $request)
    {
        $query = LineSignupReward::with(['user', 'session'])->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $rewards = $query->paginate(20);

        return view('admin.line-membership-signup.rewards', compact('rewards'));
    }

    /**
     * Grant reward manually
     */
    public function grantReward(Request $request, LineSignupReward $reward)
    {
        if ($reward->status !== LineSignupReward::STATUS_PENDING) {
            return response()->json(['error' => 'Reward already processed'], 400);
        }

        $reward->grant();

        return response()->json([
            'success' => true,
            'reward' => $reward,
        ]);
    }

    /**
     * Get analytics data for charts
     */
    public function analyticsData(Request $request)
    {
        $type = $request->input('type', 'daily');
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days);

        switch ($type) {
            case 'daily':
                $data = $this->getDailyAnalytics($startDate);
                break;

            case 'funnel':
                $data = $this->getFunnelAnalytics($startDate);
                break;

            case 'sources':
                $data = $this->getSourceAnalytics($startDate);
                break;

            case 'completion_time':
                $data = $this->getCompletionTimeAnalytics($startDate);
                break;

            default:
                $data = [];
        }

        return response()->json($data);
    }

    /**
     * Get daily analytics
     */
    protected function getDailyAnalytics($startDate): array
    {
        return LineSignupSession::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "abandoned" THEN 1 ELSE 0 END) as abandoned')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get funnel analytics
     */
    protected function getFunnelAnalytics($startDate): array
    {
        $stepOrder = LineSignupSession::STEPS;

        $data = DB::table('line_signup_step_logs')
            ->join('line_signup_sessions', 'line_signup_step_logs.session_id', '=', 'line_signup_sessions.id')
            ->where('line_signup_sessions.created_at', '>=', $startDate)
            ->select(
                'step_name',
                DB::raw('COUNT(DISTINCT session_id) as visitors'),
                DB::raw('SUM(CASE WHEN line_signup_step_logs.status = "completed" THEN 1 ELSE 0 END) as completions'),
                DB::raw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration')
            )
            ->groupBy('step_name')
            ->get()
            ->keyBy('step_name');

        // Order by step sequence
        $orderedData = [];
        foreach ($stepOrder as $step) {
            if (isset($data[$step])) {
                $orderedData[] = $data[$step];
            }
        }

        return $orderedData;
    }

    /**
     * Get source analytics
     */
    protected function getSourceAnalytics($startDate): array
    {
        return LineSignupSession::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('JSON_EXTRACT(collected_data, "$.source") as source'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('source')
            ->get()
            ->toArray();
    }

    /**
     * Get completion time analytics
     */
    protected function getCompletionTimeAnalytics($startDate): array
    {
        return LineSignupSession::where('status', LineSignupSession::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('completed_at')
            ->select(
                DB::raw('TIMESTAMPDIFF(MINUTE, started_at, completed_at) as duration_minutes')
            )
            ->get()
            ->groupBy(function ($item) {
                $duration = $item->duration_minutes;
                if ($duration <= 2) return '0-2 min';
                if ($duration <= 5) return '2-5 min';
                if ($duration <= 10) return '5-10 min';
                if ($duration <= 30) return '10-30 min';
                return '30+ min';
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();
    }

    /**
     * Export sessions data
     */
    public function exportSessions(Request $request)
    {
        $query = LineSignupSession::with(['user', 'affiliate']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $sessions = $query->get();

        $filename = 'line_signup_sessions_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($sessions) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Session ID',
                'LINE User ID',
                'Status',
                'Current Step',
                'Name',
                'Email',
                'Phone',
                'Referral Code',
                'Started At',
                'Completed At',
                'Duration (minutes)',
            ]);

            // Data
            foreach ($sessions as $session) {
                $duration = null;
                if ($session->completed_at) {
                    $duration = $session->started_at->diffInMinutes($session->completed_at);
                }

                fputcsv($file, [
                    $session->id,
                    $session->line_user_id,
                    $session->status,
                    $session->current_step,
                    $session->getCollectedData('name'),
                    $session->getCollectedData('email'),
                    $session->getCollectedData('phone'),
                    $session->getCollectedData('referral_code'),
                    $session->started_at->toDateTimeString(),
                    $session->completed_at?->toDateTimeString(),
                    $duration,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
