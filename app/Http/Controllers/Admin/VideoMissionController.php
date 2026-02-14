<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\VideoMission;
use App\Models\VideoMissionCompletion;
use App\Models\VideoMissionRankLimit;
use App\Models\VideoMissionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Admin Video Mission Controller
 *
 * จัดการภารกิจดูคลิปรับรางวัลในฝั่ง Admin
 * รวมถึงการตั้งค่า, การจัดการภารกิจ, และการตรวจสอบผลการทำ
 */
class VideoMissionController extends Controller
{
    // ==================== DASHBOARD ====================

    /**
     * แสดงหน้า Dashboard ภารกิจดูคลิป
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // สถิติรวม
        $stats = [
            'total_missions' => VideoMission::count(),
            'active_missions' => VideoMission::available()->count(),
            'total_completions' => VideoMissionCompletion::verified()->count(),
            'completions_today' => VideoMissionCompletion::verified()->today()->count(),
            'total_rewards_given' => VideoMission::sum('total_rewards_given'),
            'suspicious_count' => VideoMissionCompletion::suspicious()->whereNull('verified_by')->count(),
            'pending_verification' => VideoMissionCompletion::pendingVerification()->count(),
        ];

        // ภารกิจล่าสุด
        $recentMissions = VideoMission::with(['minRank', 'creator'])
            ->latest()
            ->take(10)
            ->get();

        // การทำล่าสุด
        $recentCompletions = VideoMissionCompletion::with(['user', 'mission'])
            ->latest()
            ->take(10)
            ->get();

        // กราฟการทำภารกิจ 7 วันล่าสุด
        $chartData = VideoMissionCompletion::verified()
            ->where('completion_date', '>=', now()->subDays(7))
            ->groupBy('completion_date')
            ->selectRaw('completion_date, COUNT(*) as count, SUM(reward_money) as total_rewards')
            ->orderBy('completion_date')
            ->get();

        return view('admin.video-missions.index', compact(
            'stats',
            'recentMissions',
            'recentCompletions',
            'chartData'
        ));
    }

    // ==================== MISSIONS CRUD ====================

    /**
     * แสดงรายการภารกิจทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function missions(Request $request)
    {
        $query = VideoMission::with(['minRank', 'maxRank', 'creator']);

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->available();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'scheduled':
                    $query->where('is_active', true)
                        ->where('start_at', '>', now());
                    break;
                case 'expired':
                    $query->where('end_at', '<', now());
                    break;
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_th', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $missions = $query->ordered()->paginate(20);

        // Categories for filter
        $categories = VideoMission::distinct('category')
            ->whereNotNull('category')
            ->pluck('category');

        return view('admin.video-missions.missions', compact('missions', 'categories'));
    }

    /**
     * แสดงฟอร์มสร้างภารกิจใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $ranks = Rank::active()->byLevel()->get();

        return view('admin.video-missions.create', compact('ranks'));
    }

    /**
     * บันทึกภารกิจใหม่
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'icon' => 'nullable|string|max:10',

            'video_url' => 'required|url',
            'video_type' => ['required', Rule::in(['youtube', 'vimeo', 'tiktok', 'facebook', 'direct', 'embed'])],
            'video_id' => 'nullable|string|max:100',
            'embed_code' => 'nullable|string',
            'video_duration_seconds' => 'nullable|integer|min:1',

            'required_watch_seconds' => 'required|integer|min:5',
            'required_watch_percentage' => 'nullable|integer|min:1|max:100',
            'must_watch_complete' => 'boolean',
            'allow_skip' => 'boolean',
            'max_skip_seconds' => 'nullable|integer|min:0',
            'allow_speed_change' => 'boolean',
            'allowed_speeds' => 'nullable|array',

            'reward_type' => ['required', Rule::in(['money', 'coins', 'points', 'tpix', 'exp', 'mixed'])],
            'reward_money' => 'nullable|numeric|min:0',
            'reward_coins' => 'nullable|numeric|min:0',
            'reward_points' => 'nullable|integer|min:0',
            'reward_tpix' => 'nullable|numeric|min:0',
            'reward_exp' => 'nullable|integer|min:0',

            'min_rank_id' => 'nullable|exists:ranks,id',
            'max_rank_id' => 'nullable|exists:ranks,id',
            'min_user_level' => 'nullable|integer|min:1',
            'min_days_registered' => 'nullable|integer|min:0',
            'require_kyc' => 'boolean',
            'require_email_verified' => 'boolean',
            'require_phone_verified' => 'boolean',

            'frequency' => ['required', Rule::in(['once', 'daily', 'weekly', 'monthly', 'yearly', 'unlimited'])],
            'reset_period_value' => 'nullable|integer|min:1',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'max_completions_per_user' => 'nullable|integer|min:1',
            'max_total_completions' => 'nullable|integer|min:1',
            'daily_limit_global' => 'nullable|integer|min:1',

            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'available_days' => 'nullable|array',
            'available_time_start' => 'nullable|date_format:H:i',
            'available_time_end' => 'nullable|date_format:H:i',

            'anti_cheat_enabled' => 'boolean',
            'require_focus' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'max_tab_switches' => 'nullable|integer|min:1',
            'require_interaction' => 'boolean',
            'interaction_interval_seconds' => 'nullable|integer|min:10',
            'verify_completion' => 'boolean',
            'verification_questions' => 'nullable|array',

            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_premium' => 'boolean',
            'priority' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('video-missions/thumbnails', 'public');
        }

        // ตรวจสอบความขัดแย้งของภารกิจ
        $conflictCheck = VideoMission::validateMissionConflicts($validated);

        if (! $conflictCheck['valid']) {
            // มี error - ไม่อนุญาตให้สร้าง
            return back()
                ->withInput()
                ->withErrors(['conflict' => $conflictCheck['errors']])
                ->with('conflict_errors', $conflictCheck['errors'])
                ->with('conflict_warnings', $conflictCheck['warnings']);
        }

        // Set created_by
        $validated['created_by'] = auth()->id();

        // Create mission
        $mission = VideoMission::create($validated);

        // ถ้ามี warnings แสดงด้วย
        $message = 'สร้างภารกิจใหม่สำเร็จ';
        if (count($conflictCheck['warnings']) > 0) {
            $message .= ' (มีคำเตือน: '.implode(', ', $conflictCheck['warnings']).')';
        }

        return redirect()
            ->route('admin.video-missions.missions')
            ->with('success', $message)
            ->with('conflict_warnings', $conflictCheck['warnings']);
    }

    /**
     * แสดงรายละเอียดภารกิจ
     *
     * @return \Illuminate\View\View
     */
    public function show(VideoMission $mission)
    {
        $mission->load(['minRank', 'maxRank', 'creator', 'updater']);

        // สถิติของภารกิจนี้
        $stats = [
            'total_completions' => $mission->completions()->verified()->count(),
            'unique_users' => $mission->completions()->verified()->distinct('user_id')->count('user_id'),
            'completions_today' => $mission->completions()->verified()->today()->count(),
            'suspicious_count' => $mission->completions()->suspicious()->count(),
            'total_rewards' => $mission->total_rewards_given,
            'avg_watch_percentage' => $mission->completions()->avg('watch_percentage') ?? 0,
        ];

        // การทำล่าสุด
        $recentCompletions = $mission->completions()
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.video-missions.show', compact('mission', 'stats', 'recentCompletions'));
    }

