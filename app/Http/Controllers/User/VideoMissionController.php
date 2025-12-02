<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\VideoMission;
use App\Models\VideoMissionCompletion;
use App\Models\VideoMissionRankLimit;
use App\Models\VideoMissionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * User Video Mission Controller
 *
 * จัดการภารกิจดูคลิปรับรางวัลในฝั่ง User
 * รวมถึงการดูภารกิจ, เริ่มทำภารกิจ, และติดตามความคืบหน้า
 */
class VideoMissionController extends Controller
{
    // ==================== DASHBOARD ====================

    /**
     * แสดงหน้าหลักภารกิจดูคลิป
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = auth()->user();

        // ตรวจสอบว่าระบบเปิดใช้งานหรือไม่
        if (!VideoMissionSetting::isSystemEnabled()) {
            return view('user.video-missions.disabled');
        }

        if (VideoMissionSetting::isMaintenanceMode()) {
            $message = VideoMissionSetting::get('maintenance_message', 'ระบบกำลังปรับปรุง');
            return view('user.video-missions.maintenance', compact('message'));
        }

        // ดึง rank limit ของ user
        $rankLimit = VideoMissionRankLimit::getForRank($user->current_rank_id ?? 0);
        $limits = $rankLimit->checkAllLimits($user);

        // สถิติของ user
        $stats = [
            'total_completed' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->count(),
            'completed_today' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->today()
                ->count(),
            'total_earnings' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->where('reward_given', true)
                ->sum('reward_money'),
            'earnings_today' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->today()
                ->where('reward_given', true)
                ->sum('reward_money'),
        ];

        // ภารกิจแนะนำ
        $featuredMissions = $this->getAvailableMissions($user)
            ->featured()
            ->take(4)
            ->get();

        // เพิ่มข้อมูลสิทธิ์ให้ featured missions
        $featuredMissions = $this->attachEligibilityInfo($featuredMissions, $user);

        // ภารกิจทั้งหมด
        $missions = $this->getAvailableMissions($user)
            ->paginate(12);

        // เพิ่มข้อมูลสิทธิ์ให้ missions
        $missions->getCollection()->transform(function ($mission) use ($user) {
            return $this->addEligibilityToMission($mission, $user);
        });

        // ภารกิจที่กำลังทำอยู่
        $inProgressMissions = VideoMissionCompletion::where('user_id', $user->id)
            ->whereIn('status', ['started', 'watching', 'paused'])
            ->with('mission')
            ->latest()
            ->get();

        return view('user.video-missions.index', compact(
            'user',
            'rankLimit',
            'limits',
            'stats',
            'featuredMissions',
            'missions',
            'inProgressMissions'
        ));
    }

    /**
     * แสดงรายการภารกิจทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function missions(Request $request)
    {
        $user = auth()->user();

        $query = $this->getAvailableMissions($user);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by reward type
        if ($request->filled('reward_type')) {
            $query->where('reward_type', $request->reward_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_th', 'like', "%{$search}%");
            });
        }

        $missions = $query->paginate(12);

        // Categories
        $categories = VideoMission::available()
            ->whereNotNull('category')
            ->distinct('category')
            ->pluck('category');

        // ดึง rank limit
        $rankLimit = VideoMissionRankLimit::getForRank($user->current_rank_id ?? 0);
        $limits = $rankLimit->checkAllLimits($user);

        return view('user.video-missions.list', compact('missions', 'categories', 'limits'));
    }

    /**
     * แสดงรายละเอียดภารกิจ
     *
     * @param VideoMission $mission
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(VideoMission $mission)
    {
        $user = auth()->user();

        // ตรวจสอบสิทธิ์
        $eligibility = $mission->checkUserEligibility($user);

        // ตรวจสอบว่าทำได้ตอนนี้ไหม
        $canDoNow = $mission->canUserDoNow($user);

        // ถ้าไม่มีสิทธิ์เลย (ไม่ใช่แค่รอ cooldown) ให้ redirect กลับไปหน้า index
        if (!$eligibility['eligible'] && !$canDoNow['can']) {
            return redirect()
                ->route('user.video-missions.index')
                ->with('error', $eligibility['reason'] ?? $canDoNow['reason'] ?? 'คุณไม่มีสิทธิ์เข้าถึงภารกิจนี้');
        }

        // ดึงประวัติการทำภารกิจของ user
        $userCompletions = VideoMissionCompletion::where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // ดึง rank limit
        $rankLimit = VideoMissionRankLimit::getForRank($user->current_rank_id ?? 0);
        $rewards = $mission->calculateRewards($rankLimit->reward_multiplier);

        return view('user.video-missions.show', compact(
            'mission',
            'eligibility',
            'userCompletions',
            'rankLimit',
            'rewards',
            'canDoNow'
        ));
    }

    // ==================== WATCH VIDEO ====================

    /**
     * เริ่มดูวิดีโอ
     *
     * @param VideoMission $mission
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function watch(VideoMission $mission)
    {
        $user = auth()->user();

        // ตรวจสอบสิทธิ์
        $eligibility = $mission->checkUserEligibility($user);
        if (!$eligibility['eligible']) {
            return redirect()
                ->route('user.video-missions.show', $mission)
                ->with('error', $eligibility['reason']);
        }

        // ตรวจสอบ rank limit
        $rankLimit = VideoMissionRankLimit::getForRank($user->current_rank_id ?? 0);
        $limits = $rankLimit->checkAllLimits($user);
        if (!$limits['can_do_more']) {
            return redirect()
                ->route('user.video-missions.show', $mission)
                ->with('error', $limits['reason']);
        }

        // ตรวจสอบว่ามี session ที่กำลังทำอยู่ไหม
        $existingSession = VideoMissionCompletion::where('user_id', $user->id)
            ->where('mission_id', $mission->id)
            ->whereIn('status', ['started', 'watching', 'paused'])
            ->first();

        if ($existingSession) {
            // ใช้ session เดิม
            $completion = $existingSession;
        } else {
            // สร้าง session ใหม่
            $completion = VideoMissionCompletion::create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'required_seconds' => $mission->required_watch_seconds,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_type' => $this->detectDeviceType(),
            ]);

            // เพิ่ม view count
            $mission->incrementViewCount();
        }

        // ดึงการตั้งค่าป้องกันโกง
        $antiCheatSettings = [
            'enabled' => $mission->anti_cheat_enabled,
            'heartbeat_interval' => VideoMissionSetting::getHeartbeatInterval(),
            'require_focus' => $mission->require_focus,
            'detect_tab_switch' => $mission->detect_tab_switch,
            'max_tab_switches' => $mission->max_tab_switches,
            'require_interaction' => $mission->require_interaction,
            'interaction_interval' => $mission->interaction_interval_seconds,
            'allow_speed_change' => $mission->allow_speed_change,
            'allowed_speeds' => $mission->allowed_speeds ?? [1.0],
            'allow_skip' => $mission->allow_skip,
            'max_skip_seconds' => $mission->max_skip_seconds,
        ];

        return view('user.video-missions.watch', compact(
            'mission',
            'completion',
            'antiCheatSettings',
            'rankLimit'
        ));
    }

    // ==================== API ENDPOINTS ====================

    /**
     * บันทึก heartbeat
     *
     * @param Request $request
     * @param VideoMissionCompletion $completion
     * @return \Illuminate\Http\JsonResponse
     */
    public function heartbeat(Request $request, VideoMissionCompletion $completion)
    {
        // ตรวจสอบว่าเป็นของ user คนนี้
        if ($completion->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // ตรวจสอบ session
        if (!$completion->isSessionValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired',
                'action' => 'reload',
            ], 400);
        }

