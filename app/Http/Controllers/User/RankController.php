<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\User;
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
        // ยังไม่มียศ → เป้าหมายคือยศแรก (เดิม null แล้วหน้าบอกว่า "อยู่ระดับสูงสุดแล้ว")
        $nextRank = $this->nextRankFor($currentRank);
        [$pointsPercent, $pointsNeeded] = $this->pointsProgress($user, $currentRank, $nextRank);

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
            'pointsPercent',
            'pointsNeeded',
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
        $user = auth()->user()->load('currentRank');
        $allRanks = Rank::active()->byLevel()->with(['activeRequirements' => fn ($q) => $q->orderBy('weight')])->get();
        $userProgress = $user->rankProgress()->get()->keyBy('target_rank_id');
        $currentRank = $user->currentRank;
        $nextRank = $this->nextRankFor($currentRank);
        [$pointsPercent, $pointsNeeded] = $this->pointsProgress($user, $currentRank, $nextRank);

        return view('user.ranks.progress', compact('user', 'allRanks', 'userProgress', 'currentRank', 'nextRank', 'pointsPercent', 'pointsNeeded'));
    }

    public function leaderboard()
    {
        $user = auth()->user();
        $leaderboard = $this->rankingService->getLeaderboard(100);
        // อันดับจริงของตัวเอง แม้ไม่ติด 100 อันดับแรก (ต้องมียศก่อนถึงจะอยู่บนกระดาน)
        $myPosition = $user->current_rank_id ? $this->getUserLeaderboardPosition($user) : null;

        return view('user.ranks.leaderboard', compact('leaderboard', 'myPosition'));
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
        $nextRank = $this->nextRankFor($currentRank);
        [$progressPercentage] = $this->pointsProgress($user, $currentRank, $nextRank);

        // Get leaderboard position
        $leaderboardPosition = $this->getUserLeaderboardPosition($user);

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
            'current_points' => (int) ($user->rank_points ?? 0),
            // user_rank_progress ไม่มีคอลัมน์ target_points (เดิมได้ 0 เสมอ) → ใช้เกณฑ์คะแนนของยศถัดไป
            'next_rank_points' => (int) ($nextRank?->min_points ?? 0),
            'progress_percentage' => round($progressPercentage, 2),
            'leaderboard_position' => $leaderboardPosition,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function getUserLeaderboardPosition($user)
    {
        // 🐛 rank_points เป็น null (โมเดลที่ยังไม่ refresh หลังสร้าง) → where('>', null) โยน InvalidArgumentException = หน้า 500
        return User::whereNotNull('current_rank_id')
            ->where('rank_points', '>', (int) ($user->rank_points ?? 0))
            ->count() + 1;
    }

    /**
     * ยศถัดไปที่ต้องไปให้ถึง — ยังไม่มียศ = ยศแรกที่เปิดใช้งาน
     */
    private function nextRankFor(?Rank $currentRank): ?Rank
    {
        return $currentRank ? $currentRank->next_rank : Rank::active()->byLevel()->first();
    }

    /**
     * ความคืบหน้าด้วยคะแนนสู่ยศถัดไป (สูตรเดียวกับการ์ดระดับบนแดชบอร์ดสมาชิก — DashboardController)
     *
     * @return array{0: float, 1: int} [เปอร์เซ็นต์ 0-100, คะแนนที่ยังขาด]
     */
    private function pointsProgress(User $user, ?Rank $currentRank, ?Rank $nextRank): array
    {
        if (! $nextRank) {
            return [100.0, 0];
        }

        $points = (int) ($user->rank_points ?? 0);
        $from = (int) ($currentRank?->min_points ?? 0);
        $to = (int) ($nextRank->min_points ?? 0);
        $needed = max(0, $to - $points);

        // ถึงเกณฑ์แล้ว (รวมยศที่ตั้งไว้ 0 คะแนน) = เต็มหลอด ไม่ใช่ 0%
        if ($needed === 0) {
            return [100.0, 0];
        }

        $span = $to - $from;
        $percent = $span > 0 ? min(100, max(0, ($points - $from) / $span * 100)) : 0;

        return [round($percent, 1), $needed];
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