    /**
     * แสดงฟอร์มแก้ไขภารกิจ
     *
     * @return \Illuminate\View\View
     */
    public function edit(VideoMission $mission)
    {
        $ranks = Rank::active()->byLevel()->get();

        return view('admin.video-missions.edit', compact('mission', 'ranks'));
    }

    /**
     * อัพเดทภารกิจ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, VideoMission $mission)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048',
            'icon' => 'nullable|string|max:10',

            'video_url' => 'required|url',
            'video_type' => ['required', Rule::in(['youtube', 'vimeo', 'tiktok', 'facebook', 'direct', 'embed'])],
            'video_id' => 'nullable|string|max:100',
            'embed_code' => 'nullable|string',
            'video_duration_seconds' => 'nullable|integer|min:1',

            'required_watch_seconds' => 'required|integer|min:5',
            'required_watch_percentage' => 'nullable|integer|min:1|max:100',
            'must_watch_complete' => 'boolean',
            'allow_skip' => 'boolean',
            'max_skip_seconds' => 'nullable|integer|min:0',
            'allow_speed_change' => 'boolean',
            'allowed_speeds' => 'nullable|array',

            'reward_type' => ['required', Rule::in(['money', 'coins', 'points', 'tpix', 'exp', 'mixed'])],
            'reward_money' => 'nullable|numeric|min:0',
            'reward_coins' => 'nullable|numeric|min:0',
            'reward_points' => 'nullable|integer|min:0',
            'reward_tpix' => 'nullable|numeric|min:0',
            'reward_exp' => 'nullable|integer|min:0',

            'min_rank_id' => 'nullable|exists:ranks,id',
            'max_rank_id' => 'nullable|exists:ranks,id',
            'min_user_level' => 'nullable|integer|min:1',
            'min_days_registered' => 'nullable|integer|min:0',
            'require_kyc' => 'boolean',
            'require_email_verified' => 'boolean',
            'require_phone_verified' => 'boolean',

            'frequency' => ['required', Rule::in(['once', 'daily', 'weekly', 'monthly', 'yearly', 'unlimited'])],
            'reset_period_value' => 'nullable|integer|min:1',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'max_completions_per_user' => 'nullable|integer|min:1',
            'max_total_completions' => 'nullable|integer|min:1',
            'daily_limit_global' => 'nullable|integer|min:1',

            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'available_days' => 'nullable|array',
            'available_time_start' => 'nullable|date_format:H:i',
            'available_time_end' => 'nullable|date_format:H:i',

            'anti_cheat_enabled' => 'boolean',
            'require_focus' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'max_tab_switches' => 'nullable|integer|min:1',
            'require_interaction' => 'boolean',
            'interaction_interval_seconds' => 'nullable|integer|min:10',
            'verify_completion' => 'boolean',
            'verification_questions' => 'nullable|array',

            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_premium' => 'boolean',
            'priority' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($mission->thumbnail) {
                Storage::disk('public')->delete($mission->thumbnail);
            }

            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('video-missions/thumbnails', 'public');
        }

        // ตรวจสอบความขัดแย้งของภารกิจ
        $conflictCheck = VideoMission::validateMissionConflicts($validated);

        if (! $conflictCheck['valid']) {
            // มี error - ไม่อนุญาตให้อัพเดท
            return back()
                ->withInput()
                ->withErrors(['conflict' => $conflictCheck['errors']])
                ->with('conflict_errors', $conflictCheck['errors'])
                ->with('conflict_warnings', $conflictCheck['warnings']);
        }

        // Set updated_by
        $validated['updated_by'] = auth()->id();

        // Update mission
        $mission->update($validated);

        // ถ้ามี warnings แสดงด้วย
        $message = 'อัพเดทภารกิจสำเร็จ';
        if (count($conflictCheck['warnings']) > 0) {
            $message .= ' (มีคำเตือน: '.implode(', ', $conflictCheck['warnings']).')';
        }

        return redirect()
            ->route('admin.video-missions.show', $mission)
            ->with('success', $message)
            ->with('conflict_warnings', $conflictCheck['warnings']);
    }

    /**
     * ลบภารกิจ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(VideoMission $mission)
    {
        // Check if has completions
        $completionCount = $mission->completions()->count();
        if ($completionCount > 0) {
            return back()->with('error', "ไม่สามารถลบได้ เพราะมีคนทำภารกิจนี้แล้ว {$completionCount} ครั้ง");
        }

        // Delete thumbnail
        if ($mission->thumbnail) {
            Storage::disk('public')->delete($mission->thumbnail);
        }

        $mission->delete();

        return redirect()
            ->route('admin.video-missions.missions')
            ->with('success', 'ลบภารกิจสำเร็จ');
    }

    /**
     * Toggle active status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(VideoMission $mission)
    {
        $mission->update(['is_active' => ! $mission->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $mission->is_active,
            'message' => $mission->is_active ? 'เปิดใช้งานภารกิจแล้ว' : 'ปิดใช้งานภารกิจแล้ว',
        ]);
    }

    // ==================== COMPLETIONS ====================

    /**
     * แสดงรายการการทำภารกิจ
     *
     * @return \Illuminate\View\View
     */
    public function completions(Request $request)
    {
        $query = VideoMissionCompletion::with(['user', 'mission']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by suspicious
        if ($request->has('suspicious')) {
            $query->suspicious();
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->where('completion_date', $request->date);
        }

        // Filter by mission
        if ($request->filled('mission_id')) {
            $query->where('mission_id', $request->mission_id);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $completions = $query->latest()->paginate(30);

        // Missions for filter
        $missions = VideoMission::select('id', 'title', 'title_th')
            ->orderBy('title')
            ->get();

        return view('admin.video-missions.completions', compact('completions', 'missions'));
    }

    /**
     * แสดงรายละเอียดการทำภารกิจ
     *
     * @return \Illuminate\View\View
     */
    public function showCompletion(VideoMissionCompletion $completion)
    {
        $completion->load(['user', 'mission', 'verifier', 'rejecter']);

        return view('admin.video-missions.completion-detail', compact('completion'));
    }

    /**
     * ยืนยันการทำภารกิจ (Manual verification)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyCompletion(Request $request, VideoMissionCompletion $completion)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($completion->status !== 'completed') {
            return back()->with('error', 'การทำภารกิจนี้ไม่อยู่ในสถานะรอตรวจสอบ');
        }

        $completion->verify(auth()->id(), $request->notes);

        // Give rewards
        $rankLimit = VideoMissionRankLimit::getForRank($completion->user->current_rank_id ?? 0);
        $multiplier = $rankLimit->reward_multiplier ?? 1.0;
        $completion->giveRewards($multiplier);

        return back()->with('success', 'ยืนยันการทำภารกิจและให้รางวัลแล้ว');
    }

    /**
     * ปฏิเสธการทำภารกิจ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectCompletion(Request $request, VideoMissionCompletion $completion)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (! in_array($completion->status, ['completed', 'verified'])) {
            return back()->with('error', 'ไม่สามารถปฏิเสธการทำภารกิจนี้ได้');
        }

        $completion->reject(auth()->id(), $request->reason);

        return back()->with('success', 'ปฏิเสธการทำภารกิจแล้ว');
    }

    // ==================== RANK LIMITS ====================

    /**
     * แสดงการตั้งค่า Rank Limits
     *
     * @return \Illuminate\View\View
     */
    public function rankLimits()
    {
        $ranks = Rank::active()->byLevel()->get();

        // ดึง rank limits ทั้งหมด และสร้างค่าเริ่มต้นถ้ายังไม่มี
        $rankLimits = [];
        foreach ($ranks as $rank) {
            $rankLimits[$rank->id] = VideoMissionRankLimit::getForRank($rank->id);
        }

        return view('admin.video-missions.rank-limits', compact('ranks', 'rankLimits'));
    }

    /**
     * อัพเดท Rank Limits
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRankLimits(Request $request)
    {
        $request->validate([
            'limits' => 'required|array',
            'limits.*.rank_id' => 'required|exists:ranks,id',
            'limits.*.daily_mission_limit' => 'required|integer|min:1',
            'limits.*.weekly_mission_limit' => 'required|integer|min:1',
            'limits.*.monthly_mission_limit' => 'required|integer|min:1',
            'limits.*.daily_reward_limit' => 'nullable|numeric|min:0',
            'limits.*.weekly_reward_limit' => 'nullable|numeric|min:0',
            'limits.*.monthly_reward_limit' => 'nullable|numeric|min:0',
            'limits.*.reward_multiplier' => 'required|numeric|min:0.1|max:10',
            'limits.*.bonus_reward_percentage' => 'nullable|numeric|min:0|max:100',
            'limits.*.bonus_exp_percentage' => 'nullable|integer|min:0|max:100',
            'limits.*.can_access_premium_missions' => 'boolean',
            'limits.*.can_access_featured_missions' => 'boolean',
            'limits.*.skip_cooldown' => 'boolean',
            'limits.*.cooldown_reduction_percent' => 'nullable|integer|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->limits as $limitData) {
                VideoMissionRankLimit::updateOrCreate(
                    ['rank_id' => $limitData['rank_id']],
                    $limitData
                );
            }

            DB::commit();

            return back()->with('success', 'อัพเดทการตั้งค่า Rank Limits สำเร็จ');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    // ==================== SETTINGS ====================

    /**
     * แสดงหน้าตั้งค่า
     *
     * @return \Illuminate\View\View
     */
    public function settings()
    {
        $settings = VideoMissionSetting::all()->groupBy('group');

        return view('admin.video-missions.settings', compact('settings'));
    }

    /**
     * อัพเดทตั้งค่า
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            $setting = VideoMissionSetting::where('key', $key)->first();

            if ($setting) {
                // แปลงค่าตาม type
                if ($setting->type === 'boolean') {
                    $value = $value ? 'true' : 'false';
                } elseif ($setting->type === 'json' || $setting->type === 'array') {
                    $value = is_array($value) ? json_encode($value) : $value;
                }

                $setting->update(['value' => $value]);
            }
        }

        // Clear cache
        VideoMissionSetting::clearCache();

        return back()->with('success', 'อัพเดทการตั้งค่าสำเร็จ');
    }

    // ==================== REPORTS ====================

    /**
     * แสดงรายงาน
     *
     * @return \Illuminate\View\View
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // รายงานรวม
        $overview = [
            'total_completions' => VideoMissionCompletion::verified()
                ->whereBetween('completion_date', [$startDate, $endDate])
                ->count(),
            'unique_users' => VideoMissionCompletion::verified()
                ->whereBetween('completion_date', [$startDate, $endDate])
                ->distinct('user_id')
                ->count('user_id'),
            'total_rewards' => VideoMissionCompletion::verified()
                ->where('reward_given', true)
                ->whereBetween('completion_date', [$startDate, $endDate])
                ->sum('reward_money'),
            'suspicious_rate' => VideoMissionCompletion::whereBetween('completion_date', [$startDate, $endDate])
                ->count() > 0
                ? round(
                    VideoMissionCompletion::suspicious()
                        ->whereBetween('completion_date', [$startDate, $endDate])
                        ->count() /
                    VideoMissionCompletion::whereBetween('completion_date', [$startDate, $endDate])
                        ->count() * 100,
                    2
                )
                : 0,
        ];

        // รายงานรายวัน
        $dailyReport = VideoMissionCompletion::verified()
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->groupBy('completion_date')
            ->selectRaw('
                completion_date,
                COUNT(*) as completions,
                COUNT(DISTINCT user_id) as unique_users,
                SUM(reward_money) as total_rewards
            ')
            ->orderBy('completion_date')
            ->get();

        // Top missions
        $topMissions = VideoMission::withCount([
            'completions as verified_completions' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'verified')
                    ->whereBetween('completion_date', [$startDate, $endDate]);
            },
        ])
            ->orderByDesc('verified_completions')
            ->take(10)
            ->get();

        // Top users
        $topUsers = VideoMissionCompletion::verified()
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->with('user')
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as completions, SUM(reward_money) as earnings')
            ->orderByDesc('completions')
            ->take(10)
            ->get();

        return view('admin.video-missions.reports', compact(
            'overview',
            'dailyReport',
            'topMissions',
            'topUsers',
            'startDate',
            'endDate'
        ));
    }

    // ==================== YOUTUBE IMPORT ====================

    /**
     * แสดงหน้านำเข้าวิดีโอจาก YouTube
     *
     * @return \Illuminate\View\View
     */
    public function importYouTube()
    {
        // ดึงหมวดหมู่ที่มีอยู่
        $categories = VideoMission::distinct('category')
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();

        // เพิ่มหมวดหมู่เริ่มต้น
        $defaultCategories = ['youtube', 'entertainment', 'education', 'music', 'gaming', 'news', 'sports'];
        $categories = array_unique(array_merge($categories, $defaultCategories));
        sort($categories);

        // ตรวจสอบว่ามี API Key หรือไม่
        $hasApiKey = ! empty(env('YOUTUBE_API_KEY'));

        return view('admin.video-missions.import-youtube', compact('categories', 'hasApiKey'));
    }

    /**
     * ประมวลผลนำเข้าวิดีโอจาก YouTube
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function processImportYouTube(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'category' => 'required|string|max:50',
            'reward_money' => 'required|numeric|min:0|max:1000',
            'reward_coins' => 'required|numeric|min:0|max:10000',
            'reward_exp' => 'required|integer|min:0|max:10000',
            'watch_percent' => 'required|integer|min:50|max:100',
            'limit' => 'required|integer|min:1|max:500',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $apiKey = $request->input('api_key') ?: env('YOUTUBE_API_KEY');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ YouTube API Key',
            ], 400);
        }

        // แปลง channel name เป็น channel ID
        $channel = $request->input('channel');
        $channelId = $this->resolveChannelId($channel, $apiKey);

        if (! $channelId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบช่อง YouTube ที่ระบุ กรุณาตรวจสอบชื่อช่องหรือ Channel ID',
            ], 404);
        }

        // ดึงข้อมูลช่อง
        $channelInfo = $this->getChannelInfo($channelId, $apiKey);

        if (! $channelInfo) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลช่องได้',
            ], 500);
        }

        // ดึงวิดีโอจากช่อง
        $uploadsPlaylistId = $channelInfo['uploadsPlaylistId'] ?? null;

        if (! $uploadsPlaylistId) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบ Playlist วิดีโอของช่อง',
            ], 500);
        }

        $videos = $this->getPlaylistVideos($uploadsPlaylistId, $apiKey, (int) $request->input('limit'));

        if (empty($videos)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบวิดีโอในช่อง',
            ], 404);
        }

        // สร้างภารกิจจากวิดีโอ
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($videos as $video) {
            try {
                $result = $this->createMissionFromVideo($video, $apiKey, [
                    'category' => $request->input('category'),
                    'reward_money' => (float) $request->input('reward_money'),
                    'reward_coins' => (float) $request->input('reward_coins'),
                    'reward_exp' => (int) $request->input('reward_exp'),
                    'watch_percent' => (int) $request->input('watch_percent'),
                    'is_active' => $request->boolean('is_active', true),
                    'is_featured' => $request->boolean('is_featured', false),
                ]);

                if ($result === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                \Log::error('YouTube Import Error', [
                    'video_id' => $video['videoId'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'นำเข้าสำเร็จ!',
            'data' => [
                'channel' => $channelInfo['title'],
                'total_videos' => count($videos),
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * แปลง Channel Handle หรือ URL เป็น Channel ID
     */
    protected function resolveChannelId(string $channel, string $apiKey): ?string
    {
        // ถ้าเป็น Channel ID อยู่แล้ว (เริ่มด้วย UC)
        if (preg_match('/^UC[\w-]{22}$/', $channel)) {
            return $channel;
        }

        // ถ้าเป็น URL
        if (str_contains($channel, 'youtube.com')) {
            // Extract channel ID or handle from URL
            if (preg_match('/youtube\.com\/channel\/(UC[\w-]{22})/', $channel, $matches)) {
                return $matches[1];
            }
            if (preg_match('/youtube\.com\/@([\w-]+)/', $channel, $matches)) {
                $channel = '@'.$matches[1];
            }
        }

        // ถ้าเป็น @handle
        if (str_starts_with($channel, '@')) {
            $handle = substr($channel, 1);
        } else {
            $handle = $channel;
        }

        // ค้นหา Channel ID จาก handle/username
        try {
            $response = \Http::get('https://www.googleapis.com/youtube/v3/search', [
                'key' => $apiKey,
                'q' => $handle,
                'type' => 'channel',
                'part' => 'snippet',
                'maxResults' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['items'])) {
                    return $data['items'][0]['id']['channelId'] ?? null;
                }
            }

            // ลองค้นหาด้วย forUsername (สำหรับ legacy usernames)
            $response = \Http::get('https://www.googleapis.com/youtube/v3/channels', [
                'key' => $apiKey,
                'forUsername' => $handle,
                'part' => 'id',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['items'])) {
                    return $data['items'][0]['id'] ?? null;
                }
            }

            // ลองค้นหาด้วย forHandle (สำหรับ @handles ใหม่)
            $response = \Http::get('https://www.googleapis.com/youtube/v3/channels', [
                'key' => $apiKey,
                'forHandle' => $handle,
                'part' => 'id',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['items'])) {
                    return $data['items'][0]['id'] ?? null;
                }
            }
        } catch (\Exception $e) {
            \Log::error('YouTube Channel Resolve Error', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * ดึงข้อมูลช่อง YouTube
     */
    protected function getChannelInfo(string $channelId, string $apiKey): ?array
    {
        try {
            $response = \Http::get('https://www.googleapis.com/youtube/v3/channels', [
                'key' => $apiKey,
                'id' => $channelId,
                'part' => 'snippet,contentDetails,statistics',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return null;
            }

            $channel = $data['items'][0];

            return [
                'id' => $channel['id'],
                'title' => $channel['snippet']['title'] ?? '',
                'description' => $channel['snippet']['description'] ?? '',
                'thumbnail' => $channel['snippet']['thumbnails']['high']['url'] ?? null,
                'subscriberCount' => $channel['statistics']['subscriberCount'] ?? 0,
                'videoCount' => $channel['statistics']['videoCount'] ?? 0,
                'uploadsPlaylistId' => $channel['contentDetails']['relatedPlaylists']['uploads'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ดึงวิดีโอจาก Playlist
     */
    protected function getPlaylistVideos(string $playlistId, string $apiKey, int $limit = 100): array
    {
        $videos = [];
        $nextPageToken = null;

        do {
            $params = [
                'key' => $apiKey,
                'playlistId' => $playlistId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min(50, $limit - count($videos)),
            ];

            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            $response = \Http::get('https://www.googleapis.com/youtube/v3/playlistItems', $params);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();

            foreach ($data['items'] ?? [] as $item) {
                $videos[] = [
                    'videoId' => $item['contentDetails']['videoId'] ?? null,
                    'title' => $item['snippet']['title'] ?? '',
                    'description' => $item['snippet']['description'] ?? '',
                    'thumbnail' => $item['snippet']['thumbnails']['high']['url']
                                ?? $item['snippet']['thumbnails']['medium']['url']
                                ?? $item['snippet']['thumbnails']['default']['url']
                                ?? null,
                    'publishedAt' => $item['snippet']['publishedAt'] ?? null,
                    'channelTitle' => $item['snippet']['channelTitle'] ?? '',
                ];

                if (count($videos) >= $limit) {
                    break 2;
                }
            }

            $nextPageToken = $data['nextPageToken'] ?? null;
        } while ($nextPageToken && count($videos) < $limit);

        return $videos;
    }

    /**
     * ดึงรายละเอียดวิดีโอ
     */
    protected function getVideoDetails(string $videoId, string $apiKey): ?array
    {
        try {
            $response = \Http::get('https://www.googleapis.com/youtube/v3/videos', [
                'key' => $apiKey,
                'id' => $videoId,
                'part' => 'contentDetails,statistics',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return null;
            }

            $video = $data['items'][0];
            $duration = $video['contentDetails']['duration'] ?? 'PT0S';

            return [
                'duration' => $this->parseDuration($duration),
                'viewCount' => (int) ($video['statistics']['viewCount'] ?? 0),
                'likeCount' => (int) ($video['statistics']['likeCount'] ?? 0),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * แปลง ISO 8601 Duration เป็นวินาที
     */
    protected function parseDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);

            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 60; // default 1 minute
        }
    }

    /**
     * สร้างภารกิจจากวิดีโอ
     */
    protected function createMissionFromVideo(array $video, string $apiKey, array $options): string
    {
        $videoId = $video['videoId'];

        if (! $videoId) {
            return 'error';
        }

        // ตรวจสอบว่ามีอยู่แล้วหรือไม่
        if (VideoMission::where('video_id', $videoId)->exists()) {
            return 'skipped';
        }

        // ดึงรายละเอียดวิดีโอ
        $details = $this->getVideoDetails($videoId, $apiKey);
        $durationSeconds = $details['duration'] ?? 60;

        // คำนวณเวลาที่ต้องดู
        $watchPercent = $options['watch_percent'] ?? 80;
        $requiredWatchSeconds = (int) ceil($durationSeconds * ($watchPercent / 100));

        if ($durationSeconds < 60) {
            $requiredWatchSeconds = $durationSeconds;
        }

        // สร้างภารกิจ
        VideoMission::create([
            'title' => $video['title'],
            'title_th' => $video['title'],
            'description' => substr($video['description'] ?? '', 0, 500),
            'description_th' => substr($video['description'] ?? '', 0, 500),
            'thumbnail' => $video['thumbnail'],

            'video_url' => "https://www.youtube.com/watch?v={$videoId}",
            'video_type' => 'youtube',
            'video_id' => $videoId,
            'video_duration_seconds' => $durationSeconds,

            'required_watch_seconds' => $requiredWatchSeconds,
            'required_watch_percentage' => $watchPercent,

            'reward_type' => 'multi',
            'reward_money' => $options['reward_money'] ?? 1,
            'reward_coins' => $options['reward_coins'] ?? 10,
            'reward_exp' => $options['reward_exp'] ?? 5,

            'frequency' => 'daily',
            'cooldown_minutes' => 0,

            'anti_cheat_enabled' => true,
            'require_focus' => true,
            'detect_tab_switch' => true,
            'max_tab_switches' => 3,

            'is_active' => $options['is_active'] ?? true,
            'is_featured' => $options['is_featured'] ?? false,
            'priority' => 0,
            'sort_order' => 0,

            'category' => $options['category'] ?? 'youtube',
            'tags' => [$video['channelTitle'] ?? 'YouTube'],

            'created_by' => auth()->id(),
        ]);

        return 'created';
    }
}
