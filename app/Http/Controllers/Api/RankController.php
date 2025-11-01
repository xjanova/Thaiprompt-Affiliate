<?php

namespace App\Http\Controllers\Api;

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

    public function index()
    {
        $ranks = Rank::active()
            ->byLevel()
            ->with(['requirements', 'bonuses'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $ranks
        ]);
    }

    public function show(Rank $rank)
    {
        $rank->load(['requirements', 'bonuses']);

        return response()->json([
            'success' => true,
            'data' => $rank
        ]);
    }

    public function userProgress(Request $request)
    {
        $user = $request->user();

        // Update progress
        $this->rankingService->calculateRankPoints($user);
        $this->rankingService->updateUserProgress($user);

        $progress = $user->rankProgress()->with('targetRank')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'current_rank' => $user->currentRank,
                'rank_points' => $user->rank_points,
                'progress' => $progress,
                'next_rank' => $user->currentRank?->next_rank,
            ]
        ]);
    }

    public function leaderboard(Request $request)
    {
        $limit = $request->input('limit', 100);
        $leaderboard = $this->rankingService->getLeaderboard($limit);

        return response()->json([
            'success' => true,
            'data' => $leaderboard
        ]);
    }

    public function checkEligibility(Request $request)
    {
        $user = $request->user();
        $eligibility = $user->checkRankEligibility();

        return response()->json([
            'success' => true,
            'data' => $eligibility
        ]);
    }

    public function requestPromotion(Request $request)
    {
        $user = $request->user();
        $targetRankId = $request->input('rank_id');

        $targetRank = Rank::findOrFail($targetRankId);
        $promotion = $this->rankingService->requestManualPromotion($user, $targetRank);

        return response()->json([
            'success' => true,
            'message' => 'Promotion request submitted successfully',
            'data' => $promotion
        ]);
    }
}
