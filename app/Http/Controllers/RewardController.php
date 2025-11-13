<?php

namespace App\Http\Controllers;

use App\Models\UserDailyStreak;
use App\Models\DailyRewardClaim;
use App\Models\GameMission;
use App\Models\UserMissionProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    /**
     * Show daily rewards page
     */
    public function daily()
    {
        $user = Auth::user();

        $streak = UserDailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );

        $canClaim = $streak->canClaimToday();
        $todayReward = $canClaim ? $streak->getTodayReward() : null;
        $upcomingRewards = $streak->getUpcomingRewards();

        // Get recent claims
        $recentClaims = DailyRewardClaim::where('user_id', $user->id)
            ->orderBy('claim_date', 'desc')
            ->limit(7)
            ->get();

        return view('rewards.daily', compact('streak', 'canClaim', 'todayReward', 'upcomingRewards', 'recentClaims'));
    }

    /**
     * Claim daily reward (API)
     */
    public function claimDaily(Request $request)
    {
        $user = Auth::user();

        $streak = UserDailyStreak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );

        try {
            DB::beginTransaction();

            $result = $streak->claimReward();

            DB::commit();

            return response()->json($result);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Show missions page
     */
    public function missions()
    {
        $user = Auth::user();

        // Get daily missions
        $dailyMissions = GameMission::daily()
            ->available()
            ->with(['userProgress' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // Get weekly missions
        $weeklyMissions = GameMission::weekly()
            ->available()
            ->with(['userProgress' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // Get user's completed missions
        $completedCount = UserMissionProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->count();

        return view('rewards.missions', compact('dailyMissions', 'weeklyMissions', 'completedCount'));
    }

    /**
     * Claim mission reward (API)
     */
    public function claimMission(Request $request, $missionId)
    {
        $user = Auth::user();

        $progress = UserMissionProgress::where('user_id', $user->id)
            ->where('mission_id', $missionId)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $result = $progress->claimReward();

            DB::commit();

            return response()->json($result);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get missions progress (API)
     */
    public function getMissionsProgress()
    {
        $user = Auth::user();

        $missions = GameMission::available()
            ->with(['userProgress' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->map(function ($mission) use ($user) {
                $progress = $mission->userProgress->first();

                return [
                    'id' => $mission->id,
                    'name' => $mission->name,
                    'description' => $mission->description,
                    'type' => $mission->type,
                    'objective' => $mission->objective,
                    'target' => $mission->target,
                    'current_progress' => $progress->current_progress ?? 0,
                    'is_completed' => $progress->is_completed ?? false,
                    'is_claimed' => $progress->is_claimed ?? false,
                    'reward' => [
                        'type' => $mission->reward_type,
                        'amount' => $mission->reward_amount,
                        'item' => $mission->reward_item,
                        'points' => $mission->reward_points,
                    ],
                    'difficulty' => $mission->difficulty,
                ];
            });

        return response()->json($missions);
    }
}