        $request->validate([
            'position' => 'required|integer|min:0',
            'speed' => 'nullable|numeric|min:0.25|max:2',
        ]);

        $completion->recordHeartbeat(
            $request->position,
            $request->input('speed', 1.0)
        );

        return response()->json([
            'success' => true,
            'watched_seconds' => $completion->watched_seconds,
            'watch_percentage' => $completion->watch_percentage,
            'remaining_seconds' => max(0, $completion->required_seconds - $completion->watched_seconds),
            'can_complete' => $completion->watched_seconds >= $completion->required_seconds,
        ]);
    }

    /**
     * บันทึก event (tab switch, pause, seek, etc.)
     *
     * @param Request $request
     * @param VideoMissionCompletion $completion
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordEvent(Request $request, VideoMissionCompletion $completion)
    {
        if ($completion->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'event' => 'required|string|in:tab_switch,pause,resume,seek,focus_lost,interaction',
            'from_position' => 'nullable|integer|min:0',
            'to_position' => 'nullable|integer|min:0',
        ]);

        switch ($request->event) {
            case 'tab_switch':
                $completion->recordTabSwitch();
                break;
            case 'pause':
                $completion->recordPause();
                break;
            case 'resume':
                $completion->recordResume();
                break;
            case 'seek':
                $completion->recordSeek(
                    $request->from_position ?? 0,
                    $request->to_position ?? 0
                );
                break;
            case 'focus_lost':
                $completion->recordFocusLost();
                break;
            case 'interaction':
                $completion->recordInteraction();
                break;
        }

        return response()->json(['success' => true]);
    }

    /**
     * ทำภารกิจเสร็จ
     *
     * @param Request $request
     * @param VideoMissionCompletion $completion
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request, VideoMissionCompletion $completion)
    {
        if ($completion->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // ตรวจสอบว่าดูครบหรือยัง
        if ($completion->watched_seconds < $completion->required_seconds) {
            return response()->json([
                'success' => false,
                'message' => 'ยังดูไม่ครบตามที่กำหนด',
                'watched_seconds' => $completion->watched_seconds,
                'required_seconds' => $completion->required_seconds,
            ], 400);
        }

        // ทำเครื่องหมายว่าดูจบ
        $completion->markCompleted();

        // ตรวจสอบและให้รางวัล (auto-verify ถ้าไม่น่าสงสัย)
        $mission = $completion->mission;
        $verified = false;
        $rewardGiven = false;

        if (!$mission->verify_completion) {
            // Auto-verify
            $verified = $completion->verify();

            if ($verified) {
                // ให้รางวัล
                $rankLimit = VideoMissionRankLimit::getForRank(auth()->user()->current_rank_id ?? 0);
                $rewardGiven = $completion->giveRewards($rankLimit->reward_multiplier);

                // เพิ่มรางวัลเข้า wallet/coins ของ user
                if ($rewardGiven) {
                    $this->processRewards($completion);
                }
            }
        }

        return response()->json([
            'success' => true,
            'status' => $completion->status,
            'verified' => $verified,
            'reward_given' => $rewardGiven,
            'rewards' => $rewardGiven ? [
                'money' => $completion->reward_money,
                'coins' => $completion->reward_coins,
                'points' => $completion->reward_points,
                'tpix' => $completion->reward_tpix,
                'exp' => $completion->reward_exp,
            ] : null,
            'message' => $verified
                ? ($rewardGiven ? 'ยินดีด้วย! คุณได้รับรางวัลแล้ว' : 'ทำภารกิจสำเร็จ')
                : 'ทำภารกิจสำเร็จ รอตรวจสอบ',
        ]);
    }

    /**
     * ยกเลิกการทำภารกิจ
     *
     * @param VideoMissionCompletion $completion
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(VideoMissionCompletion $completion)
    {
        if ($completion->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($completion->status, ['started', 'watching', 'paused'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถยกเลิกได้',
            ], 400);
        }

        $completion->update([
            'status' => 'cancelled',
            'verification_notes' => 'ยกเลิกโดยผู้ใช้',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกการทำภารกิจแล้ว',
        ]);
    }

    // ==================== HISTORY ====================

    /**
     * แสดงประวัติการทำภารกิจ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $user = auth()->user();

        $query = VideoMissionCompletion::where('user_id', $user->id)
            ->with('mission');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->where('completion_date', $request->date);
        }

        $completions = $query->latest()->paginate(20);

        // สรุปรายได้
        $earnings = [
            'today' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->today()
                ->where('reward_given', true)
                ->sum('reward_money'),
            'this_week' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->thisWeek()
                ->where('reward_given', true)
                ->sum('reward_money'),
            'this_month' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->thisMonth()
                ->where('reward_given', true)
                ->sum('reward_money'),
            'total' => VideoMissionCompletion::where('user_id', $user->id)
                ->verified()
                ->where('reward_given', true)
                ->sum('reward_money'),
        ];

        return view('user.video-missions.history', compact('completions', 'earnings'));
    }

    // ==================== HELPER METHODS ====================

    /**
     * ดึง query สำหรับภารกิจที่ user สามารถทำได้
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getAvailableMissions($user)
    {
        $userRank = $user->currentRank;
        $rankLevel = $userRank ? $userRank->level : 0;

        return VideoMission::available()
            ->ordered()
            ->where(function ($q) use ($rankLevel) {
                $q->whereNull('min_rank_id')
                    ->orWhereHas('minRank', function ($subQ) use ($rankLevel) {
                        $subQ->where('level', '<=', $rankLevel);
                    });
            })
            ->where(function ($q) use ($rankLevel) {
                $q->whereNull('max_rank_id')
                    ->orWhereHas('maxRank', function ($subQ) use ($rankLevel) {
                        $subQ->where('level', '>=', $rankLevel);
                    });
            });
    }

    /**
     * ตรวจจับประเภทอุปกรณ์
     *
     * @return string
     */
    protected function detectDeviceType(): string
    {
        $agent = request()->userAgent();

        if (preg_match('/Mobile|Android|iPhone|iPad/', $agent)) {
            if (preg_match('/iPad|Tablet/', $agent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * ประมวลผลรางวัล (เพิ่มเข้า wallet/coins)
     *
     * @param VideoMissionCompletion $completion
     * @return void
     */
    protected function processRewards(VideoMissionCompletion $completion): void
    {
        $user = $completion->user;

        // เพิ่มเงินเข้า Wallet
        if ($completion->reward_money > 0) {
            // ตรวจสอบว่ามี Wallet หรือยัง
            $wallet = $user->wallet;
            if ($wallet) {
                DB::transaction(function () use ($wallet, $completion) {
                    // เพิ่มยอดเงิน
                    $wallet->increment('balance', $completion->reward_money);

                    // บันทึก transaction
                    $wallet->transactions()->create([
                        'transaction_id' => 'TXN' . strtoupper(uniqid()),
                        'type' => 'bonus',
                        'amount' => $completion->reward_money,
                        'balance_before' => $wallet->balance - $completion->reward_money,
                        'balance_after' => $wallet->balance,
                        'reference_type' => VideoMissionCompletion::class,
                        'reference_id' => $completion->id,
                        'description' => 'รางวัลจากภารกิจดูคลิป: ' . $completion->mission->display_title,
                        'status' => 'completed',
                    ]);
                });
            }
        }

        // เพิ่ม Video Coins
        if ($completion->reward_coins > 0) {
            $videoCoin = \App\Models\VideoCoin::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'lifetime_earned' => 0]
            );

            $videoCoin->addCoins(
                $completion->reward_coins,
                'mission_reward',
                VideoMissionCompletion::class,
                $completion->id,
                'รางวัลจากภารกิจ: ' . $completion->mission->display_title
            );
        }

        // เพิ่ม Points (ถ้ามีระบบ points)
        if ($completion->reward_points > 0 && method_exists($user, 'addPoints')) {
            $user->addPoints($completion->reward_points, 'video_mission', $completion->id);
        }

        // เพิ่ม EXP (ถ้ามีระบบ video level)
        if ($completion->reward_exp > 0) {
            $userVideoLevel = \App\Models\UserVideoLevel::firstOrCreate(
                ['user_id' => $user->id],
                ['current_level_id' => 1, 'current_exp' => 0, 'total_exp' => 0]
            );

            $userVideoLevel->addExp($completion->reward_exp);
        }

        // TPIX จะต้อง implement เพิ่มตาม blockchain system ของระบบ
    }

    /**
     * เพิ่มข้อมูลสิทธิ์การทำภารกิจให้กับแต่ละ mission
     *
     * @param \Illuminate\Support\Collection $missions
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    protected function attachEligibilityInfo($missions, $user)
    {
        return $missions->map(function ($mission) use ($user) {
            return $this->addEligibilityToMission($mission, $user);
        });
    }

    /**
     * เพิ่มข้อมูลสิทธิ์ให้ mission เดียว
     *
     * @param \App\Models\VideoMission $mission
     * @param \App\Models\User $user
     * @return \App\Models\VideoMission
     */
    protected function addEligibilityToMission($mission, $user)
    {
        // ตรวจสอบสิทธิ์พื้นฐาน
        $eligibility = $mission->checkUserEligibility($user);

        // ตรวจสอบว่าทำได้ตอนนี้หรือไม่
        $canDoNow = $mission->canUserDoNow($user);

        // รวมผลการตรวจสอบ
        $mission->user_eligible = $eligibility['eligible'] && $canDoNow['can'];
        $mission->eligibility_reason = $eligibility['reason'] ?? $canDoNow['reason'] ?? null;
        $mission->next_available_at = $canDoNow['next_available_at'] ?? null;

        return $mission;
    }
}
