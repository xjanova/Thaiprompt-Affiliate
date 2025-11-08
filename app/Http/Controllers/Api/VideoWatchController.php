<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoContent;
use App\Models\UserVideoWatch;
use App\Models\VideoWatchSession;
use App\Models\VideoCoin;
use App\Models\UserVideoLevel;
use App\Models\UserDailyStreak;
use App\Models\UserQuestProgress;
use App\Models\VideoQuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VideoWatchController extends Controller
{
    /**
     * Start watching a video
     */
    public function startWatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:video_contents,id',
            'start_position' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $videoId = $request->input('video_id');
        $startPosition = $request->input('start_position', 0);

        $video = VideoContent::findOrFail($videoId);

        // Create or get watch record
        $userWatch = UserVideoWatch::firstOrCreate(
            [
                'user_id' => $user->id,
                'video_id' => $videoId,
            ],
            [
                'watch_time_seconds' => 0,
                'total_watch_time' => 0,
                'watch_percentage' => 0,
                'completed' => false,
            ]
        );

        // Create watch session
        $session = VideoWatchSession::create([
            'user_id' => $user->id,
            'video_id' => $videoId,
            'session_token' => VideoWatchSession::generateToken(),
            'start_position' => $startPosition,
            'end_position' => $startPosition,
            'watch_duration' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        // Increment view count
        $video->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => [
                'session_token' => $session->session_token,
                'video' => $video,
                'user_watch' => $userWatch,
            ],
        ]);
    }

    /**
     * Send heartbeat during video watching
     */
    public function heartbeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|exists:video_watch_sessions,session_token',
            'current_position' => 'required|integer|min:0',
            'timestamp' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $session = VideoWatchSession::where('session_token', $request->input('session_token'))->first();

        if (!$session || $session->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session',
            ], 403);
        }

        // Add heartbeat
        $session->addHeartbeat($request->input('current_position'), $request->input('timestamp'));

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat recorded',
        ]);
    }

    /**
     * End watching session
     */
    public function endWatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|exists:video_watch_sessions,session_token',
            'end_position' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $session = VideoWatchSession::where('session_token', $request->input('session_token'))->first();

        if (!$session || $session->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session',
            ], 403);
        }

        // End session
        $session->endSession($request->input('end_position'));

        // Validate session (anti-cheat)
        $isValid = $session->validateSession();

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid watch session detected',
            ], 400);
        }

        // Update user watch progress
        $userWatch = UserVideoWatch::where('user_id', $user->id)
            ->where('video_id', $session->video_id)
            ->first();

        if ($userWatch) {
            $userWatch->updateProgress($session->watch_duration);
        }

        // Update daily streak
        $streak = UserDailyStreak::firstOrCreate(['user_id' => $user->id]);
        $streak->recordActivity();

        // Update quest progress
        $this->updateQuestProgress($user, 'watch_videos', 1);
        $this->updateQuestProgress($user, 'watch_duration', $session->watch_duration);

        // Check if watching from specific channel
        $video = $session->video;
        if ($video->channel_id) {
            $this->updateChannelQuestProgress($user, $video->channel_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'watch_duration' => $session->watch_duration,
                'user_watch' => $userWatch->fresh(),
                'completed' => $userWatch->completed,
                'can_claim_reward' => $userWatch->completed && !$userWatch->reward_claimed,
            ],
        ]);
    }

    /**
     * Claim video reward
     */
    public function claimReward(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:video_contents,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $videoId = $request->input('video_id');

        $userWatch = UserVideoWatch::where('user_id', $user->id)
            ->where('video_id', $videoId)
            ->first();

        if (!$userWatch || !$userWatch->completed || $userWatch->reward_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot claim reward',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Claim reward
            $reward = $userWatch->claimReward();

            if (!$reward) {
                throw new \Exception('Failed to claim reward');
            }

            // Add coins
            $videoCoin = VideoCoin::firstOrCreate(['user_id' => $user->id]);
            $videoCoin->addCoins($reward['coins'], 'earned_video', 'VideoContent', $videoId, 'Watched video: ' . $userWatch->video->title);

            // Add experience
            $userLevel = UserVideoLevel::where('user_id', $user->id)->first();
            if ($userLevel) {
                $leveledUp = $userLevel->addExp($reward['exp']);
                $userLevel->incrementVideosWatched();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'coins_earned' => $reward['coins'],
                    'exp_earned' => $reward['exp'],
                    'leveled_up' => $leveledUp ?? false,
                    'new_balance' => $videoCoin->fresh()->balance,
                    'user_level' => $userLevel?->fresh(),
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
     * Update quest progress
     */
    protected function updateQuestProgress($user, $questType, $increment)
    {
        $quests = VideoQuest::active()
            ->where('type', $questType)
            ->where('is_active', true)
            ->get();

        foreach ($quests as $quest) {
            if (!$quest->isUserEligible($user)) {
                continue;
            }

            $period = $quest->getCurrentPeriod();

            $progress = UserQuestProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'quest_id' => $quest->id,
                    'period_start' => $period['start'],
                ],
                [
                    'current_progress' => 0,
                    'target_value' => $quest->target_value,
                    'period_end' => $period['end'],
                ]
            );

            $progress->updateProgress($increment);
        }
    }

    /**
     * Update channel-specific quest progress
     */
    protected function updateChannelQuestProgress($user, $channelId)
    {
        $quests = VideoQuest::active()
            ->where('type', 'watch_channel')
            ->where('channel_id', $channelId)
            ->where('is_active', true)
            ->get();

        foreach ($quests as $quest) {
            if (!$quest->isUserEligible($user)) {
                continue;
            }

            $period = $quest->getCurrentPeriod();

            $progress = UserQuestProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'quest_id' => $quest->id,
                    'period_start' => $period['start'],
                ],
                [
                    'current_progress' => 0,
                    'target_value' => $quest->target_value,
                    'period_end' => $period['end'],
                ]
            );

            $progress->updateProgress(1);
        }
    }
}
