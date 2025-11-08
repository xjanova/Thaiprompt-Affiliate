<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoQuest;
use App\Models\UserQuestProgress;
use App\Models\UserVideoLevel;
use App\Models\VideoCoin;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VideoQuestController extends Controller
{
    /**
     * Get available quests for user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $frequency = $request->input('frequency'); // daily, weekly, monthly, once, repeatable

        $query = VideoQuest::active();

        if ($frequency) {
            $query->where('frequency', $frequency);
        }

        $quests = $query->get()->filter(function ($quest) use ($user) {
            return $quest->isUserEligible($user);
        });

        // Get user's progress for each quest
        $questsWithProgress = $quests->map(function ($quest) use ($user) {
            $period = $quest->getCurrentPeriod();
            $progress = $quest->getUserProgress($user->id, $period['start']);

            return [
                'quest' => $quest,
                'progress' => $progress,
                'can_claim' => $progress && $progress->completed && !$progress->reward_claimed,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $questsWithProgress->values(),
        ]);
    }

    /**
     * Get quest details
     */
    public function show(Request $request, $questId)
    {
        $user = $request->user();
        $quest = VideoQuest::findOrFail($questId);

        if (!$quest->isUserEligible($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not eligible for this quest',
            ], 403);
        }

        $period = $quest->getCurrentPeriod();
        $progress = $quest->getUserProgress($user->id, $period['start']);

        return response()->json([
            'success' => true,
            'data' => [
                'quest' => $quest,
                'progress' => $progress,
                'can_claim' => $progress && $progress->completed && !$progress->reward_claimed,
            ],
        ]);
    }

    /**
     * Claim quest reward
     */
    public function claimReward(Request $request, $questId)
    {
        $user = $request->user();
        $quest = VideoQuest::findOrFail($questId);

        $period = $quest->getCurrentPeriod();
        $progress = $quest->getUserProgress($user->id, $period['start']);

        if (!$progress || !$progress->completed || $progress->reward_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot claim reward',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Claim rewards
            $rewards = $progress->claimRewards();

            if (!$rewards) {
                throw new \Exception('Failed to claim rewards');
            }

            // Add coins
            if ($rewards['coins'] > 0) {
                $videoCoin = VideoCoin::firstOrCreate(['user_id' => $user->id]);
                $videoCoin->addCoins($rewards['coins'], 'earned_quest', 'VideoQuest', $questId, 'Completed quest: ' . $quest->name);
            }

            // Add experience
            if ($rewards['exp'] > 0) {
                $userLevel = UserVideoLevel::where('user_id', $user->id)->first();
                if ($userLevel) {
                    $leveledUp = $userLevel->addExp($rewards['exp']);
                    $userLevel->incrementQuestsCompleted();
                }
            }

            // Add money to wallet
            if ($rewards['money'] > 0) {
                $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $rewards['money'],
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance + $rewards['money'],
                    'description' => 'Quest reward: ' . $quest->name,
                    'reference_type' => 'VideoQuest',
                    'reference_id' => $questId,
                    'status' => 'completed',
                ]);

                $wallet->balance += $rewards['money'];
                $wallet->total_income += $rewards['money'];
                $wallet->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'rewards' => $rewards,
                    'leveled_up' => $leveledUp ?? false,
                    'user_level' => $userLevel ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to claim reward: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's quest history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $history = UserQuestProgress::with('quest')
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->orderBy('completed_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
