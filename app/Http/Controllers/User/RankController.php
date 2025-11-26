<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Services\RankingService;
use Illuminate\Http\Request;

class RankController extends Controller
{
    protected $rankingService;

    public function __construct(RankingService $rankingService)
    {
        $this->rankingService = $rankingService;
    }

    public function dashboard()
    {
        $user = auth()->user()->load(['currentRank', 'mlmMembers']);

        // Calculate and update points (MLM Member)
        $mlmMember = $user->mlmMembers()->first();
        if ($mlmMember) {
            $this->rankingService->calculateRankPoints($user);
            $this->rankingService->updateUserProgress($user);
        }

        $nextRankProgress = $user->next_rank_progress;
        $currentRank = $user->currentRank;
        $nextRank = $currentRank?->next_rank;

        // Get leaderboard position
        $leaderboardPosition = $this->getUserLeaderboardPosition($user);

        // ดึงเงื่อนไขและโบนัสของ Rank ปัจจุบัน
        $currentRankRequirements = $currentRank?->activeRequirements()
            ->orderBy('weight')
            ->get() ?? collect();
        $currentRankBonuses = $currentRank?->activeBonuses()
            ->orderBy('priority')
            ->get() ?? collect();
        $currentRankPrivileges = $currentRank?->getPrivilegesWithDescriptions() ?? [];

        // ดึงเงื่อนไขและโบนัสของ Rank ถัดไป พร้อมความคืบหน้า
        $nextRankRequirements = $nextRank?->activeRequirements()
            ->orderBy('weight')
            ->get() ?? collect();
        $nextRankRequirementsProgress = [];
        foreach ($nextRankRequirements as $req) {
            $nextRankRequirementsProgress[$req->id] = [
                'current' => $req->getUserCurrentValue($user),
                'target' => $req->target_value,
                'percentage' => $req->getProgressPercentage($user),
                'met' => $req->checkUserMeetsRequirement($user),
            ];
        }

        $nextRankBonuses = $nextRank?->activeBonuses()
            ->orderBy('priority')
            ->get() ?? collect();
        $nextRankPrivileges = $nextRank?->getPrivilegesWithDescriptions() ?? [];

        // ดึง Ranks ทั้งหมดสำหรับแสดง roadmap
        $allRanks = Rank::active()->byLevel()->with(['activeRequirements', 'activeBonuses'])->get();

        return view('user.ranks.dashboard', compact(
            'user',
            'currentRank',
            'nextRank',
            'nextRankProgress',
            'leaderboardPosition',
            'currentRankRequirements',
            'currentRankBonuses',
            'currentRankPrivileges',
            'nextRankRequirements',
            'nextRankRequirementsProgress',
            'nextRankBonuses',
            'nextRankPrivileges',
            'allRanks'
        ));
    }

    public function progress()
    {
        $user = auth()->user();
        $allRanks = Rank::active()->byLevel()->get();
        $userProgress = $user->rankProgress()->with('targetRank')->get();

        return view('user.ranks.progress', compact('user', 'allRanks', 'userProgress'));
    }

    public function leaderboard()
    {
        $leaderboard = $this->rankingService->getLeaderboard(100);

        return view('user.ranks.leaderboard', compact('leaderboard'));
    }

    public function requestPromotion(Request $request)
    {
        $user = auth()->user();
        $targetRankId = $request->input('rank_id');

        $targetRank = Rank::findOrFail($targetRankId);

        $promotion = $this->rankingService->requestManualPromotion($user, $targetRank);

        return back()->with('success', 'Promotion request submitted successfully!');
    }

    public function widgetData()
    {
        $user = auth()->user()->load(['currentRank', 'mlmMembers']);

        // Calculate and update points (MLM Member)
        $mlmMember = $user->mlmMembers()->first();
        if ($mlmMember) {
            $this->rankingService->calculateRankPoints($user);
            $this->rankingService->updateUserProgress($user);
        }

        $currentRank = $user->currentRank;
        $nextRank = $currentRank?->next_rank;
        $nextRankProgress = $user->next_rank_progress;

        // Get leaderboard position
        $leaderboardPosition = $this->getUserLeaderboardPosition($user);

        // Calculate progress percentage
        $progressPercentage = 0;
        if ($nextRank && $nextRankProgress) {
            $progressPercentage = $nextRankProgress->progress_percentage ?? 0;
        }

        $data = [
            'current_rank' => [
                'id' => $currentRank->id ?? null,
                'name' => $currentRank->name ?? 'No Rank',
                'name_th' => $currentRank->name_th ?? 'ไม่มีระดับ',
                'level' => $currentRank->level ?? 0,
                'icon' => $currentRank->icon ?? 'fa-star',
            ],
            'next_rank' => $nextRank ? [
                'id' => $nextRank->id,
                'name' => $nextRank->name,
                'name_th' => $nextRank->name_th,
                'level' => $nextRank->level,
            ] : null,
            'current_points' => $user->rank_points ?? 0,
            'next_rank_points' => $nextRankProgress->target_points ?? 0,
            'progress_percentage' => round($progressPercentage, 2),
            'leaderboard_position' => $leaderboardPosition,
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getUserLeaderboardPosition($user)
    {
        return \App\Models\User::whereNotNull('current_rank_id')
            ->where('rank_points', '>', $user->rank_points)
            ->count() + 1;
    }

    /**
     * แสดงบัตรประจำตัวเสมือนของผู้ใช้
     *
     * @return \Illuminate\View\View
     */
    public function virtualIdCard()
    {
        $user = auth()->user()->load(['currentRank']);

        $currentRank = $user->currentRank;

        // ดึงการตั้งค่า ID Card จากระบบ (ถ้ามี)
        $idCardSettings = \App\Models\IdCardSetting::getActiveSettingsForRank($currentRank?->level ?? 1);

        return view('user.virtual-id-card', compact(
            'user',
            'currentRank',
            'idCardSettings'
        ));
    }
}
